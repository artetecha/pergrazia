<?php
/**
 * Move the never-published Acts introduction into the commentary on deployment.
 * The Upsun runner records success once per database, including cloned previews.
 */
return static function () {
	if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! class_exists( 'PG_Commentario' ) ) {
		throw new RuntimeException( 'The commentary MU plugin and WP-CLI are required.' );
	}
	$id = 5126;
	$intro = get_post( $id );
	if ( ! $intro || 'draft' !== $intro->post_status || 'Atti degli Apostoli — Introduzione' !== $intro->post_title || ! in_array( $intro->post_type, array( 'post', 'pg_commentario' ), true ) ) {
		WP_CLI::log( 'Skipping introduction migration: post 5126 is absent or no longer the expected unpublished draft.' );
		return;
	}
	$before = array_intersect_key( $intro->to_array(), array_flip( array( 'ID', 'post_content', 'post_title', 'post_date', 'post_date_gmt', 'post_status', 'post_author', 'comment_count', 'comment_status' ) ) );
	$books = get_posts( array( 'post_type' => 'pg_commentario', 'post_parent' => 0, 'name' => 'atti', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'numberposts' => 2 ) );
	if ( count( $books ) > 1 || ( $books && 44 !== (int) get_post_meta( $books[0]->ID, 'pg_book', true ) ) ) {
		WP_CLI::log( 'Skipping introduction migration: the existing Atti entry is incompatible or ambiguous.' );
		return;
	}
	if ( $books && 'publish' !== $books[0]->post_status ) {
		WP_CLI::log( 'Skipping introduction migration: the existing Atti book is not published.' );
		return;
	}

	// This persistent mount is outside WordPress's document root. A failed
	// export must stop the deployment before any commentary content changes.
	$directory = dirname( rtrim( ABSPATH, '/' ) ) . '/private/migration-backups';
	$old_umask = umask( 0077 );
	try {
		if ( ! wp_mkdir_p( $directory ) || ! chmod( $directory, 0700 ) ) {
			throw new RuntimeException( 'Cannot create the private migration backup directory.' );
		}
		$backup = $directory . '/20261112_0001_commentario_' . gmdate( 'Ymd_His' ) . '_' . wp_generate_password( 8, false, false ) . '.sql';
		$file = fopen( $backup, 'x' );
		if ( false === $file ) {
			throw new RuntimeException( 'Cannot create the database backup file.' );
		}
		fclose( $file );
		$result = WP_CLI::runcommand(
			'db export ' . escapeshellarg( $backup ) . ' --single-transaction --skip-lock-tables',
			array( 'return' => 'all', 'exit_error' => false )
		);
		clearstatcache( true, $backup );
		if ( 0 !== $result->return_code || filesize( $backup ) < 100 || ! chmod( $backup, 0600 ) ) {
			throw new RuntimeException( 'Database backup failed; commentary migration not started.' );
		}
	} finally {
		umask( $old_umask );
	}
	WP_CLI::log( 'Pre-migration database backup: ' . $backup );

	global $wpdb;
	if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
		throw new RuntimeException( 'Cannot start the commentary migration transaction.' );
	}
	try {
		$book_id = $books ? $books[0]->ID : wp_insert_post( array(
			'post_type' => 'pg_commentario', 'post_status' => 'publish',
			'post_title' => 'Atti', 'post_name' => 'atti', 'menu_order' => 44,
			'comment_status' => 'closed', 'meta_input' => array( 'pg_book' => 44 ),
		), true );
		if ( is_wp_error( $book_id ) ) {
			throw new RuntimeException( $book_id->get_error_message() );
		}
		$result = wp_update_post( array(
			'ID' => $id, 'post_type' => 'pg_commentario', 'post_parent' => $book_id,
			'post_name' => 'introduzione', 'post_status' => 'draft', 'menu_order' => 0, 'edit_date' => true,
			'post_date' => $intro->post_date, 'post_date_gmt' => $intro->post_date_gmt,
			'meta_input' => array( 'pg_kind' => 'introduzione', 'pg_ref' => '' ),
		), true );
		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( $result->get_error_message() );
		}
		$after = get_post( $id );
		foreach ( $before as $field => $value ) {
			if ( $after->$field !== $value ) {
				throw new RuntimeException( 'Preservation check failed for ' . $field . '; retain the backup and investigate.' );
			}
		}
		if ( $after->post_parent !== $book_id || 'introduzione' !== $after->post_name || 'pg_commentario' !== $after->post_type || 0 !== (int) $after->menu_order ) {
			throw new RuntimeException( 'Migration verification failed; retain the backup and investigate.' );
		}
		// The introduction was never published: no dated blog permalink ever existed.
		// Keep the ID URL as an alias, activated by template_redirect only on publication.
		$redirects = get_option( 'pg_commentario_redirects', array() );
		$redirects[ '?p=' . $id ] = $id;
		update_option( 'pg_commentario_redirects', $redirects, false );

		if ( false === $wpdb->query( 'COMMIT' ) ) {
			throw new RuntimeException( 'Cannot commit the commentary migration.' );
		}
	} catch ( Throwable $error ) {
		$wpdb->query( 'ROLLBACK' );
		wp_cache_flush();
		throw $error;
	}
	// Run after commit so the fresh processes see the migrated database.
	foreach ( array( 'rewrite flush', 'cache flush' ) as $command ) {
		$result = WP_CLI::runcommand( $command, array( 'return' => 'all', 'exit_error' => false ) );
		if ( 0 !== $result->return_code ) {
			throw new RuntimeException( 'Commentary migration follow-up failed: ' . $command );
		}
	}
	WP_CLI::log( sprintf( 'Atti: %d. Introduction: %d, still draft; original ID, content, dates and comments preserved.', $book_id, $id ) );
};
