<?php
/**
 * Render per-project social banners (1200×630) for the project pages.
 *
 * Run from the command line on the local WAMP install:
 *
 *   c:/wamp64/bin/php/php8.2.29/php.exe \
 *     c:/wamp64/www/dariobimbi.com/wp-content/themes/dariobimbi/tools/render-banners.php
 *
 * Each banner lands at assets/projects/<slug>/banner.png. Upload the file
 * to the Media Library, then set it as the project page's Featured Image.
 * The existing per-page OG image and image-sitemap code in inc/seo.php and
 * inc/sitemap.php pick the featured image up automatically once it is set.
 *
 * Why GD instead of an SVG source: GD with FreeType is already available
 * on this WAMP build, Imagick is not, and PNG is what every social-share
 * crawler accepts without a fight. The raster code below IS the design;
 * tweak the constants at the top to iterate.
 *
 * No external assets required besides the Windows system fonts (Segoe UI).
 * Fonts fall back to the GD bundled fonts if Segoe is missing, so the
 * script is portable enough to run on a different machine if needed.
 */

if ( PHP_SAPI !== 'cli' ) {
    exit( "Run from the command line.\n" );
}
if ( ! extension_loaded( 'gd' ) ) {
    exit( "GD extension is required.\n" );
}

/* ------------------------------------------------------------------
 * Configuration
 * ------------------------------------------------------------------ */

const W = 1200;
const H = 630;

const FONT_BOLD     = 'C:/Windows/Fonts/segoeuib.ttf';   // Segoe UI Bold
const FONT_SEMIBOLD = 'C:/Windows/Fonts/seguisb.ttf';    // Segoe UI Semibold
const FONT_REGULAR  = 'C:/Windows/Fonts/segoeui.ttf';    // Segoe UI
const FONT_MONO     = 'C:/Windows/Fonts/consola.ttf';    // Consolas

/* Theme palette, mirrored from theme.json so the banners feel native to
   the rest of the site. RGB triples for GD's allocateColor calls. */
$palette = [
    'background' => [ 0x0a, 0x0e, 0x1a ],
    'surface'    => [ 0x11, 0x17, 0x2a ],
    'subtle'     => [ 0x1a, 0x21, 0x38 ],
    'foreground' => [ 0xe8, 0xec, 0xf6 ],
    'muted'      => [ 0x94, 0xa3, 0xb8 ],
    'dim'        => [ 0x47, 0x55, 0x69 ],
    'primary'    => [ 0x3b, 0x82, 0xf6 ],   // electric blue
    'primary_s'  => [ 0x60, 0xa5, 0xfa ],
    'accent'     => [ 0xa8, 0x55, 0xf7 ],   // violet
    'accent_s'   => [ 0xc0, 0x84, 0xfc ],
    'hot'        => [ 0xf4, 0x3f, 0x5e ],   // dragon pink
    'highlight'  => [ 0xfa, 0xcc, 0x15 ],   // sticky yellow
];

$theme_dir   = dirname( __DIR__ );
$out_root    = $theme_dir . '/assets/projects';

/* ------------------------------------------------------------------
 * Drawing helpers
 * ------------------------------------------------------------------ */

/** Allocate a palette color on the given image. */
function color( $im, array $rgb, int $alpha = 0 ) {
    return imagecolorallocatealpha( $im, $rgb[0], $rgb[1], $rgb[2], $alpha );
}

/** Linear-interpolate two RGB triples by t in [0,1]. */
function lerp_rgb( array $a, array $b, float $t ): array {
    return [
        (int) round( $a[0] + ( $b[0] - $a[0] ) * $t ),
        (int) round( $a[1] + ( $b[1] - $a[1] ) * $t ),
        (int) round( $a[2] + ( $b[2] - $a[2] ) * $t ),
    ];
}

/**
 * Paint a diagonal linear gradient between two colors over the rectangle
 * [x, y, x+w, y+h]. GD has no native gradient, so we walk the diagonal in
 * one-pixel slices. Cheap and pleasant on a 1200×630 canvas.
 */
