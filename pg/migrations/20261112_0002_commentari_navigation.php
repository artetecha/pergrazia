<?php
/** Add the commentary landing page immediately after Home in the primary menu. */
return static function () {
	if ( ! get_post_type_archive_link( 'pg_commentario' ) ) {
		throw new RuntimeException( 'The commentary archive must be registered before migrating navigation.' );
	}
	// Activate plural URLs even when this database has no applicable menu.
	flush_rewrite_rules( false );
	$locations = get_nav_menu_locations();
	$menu_id = (int) ( $locations['primary'] ?? 0 );
	$items = $menu_id ? wp_get_nav_menu_items( $menu_id ) : array();
	if ( ! $items ) {
		WP_CLI::log( 'Skipping Commentari menu update: no populated primary menu is assigned.' );
		return;
	}
	$home_id = 0;
	$existing = array();
	$home_path = trailingslashit( wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ?: '/' );
	$archive_paths = array(
		$home_path . 'commentari/',
		$home_path . 'commentario/',
	);
	foreach ( $items as $item ) {
		$path = trailingslashit( wp_parse_url( $item->url, PHP_URL_PATH ) ?: '/' );
		if ( 0 === (int) $item->menu_item_parent && 'Home' === trim( wp_strip_all_tags( $item->title ) ) && $home_path === $path && ! wp_parse_url( $item->url, PHP_URL_FRAGMENT ) && ! wp_parse_url( $item->url, PHP_URL_QUERY ) ) {
			$home_id = $item->ID;
		}
		if ( ( 'post_type_archive' === $item->type && 'pg_commentario' === $item->object ) || ( 'custom' === $item->type && in_array( $path, $archive_paths, true ) ) ) {
			$existing[] = $item;
		}
	}
	if ( ! $home_id || count( $existing ) > 1 ) {
		WP_CLI::log( 'Skipping Commentari menu update: Home is absent or commentary links are ambiguous.' );
		return;
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
	wp_cache_flush();
};
