<?php
/**
 * Site header.
 *
 * @package Pergrazia
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#main-content"><?php esc_html_e( 'Skip to content', 'pergrazia' ); ?></a>

<header class="site-header">
	<div class="site-header__inner shell">
		<div class="site-branding">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
			<?php endif; ?>
			<?php $pergrazia_description = get_bloginfo( 'description', 'display' ); ?>
			<?php if ( $pergrazia_description || is_customize_preview() ) : ?>
				<p class="site-description"><?php echo esc_html( $pergrazia_description ); ?></p>
			<?php endif; ?>
		</div>

		<button class="menu-toggle" type="button" aria-controls="primary-navigation" aria-expanded="false">
			<span class="menu-toggle__bars" aria-hidden="true"></span>
			<span><?php esc_html_e( 'Menu', 'pergrazia' ); ?></span>
		</button>

		<nav id="primary-navigation" class="primary-navigation" aria-label="<?php esc_attr_e( 'Primary navigation', 'pergrazia' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_id'        => 'primary-menu',
					'fallback_cb'    => 'pergrazia_page_menu',
					'depth'          => 1,
				)
			);
			?>
			<div class="header-search">
				<?php get_search_form(); ?>
			</div>
		</nav>
	</div>
</header>
