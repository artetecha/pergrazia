<?php
/**
 * Title: Page not found
 * Slug: donne-per-la-grazia/page-404
 * Categories: donne-per-la-grazia
 * Inserter: no
 *
 * @package DonnePerLaGrazia
 */

?>
<!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"36rem"}} -->
<div class="wp-block-group alignwide"><!-- wp:image {"width":"96px","sizeSlug":"full","linkDestination":"none","align":"center","className":"dplg-404-mark"} -->
<figure class="wp-block-image aligncenter size-full is-resized dplg-404-mark"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/mark.svg' ) ); ?>" alt="" style="width:96px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"align":"center","className":"is-style-eyebrow"} -->
<p class="has-text-align-center is-style-eyebrow"><?php esc_html_e( 'Error 404', 'donne-per-la-grazia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'This page is not here.', 'donne-per-la-grazia' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"contrast-2"} -->
<p class="has-text-align-center has-contrast-2-color has-text-color"><?php esc_html_e( 'It may have moved, or the address may be mistyped. A search might help.', 'donne-per-la-grazia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:search {"label":"<?php echo esc_attr_x( 'Search', 'search form label', 'donne-per-la-grazia' ); ?>","showLabel":false,"placeholder":"<?php echo esc_attr__( 'Search the articles…', 'donne-per-la-grazia' ); ?>","buttonText":"<?php echo esc_attr_x( 'Search', 'search button text', 'donne-per-la-grazia' ); ?>"} /--></div>
<!-- /wp:group -->
