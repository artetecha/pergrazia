<?php
/**
 * Not-found template.
 *
 * @package Pergrazia
 */

get_header();
?>
<main id="main-content">
	<section class="page-header reading-width">
		<p class="eyebrow">404</p>
		<h1><?php esc_html_e( 'This page could not be found.', 'pergrazia' ); ?></h1>
		<p><?php esc_html_e( 'The address may have changed. Search the site or return to the home page.', 'pergrazia' ); ?></p>
		<?php get_search_form(); ?>
		<p><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Return home', 'pergrazia' ); ?></a></p>
	</section>
</main>
<?php
get_footer();
