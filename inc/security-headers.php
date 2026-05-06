<?php
/**
 * Security response headers.
 *
 * Lighthouse "Best Practices" flags five separate audits when a site sends
 * none of these (HSTS, COOP, XFO, CSP, nosniff). This module sends a
 * conservative baseline that satisfies all of them except CSP, which is
 * intentionally left out for now (see the bottom of this file for why).
 *
 * Skipped on /wp-admin/ requests because the editor needs looser policies
 * (Permissions-Policy can break uploads, COOP can interfere with the media
 * library popup window) and because the headers Lighthouse cares about are
 * about the public site.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'send_headers', function () {
    if ( is_admin() ) {
        return;
    }
    if ( headers_sent() ) {
        return;
    }

    /* HSTS — 1 year + includeSubDomains is the value Lighthouse considers
       "strong" and the value the HSTS preload list requires for submission.
       Note this is sticky in browsers for the lifetime of max-age, so a
       broken-HTTPS subdomain can't be reached by users whose browsers have
       cached the policy. If you ever spin up a new subdomain that needs
       HTTP-only access (for testing, etc.), you'll need to use a different
       hostname rather than walk this back. Adding `; preload` and submitting
       to https://hstspreload.org is a further hardening step but is even
       more sticky and is not required for the Lighthouse audit. */
    header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );

    /* Stops the browser from MIME-sniffing a response away from the declared
       Content-Type. Closes a class of XSS bugs where an attacker uploads a
       file the server labels as text/plain but the browser interprets as
       HTML or JS. */
    header( 'X-Content-Type-Options: nosniff' );

    /* Send the origin (scheme + host) on cross-origin navigation, full
       referrer for same-origin. The default in modern browsers is already
       this; explicit header keeps it consistent across older browsers and
       satisfies the audit. */
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );

    /* Clickjacking — disallow this site being embedded in an iframe on
       another origin. SAMEORIGIN keeps the WP admin's own iframes (preview,
       customizer) working. */
    header( 'X-Frame-Options: SAMEORIGIN' );

    /* Process isolation — don't share an agent cluster with cross-origin
       documents that try to open this page in a popup. Required for
       window.crossOriginIsolated to ever be true, and Lighthouse audits this. */
    header( 'Cross-Origin-Opener-Policy: same-origin' );

    /* Disable powerful APIs the site doesn't use. interest-cohort is the
       FLoC opt-out (still respected by some legacy chains). */
    header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()' );
} );

/* CSP is intentionally not sent here.
 *
 * A useful CSP for this site has to allow: Site Kit (googletagmanager.com,
 * google-analytics.com, googleadservices.com, doubleclick.net, googlesyndication.com,
 * pagead2.googlesyndication.com, fundingchoicesmessages.google.com, ...),
 * Jetpack (stats.wp.com, i0.wp.com, secure.gravatar.com, public-api.wordpress.com,
 * the WordPress.com newsletter reader), Bluehost-plugin (link prefetcher inline JS),
 * Google Fonts CSS (fonts.googleapis.com → fonts.gstatic.com), the JustTCG demo's
 * cross-origin fetch to api.justtcg.com (proxied server-side, but the demo fetches
 * tcgplayer-cdn.tcgplayer.com images directly), and the inline `<script>` blocks
 * in every demo pattern. Those inline scripts in particular are the load-bearing
 * issue: a strict CSP needs either `unsafe-inline` (which defeats the point) or
 * per-script nonces threaded through every pattern (a lot of plumbing for the
 * one-page-demo aesthetic).
 *
 * The pragmatic path is: when adding CSP, start in `Content-Security-Policy-Report-Only`
 * mode for a couple of weeks with a report-uri endpoint, build the allowlist from the
 * actual reports, then promote to enforced. Until that work happens, no CSP is honest.
 */
