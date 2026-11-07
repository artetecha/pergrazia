<?php
/**
 * Title: Home hero
 * Slug: donne-per-la-grazia/hero-home
 * Categories: donne-per-la-grazia, banner
 * Description: Welcome section with the open-arch artwork.
 * Keywords: hero, home, welcome
 *
 * @package DonnePerLaGrazia
 */

?>
<!-- wp:group {"align":"full","className":"dplg-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull dplg-hero" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%"><!-- wp:paragraph {"className":"is-style-eyebrow"} -->
<p class="is-style-eyebrow"><?php esc_html_e( 'A Per Grazia space', 'donne-per-la-grazia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"fontSize":"display"} -->
<h1 class="wp-block-heading has-display-font-size"><?php echo wp_kses_post( __( 'Women who stand firm in <em>grace</em>.', 'donne-per-la-grazia' ) ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"contrast-2","fontSize":"large"} -->
<p class="has-contrast-2-color has-text-color has-large-font-size"><?php esc_html_e( 'Bible readings, reflections and stories from women living each day by the free gift of God.', 'donne-per-la-grazia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--50)"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#articoli"><?php esc_html_e( 'Start reading', 'donne-per-la-grazia' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"42%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:42%"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"dplg-hero__art"} -->
<figure class="wp-block-image size-full dplg-hero__art"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/hero-arch.svg' ) ); ?>" alt="<?php esc_attr_e( 'An arched doorway opening onto a rising sun, a cross and gentle hills.', 'donne-per-la-grazia' ); ?>"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
