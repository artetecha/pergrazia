<?php
/**
 * Featured article card.
 *
 * @package Pergrazia
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( has_post_thumbnail() ? 'featured-story has-media' : 'featured-story no-media' ); ?>>
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
