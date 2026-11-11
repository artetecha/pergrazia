<?php
/** Commentary entries use passage navigation supplied by the MU plugin. */
get_header();
?>
<main id="main-content">
	<?php while ( have_posts() ) : the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="single-hero shell is-long-title">
				<p class="entry-kicker"><a href="<?php echo esc_url( get_post_type_archive_link( 'pg_commentario' ) ); ?>">Commentario</a></p>
				<h1><?php the_title(); ?></h1>
				<?php if ( get_post_meta( get_the_ID(), 'pg_ref', true ) ) : ?>
					<p class="pg-reference"><?php echo esc_html( get_post_meta( get_the_ID(), 'pg_ref', true ) ); ?></p>
				<?php endif; ?>
			</header>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="single-featured-image"><?php the_post_thumbnail( 'full' ); ?></figure>
			<?php endif; ?>
			<div class="page-layout">
				<div class="entry-content">
					<?php the_content(); ?>
					<?php wp_link_pages( array( 'before' => '<nav class="page-links" aria-label="Pagine della puntata">Pagine: ', 'after' => '</nav>' ) ); ?>
				</div>
				<?php if ( comments_open() || get_comments_number() ) : comments_template(); endif; ?>
			</div>
		</article>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
