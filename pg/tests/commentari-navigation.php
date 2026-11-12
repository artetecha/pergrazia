<?php
/** Check placement, environment-aware archive links and safe retries. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Requires a disposable local database.' );
}
$locations = get_nav_menu_locations();
$menu_id = wp_create_nav_menu( 'Commentari navigation QA ' . wp_generate_password( 8, false, false ) );
if ( is_wp_error( $menu_id ) ) { throw new RuntimeException( $menu_id->get_error_message() ); }
$migrate = require dirname( __DIR__ ) . '/migrations/20261112_0002_commentari_navigation.php';
$rename = require dirname( __DIR__ ) . '/migrations/20261112_0003_statuto_navigation.php';
$checks = 0;
$assert = static function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	++$checks;
};
try {
	set_theme_mod( 'nav_menu_locations', array_merge( $locations, array( 'primary' => 0 ) ) );
	delete_option( 'rewrite_rules' );
	$migrate();
	$rename();
	$assert( (bool) array_filter( array_keys( get_option( 'rewrite_rules', array() ) ), static fn ( $rule ) => str_starts_with( $rule, 'commentari' ) ), 'Missing menu still activates plural rewrite rules' );
	set_theme_mod( 'nav_menu_locations', array_merge( $locations, array( 'primary' => $menu_id ) ) );
	$migrate();
	$rename();
	$assert( ! wp_get_nav_menu_items( $menu_id ), 'Empty menus are left unchanged' );
	foreach ( array( 'Home' => '/', 'Statuto di fede' => '/statuto-di-fede/', 'Contatti' => '/contatti/' ) as $title => $url ) {
		wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => $title, 'menu-item-type' => 'custom', 'menu-item-url' => $url, 'menu-item-status' => 'publish' ) );
	}
	$original = wp_get_nav_menu_items( $menu_id );
	$migrate();
	$items = wp_get_nav_menu_items( $menu_id );
	$assert( array( 'Home', 'Commentari', 'Statuto di fede', 'Contatti' ) === wp_list_pluck( $items, 'title' ), 'Commentari immediately follows Home' );
	$link = $items[1];
	$assert( 'post_type_archive' === $link->type && 'pg_commentario' === $link->object, 'Menu uses the native commentary archive' );
	$assert( home_url( '/commentari/' ) === $link->url, 'Link resolves to the plural landing page on this environment' );
	$assert( 0 === (int) $link->menu_item_parent, 'Commentari is a top-level item' );
	$assert( $original[1]->ID === $items[2]->ID && $original[2]->url === $items[3]->url, 'Other menu items and their URLs are preserved' );
	$ids = wp_list_pluck( $items, 'ID' );
	$migrate();
	$assert( $ids === wp_list_pluck( wp_get_nav_menu_items( $menu_id ), 'ID' ), 'Retry reuses the existing link and order' );
	wp_update_post( array( 'ID' => $link->ID, 'menu_order' => 99 ) );
	$migrate();
	$assert( $ids === wp_list_pluck( wp_get_nav_menu_items( $menu_id ), 'ID' ), 'An existing misplaced link is moved after Home' );
	update_post_meta( $link->ID, '_menu_item_menu_item_parent', $original[2]->ID );
	$migrate();
	$items = wp_get_nav_menu_items( $menu_id );
	$assert( $ids === wp_list_pluck( $items, 'ID' ) && 0 === (int) $items[1]->menu_item_parent, 'Nested archive is reused and promoted without a duplicate' );
	wp_update_nav_menu_item( $menu_id, $link->ID, array( 'menu-item-title' => 'Commentario', 'menu-item-type' => 'custom', 'menu-item-url' => '/commentario/', 'menu-item-status' => 'publish', 'menu-item-parent-id' => $original[2]->ID ) );
	$migrate();
	$items = wp_get_nav_menu_items( $menu_id );
	$assert( $ids === wp_list_pluck( $items, 'ID' ) && 'post_type_archive' === $items[1]->type && 0 === (int) $items[1]->menu_item_parent, 'Nested legacy link is converted and reused' );
	wp_update_post( array( 'ID' => $original[0]->ID, 'post_title' => 'Welcome' ) );
	$snapshot = wp_get_nav_menu_items( $menu_id );
	$migrate();
	$assert( $snapshot == wp_get_nav_menu_items( $menu_id ), 'Missing Home skips without menu mutations' );
	wp_update_post( array( 'ID' => $original[0]->ID, 'post_title' => 'Home' ) );
	$duplicate = wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Another commentary link', 'menu-item-type' => 'custom', 'menu-item-url' => '/commentari/', 'menu-item-status' => 'publish' ) );
	$snapshot = wp_get_nav_menu_items( $menu_id );
	$migrate();
	$assert( $snapshot == wp_get_nav_menu_items( $menu_id ), 'Ambiguous commentary links skip without mutations' );
	wp_delete_post( $duplicate, true );
	// Rename only the label, including when editors have nested the existing item.
	$statuto = $original[1]->ID;
	update_post_meta( $statuto, '_menu_item_menu_item_parent', $original[2]->ID );
	$before = get_post( $statuto );
	$meta = get_post_meta( $statuto );
	$rename();
	$assert( 'Statuto' === get_post_field( 'post_title', $statuto ) && $meta === get_post_meta( $statuto ) && $before->menu_order === get_post( $statuto )->menu_order, 'Nested Statuto keeps its target, parent, and position' );
	$snapshot = wp_get_nav_menu_items( $menu_id );
	$rename();
	$assert( $snapshot == wp_get_nav_menu_items( $menu_id ), 'Statuto retry changes nothing' );
	wp_update_post( array( 'ID' => $statuto, 'post_title' => 'Our beliefs' ) );
	$snapshot = wp_get_nav_menu_items( $menu_id );
	$rename();
	$assert( $snapshot == wp_get_nav_menu_items( $menu_id ), 'Renamed or missing Statuto is skipped' );
	wp_update_post( array( 'ID' => $statuto, 'post_title' => 'Statuto di fede' ) );
	wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Statuto', 'menu-item-type' => 'custom', 'menu-item-url' => '/different/', 'menu-item-status' => 'publish' ) );
	$snapshot = wp_get_nav_menu_items( $menu_id );
	$rename();
	$assert( $snapshot == wp_get_nav_menu_items( $menu_id ), 'Ambiguous Statuto matches are skipped' );
	WP_CLI::success( "$checks navigation migration assertions passed." );
} finally {
	wp_delete_nav_menu( $menu_id );
	set_theme_mod( 'nav_menu_locations', $locations );
}
