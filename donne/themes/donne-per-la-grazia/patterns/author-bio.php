<?php
/**
 * Title: Author biography
 * Slug: donne-per-la-grazia/author-bio
 * Categories: donne-per-la-grazia
 * Inserter: no
 *
 * @package DonnePerLaGrazia
 */

?>
<!-- wp:group {"className":"is-style-card dplg-author","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group is-style-card dplg-author"><!-- wp:avatar {"size":72,"style":{"border":{"radius":"999px"}}} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"0.35rem"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"is-style-eyebrow"} -->
<p class="is-style-eyebrow"><?php esc_html_e( 'Written by', 'donne-per-la-grazia' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:post-author-name {"isLink":true,"fontSize":"large"} /-->

<!-- wp:post-author-biography {"fontSize":"small"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
