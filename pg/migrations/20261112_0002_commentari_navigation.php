<?php
/** Add the commentary landing page immediately after Home in the primary menu. */
return static function () {
	$locations = get_nav_menu_locations();
	$menu_id = (int) ( $locations['primary'] ?? 0 );
	if ( ! $menu_id || ! get_post_type_archive_link( 'pg_commentario' ) ) {
		throw new RuntimeException( 'The primary menu and commentary archive must exist before adding Commentari.' );
	}
	$items = wp_get_nav_menu_items( $menu_id );
	if ( ! $items ) {
		throw new RuntimeException( 'The primary menu has no items.' );
	}
	$home_id = 0;
	$existing = array();
	$home_path = trailingslashit( wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ?: '/' );
	$archive_paths = array(
		$home_path . 'commentari/',
		$home_path . 'commentario/',
	);
	foreach ( $items as $item ) {
		if ( (int) $item->menu_item_parent ) {
			continue;
		}
		$path = trailingslashit( wp_parse_url( $item->url, PHP_URL_PATH ) ?: '/' );
		if ( 'Home' === trim( wp_strip_all_tags( $item->title ) ) && $home_path === $path && ! wp_parse_url( $item->url, PHP_URL_FRAGMENT ) && ! wp_parse_url( $item->url, PHP_URL_QUERY ) ) {
			$home_id = $item->ID;
		}
		if ( ( 'post_type_archive' === $item->type && 'pg_commentario' === $item->object ) || ( 'custom' === $item->type && in_array( $path, $archive_paths, true ) ) ) {
			$existing[] = $item;
		}
	}
	if ( ! $home_id || count( $existing ) > 1 ) {
		throw new RuntimeException( 'Expected Home and at most one commentary landing link in the primary menu.' );
	}
	$current = $existing[0] ?? null;
	// Native archive items resolve against this environment's URL and track the
	// registered archive slug; no production hostname is stored in the menu.
	$link_id = wp_update_nav_menu_item( $menu_id, $current ? $current->ID : 0, array(
		'menu-item-title' => 'Commentari',
		'menu-item-type' => 'post_type_archive',
		'menu-item-object' => 'pg_commentario',
		'menu-item-status' => 'publish',
		'menu-item-parent-id' => 0,
		'menu-item-description' => $current ? $current->description : '',
		'menu-item-attr-title' => $current ? $current->attr_title : '',
		'menu-item-target' => $current ? $current->target : '',
		'menu-item-classes' => $current ? implode( ' ', $current->classes ) : '',
		'menu-item-xfn' => $current ? $current->xfn : '',
	) );
	if ( is_wp_error( $link_id ) || ! $link_id ) {
		throw new RuntimeException( is_wp_error( $link_id ) ? $link_id->get_error_message() : 'Cannot create the Commentari menu item.' );
	}
	$order = array();
	foreach ( $items as $item ) {
		if ( $item->ID === $link_id ) {
			continue;
		}
		$order[] = $item->ID;
		if ( $item->ID === $home_id ) {
			$order[] = $link_id;
		}
	}
	foreach ( $order as $position => $item_id ) {
		if ( (int) get_post_field( 'menu_order', $item_id ) !== $position + 1 ) {
			$result = wp_update_post( array( 'ID' => $item_id, 'menu_order' => $position + 1 ), true );
			if ( is_wp_error( $result ) ) {
				throw new RuntimeException( $result->get_error_message() );
			}
		}
	}
	// This migration also activates the plural archive rewrite on existing previews.
	flush_rewrite_rules( false );
	wp_cache_flush();
};
