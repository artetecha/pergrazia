<?php
/**
 * Shared linked site artwork.
 *
 * @package Pergrazia
 */

$pergrazia_header_image = get_header_image();

if ( ! $pergrazia_header_image ) {
	return;
}

$pergrazia_hero_classes = 'site-hero__image';
$pergrazia_hero_src     = $pergrazia_header_image;

if ( str_contains( $pergrazia_header_image, '/pergrazia-hero.' ) ) {
	$pergrazia_hero_classes .= ' is-pergrazia-artwork is-pergrazia-new-artwork';
} elseif ( str_contains( $pergrazia_header_image, '/pergrazia-hero-original.' ) ) {
	$pergrazia_hero_classes .= ' is-pergrazia-artwork is-pergrazia-original-artwork';
}

if ( str_contains( $pergrazia_hero_src, '/themes/pergrazia/assets/images/' ) ) {
	$pergrazia_hero_src = add_query_arg( 'ver', PERGRAZIA_VERSION, $pergrazia_hero_src );
}
?>
<section class="site-hero" aria-label="<?php esc_attr_e( 'Per Grazia artwork', 'pergrazia' ); ?>">
	<a class="site-hero__link" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Go to the homepage', 'pergrazia' ); ?>">
		<figure class="<?php echo esc_attr( $pergrazia_hero_classes ); ?>">
			<img src="<?php echo esc_url( $pergrazia_hero_src ); ?>" width="<?php echo esc_attr( (string) get_custom_header()->width ); ?>" height="<?php echo esc_attr( (string) get_custom_header()->height ); ?>" alt="">
		</figure>
	</a>
</section>