function diag_gradient( $im, int $x, int $y, int $w, int $h, array $a, array $b, float $alpha = 0.0 ): void {
    $diag  = $w + $h;
    $gd_a  = (int) round( $alpha * 127 );
    for ( $i = 0; $i < $diag; $i++ ) {
        $t  = $i / max( 1, $diag - 1 );
        $rgb = lerp_rgb( $a, $b, $t );
        $c   = imagecolorallocatealpha( $im, $rgb[0], $rgb[1], $rgb[2], $gd_a );
        // Walk a diagonal line from (x+i, y) to (x, y+i), clipped to the rect.
        $x1 = min( $x + $i, $x + $w - 1 );
        $y1 = $y + max( 0, $i - $w + 1 );
        $x2 = $x + max( 0, $i - $h + 1 );
        $y2 = min( $y + $i, $y + $h - 1 );
        imageline( $im, $x1, $y1, $x2, $y2, $c );
    }
}

/**
 * Paint a soft radial glow centered at (cx, cy) with the given color and
 * radius. Built as concentric circles with falling alpha so the result
 * fades out smoothly without needing a real radial gradient.
 */
function radial_glow( $im, int $cx, int $cy, int $r, array $rgb, float $peak_alpha = 0.18 ): void {
    for ( $i = $r; $i > 0; $i-- ) {
        $t     = $i / $r;                        // 1.0 at edge, 0.0 at center
        $alpha = (int) round( ( 1 - $t ) * ( 1 - $t ) * $peak_alpha * 127 );
        $alpha = 127 - $alpha;                   // GD: 0 opaque, 127 transparent
        $c     = imagecolorallocatealpha( $im, $rgb[0], $rgb[1], $rgb[2], $alpha );
        imagefilledellipse( $im, $cx, $cy, $i * 2, $i * 2, $c );
    }
}

/** Render text in a chosen font/size/color at (x, y) where y is the baseline. */
function draw_text( $im, string $text, string $font, int $size, int $x, int $y, $color, float $angle = 0.0 ): array {
    return imagettftext( $im, $size, $angle, $x, $y, $color, $font, $text );
}

/** Measure rendered text width (no rotation). */
function text_width( string $text, string $font, int $size ): int {
    $bbox = imagettfbbox( $size, 0, $font, $text );
    return abs( $bbox[2] - $bbox[0] );
}

/**
 * Paint a wordmark using the brand gradient as the fill. GD doesn't support
 * gradient-clipped text natively, so we render the text into a grayscale
 * mask, then walk the mask pixels and paint each at the gradient's color
 * for that horizontal position.
 */
function gradient_text( $im, string $text, string $font, int $size, int $x, int $y, array $a, array $b ): void {
    // Measure the text first, then render it on a transparent mask.
    $bbox = imagettfbbox( $size, 0, $font, $text );
    $tw   = abs( $bbox[2] - $bbox[0] );
    $th   = abs( $bbox[7] - $bbox[1] ) + 12;
    $pad  = 8;

    $mask = imagecreatetruecolor( $tw + $pad * 2, $th + $pad * 2 );
    imagealphablending( $mask, false );
    imagesavealpha( $mask, true );
    $clear = imagecolorallocatealpha( $mask, 0, 0, 0, 127 );
    imagefilledrectangle( $mask, 0, 0, imagesx( $mask ) - 1, imagesy( $mask ) - 1, $clear );

    imagealphablending( $mask, true );
    $white = imagecolorallocate( $mask, 255, 255, 255 );
    imagettftext( $mask, $size, 0, $pad - $bbox[0], $pad - $bbox[7], $white, $font, $text );

    // Walk the mask: for every visible pixel, sample the gradient at that
    // horizontal position and paint that color (modulated by mask alpha) on
    // the destination image.
    $mw = imagesx( $mask );
    $mh = imagesy( $mask );
    for ( $py = 0; $py < $mh; $py++ ) {
        for ( $px = 0; $px < $mw; $px++ ) {
            $rgba = imagecolorat( $mask, $px, $py );
            $a_   = ( $rgba >> 24 ) & 0x7f;       // 0 opaque, 127 transparent
            if ( $a_ >= 127 ) { continue; }
            $t    = $tw > 0 ? max( 0.0, min( 1.0, ( $px - $pad ) / $tw ) ) : 0.0;
            $rgb  = lerp_rgb( $a, $b, $t );
            $c    = imagecolorallocatealpha( $im, $rgb[0], $rgb[1], $rgb[2], $a_ );
            imagesetpixel( $im, $x - $pad + $px, $y - ( $th - $pad ) + $py - $pad, $c );
        }
    }
    imagedestroy( $mask );
}

