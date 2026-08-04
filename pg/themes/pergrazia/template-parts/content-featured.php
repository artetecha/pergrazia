<?php
/**
 * Featured article card.
 *
 * @package Pergrazia
 */

$pergrazia_is_wide_thumbnail = false;

if ( has_post_thumbnail() ) {
	$pergrazia_thumbnail_metadata = wp_get_attachment_metadata( get_post_thumbnail_id() );

	if (
		is_array( $pergrazia_thumbnail_metadata )
		&& ! empty( $pergrazia_thumbnail_metadata['width'] )
		&& ! empty( $pergrazia_thumbnail_metadata['height'] )
		&& ( $pergrazia_thumbnail_metadata['width'] / $pergrazia_thumbnail_metadata['height'] ) > 1.7
	) {
		$pergrazia_is_wide_thumbnail = true;
	}
}

$pergrazia_featured_classes = has_post_thumbnail() ? 'featured-story has-media' : 'featured-story no-media';

if ( $pergrazia_is_wide_thumbnail ) {
	$pergrazia_featured_classes .= ' has-wide-media';
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( $pergrazia_featured_classes ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="featured-story__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'large', array( 'loading' => 'eager' ) ); ?>
		</a>
	<?php endif; ?>
	<div class="featured-story__content">
		<?php pergrazia_primary_category(); ?>
		<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<div class="entry-meta"><?php pergrazia_post_meta(); ?></div>
		<div class="entry-summary"><?php the_excerpt(); ?></div>
		<a class="read-more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read the article', 'pergrazia' ); ?></a>
	</div>
</article>
