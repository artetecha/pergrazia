<?php
/**
 * Page template.
 *
 * @package Pergrazia
 */

get_header();
?>
<main id="main-content">
	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>
		<?php $pergrazia_long_title = strlen( wp_strip_all_tags( get_the_title() ) ) > 48; ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="single-hero shell<?php echo $pergrazia_long_title ? ' is-long-title' : ''; ?>">
				<h1><?php the_title(); ?></h1>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="single-featured-image"><?php the_post_thumbnail( 'full' ); ?></figure>
			<?php endif; ?>

			<div class="page-layout">
				<div class="entry-content">
					<?php the_content(); ?>
					<?php
					wp_link_pages(
						array(
							'before' => '<nav class="page-links">' . esc_html__( 'Pages:', 'pergrazia' ),
							'after'  => '</nav>',
						)
					);
					?>
				</div>
			</div>
		</article>
	<?php endwhile; ?>
</main>
<?php
get_footer();
