<?php
/**
 * Remove local database state left by ElasticPress.
 *
 * WordPress content and any Search Ordering content are intentionally
 * preserved. The external search index is removed with the Upsun service.
 */

return static function () {
	global $wpdb;

	$plugin_file = 'elasticpress/elasticpress.php';

	$delete_prefixed_rows = static function ( string $table, string $column, array $prefixes ) use ( $wpdb ): void {
		foreach ( $prefixes as $prefix ) {
			$result = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE {$column} LIKE %s",
					$wpdb->esc_like( $prefix ) . '%'
				)
			);

			if ( false === $result ) {
				throw new RuntimeException( "Failed to delete {$prefix} records from {$table}: {$wpdb->last_error}" );
			}
		}
	};

	$cleanup_blog = static function () use ( $wpdb, $plugin_file, $delete_prefixed_rows ): void {
		$active_plugins = get_option( 'active_plugins', array() );
		if ( is_array( $active_plugins ) ) {
			$filtered_plugins = array_values( array_diff( $active_plugins, array( $plugin_file ) ) );
			if ( $filtered_plugins !== $active_plugins ) {
				update_option( 'active_plugins', $filtered_plugins );
			}
		}

		$delete_prefixed_rows(
			$wpdb->options,
			'option_name',
			array(
				'ep_',
				'elasticpress_',
				'_transient_ep_',
				'_transient_timeout_ep_',
				'_site_transient_ep_',
				'_site_transient_timeout_ep_',
				'_transient_logging_ep_',
				'_transient_timeout_logging_ep_',
			)
		);

		if ( false === $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'ep_exclude_from_search' ) ) ) {
			throw new RuntimeException( "Failed to remove ElasticPress post metadata: {$wpdb->last_error}" );
		}

		$cron = _get_cron_array();
		if ( is_array( $cron ) ) {
			$hooks = array();
			foreach ( $cron as $events ) {
				foreach ( array_keys( $events ) as $hook ) {
					if ( str_starts_with( $hook, 'ep_' ) || str_starts_with( $hook, 'elasticpress_' ) ) {
						$hooks[ $hook ] = true;
					}
				}
			}

			foreach ( array_keys( $hooks ) as $hook ) {
				$result = wp_clear_scheduled_hook( $hook );
				if ( false === $result || is_wp_error( $result ) ) {
					throw new RuntimeException( "Failed to clear the {$hook} cron hook." );
				}
			}
		}

		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			$administrator->remove_cap( 'manage_elasticpress' );
		}
	};

	$original_blog_id = get_current_blog_id();
	$site_ids         = is_multisite()
		? get_sites( array( 'fields' => 'ids', 'number' => 0 ) )
		: array( $original_blog_id );

	foreach ( $site_ids as $site_id ) {
		$switched = is_multisite() && (int) $site_id !== get_current_blog_id();
		if ( $switched ) {
			switch_to_blog( (int) $site_id );
		}

		try {
			$cleanup_blog();
			if ( is_multisite() ) {
				delete_site_meta( (int) $site_id, 'ep_indexable' );
			}
		} finally {
			if ( $switched ) {
				restore_current_blog();
			}
		}
	}

	if ( false === $wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'ep_token' ) ) ) {
		throw new RuntimeException( "Failed to remove ElasticPress user metadata: {$wpdb->last_error}" );
	}

	if ( is_multisite() ) {
		$delete_prefixed_rows(
			$wpdb->sitemeta,
			'meta_key',
			array(
				'ep_',
				'elasticpress_',
				'_transient_ep_',
				'_transient_timeout_ep_',
				'_site_transient_ep_',
				'_site_transient_timeout_ep_',
				'_transient_logging_ep_',
				'_transient_timeout_logging_ep_',
			)
		);

		$network_plugins = get_site_option( 'active_sitewide_plugins', array() );
		if ( is_array( $network_plugins ) && isset( $network_plugins[ $plugin_file ] ) ) {
			unset( $network_plugins[ $plugin_file ] );
			update_site_option( 'active_sitewide_plugins', $network_plugins );
		}
	}

	// Bulk SQL bypasses WordPress's object-cache invalidation.
	wp_cache_flush();
};
