<?php
/**
 * Thin REST proxy for the JustTCG API.
 *
 * The card-browser demo (patterns/demo-card-browser.php) talks to this
 * proxy instead of JustTCG directly so the API key stays server-side. It
 * also caches aggressively against the free-tier limit (100 req/day, 10/min)
 * via WP transients, and falls back to a longer-lived "stale" cache if
 * JustTCG returns 429 — better to serve last-known prices than to error.
 *
 * Routes:
 *   GET /wp-json/dariobimbi/v1/jtcg/sets?game=pokemon
 *   GET /wp-json/dariobimbi/v1/jtcg/cards?game=pokemon&set=base-set-pokemon&offset=0&limit=20
 *
 * Defines `JUSTTCG_API_KEY` must live in wp-config.php. If it is missing the
 * proxy returns a 503 with a helpful message rather than a silent failure,
 * so the demo can show a configuration banner instead of an empty grid.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const DARIOBIMBI_JTCG_BASE      = 'https://api.justtcg.com/v1';
const DARIOBIMBI_JTCG_NAMESPACE = 'dariobimbi/v1';

/**
 * Whitelisted game IDs. JustTCG supports many games (MTG, Yugioh, Lorcana,
 * etc.); this proxy is only for the Pokémon card-browser demo, so any other
 * game ID is rejected. Keeps the proxy from being abused as an open relay.
 */
const DARIOBIMBI_JTCG_GAMES = [ 'pokemon', 'pokemon-japan' ];

/* Cache durations. Sets rarely change (release dates, value indices drift
   slowly), so 24h is fine. Card grids drift faster but are read repeatedly,
   so 6h. The "stale" copy is what we serve if JustTCG 429s before the fresh
   cache expires — 7 days. */
const DARIOBIMBI_JTCG_TTL_SETS        = DAY_IN_SECONDS;
const DARIOBIMBI_JTCG_TTL_CARDS       = 6 * HOUR_IN_SECONDS;
const DARIOBIMBI_JTCG_TTL_STALE       = 7 * DAY_IN_SECONDS;

add_action( 'rest_api_init', function () {
    register_rest_route( DARIOBIMBI_JTCG_NAMESPACE, '/jtcg/sets', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => 'dariobimbi_jtcg_sets',
        'args'                => [
            'game' => [
                'type'              => 'string',
                'required'          => false,
                'default'           => 'pokemon',
                'sanitize_callback' => 'sanitize_key',
            ],
        ],
    ] );

    register_rest_route( DARIOBIMBI_JTCG_NAMESPACE, '/jtcg/cards', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => 'dariobimbi_jtcg_cards',
        'args'                => [
            'game'      => [ 'type' => 'string', 'default' => 'pokemon', 'sanitize_callback' => 'sanitize_key' ],
            'set'       => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
            'offset'    => [ 'type' => 'integer', 'default' => 0 ],
            'limit'     => [ 'type' => 'integer', 'default' => 20 ],
            'condition' => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
            'printing'  => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
            'orderBy'   => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ],
            'order'     => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ],
        ],
    ] );
} );

function dariobimbi_jtcg_sets( WP_REST_Request $req ) {
    $game = (string) $req->get_param( 'game' );
    if ( ! in_array( $game, DARIOBIMBI_JTCG_GAMES, true ) ) {
        return new WP_REST_Response( [ 'error' => 'Unsupported game.' ], 400 );
    }
    return dariobimbi_jtcg_call(
        '/sets',
        [ 'game' => $game, 'limit' => 100 ],
        DARIOBIMBI_JTCG_TTL_SETS
    );
}

