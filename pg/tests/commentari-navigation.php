<?php
/** Check placement, environment-aware archive links and safe retries. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Requires a disposable local database.' );
}
$locations = get_nav_menu_locations();
$menu_id = wp_create_nav_menu( 'Commentari navigation QA ' . wp_generate_password( 8, false, false ) );
if ( is_wp_error( $menu_id ) ) { throw new RuntimeException( $menu_id->get_error_message() ); }
$migrate = require dirname( __DIR__ ) . '/migrations/20261112_0002_commentari_navigation.php';
$checks = 0;
$assert = static function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	++$checks;
};
try {
	set_theme_mod( 'nav_menu_locations', array_merge( $locations, array( 'primary' => $menu_id ) ) );
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
	WP_CLI::success( "$checks navigation migration assertions passed." );
} finally {
	wp_delete_nav_menu( $menu_id );
	set_theme_mod( 'nav_menu_locations', $locations );
}