/* ------------------------------------------------------------------
 * Banner builder
 * ------------------------------------------------------------------ */

/**
 * Compose the shared banner shell: navy fill, dot grid, soft brand glow,
 * eyebrow / title / subtitle text stack, corner mark. The motif callback
 * draws on top of the shell, in the right-side region.
 *
 * @param array    $project ['slug','eyebrow','title','subtitle']
 * @param callable $motif   function ( $im, $palette ) : void
 */
function build_banner( array $project, array $palette, callable $motif ): void {
    $im = imagecreatetruecolor( W, H );
    imagealphablending( $im, true );
    imagesavealpha( $im, true );

    /* Background fill */
    imagefilledrectangle( $im, 0, 0, W, H, color( $im, $palette['background'] ) );

    /* Subtle 40px dot grid in the subtle-border color */
    $dot = color( $im, $palette['subtle'] );
    for ( $gy = 20; $gy < H; $gy += 40 ) {
        for ( $gx = 20; $gx < W; $gx += 40 ) {
            imagefilledellipse( $im, $gx, $gy, 3, 3, $dot );
        }
    }

    /* Single brand-gradient glow upper-right. A second violet glow further
       behind it gives the highlight a slight color shift without bleeding
       into the text column. */
    radial_glow( $im, 1020, 90,  340, $palette['primary'], 0.28 );
    radial_glow( $im, 1080, 130, 200, $palette['accent'],  0.22 );

    /* Soft top border bar — picks up the brand gradient across the width. */
    diag_gradient( $im, 0, 0, W, 4, $palette['primary'], $palette['accent'] );

    /* Project-specific motif on the right half. */
    $motif( $im, $palette );

    /* Text stack on the left. Generous padding so titles don't feel
       cramped against the edge. */
    $left_x = 80;

    /* Eyebrow: monospace, muted, wide letter spacing via a manual char
       walk because GD's imagettftext has no letter-spacing param. */
    $eyebrow_font = file_exists( FONT_MONO ) ? FONT_MONO : FONT_REGULAR;
    $eyebrow_size = 18;
    $eyebrow_col  = color( $im, $palette['muted'] );
    $cursor_x     = $left_x;
    /* mb_str_split walks UTF-8 codepoints, not bytes, so the middot (·)
       and other multibyte glyphs render as a single character instead of
       splitting into mojibake (e.g. "Â·"). */
    foreach ( mb_str_split( mb_strtoupper( $project['eyebrow'], 'UTF-8' ), 1, 'UTF-8' ) as $ch ) {
        draw_text( $im, $ch, $eyebrow_font, $eyebrow_size, $cursor_x, 200, $eyebrow_col );
        $cursor_x += text_width( $ch, $eyebrow_font, $eyebrow_size ) + 6;
    }

    /* Title: gradient-filled wordmark, big and bold. */
    $title_font = file_exists( FONT_BOLD ) ? FONT_BOLD : FONT_REGULAR;
    $title_size = 78;
    /* Titles longer than a single line get split at the manual break. */
    $title_lines = preg_split( '/\s*\n\s*/', $project['title'] );
    $line_y      = 290;
    foreach ( $title_lines as $line ) {
        gradient_text( $im, $line, $title_font, $title_size, $left_x, $line_y + $title_size, $palette['primary_s'], $palette['accent_s'] );
        $line_y += $title_size + 8;
    }

    /* Subtitle: single line of foreground-color text describing the project
       in one breath. Wrapped softly if needed. */
    $sub_font = file_exists( FONT_REGULAR ) ? FONT_REGULAR : FONT_REGULAR;
    $sub_size = 22;
    $sub_col  = color( $im, $palette['muted'] );
    $sub_y    = $line_y + 50;
    foreach ( wordwrap_lines( $project['subtitle'], $sub_font, $sub_size, 720 ) as $line ) {
        draw_text( $im, $line, $sub_font, $sub_size, $left_x, $sub_y, $sub_col );
        $sub_y += $sub_size + 10;
    }

    /* Corner brand mark: small monospace `</> dariobimbi.com` lower right. */
    $mark      = '</> dariobimbi.com';
    $mark_size = 16;
    $mark_col  = color( $im, $palette['dim'] );
    $mark_w    = text_width( $mark, $eyebrow_font, $mark_size );
    draw_text( $im, $mark, $eyebrow_font, $mark_size, W - 80 - $mark_w, H - 50, $mark_col );

    /* Save. */
    global $out_root;
    $dir  = $out_root . '/' . $project['slug'];
    $path = $dir . '/banner.png';
    if ( ! is_dir( $dir ) ) { mkdir( $dir, 0775, true ); }
    imagepng( $im, $path, 6 );
    imagedestroy( $im );

    printf( "  ✓ %s  (%s)\n", $project['slug'], $path );
}