function dariobimbi_jtcg_cards( WP_REST_Request $req ) {
    $game = (string) $req->get_param( 'game' );
    if ( ! in_array( $game, DARIOBIMBI_JTCG_GAMES, true ) ) {
        return new WP_REST_Response( [ 'error' => 'Unsupported game.' ], 400 );
    }

    /* Clamp the limit so a malformed or hostile caller can't ask for 10k
       cards in one request, and so we never exceed the per-plan cap. The
       free tier caps `limit` at 20 (returns 400 INVALID_REQUEST otherwise),
       so 20 is the safe ceiling for this proxy. Bump if the site upgrades. */
    $limit  = max( 1, min( 20, (int) $req->get_param( 'limit' ) ) );
    $offset = max( 0, (int) $req->get_param( 'offset' ) );

    $params = [
        'game'   => $game,
        'limit'  => $limit,
        'offset' => $offset,
    ];

    $set = (string) $req->get_param( 'set' );
    if ( $set !== '' ) {
        $params['set'] = $set;
    }
    foreach ( [ 'condition', 'printing', 'orderBy', 'order' ] as $passthrough ) {
        $val = (string) $req->get_param( $passthrough );
        if ( $val !== '' ) {
            $params[ $passthrough ] = $val;
        }
    }

    return dariobimbi_jtcg_call( '/cards', $params, DARIOBIMBI_JTCG_TTL_CARDS );
}

/**
 * Hit JustTCG, cache the response in a transient, and return a WP_REST_Response.
 * On 429 (rate limit), fall back to a longer-lived "stale" copy if one exists
 * so the demo keeps working through a quota burn rather than going blank.
 */
function dariobimbi_jtcg_call( string $path, array $params, int $ttl ) {
    if ( ! defined( 'JUSTTCG_API_KEY' ) || ! JUSTTCG_API_KEY ) {
        return new WP_REST_Response( [
            'error' => 'JUSTTCG_API_KEY is not defined in wp-config.php. The card-browser demo will not work until it is set.',
        ], 503 );
    }

    ksort( $params );
    $cache_key       = 'jtcg_' . md5( $path . '|' . wp_json_encode( $params ) );
    $stale_cache_key = $cache_key . '_stale';

    $cached = get_transient( $cache_key );
    if ( is_array( $cached ) && isset( $cached['body'] ) ) {
        return new WP_REST_Response( $cached['body'], $cached['status'] );
    }

    $url = add_query_arg( $params, DARIOBIMBI_JTCG_BASE . $path );

    $resp = wp_remote_get( $url, [
        'timeout' => 12,
        'headers' => [
            'X-API-Key' => JUSTTCG_API_KEY,
            'Accept'    => 'application/json',
        ],
    ] );

    if ( is_wp_error( $resp ) ) {
        $stale = get_transient( $stale_cache_key );
        if ( is_array( $stale ) && isset( $stale['body'] ) ) {
            return new WP_REST_Response(
                array_merge( $stale['body'], [ '_dariobimbi_stale' => true ] ),
                $stale['status']
            );
        }
        return new WP_REST_Response( [
            'error' => 'Could not reach JustTCG: ' . $resp->get_error_message(),
        ], 502 );
    }

    $status = (int) wp_remote_retrieve_response_code( $resp );
    $body   = json_decode( wp_remote_retrieve_body( $resp ), true );

    /* On 429 (rate-limited), fall back to whatever we have in the long-lived
       stale cache. Better to show last-known prices with a "stale" flag than
       a blank grid. */
    if ( $status === 429 ) {
        $stale = get_transient( $stale_cache_key );
        if ( is_array( $stale ) && isset( $stale['body'] ) ) {
            return new WP_REST_Response(
                array_merge( $stale['body'], [ '_dariobimbi_stale' => true ] ),
                $stale['status']
            );
        }
    }

    if ( ! is_array( $body ) ) {
        return new WP_REST_Response( [ 'error' => 'JustTCG returned a non-JSON response.' ], 502 );
    }

    /* Strip JustTCG's _metadata block, which leaks our request count and plan
       name to anyone who looks. Pagination meta stays. */
    if ( isset( $body['_metadata'] ) ) {
        unset( $body['_metadata'] );
    }

    /* Cache only successful responses. 4xx/5xx other than 429 fall through
       so a momentary upstream blip doesn't poison the cache for 6 hours. */
    if ( $status >= 200 && $status < 300 ) {
        $payload = [ 'body' => $body, 'status' => $status ];
        set_transient( $cache_key, $payload, $ttl );
        set_transient( $stale_cache_key, $payload, DARIOBIMBI_JTCG_TTL_STALE );
    }

    return new WP_REST_Response( $body, $status );
}
