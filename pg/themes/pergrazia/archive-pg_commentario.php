<?php
/** Canonical book order is set by the commentary MU plugin. */
get_header();
?>
<main id="main-content">
	<header class="archive-header"><div class="shell">
		<p class="archive-kicker">La Bibbia, brano per brano</p>
		<h1>Commentari</h1>
		<p>Scegli un libro per consultarne l’introduzione, le puntate e gli excursus.</p>
	</div></header>
	<section class="archive-posts shell" aria-label="Libri del commentario">
		<?php if ( have_posts() ) : ?>
			<div class="post-grid">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php get_template_part( 'template-parts/content', 'commentario' ); ?>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p>Il commentario sarà disponibile prossimamente.</p>
		<?php endif; ?>
	</section>
</main>
<?php get_footer(); ?>