/** Tiny word-wrap that respects rendered pixel width. */
function wordwrap_lines( string $text, string $font, int $size, int $max_w ): array {
    $words = preg_split( '/\s+/', trim( $text ) );
    $lines = [];
    $cur   = '';
    foreach ( $words as $w ) {
        $try = $cur === '' ? $w : ( $cur . ' ' . $w );
        if ( text_width( $try, $font, $size ) > $max_w && $cur !== '' ) {
            $lines[] = $cur;
            $cur     = $w;
        } else {
            $cur = $try;
        }
    }
    if ( $cur !== '' ) { $lines[] = $cur; }
    return $lines;
}

/* ------------------------------------------------------------------
 * Per-project motifs
 * ------------------------------------------------------------------ */

/** Hex grid with one filled hex glowing in brand gradient (mech / tactical). */
function motif_space_dwarves( $im, array $palette ): void {
    $cx = 920; $cy = 315;
    $r  = 36;            // hex radius
    $dx = $r * 1.732;    // horizontal step (sqrt 3)
    $dy = $r * 1.5;      // vertical step

    $stroke = color( $im, $palette['subtle'] );
    $accent = color( $im, $palette['primary'], 30 );

    // Draw a 5×5 honeycomb centered on cx,cy.
    for ( $row = -3; $row <= 3; $row++ ) {
        for ( $col = -3; $col <= 3; $col++ ) {
            $hx = $cx + $col * $dx + ( ( $row & 1 ) ? $dx / 2 : 0 );
            $hy = $cy + $row * $dy;
            if ( abs( $col ) + abs( $row ) > 5 ) continue;
            draw_hex( $im, $hx, $hy, $r - 4, $stroke, false );
        }
    }
    // Highlight the center hex with a brand-gradient fill.
    draw_hex_gradient( $im, $cx, $cy, $r - 4, $palette['primary'], $palette['accent'] );
}

function draw_hex( $im, float $cx, float $cy, float $r, $color, bool $filled = false ): void {
    $pts = [];
    for ( $i = 0; $i < 6; $i++ ) {
        $a = deg2rad( 60 * $i - 30 );
        $pts[] = $cx + cos( $a ) * $r;
        $pts[] = $cy + sin( $a ) * $r;
    }
    if ( $filled ) {
        if ( PHP_VERSION_ID >= 80100 ) { imagefilledpolygon( $im, $pts, $color ); }
        else                            { imagefilledpolygon( $im, $pts, 6, $color ); }
    } else {
        for ( $i = 0; $i < 6; $i++ ) {
            $x1 = $pts[ $i * 2 ];
            $y1 = $pts[ $i * 2 + 1 ];
            $x2 = $pts[ ( ( $i + 1 ) % 6 ) * 2 ];
            $y2 = $pts[ ( ( $i + 1 ) % 6 ) * 2 + 1 ];
            imageline( $im, (int) $x1, (int) $y1, (int) $x2, (int) $y2, $color );
        }
    }
}

function draw_hex_gradient( $im, float $cx, float $cy, float $r, array $a, array $b ): void {
    // Brute-force horizontal-gradient fill of a hex by sampling per pixel.
    $minx = (int) floor( $cx - $r );
    $maxx = (int) ceil( $cx + $r );
    $miny = (int) floor( $cy - $r );
    $maxy = (int) ceil( $cy + $r );
    for ( $py = $miny; $py <= $maxy; $py++ ) {
        for ( $px = $minx; $px <= $maxx; $px++ ) {
            if ( point_in_hex( $px, $py, $cx, $cy, $r ) ) {
                $t   = max( 0.0, min( 1.0, ( $px - ( $cx - $r ) ) / ( 2 * $r ) ) );
                $rgb = lerp_rgb( $a, $b, $t );
                $c   = imagecolorallocate( $im, $rgb[0], $rgb[1], $rgb[2] );
                imagesetpixel( $im, $px, $py, $c );
            }
        }
    }
}

