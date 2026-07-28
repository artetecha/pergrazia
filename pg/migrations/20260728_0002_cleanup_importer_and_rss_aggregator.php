<?php
/**
 * Remove database state left by WordPress Importer and WP RSS Aggregator.
 *
 * WordPress Importer does not own imported content, so that content is
 * intentionally preserved. WP RSS Aggregator's custom content is removed.
 */

return static function () {
	global $wpdb;

	$plugin_files = array(
		'wordpress-importer/wordpress-importer.php',
		'wp-rss-aggregator/wp-rss-aggregator.php',
	);

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

	$remove_capabilities = static function (): void {
		$legacy_capabilities = array( 'manage_feed_settings' );
		foreach ( array( 'feed', 'feed_source' ) as $type ) {
			$legacy_capabilities = array_merge(
				$legacy_capabilities,
				array(
					"edit_{$type}",
					"read_{$type}",
					"delete_{$type}",
					"edit_{$type}s",
					"edit_others_{$type}s",
					"publish_{$type}s",
					"read_private_{$type}s",
					"delete_{$type}s",
					"delete_private_{$type}s",
					"delete_published_{$type}s",
					"delete_others_{$type}s",
					"edit_private_{$type}s",
					"edit_published_{$type}s",
					"manage_{$type}_terms",
					"edit_{$type}_terms",
					"delete_{$type}_terms",
					"assign_{$type}_terms",
				)
			);
		}

		foreach ( array( 'administrator', 'editor' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}

			foreach ( $legacy_capabilities as $capability ) {
				$role->remove_cap( $capability );
			}
		}

		$administrator = get_role( 'administrator' );
		if ( ! $administrator ) {
			return;
		}

		foreach (
			array(
				'see_aggregator',
				'add_sources',
				'edit_sources',
				'delete_sources',
				'add_displays',
				'edit_displays',
				'delete_displays',
				'edit_settings',
			) as $capability
		) {
			$administrator->remove_cap( $capability );
		}
	};

	$delete_plugin_posts = static function () use ( $wpdb ): void {
		$post_types  = array(
			'wprss_feed',
			'wprss_feed_item',
			'wprss_feed_template',
			'wprss_feed_blacklist',
			'wprss_blacklist',
			'wpra-logger',
		);
		$placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

		do {
			$post_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type IN ({$placeholders}) LIMIT 500",
					$post_types
				)
			);

			if ( '' !== $wpdb->last_error ) {
				throw new RuntimeException( "Failed to find WP RSS Aggregator posts: {$wpdb->last_error}" );
			}

			foreach ( $post_ids as $post_id ) {
				if ( false === wp_delete_post( (int) $post_id, true ) ) {
					throw new RuntimeException( "Failed to delete WP RSS Aggregator post {$post_id}." );
				}
			}
		} while ( ! empty( $post_ids ) );
	};

	$delete_plugin_terms = static function (): void {
		$registered_for_cleanup = false;
		if ( ! taxonomy_exists( 'wprss_category' ) ) {
			register_taxonomy( 'wprss_category', array(), array( 'public' => false ) );
			$registered_for_cleanup = true;
		}

		try {
			do {
				$term_ids = get_terms(
					array(
						'taxonomy'   => 'wprss_category',
						'hide_empty' => false,
						'fields'     => 'ids',
						'number'     => 500,
					)
				);

				if ( is_wp_error( $term_ids ) ) {
					throw new RuntimeException( 'Failed to find WP RSS Aggregator terms: ' . $term_ids->get_error_message() );
				}

				foreach ( $term_ids as $term_id ) {
					$result = wp_delete_term( (int) $term_id, 'wprss_category' );
					if ( false === $result || is_wp_error( $result ) ) {
						throw new RuntimeException( "Failed to delete WP RSS Aggregator term {$term_id}." );
					}
				}
			} while ( ! empty( $term_ids ) );
		} finally {
			if ( $registered_for_cleanup ) {
				unregister_taxonomy( 'wprss_category' );
			}
		}
	};

	$cleanup_blog = static function () use (
		$wpdb,
		$plugin_files,
		$delete_prefixed_rows,
		$drop_table,
		$remove_capabilities,
		$delete_plugin_posts,
		$delete_plugin_terms
	): void {
		$active_plugins = get_option( 'active_plugins', array() );
		if ( is_array( $active_plugins ) ) {
			$filtered_plugins = array_values( array_diff( $active_plugins, $plugin_files ) );
			if ( $filtered_plugins !== $active_plugins ) {
				update_option( 'active_plugins', $filtered_plugins );
			}
		}

		$delete_prefixed_rows(
			$wpdb->options,
			'option_name',
			array(
				'wprss_',
				'wpra_',
				'_transient_wprss_',
				'_transient_timeout_wprss_',
				'_transient__wprss_',
				'_transient_timeout__wprss_',
				'_site_transient_wprss_',
				'_site_transient_timeout_wprss_',
				'_transient_wpra_',
				'_transient_timeout_wpra_',
				'_transient_wpra/',
				'_transient_timeout_wpra/',
				'_site_transient_wpra_',
				'_site_transient_timeout_wpra_',
			)
		);

		$delete_prefixed_rows(
			$wpdb->postmeta,
			'meta_key',
			array( 'wprss_', '_wprss_', 'wpra_', '_wpra_' )
		);

		$wpseo_titles = get_option( 'wpseo_titles', array() );
		if ( is_array( $wpseo_titles ) ) {
			$changed = false;
			foreach ( array( 'hideeditbox-wprss_feed', 'hideeditbox-wprss_feed_item' ) as $key ) {
				if ( array_key_exists( $key, $wpseo_titles ) ) {
					unset( $wpseo_titles[ $key ] );
					$changed = true;
				}
			}

			if ( $changed ) {
				update_option( 'wpseo_titles', $wpseo_titles );
			}
		}

		$cron = _get_cron_array();
		if ( is_array( $cron ) ) {
			$hooks = array();
			foreach ( $cron as $events ) {
				foreach ( array_keys( $events ) as $hook ) {
					if (
						str_starts_with( $hook, 'wprss_' ) ||
						str_starts_with( $hook, 'wpra_' ) ||
						str_starts_with( $hook, 'wpra/' )
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

		$remove_capabilities();
		$delete_plugin_posts();
		$delete_plugin_terms();

		if ( false === $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' ) ) {
			throw new RuntimeException( "Failed to disable foreign key checks: {$wpdb->last_error}" );
		}

		try {
			foreach (
				array(
					'agg_sources',
					'agg_reject_list',
					'agg_displays',
					'agg_progress',
					'agg_ir_posts',
					'agg_folders',
					'agg_folder_sources',
					'wprss_logs',
				) as $table_suffix
			) {
				$drop_table( $wpdb->prefix . $table_suffix );
			}
		} finally {
			if ( false === $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' ) ) {
				throw new RuntimeException( "Failed to re-enable foreign key checks: {$wpdb->last_error}" );
			}
		}

		flush_rewrite_rules( false );
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

	if ( is_multisite() ) {
		$delete_prefixed_rows(
			$wpdb->sitemeta,
			'meta_key',
			array(
				'wprss_',
				'wpra_',
				'_site_transient_wprss_',
				'_site_transient_timeout_wprss_',
				'_site_transient_wpra_',
				'_site_transient_timeout_wpra_',
			)
		);

		$network_plugins = get_site_option( 'active_sitewide_plugins', array() );
		if ( is_array( $network_plugins ) ) {
			$changed = false;
			foreach ( $plugin_files as $plugin_file ) {
				if ( isset( $network_plugins[ $plugin_file ] ) ) {
					unset( $network_plugins[ $plugin_file ] );
					$changed = true;
				}
			}

			if ( $changed ) {
				update_site_option( 'active_sitewide_plugins', $network_plugins );
			}
		}
	}

	wp_cache_flush();
};
