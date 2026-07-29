<?php
/**
 * Theme Customizer settings.
 *
 * @package Pergrazia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register homepage settings.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
 */
function pergrazia_customize_register( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'pergrazia_homepage',
		array(
			'title'       => __( 'Homepage settings', 'pergrazia' ),
			'description' => __( 'Configure the calls to action shown below the latest articles.', 'pergrazia' ),
			'priority'    => 35,
		)
	);

	$wp_customize->add_setting(
		'pergrazia_donate_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);

	$wp_customize->add_control(
		'pergrazia_donate_url',
		array(
			'label'       => __( 'Donation URL', 'pergrazia' ),
			'description' => __( 'The homepage donation button will become active when this URL is set.', 'pergrazia' ),
			'section'     => 'pergrazia_homepage',
			'type'        => 'url',
		)
	);
}
add_action( 'customize_register', 'pergrazia_customize_register' );
