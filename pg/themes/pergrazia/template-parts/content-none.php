<?php
/**
 * Empty results state.
 *
 * @package Pergrazia
 */
?>
<section class="no-results reading-width">
	<h2><?php esc_html_e( 'Nothing found', 'pergrazia' ); ?></h2>
	<?php if ( is_search() ) : ?>
		<p><?php esc_html_e( 'No results matched your search. Try a different phrase.', 'pergrazia' ); ?></p>
		<?php get_search_form(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'There are no articles here yet.', 'pergrazia' ); ?></p>
	<?php endif; ?>
</section>
