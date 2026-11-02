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
 * Every parameter that reaches JustTCG is validated against a whitelist or a
 * pattern, and upstream calls (cache misses only) are throttled per IP, so the
 * proxy can't be used to burn the daily quota or to fill the transient table.
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

/* Abuse limits. Upstream calls (cache misses) are counted per IP; cache hits
   are free. The lock keeps concurrent visitors from all going upstream when
   one transient expires. */
const DARIOBIMBI_JTCG_RATE_MAX    = 30;
const DARIOBIMBI_JTCG_RATE_WINDOW = 10 * MINUTE_IN_SECONDS;
const DARIOBIMBI_JTCG_LOCK_TTL    = 15;
const DARIOBIMBI_JTCG_MAX_OFFSET  = 5000;

/* Values the /cards endpoint is allowed to receive. orderBy and order are
   matched exactly (sanitize_key would lowercase them). orderBy is JustTCG's
   sort field list (price, 24h, 7d, 30d), confirmed against production. The
   condition list holds JustTCG's short codes plus the full names that come
   back on variants; printing matches the values the card browser derives
   from real variants. */
const DARIOBIMBI_JTCG_ORDER_BY   = [ 'price', '24h', '7d', '30d' ];
const DARIOBIMBI_JTCG_ORDER      = [ 'asc', 'desc' ];
const DARIOBIMBI_JTCG_CONDITIONS = [
    'S', 'NM', 'LP', 'MP', 'HP', 'DMG',
    'Sealed', 'Near Mint', 'Lightly Played', 'Moderately Played', 'Heavily Played', 'Damaged',
];
const DARIOBIMBI_JTCG_PRINTINGS  = [
    'Normal', 'Foil', 'Holofoil', 'Reverse Holofoil', '1st Edition',
    '1st Edition Holofoil', 'Unlimited', 'Unlimited Holofoil', 'Shadowless',
];

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
            'orderBy'   => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
            'order'     => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
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
    $offset = max( 0, min( DARIOBIMBI_JTCG_MAX_OFFSET, (int) $req->get_param( 'offset' ) ) );

    $params = [
        'game'   => $game,
        'limit'  => $limit,
        'offset' => $offset,
    ];

    /* JustTCG set ids are lowercase and hyphenated with the game on the end
       (e.g. "base-set-pokemon"). Anything else is rejected before it can
       become a cache key or an upstream request. */
    $set = (string) $req->get_param( 'set' );
    if ( $set !== '' ) {
        if ( ! preg_match( '/^[a-z0-9][a-z0-9_-]{0,99}$/', $set ) ) {
            return new WP_REST_Response( [ 'error' => 'Invalid set.' ], 400 );
        }
        $params['set'] = $set;
    }

    $order_by = (string) $req->get_param( 'orderBy' );
    if ( $order_by !== '' ) {
        if ( ! in_array( $order_by, DARIOBIMBI_JTCG_ORDER_BY, true ) ) {
            return new WP_REST_Response( [ 'error' => 'Invalid sort.' ], 400 );
        }
        $params['orderBy'] = $order_by;
    }

    $order = (string) $req->get_param( 'order' );
    if ( $order !== '' ) {
        if ( ! in_array( $order, DARIOBIMBI_JTCG_ORDER, true ) ) {
            return new WP_REST_Response( [ 'error' => 'Invalid sort order.' ], 400 );
        }
        $params['order'] = $order;
    }

    /* condition and printing can be comma-separated lists upstream, so every
       item has to be on the whitelist. */
    $lists = [
        'condition' => DARIOBIMBI_JTCG_CONDITIONS,
        'printing'  => DARIOBIMBI_JTCG_PRINTINGS,
    ];
    foreach ( $lists as $name => $allowed ) {
        $raw = (string) $req->get_param( $name );
        if ( $raw === '' ) {
            continue;
        }
        $items = array_map( 'trim', explode( ',', $raw ) );
        foreach ( $items as $item ) {
            if ( ! in_array( $item, $allowed, true ) ) {
                return new WP_REST_Response( [ 'error' => 'Invalid ' . $name . '.' ], 400 );
            }
        }
        $params[ $name ] = implode( ',', $items );
    }

    return dariobimbi_jtcg_call( '/cards', $params, DARIOBIMBI_JTCG_TTL_CARDS );
}

/**
 * Build a response from the long-lived stale copy, or null if there isn't one.
 */