function point_in_hex( float $px, float $py, float $cx, float $cy, float $r ): bool {
    $dx = abs( $px - $cx );
    $dy = abs( $py - $cy );
    if ( $dy > $r * 0.866 ) return false;            // outside vertical extent
    if ( $dx > $r ) return false;                    // outside horizontal extent
    return ( $r * 0.866 - $dy ) >= ( $dx - $r * 0.5 ) * 0.577;
}

/** Four shapes (circle, square, triangle, hexagon) — direct callback to the
 *  Lost Probe puzzle mechanic. Shrunk and pushed right so they sit alongside
 *  the title rather than under it. */
function motif_lost_probe( $im, array $palette ): void {
    $cy = 315;
    $r  = 36;
    $gap = 22;
    $start_x = 820;

    // Circle — primary blue
    $circ = color( $im, $palette['primary'] );
    imagefilledellipse( $im, $start_x + $r, $cy, $r * 2, $r * 2, $circ );

    // Square — accent violet, slightly smaller for visual weight balance
    $sq = color( $im, $palette['accent'] );
    $sx = $start_x + $r * 2 + $gap + $r;
    imagefilledrectangle( $im, $sx - $r + 4, $cy - $r + 4, $sx + $r - 4, $cy + $r - 4, $sq );

    // Triangle — hot pink
    $tx = $sx + $r + $gap + $r;
    $tri = color( $im, $palette['hot'] );
    $pts = [
        $tx,           $cy - $r,
        $tx + $r,      $cy + $r * 0.85,
        $tx - $r,      $cy + $r * 0.85,
    ];
    if ( PHP_VERSION_ID >= 80100 ) { imagefilledpolygon( $im, $pts, $tri ); }
    else                            { imagefilledpolygon( $im, $pts, 3, $tri ); }

    // Hexagon — yellow
    $hx = $tx + $r + $gap + $r;
    $yel = color( $im, $palette['highlight'] );
    draw_hex( $im, $hx, $cy, $r, $yel, true );
}

/** Robot / cyborg head: a blocky silhouette with one glowing optical sensor.
 *  Sized to fit in the right-side motif zone (~170px wide) without colliding
 *  with the title. */
function motif_one_robot_army( $im, array $palette ): void {
    $cx = 1030; $cy = 315;
    $body  = color( $im, $palette['subtle'] );
    $eye   = color( $im, $palette['hot'] );

    // Antenna
    imagefilledrectangle( $im, $cx - 3, $cy - 130, $cx + 3, $cy - 100, $body );
    imagefilledellipse( $im, $cx, $cy - 138, 16, 16, $eye );

    // Head box
    $hx = 75; $hy = 70;
    imagefilledrectangle( $im, $cx - $hx, $cy - $hy, $cx + $hx, $cy + $hy, $body );

    // Visor strip — dark inset, one bright optical dot.
    $visor_t = color( $im, $palette['background'] );
    imagefilledrectangle( $im, $cx - $hx + 12, $cy - 22, $cx + $hx - 12, $cy + 8, $visor_t );

    // Optical sensor — radial glow + solid dot, slightly right of center.
    radial_glow( $im, $cx + 22, $cy - 7, 50, $palette['hot'], 0.45 );
    imagefilledellipse( $im, $cx + 22, $cy - 7, 22, 22, $eye );
    imagefilledellipse( $im, $cx + 22, $cy - 7, 10, 10, color( $im, $palette['foreground'] ) );

    // Mouth grille — three short bars under the visor.
    $bar = color( $im, $palette['dim'] );
    for ( $i = -1; $i <= 1; $i++ ) {
        imagefilledrectangle( $im, $cx + $i * 18 - 6, $cy + 35, $cx + $i * 18 + 6, $cy + 52, $bar );
    }

    // Horizon line / radiation hash beneath, very subtle.
    $hash = color( $im, $palette['highlight'], 90 );
    for ( $i = 0; $i < 12; $i++ ) {
        $x1 = $cx - $hx - 20 + $i * 16;
        imageline( $im, $x1, $cy + 100, $x1 + 8, $cy + 100, $hash );
    }
}

