<?php
/**
 * Site footer.
 *
 * @package Pergrazia
 */
?>
<footer class="site-footer">
	<div class="shell">
		<div class="site-footer__grid">
			<div class="footer-brand">
				<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></p>
				<p><?php bloginfo( 'description' ); ?></p>
			</div>

			<div>
				<h2 class="footer-heading"><?php esc_html_e( 'Explore', 'pergrazia' ); ?></h2>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'footer-menu',
						'fallback_cb'    => false,
						'depth'          => 1,
					)
				);
				?>
			</div>

			<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
				<div class="footer-widgets">
					<?php dynamic_sidebar( 'footer-1' ); ?>
				</div>
			<?php else : ?>
				<div>
					<h2 class="footer-heading"><?php esc_html_e( 'Search', 'pergrazia' ); ?></h2>
					<?php get_search_form(); ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="site-info">
			<span>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
			<span><?php esc_html_e( 'Grace received, words shared.', 'pergrazia' ); ?></span>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
