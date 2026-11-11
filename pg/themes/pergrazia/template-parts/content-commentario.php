<?php
/** A commentary search result or book card, without a publication date. */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
	<p class="entry-kicker">Commentario<?php if ( wp_get_post_parent_id( get_the_ID() ) ) : ?> — <?php echo esc_html( get_the_title( wp_get_post_parent_id( get_the_ID() ) ) ); ?><?php endif; ?></p>
	<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
	<?php if ( get_post_meta( get_the_ID(), 'pg_ref', true ) ) : ?>
		<p class="pg-reference"><?php echo esc_html( get_post_meta( get_the_ID(), 'pg_ref', true ) ); ?></p>
	<?php endif; ?>
	<div class="entry-summary"><?php the_excerpt(); ?></div>
	<a class="read-more" href="<?php the_permalink(); ?>"><?php echo wp_get_post_parent_id( get_the_ID() ) ? 'Leggi la puntata' : 'Consulta l’indice'; ?></a>
</article>
