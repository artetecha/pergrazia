<?php
/**
 * Required fallback template.
 *
 * @package Pergrazia
 */

get_header();
?>
<main id="main-content">
	<header class="archive-header">
		<div class="shell">
			<p class="archive-kicker"><?php esc_html_e( 'Journal', 'pergrazia' ); ?></p>
			<h1><?php esc_html_e( 'Latest articles', 'pergrazia' ); ?></h1>
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