function dariobimbi_jtcg_stale_response( string $stale_cache_key ) {
    $stale = get_transient( $stale_cache_key );
    if ( is_array( $stale ) && isset( $stale['body'] ) && is_array( $stale['body'] ) ) {
        return new WP_REST_Response(
            array_merge( $stale['body'], [ '_dariobimbi_stale' => true ] ),
            $stale['status']
        );
    }
    return null;
}

/**
 * Hit JustTCG, cache the response in a transient, and return a WP_REST_Response.
 * Cache hits cost nothing. A miss is guarded three ways: a short lock so
 * concurrent visitors don't all go upstream when a transient expires, a per-IP
 * throttle on upstream calls, and a stale fallback (transport errors, 429, any
 * 5xx, non-JSON bodies) so the demo keeps working through a quota burn or an
 * upstream blip rather than going blank.
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
    $lock_key        = $cache_key . '_lock';

    $cached = get_transient( $cache_key );
    if ( is_array( $cached ) && isset( $cached['body'] ) ) {
        return new WP_REST_Response( $cached['body'], $cached['status'] );
    }

    /* Someone else is already refreshing this exact request. Serve stale if
       we have it; otherwise wait a moment for their result to land. */
    if ( get_transient( $lock_key ) ) {
        $stale = dariobimbi_jtcg_stale_response( $stale_cache_key );
        if ( $stale ) {
            return $stale;
        }
        for ( $i = 0; $i < 6; $i++ ) {
            usleep( 500000 );
            $cached = get_transient( $cache_key );
            if ( is_array( $cached ) && isset( $cached['body'] ) ) {
                return new WP_REST_Response( $cached['body'], $cached['status'] );
            }
        }
        return new WP_REST_Response( [
            'error' => 'Prices are still loading. Please try again in a few seconds.',
        ], 503 );
    }

    /* Per-IP throttle on upstream calls only. REMOTE_ADDR is used over
       forwarded-for headers, which a client can spoof (same as inc/contact.php). */
    $ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    $rl_key = 'dariobimbi_jtcg_rl_' . md5( $ip );
    $tries  = (int) get_transient( $rl_key );
    if ( $tries >= DARIOBIMBI_JTCG_RATE_MAX ) {
        $stale = dariobimbi_jtcg_stale_response( $stale_cache_key );
        if ( $stale ) {
            return $stale;
        }
        return new WP_REST_Response( [
            'error' => 'You have loaded a lot of new cards in a short time. Please give it a few minutes and try again.',
        ], 429 );
    }
    set_transient( $rl_key, $tries + 1, DARIOBIMBI_JTCG_RATE_WINDOW );
    set_transient( $lock_key, 1, DARIOBIMBI_JTCG_LOCK_TTL );

    $url = add_query_arg( $params, DARIOBIMBI_JTCG_BASE . $path );

    $resp = wp_remote_get( $url, [
        'timeout' => 12,
        'headers' => [
            'X-API-Key' => JUSTTCG_API_KEY,
            'Accept'    => 'application/json',
        ],
    ] );

    if ( is_wp_error( $resp ) ) {
        delete_transient( $lock_key );
        $stale = dariobimbi_jtcg_stale_response( $stale_cache_key );
        if ( $stale ) {
            return $stale;
        }
        /* The WP_Error text is not echoed: it can include the request URL. */
        return new WP_REST_Response( [
            'error' => 'Could not reach JustTCG. Please try again in a moment.',
        ], 502 );
    }

    $status = (int) wp_remote_retrieve_response_code( $resp );
    $body   = json_decode( wp_remote_retrieve_body( $resp ), true );
    delete_transient( $lock_key );

    /* Rate-limited (429), any server error (5xx), or a body that isn't JSON:
       fall back to the long-lived stale cache. Better to show last-known
       prices with a "stale" flag than a blank grid. */
    if ( $status === 429 || $status >= 500 || ! is_array( $body ) ) {
        $stale = dariobimbi_jtcg_stale_response( $stale_cache_key );
        if ( $stale ) {
            return $stale;
        }
    }

    if ( ! is_array( $body ) ) {
        return new WP_REST_Response( [ 'error' => 'JustTCG returned a non-JSON response.' ], 502 );
    }

    if ( $status === 429 ) {
        return new WP_REST_Response( [
            'error' => 'JustTCG is getting a lot of requests right now. Please try again in a bit.',
        ], 429 );
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
