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
