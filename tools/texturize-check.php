<?php
/**
 * Check that WordPress won't mangle any pattern's inline <script>.
 *
 * Run from the command line on the local WAMP install (works whichever theme
 * is active, because it forces this one for the run only):
 *
 *   c:/wamp64/bin/php/php8.2.29/php.exe \
 *     c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/tools/texturize-check.php
 *
 * Why: block templates go through wptexturize, which treats anything from a `<`
 * to the next `>` as an HTML tag and encodes every `&` inside it as `&#038;`,
 * even inside <script>. So an `&&` (or a `&` in a URL string) that sits after a
 * less-than comparison like `i < n` and before the next `>` is served broken.
 * Whether it bites depends on where the `<` and `>` fall, which is why it looked
 * random. This renders every pattern that has a script, runs wptexturize the way
 * WordPress does, and prints any script line that comes out different.
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( "Run from the command line.\n" );
}

$theme_slug = basename( dirname( __DIR__ ) );
$force      = static function () use ( $theme_slug ) { return $theme_slug; };
$GLOBALS['wp_filter'] = [
	'pre_option_stylesheet' => [ 10 => [ [ 'function' => $force, 'accepted_args' => 1 ] ] ],
	'pre_option_template'   => [ 10 => [ [ 'function' => $force, 'accepted_args' => 1 ] ] ],
];
$_SERVER['HTTP_HOST'] = 'localhost';
require dirname( __DIR__, 4 ) . '/wp-load.php';

$bad = 0;
foreach ( glob( dirname( __DIR__ ) . '/patterns/*.php' ) as $file ) {
	$source = file_get_contents( $file );
	if ( false === strpos( $source, '<script' ) ) {
		continue;
	}
	if ( ! preg_match( '/^\s*\*\s*Slug:\s*(\S+)/m', $source, $m ) ) {
		continue;
	}
	$html = do_blocks( '<!-- wp:pattern {"slug":"' . $m[1] . '"} /-->' );
	$re   = '/<script\b[^>]*>([\s\S]*?)<\/script>/i';
	preg_match_all( $re, $html, $raw );
	preg_match_all( $re, wptexturize( $html ), $tex );
	foreach ( $raw[1] as $i => $body ) {
		if ( $body === $tex[1][ $i ] ) {
			continue;
		}
		$bad++;
		$before = explode( "\n", $body );
		$after  = explode( "\n", $tex[1][ $i ] );
		foreach ( $before as $n => $line ) {
			if ( isset( $after[ $n ] ) && $line !== $after[ $n ] ) {
				echo basename( $file ), ' script #', $i + 1, ' line ', $n + 1, ': ', trim( $after[ $n ] ), "\n";
			}
		}
	}
}

echo $bad ? "\n$bad script(s) would be served mangled.\n" : "OK: no inline script is changed by wptexturize.\n";
exit( $bad ? 1 : 0 );
