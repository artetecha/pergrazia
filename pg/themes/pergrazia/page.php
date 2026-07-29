<?php
/**
 * Page template.
 *
 * @package Pergrazia
 */

get_header();
?>
<main id="main-content" class="page-content-wrap">
	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="page-header reading-width"><h1><?php the_title(); ?></h1></header>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="single-featured-image"><?php the_post_thumbnail( 'full' ); ?></figure>
			<?php endif; ?>
			<div class="entry-content reading-width">
				<?php the_content(); ?>
				<?php wp_link_pages(); ?>
			</div>
		</article>
		<?php if ( comments_open() || get_comments_number() ) : ?>
			<div class="reading-width"><?php comments_template(); ?></div>
		<?php endif; ?>
	<?php endwhile; ?>
</main>
<?php
get_footer();
