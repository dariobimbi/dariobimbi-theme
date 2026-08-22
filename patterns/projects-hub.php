<?php
/**
 * Title: Projects Hub
 * Slug: dariobimbi/projects-hub
 * Categories: dariobimbi
 * Description: Landing page for /projects/. Auto-lists every child of the Projects page as a card. Per-slug metadata (tag, blurb, status) lives in $meta below — when you publish a new child page, give it an entry here for tighter copy, otherwise it falls back to the Page's title and excerpt.
 *
 * The page itself (slug "projects") stays empty in admin — content lives here.
 * Template: page-projects.html (auto-picked from slug, also registered in
 * theme.json customTemplates).
 */

$projects_page = get_page_by_path( 'projects' );
$children      = $projects_page
    ? get_pages( [
        'parent'      => $projects_page->ID,
        'sort_column' => 'menu_order,post_title',
    ] )
    : [];

/**
 * Per-project metadata, keyed by child page slug. Add an entry per project so
 * the card copy stays tight and consistent. Anything missing falls back to a
 * tasteful default.
 */
$meta = [
    'space-dwarves' => [
        'tag'    => 'Game · Unity · Studio',
        'tone'   => 'hot',
        'blurb'  => 'A year and a half as Lead Level Designer at Space Dwarves Entertainment, working across two Unity games (Bionic Marine Command Online and Osiris).',
        'status' => '2014 to 2015',
    ],
    'lost-probe' => [
        'tag'    => 'Game · Unity · School',
        'tone'   => 'hot',
        'blurb'  => 'A team capstone at DePaul. An adventure-puzzle game where the player is a robot looking for the rest of his body after a crash. I built the levels and designed the puzzles.',
        'status' => '2014',
    ],
    'one-robot-army' => [
        'tag'    => 'Game · Unity · School',
        'tone'   => 'hot',
        'blurb'  => 'A small Unity action game with a four-person team in DePaul GAM 245. Set in a post-nuclear future where a cyborg fights through three levels to rescue his wife.',
        'status' => '2013',
    ],
    'nickey-performance' => [
        'tag'    => 'Web · WordPress · Joomla',
        'tone'   => 'primary-soft',
        'blurb'  => 'The website for a performance auto shop in Loves Park, Illinois. WordPress and Joomla, plus a custom HTML pipeline for their eBay vehicle listings.',
        'status' => 'Live at nickeyperformance.com',
    ],
    'davis-pto' => [
        'tag'    => 'Web · Squarespace · Volunteer',
        'tone'   => 'primary-soft',
        'blurb'  => 'The website for my kid\'s elementary school PTO in St. Charles. Third school year as the volunteer webmaster, working a real design into what Squarespace allows so any board member can still edit it.',
        'status' => 'Live at daviselementarypto.com',
    ],
    'dariobimbi-com' => [
        'tag'    => 'Web · WordPress',
        'tone'   => 'primary-soft',
        'blurb'  => 'This site. A custom WordPress block theme written from scratch, no page builder, no external fonts, no JavaScript framework.',
        'status' => 'You are reading it now',
    ],
];

$default_meta = [
    'tag'    => 'Project',
    'tone'   => 'primary-soft',
    'blurb'  => '',
    'status' => '',
];
?>
<!-- wp:group {"align":"full","className":"section section--ambient projects-hub-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|50","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section--ambient projects-hub-hero" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"align":"center","className":"eyebrow"} -->
    <p class="has-text-align-center eyebrow">selected work</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"textAlign":"center","level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}}} -->
    <h1 class="wp-block-heading has-text-align-center has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--30)">Projects.</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center","fontSize":"large","textColor":"muted","style":{"typography":{"lineHeight":"1.55"}}} -->
    <p class="has-text-align-center has-muted-color has-text-color has-large-font-size" style="line-height:1.55">A more thorough look at the work I am most proud of, with a longer description and screenshots for each one. Click any card to see the full write-up.</p>
    <!-- /wp:paragraph -->

</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"section section--surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section--surface" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

<!-- wp:html -->
<?php if ( empty( $children ) ) : ?>
    <p class="demos-hub-empty">No projects published yet. The Projects parent page has no children. Add child pages with the corresponding template and they will show up here.</p>
<?php else : ?>
    <ul class="projects-grid alignwide">
        <?php foreach ( $children as $child ) :
            $slug = $child->post_name;
            $m    = isset( $meta[ $slug ] ) ? array_merge( $default_meta, $meta[ $slug ] ) : $default_meta;
            $blurb = $m['blurb'] !== ''
                ? $m['blurb']
                : ( $child->post_excerpt !== '' ? $child->post_excerpt : 'Open it up to read the full write-up.' );
        ?>
        <?php
            /* Card preview image: prefer the wide cinematic banner-hero.png
               (Gemini art) when present, fall back to the 1200×630 OG card.
               Both live under assets/projects/<slug>/. */
            $hero_path = get_stylesheet_directory()     . '/assets/projects/' . $slug . '/banner-hero.png';
            $hero_url  = get_stylesheet_directory_uri() . '/assets/projects/' . $slug . '/banner-hero.png';
            $og_path   = get_stylesheet_directory()     . '/assets/projects/' . $slug . '/banner.png';
            $og_url    = get_stylesheet_directory_uri() . '/assets/projects/' . $slug . '/banner.png';

            if ( file_exists( $hero_path ) ) {
                $preview_url = $hero_url;
            } elseif ( file_exists( $og_path ) ) {
                $preview_url = $og_url;
            } else {
                $preview_url = '';
            }
        ?>
        <li class="project-card">
            <a class="project-card-link" href="<?php echo esc_url( get_permalink( $child ) ); ?>" aria-labelledby="project-<?php echo esc_attr( $slug ); ?>-title">
                <?php if ( $preview_url ) : ?>
                <div class="project-card-preview" aria-hidden="true">
                    <img class="project-card-preview-img" src="<?php echo esc_url( $preview_url ); ?>" alt="" loading="lazy" decoding="async">
                </div>
                <?php endif; ?>
                <div class="project-card-body">
                    <p class="project-card-tag has-<?php echo esc_attr( $m['tone'] ); ?>-color has-text-color"><?php echo esc_html( $m['tag'] ); ?></p>
                    <h3 class="project-card-title" id="project-<?php echo esc_attr( $slug ); ?>-title"><?php echo esc_html( get_the_title( $child ) ); ?></h3>
                    <p class="project-card-blurb"><?php echo esc_html( $blurb ); ?></p>
                    <?php if ( ! empty( $m['status'] ) ) : ?>
                        <p class="project-card-status"><?php echo esc_html( $m['status'] ); ?></p>
                    <?php endif; ?>
                    <span class="project-card-cta">Read more <span class="project-card-arrow" aria-hidden="true">→</span></span>
                </div>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<!-- /wp:html -->

</div>
<!-- /wp:group -->
