<?php
/** Shorten the primary-menu label without changing the page or its URL. */
return static function () {
	$locations = get_nav_menu_locations();
	$menu_id = (int) ( $locations['primary'] ?? 0 );
	$items = $menu_id ? wp_get_nav_menu_items( $menu_id ) : array();
	$matches = array_filter( $items ?: array(), static function ( $item ) {
		return in_array( trim( wp_strip_all_tags( $item->title ) ), array( 'Statuto di fede', 'Statuto' ), true );
	} );
	if ( 1 !== count( $matches ) ) {
		WP_CLI::log( 'Skipping Statuto menu update: no unique matching item exists in the primary menu.' );
		return;
	}
	$item = reset( $matches );
	if ( 'Statuto' !== $item->title ) {
		$result = wp_update_post( array( 'ID' => $item->ID, 'post_title' => 'Statuto' ), true );
		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( $result->get_error_message() );
		}
	}
	wp_cache_flush();
};
