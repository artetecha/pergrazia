<?php
/**
 * Title: Scripture card
 * Slug: donne-per-la-grazia/verse-card
 * Categories: donne-per-la-grazia, text
 * Description: A Bible verse set apart, with its reference.
 * Keywords: verse, scripture, bible, quote
 *
 * @package DonnePerLaGrazia
 */

?>
<!-- wp:group {"className":"is-style-section-blush","style":{"border":{"radius":"1.25rem"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-section-blush" style="border-radius:1.25rem;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:quote {"className":"is-style-verse"} -->
<blockquote class="wp-block-quote is-style-verse"><!-- wp:paragraph -->
<p><?php esc_html_e( 'For by grace are ye saved through faith; and that not of yourselves: it is the gift of God.', 'donne-per-la-grazia' ); ?></p>
<!-- /wp:paragraph --><cite><?php esc_html_e( 'Ephesians 2:8', 'donne-per-la-grazia' ); ?></cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:group -->
