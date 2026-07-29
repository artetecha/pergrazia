<?php
/**
 * Standard article card.
 *
 * @package Pergrazia
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="post-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'large' ); ?>
		</a>
	<?php endif; ?>
	<?php pergrazia_primary_category(); ?>
	<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
	<div class="entry-meta"><?php pergrazia_post_meta(); ?></div>
	<div class="entry-summary"><?php the_excerpt(); ?></div>
	<a class="read-more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read the article', 'pergrazia' ); ?></a>
</article>
