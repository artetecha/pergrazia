<?php
/**
 * Title: Header
 * Slug: donne-per-la-grazia/header
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * @package DonnePerLaGrazia
 */

?>
<!-- wp:group {"align":"full","className":"dplg-family-bar","style":{"spacing":{"padding":{"top":"0.45rem","bottom":"0.45rem"}}},"backgroundColor":"contrast","textColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull dplg-family-bar has-base-color has-contrast-background-color has-text-color has-background" style="padding-top:0.45rem;padding-bottom:0.45rem"><!-- wp:paragraph {"align":"center","fontSize":"x-small"} -->
<p class="has-text-align-center has-x-small-font-size">
<?php
echo wp_kses_post(
	sprintf(
		/* translators: %s: link to the Per Grazia website. */
		__( 'A space of the %s family', 'donne-per-la-grazia' ),
		'<a href="https://www.pergrazia.com/">Per Grazia</a>'
	)
);
?>
</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"dplg-header","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull dplg-header" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide"><!-- wp:group {"className":"dplg-brand","style":{"spacing":{"blockGap":"0.15rem"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group dplg-brand"><!-- wp:site-title {"level":0} /-->

<!-- wp:site-tagline /--></div>
<!-- /wp:group -->

<!-- wp:navigation {"overlayBackgroundColor":"base","overlayTextColor":"contrast","layout":{"type":"flex","justifyContent":"right","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"var:preset|spacing|40"}}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
