<?php
/**
 * Archive template.
 *
 * @package Pergrazia
 */

get_header();
?>
<main id="main-content">
	<header class="archive-header">
		<div class="shell">
			<p class="archive-kicker"><?php esc_html_e( 'Archive', 'pergrazia' ); ?></p>
			<?php the_archive_title( '<h1>', '</h1>' ); ?>
			<?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
		</div>
	</header>
	<section class="archive-posts shell">
		<?php if ( have_posts() ) : ?>
			<div class="post-grid">
				<?php while ( have_posts() ) : ?>
					<?php the_post(); ?>
					<?php get_template_part( 'template-parts/content', 'card' ); ?>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</section>
</main>
<?php
get_footer();
