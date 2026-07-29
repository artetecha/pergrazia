<?php
/**
 * Posts page and site home.
 *
 * @package Pergrazia
 */

get_header();
$pergrazia_header_image = get_header_image();
?>
<main id="main-content">
	<?php if ( ! is_paged() ) : ?>
		<?php if ( $pergrazia_header_image ) : ?>
			<section class="site-hero" aria-label="<?php esc_attr_e( 'Pergrazia artwork', 'pergrazia' ); ?>">
				<figure class="site-hero__image">
					<img src="<?php echo esc_url( $pergrazia_header_image ); ?>" width="<?php echo esc_attr( (string) get_custom_header()->width ); ?>" height="<?php echo esc_attr( (string) get_custom_header()->height ); ?>" alt="">
				</figure>
			</section>
		<?php endif; ?>
		<header class="home-intro reading-width">
			<p class="eyebrow"><?php esc_html_e( 'Faith, grace and life', 'pergrazia' ); ?></p>
			<h1><?php esc_html_e( 'Thoughts for a lived faith.', 'pergrazia' ); ?></h1>
			<p><?php bloginfo( 'description' ); ?></p>
		</header>
	<?php else : ?>
		<header class="archive-header">
			<div class="shell">
				<p class="archive-kicker"><?php esc_html_e( 'Journal', 'pergrazia' ); ?></p>
				<h1><?php esc_html_e( 'Latest articles', 'pergrazia' ); ?></h1>
			</div>
		</header>
	<?php endif; ?>

	<section class="post-list shell" aria-label="<?php esc_attr_e( 'Latest articles', 'pergrazia' ); ?>">
		<?php if ( have_posts() ) : ?>
			<?php $pergrazia_position = 0; ?>
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<?php if ( 0 === $pergrazia_position && ! is_paged() ) : ?>
					<?php get_template_part( 'template-parts/content', 'featured' ); ?>
					<div class="post-grid">
				<?php else : ?>
					<?php if ( 0 === $pergrazia_position ) : ?><div class="post-grid"><?php endif; ?>
					<?php get_template_part( 'template-parts/content', 'card' ); ?>
				<?php endif; ?>
				<?php ++$pergrazia_position; ?>
			<?php endwhile; ?>
			</div>

			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => __( 'Previous', 'pergrazia' ),
					'next_text' => __( 'Next', 'pergrazia' ),
				)
			);
			?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</section>
</main>
<?php
get_footer();
