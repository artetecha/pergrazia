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
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="single-hero reading-width">
				<?php pergrazia_primary_category(); ?>
				<h1><?php the_title(); ?></h1>
				<div class="entry-meta"><?php pergrazia_post_meta(); ?></div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="single-featured-image"><?php the_post_thumbnail( 'full' ); ?></figure>
			<?php endif; ?>

			<div class="entry-content reading-width">
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
		</article>

		<div class="reading-width">
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
	<?php endwhile; ?>
</main>
<?php
get_footer();
