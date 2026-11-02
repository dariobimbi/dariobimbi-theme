<?php
/**
 * Title: Skills
 * Slug: dariobimbi/skills
 * Categories: dariobimbi
 * Description: Skills grouped by category, displayed as colored pill chips, on the elevated surface tone.
 */

$groups = [
    [ 'label' => 'Web',                'tone' => 'is-primary', 'items' => [ 'WordPress', 'PHP', 'JavaScript', 'TypeScript', 'HTML', 'CSS', 'Sass', 'Divi', 'Block themes / theme.json', 'Responsive design' ] ],
    [ 'label' => 'Apps & Frameworks',  'tone' => 'is-accent',  'items' => [ 'Angular', 'Flutter', 'Node.js', 'Unity3D', 'C#', 'Custom PHP CMS' ] ],
    [ 'label' => 'APIs & Data',        'tone' => 'is-primary', 'items' => [ 'REST APIs', 'WordPress REST API', 'MySQL', 'Firestore', 'Google Cloud' ] ],
    [ 'label' => 'Craft & Tools',      'tone' => 'is-hot',     'items' => [ 'Git', 'GitHub', 'PHPUnit', 'Jest', 'SEO', 'Accessibility', 'Core Web Vitals', 'Code review', 'WP-CLI', 'Playwright', 'Photoshop' ] ],
];

// Cert data lives in inc/seo.php (DARIOBIMBI_CERTS) so the JSON-LD reads the same list.
$certs = function_exists( 'dariobimbi_certifications' ) ? dariobimbi_certifications() : [];
?>
<!-- wp:group {"align":"full","className":"section section--surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section--surface" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">skills</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|40"}}}} -->
    <h2 class="wp-block-heading has-gradient-text" id="skills" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--40)">The toolbox.</h2>
    <!-- /wp:heading -->

    <!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group">

        <?php foreach ( $groups as $g ) : ?>

            <!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
            <div class="wp-block-group">

                <!-- wp:heading {"level":3} -->
                <h3 class="wp-block-heading"><?php echo esc_html( $g['label'] ); ?></h3>
                <!-- /wp:heading -->

                <!-- wp:html -->
                <div class="skill-pill-row">
                    <?php foreach ( $g['items'] as $item ) : ?>
                        <span class="skill-pill <?php echo esc_attr( $g['tone'] ); ?>"><?php echo esc_html( $item ); ?></span>
                    <?php endforeach; ?>
                </div>
                <!-- /wp:html -->

            </div>
            <!-- /wp:group -->

        <?php endforeach; ?>

        <?php if ( $certs ) : ?>
            <!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
            <div class="wp-block-group">

                <!-- wp:heading {"level":3} -->
                <h3 class="wp-block-heading">Certifications</h3>
                <!-- /wp:heading -->

                <!-- wp:html -->
                <div class="cert-row">
                    <?php foreach ( $certs as $c ) : ?>
                        <a class="dario-card cert-card" href="<?php echo esc_url( $c['url'] ); ?>" target="_blank" rel="noopener">
                            <img src="<?php echo esc_url( get_theme_file_uri( 'assets/certs/' . $c['badge'] ) ); ?>" width="96" height="96" loading="lazy" decoding="async" alt="<?php echo esc_attr( $c['name'] . ' badge' ); ?>">
                            <span class="cert-card__text">
                                <span class="cert-card__name"><?php echo esc_html( $c['name'] ); ?></span>
                                <span class="cert-card__meta"><?php echo esc_html( $c['issuer'] . ' · Issued ' . wp_date( 'M Y', strtotime( $c['issued'] ) ) ); ?></span>
                                <span class="cert-card__verify">Verify credential<span class="screen-reader-text"> (opens in a new tab)</span></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <!-- /wp:html -->

            </div>
            <!-- /wp:group -->
        <?php endif; ?>

    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
