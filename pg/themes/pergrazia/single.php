<?php
/**
 * Single post template.
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
				<?php pergrazia_primary_category(); ?>
				<h1><?php the_title(); ?></h1>
				<div class="entry-meta"><?php pergrazia_post_meta(); ?></div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="single-featured-image"><?php the_post_thumbnail( 'full' ); ?></figure>
			<?php endif; ?>

			<div class="single-layout shell">
				<div class="single-main">
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

						<?php $pergrazia_tags = get_the_tag_list( '', '' ); ?>
						<?php if ( $pergrazia_tags ) : ?>
							<footer class="post-footer">
								<div class="post-tags"><?php echo wp_kses_post( $pergrazia_tags ); ?></div>
							</footer>
						<?php endif; ?>
					</div>

					<?php
					the_post_navigation(
						array(
							'prev_text' => '<span class="screen-reader-text">' . esc_html__( 'Previous article:', 'pergrazia' ) . '</span>%title',
							'next_text' => '<span class="screen-reader-text">' . esc_html__( 'Next article:', 'pergrazia' ) . '</span>%title',
						)
					);
					?>
					<?php if ( comments_open() || get_comments_number() ) : ?>
						<?php comments_template(); ?>
					<?php endif; ?>
				</div>

				<?php get_sidebar(); ?>
			</div>
		</article>
	<?php endwhile; ?>
</main>
<?php
get_footer();