/** NicKey: angled racing stripes alone — the muscle-car silhouette read as
 *  cartoonish at this scale, and the stripes alone communicate "performance"
 *  while staying in the right-side motif zone. */
function motif_nickey_performance( $im, array $palette ): void {
    $cy = 315;

    /* Four angled stripes in the brand → hot palette. They lean up-and-
       right as a speed-line callback. The whole motif is sized to sit
       inside the right column without clipping the canvas edges. */
    $stripes = [
        [ $palette['primary'],   0,  60 ],
        [ $palette['accent'],   55,  70 ],
        [ $palette['accent_s'], 115, 50 ],
        [ $palette['hot'],      155, 55 ],
    ];
    $base_x   = 880;
    $top_y    = $cy - 170;
    $bot_y    = $cy + 170;
    $skew     = 130;   // horizontal rise from bottom to top edge
    foreach ( $stripes as $stripe ) {
        [ $col, $offset, $width ] = $stripe;
        $c = color( $im, $col, 30 );
        $pts = [
            $base_x + $offset,                  $bot_y,
            $base_x + $offset + $skew,          $top_y,
            $base_x + $offset + $skew + $width, $top_y,
            $base_x + $offset + $width,         $bot_y,
        ];
        if ( PHP_VERSION_ID >= 80100 ) { imagefilledpolygon( $im, $pts, $c ); }
        else                            { imagefilledpolygon( $im, $pts, 4, $c ); }
    }
}

/** dariobimbi.com: the </> brand mark, brand-gradient-filled, sized to fit
 *  in the right-side motif zone next to the title. */
function motif_dariobimbi_com( $im, array $palette ): void {
    $cx = 1010; $cy = 315;
    $font = file_exists( FONT_BOLD ) ? FONT_BOLD : FONT_REGULAR;
    $size = 140;
    $bbox = imagettfbbox( $size, 0, $font, '</>' );
    $tw   = abs( $bbox[2] - $bbox[0] );
    $th   = abs( $bbox[7] - $bbox[1] );
    $x    = $cx - $tw / 2;
    $y    = $cy + $th / 2 - 18;
    gradient_text( $im, '</>', $font, $size, (int) $x, (int) $y, $palette['primary_s'], $palette['accent_s'] );
}

/* ------------------------------------------------------------------
 * Project list and run
 * ------------------------------------------------------------------ */

$projects = [
    [
        'slug'     => 'space-dwarves',
        'eyebrow'  => 'Game · Unity · Studio',
        'title'    => "Space Dwarves\nEntertainment",
        'subtitle' => 'Lead Level Designer, 2014 to 2015. Two Unity games at a small Chicago studio.',
        'motif'    => 'motif_space_dwarves',
    ],
    [
        'slug'     => 'lost-probe',
        'eyebrow'  => 'Game · Unity · Capstone',
        'title'    => 'The Lost Probe',
        'subtitle' => 'Capstone game at DePaul. A robot named Scrapes wanders a strange new world.',
        'motif'    => 'motif_lost_probe',
    ],
    [
        'slug'     => 'one-robot-army',
        'eyebrow'  => 'Game · Unity · School',
        'title'    => 'One Robot Army',
        'subtitle' => 'A small Unity action game made with a four-person team at DePaul (GAM 245).',
        'motif'    => 'motif_one_robot_army',
    ],
    [
        'slug'     => 'nickey-performance',
        'eyebrow'  => 'Web · WordPress · Joomla',
        'title'    => 'NicKey Performance',
        'subtitle' => 'Performance auto shop website. Built and maintained 2014 to 2017, plus the eBay listing pipeline.',
        'motif'    => 'motif_nickey_performance',
    ],
    [
        'slug'     => 'dariobimbi-com',
        'eyebrow'  => 'Web · WordPress · This site',
        'title'    => 'dariobimbi.com',
        'subtitle' => 'Custom block theme written from scratch. No page builder, no JavaScript framework.',
        'motif'    => 'motif_dariobimbi_com',
    ],
];

echo "Rendering project banners (1200×630 PNG):\n";
foreach ( $projects as $p ) {
    build_banner( $p, $palette, $p['motif'] );
}
echo "Done.\n";
