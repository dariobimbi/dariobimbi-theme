<?php
/**
 * Preview this theme locally without switching the site's active theme.
 *
 * The local install usually has bimbi-templates active, so Apache serves that
 * theme. This router for PHP's built-in server renders every WordPress request
 * with this theme and with home/siteurl pointed at the built-in server, so REST
 * calls come back here too. Static files pass straight through. Nothing is
 * written to the options table; the overrides live in pre_option filters for
 * the request only.
 *
 * Run from the WAMP web root (from Git Bash, prefix MSYS_NO_PATHCONV=1):
 *
 *   cd c:/wamp64/www
 *   c:/wamp64/bin/php/php8.2.29/php.exe -S 127.0.0.1:8099 -t c:/wamp64/www \
 *     c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/tools/preview-router.php
 *
 * Then browse http://127.0.0.1:8099/dariobimbi.com/
 */

$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$file = $_SERVER['DOCUMENT_ROOT'] . urldecode( $path );
if ( is_file( $file ) ) {
	if ( substr( $file, -4 ) !== '.php' ) {
		return false;
	}
}

$site_root  = dirname( __DIR__, 4 );
$theme_slug = basename( dirname( __DIR__ ) );
$base       = 'http://' . $_SERVER['HTTP_HOST'] . '/' . basename( $site_root );
$theme      = static function () use ( $theme_slug ) { return $theme_slug; };
$url        = static function () use ( $base ) { return $base; };
$GLOBALS['wp_filter'] = [
	'pre_option_stylesheet' => [ 10 => [ [ 'function' => $theme, 'accepted_args' => 1 ] ] ],
	'pre_option_template'   => [ 10 => [ [ 'function' => $theme, 'accepted_args' => 1 ] ] ],
	'pre_option_home'       => [ 10 => [ [ 'function' => $url, 'accepted_args' => 1 ] ] ],
	'pre_option_siteurl'    => [ 10 => [ [ 'function' => $url, 'accepted_args' => 1 ] ] ],
];
$_SERVER['SCRIPT_NAME'] = '/' . basename( $site_root ) . '/index.php';
$_SERVER['PHP_SELF']    = $_SERVER['SCRIPT_NAME'];
chdir( $site_root );
define( 'WP_USE_THEMES', true );
require $site_root . '/wp-blog-header.php';
