<?php
/**
 * Plugin Name: Pergrazia Upsun site configuration
 * Description: Project-owned tuning for the upsun-wp MU plugin.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Mirror the route cache configuration from .upsun/config.yaml so
 * `wp upsun cache-check` reports the effective cookie allowlist.
 */
add_filter( 'upsun_cache_check_route_cache', function ( array $config ) {
	return array(
		'enabled'     => true,
		'default_ttl' => 0,
		'cookies'     => array(
			'/^wordpress_logged_in_/',
			'/^wordpress_sec_/',
			'wordpress_test_cookie',
			'/^wp-settings-/',
			'/^wp-postpass/',
			'PHPSESSID',
		),
		'known'       => true,
	);
} );
