<?php
/**
 * Remove local database state left by Jetpack.
 *
 * Jetpack Stats history is stored on WordPress.com and cannot be migrated to
 * Cloudflare. Posts, comments, media, and other user-authored content are
 * intentionally preserved.
 */

return static function () {
	global $wpdb;

	$plugin_file = 'jetpack/jetpack.php';

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

	$drop_table = static function ( string $table ) use ( $wpdb ): void {
		if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $table ) ) {
			throw new RuntimeException( "Refusing to drop an invalid table name: {$table}" );
		}

		if ( false === $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ) ) {
			throw new RuntimeException( "Failed to drop {$table}: {$wpdb->last_error}" );
		}
	};

	$cleanup_action_scheduler = static function () use ( $wpdb ): void {
		$actions_table = $wpdb->prefix . 'actionscheduler_actions';
		$groups_table  = $wpdb->prefix . 'actionscheduler_groups';
		$logs_table    = $wpdb->prefix . 'actionscheduler_logs';

		foreach ( array( $actions_table, $groups_table, $logs_table ) as $table ) {
			$found = $wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) )
			);

			if ( $found !== $table ) {
				return;
			}
		}

		$hook_patterns  = array( 'jetpack_%', 'jp_%', 'wpcom_%' );
		$hook_conditions = implode( ' OR ', array_fill( 0, count( $hook_patterns ), 'actions.hook LIKE %s' ) );
		$prepared_hooks = array_map(
			static fn ( string $pattern ): string => $wpdb->esc_like( substr( $pattern, 0, -1 ) ) . '%',
			$hook_patterns
		);

		$queries = array(
			$wpdb->prepare(
				"DELETE logs FROM `{$logs_table}` logs INNER JOIN `{$actions_table}` actions ON actions.action_id = logs.action_id WHERE {$hook_conditions}",
				$prepared_hooks
			),
			$wpdb->prepare(
				"DELETE actions FROM `{$actions_table}` actions WHERE {$hook_conditions}",
				$prepared_hooks
			),
			$wpdb->prepare(
				"DELETE FROM `{$groups_table}` WHERE slug LIKE %s OR slug LIKE %s",
				$wpdb->esc_like( 'jetpack' ) . '%',
				$wpdb->esc_like( 'wpcom' ) . '%'
			),
		);

		foreach ( $queries as $query ) {
			if ( false === $wpdb->query( $query ) ) {
				throw new RuntimeException( "Failed to remove Jetpack scheduled actions: {$wpdb->last_error}" );
			}
		}
	};

	$cleanup_blog = static function () use (
		$wpdb,
		$plugin_file,
		$delete_prefixed_rows,
		$drop_table,
		$cleanup_action_scheduler
	): void {
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
				'jetpack_',
				'jetpack-',
				'_jetpack_',
				'my_jetpack_',
				'wpcom_',
				'widget_jetpack_',
				'_transient_jetpack_',
				'_transient_timeout_jetpack_',
				'_site_transient_jetpack_',
				'_site_transient_timeout_jetpack_',
				'_transient_wpcom_',
				'_transient_timeout_wpcom_',
				'_site_transient_wpcom_',
				'_site_transient_timeout_wpcom_',
			)
		);

		foreach (
			array(
				'disabled_likes',
				'disabled_reblogs',
				'enable_odyssey_stats',
				'odyssey_stats_changed_at',
				'safecss_preview_rev',
				'safecss_rev',
				'safecss_revision_migrated',
				'sharedaddy_disable_resources',
				'sharing-options',
				'sharing-services',
				'stats_dashboard_widget',
				'stats_options',
			) as $option
		) {
			delete_option( $option );
		}

		foreach ( array( $wpdb->postmeta, $wpdb->commentmeta, $wpdb->termmeta ) as $meta_table ) {
			$delete_prefixed_rows(
				$meta_table,
				'meta_key',
				array( '_jetpack_', 'jetpack_', '_wpcom_', 'wpcom_' )
			);
		}

		$cron = _get_cron_array();
		if ( is_array( $cron ) ) {
			$hooks = array();
			foreach ( $cron as $events ) {
				foreach ( array_keys( $events ) as $hook ) {
					if (
						str_starts_with( $hook, 'jetpack_' ) ||
						str_starts_with( $hook, 'jp_' ) ||
						str_starts_with( $hook, 'wpcom_' ) ||
						str_starts_with( $hook, 'videopress_' ) ||
						in_array(
							$hook,
							array( 'grunion_scheduled_delete', 'grunion_scheduled_delete_temp', 'wordads_cron_status' ),
							true
						)
					) {
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

		$cleanup_action_scheduler();
		$drop_table( $wpdb->prefix . 'jetpack_sync_queue' );
		$drop_table( $wpdb->prefix . 'jetpack_waf_blocklog' );
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
		} finally {
			if ( $switched ) {
				restore_current_blog();
			}
		}
	}

	$delete_prefixed_rows(
		$wpdb->usermeta,
		'meta_key',
		array( 'jetpack_', '_jetpack_', 'wpcom_', '_wpcom_' )
	);

	if ( is_multisite() ) {
		$delete_prefixed_rows(
			$wpdb->sitemeta,
			'meta_key',
			array(
				'jetpack_',
				'jetpack-',
				'_jetpack_',
				'wpcom_',
				'_transient_jetpack_',
				'_transient_timeout_jetpack_',
				'_site_transient_jetpack_',
				'_site_transient_timeout_jetpack_',
				'_transient_wpcom_',
				'_transient_timeout_wpcom_',
				'_site_transient_wpcom_',
				'_site_transient_timeout_wpcom_',
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
