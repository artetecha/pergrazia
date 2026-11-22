<?php
/**
 * Donne per la Grazia theme bootstrap.
 *
 * Presentation lives in theme.json, templates, parts, patterns and styles.
 * This file only wires up what those files cannot declare.
 *
 * @package DonnePerLaGrazia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load translations and editor styles.
 */
function donne_per_la_grazia_setup(): void {
	load_theme_textdomain( 'donne-per-la-grazia', get_template_directory() . '/languages' );
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'donne_per_la_grazia_setup' );

/**
 * Enqueue the small stylesheet for rules theme.json cannot express.
 */
function donne_per_la_grazia_assets(): void {
	wp_enqueue_style(
		'donne-per-la-grazia',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'donne_per_la_grazia_assets' );

/**
 * Group the theme's patterns in the inserter.
 */
function donne_per_la_grazia_pattern_categories(): void {
	register_block_pattern_category(
		'donne-per-la-grazia',
		array(
			'label'       => __( 'Donne per la Grazia', 'donne-per-la-grazia' ),
			'description' => __( 'Sections and content blocks designed for this site.', 'donne-per-la-grazia' ),
		)
	);
}
add_action( 'init', 'donne_per_la_grazia_pattern_categories' );

/**
 * Use the arch mark as the favicon until a Site Icon is chosen in
 * Settings → General, which then takes over.
 */
function donne_per_la_grazia_favicon(): void {
	if ( has_site_icon() ) {
		return;
	}

	printf(
		'<link rel="icon" href="%1$s" type="image/svg+xml">' . "\n" .
		'<link rel="icon" href="%2$s" sizes="32x32" type="image/png">' . "\n" .
		'<link rel="apple-touch-icon" href="%3$s">' . "\n",
		esc_url( get_theme_file_uri( 'assets/images/favicon.svg' ) ),
		esc_url( get_theme_file_uri( 'assets/images/favicon-32.png' ) ),
		esc_url( get_theme_file_uri( 'assets/images/apple-touch-icon.png' ) )
	);
}
add_action( 'wp_head', 'donne_per_la_grazia_favicon' );
add_action( 'login_head', 'donne_per_la_grazia_favicon' );
add_action( 'admin_head', 'donne_per_la_grazia_favicon' );
