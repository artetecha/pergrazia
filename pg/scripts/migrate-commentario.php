<?php
/**
 * Explicit migration, never run by the deploy hook.
 * wp eval-file scripts/migrate-commentario.php 5126 /tmp/commentario-before.sql
 * On production, append production-approved only after operator approval.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! class_exists( 'PG_Commentario' ) ) {
	throw new RuntimeException( 'Run this file with WP-CLI after installing pg-commentario.php.' );
}

$id = (int) ( $args[0] ?? 0 );
$backup = $args[1] ?? '';
if ( 'production' === wp_get_environment_type() && 'production-approved' !== ( $args[2] ?? '' ) ) {
	WP_CLI::error( 'Production requires explicit approval. After approval, append production-approved.' );
}
if ( ! is_file( $backup ) || ! is_readable( $backup ) || filesize( $backup ) < 100 || filemtime( $backup ) < time() - HOUR_IN_SECONDS ) {
	WP_CLI::error( 'Provide a readable database export made within the last hour before migrating.' );
}
$intro = get_post( $id );
if ( ! $intro || 'draft' !== $intro->post_status || 'Atti degli Apostoli — Introduzione' !== $intro->post_title || ! in_array( $intro->post_type, array( 'post', 'pg_commentario' ), true ) ) {
	WP_CLI::error( 'The specified post must be the unpublished Acts introduction draft.' );
}
$before = array_intersect_key( $intro->to_array(), array_flip( array( 'ID', 'post_content', 'post_title', 'post_date', 'post_date_gmt', 'post_status', 'post_author', 'comment_count', 'comment_status' ) ) );
$books = get_posts( array( 'post_type' => 'pg_commentario', 'post_parent' => 0, 'name' => 'atti', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'numberposts' => 2 ) );
if ( count( $books ) > 1 || ( $books && 44 !== (int) get_post_meta( $books[0]->ID, 'pg_book', true ) ) ) {
	WP_CLI::error( 'An incompatible Atti entry already exists; resolve it before migrating.' );
}
if ( $books && 'publish' !== $books[0]->post_status ) {
	WP_CLI::error( 'The existing Atti book must be published before migrating its introduction.' );
}
$book_id = $books ? $books[0]->ID : wp_insert_post( array(
	'post_type' => 'pg_commentario', 'post_status' => 'publish',
	'post_title' => 'Atti', 'post_name' => 'atti', 'menu_order' => 44,
	'comment_status' => 'closed', 'meta_input' => array( 'pg_book' => 44 ),
), true );
if ( is_wp_error( $book_id ) ) {
	WP_CLI::error( $book_id->get_error_message() );
}
$result = wp_update_post( array(
	'ID' => $id, 'post_type' => 'pg_commentario', 'post_parent' => $book_id,
	'post_name' => 'introduzione', 'post_status' => 'draft', 'menu_order' => 0,
	'post_date' => $intro->post_date, 'post_date_gmt' => $intro->post_date_gmt,
	'meta_input' => array( 'pg_kind' => 'introduzione', 'pg_ref' => '' ),
), true );
if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}
$after = get_post( $id );
foreach ( $before as $field => $value ) {
	if ( $after->$field !== $value ) {
		WP_CLI::error( 'Preservation check failed for ' . $field . '; retain the backup and investigate.' );
	}
}
if ( $after->post_parent !== $book_id || 'introduzione' !== $after->post_name || 'pg_commentario' !== $after->post_type || 0 !== (int) $after->menu_order ) {
	WP_CLI::error( 'Migration verification failed; retain the backup and investigate.' );
}
// The introduction was never published: no dated blog permalink ever existed.
// Keep the ID URL as an alias, activated by template_redirect only on publication.
$redirects = get_option( 'pg_commentario_redirects', array() );
$redirects[ '?p=' . $id ] = $id;
update_option( 'pg_commentario_redirects', $redirects, false );
WP_CLI::runcommand( 'rewrite flush' );
WP_CLI::runcommand( 'cache flush' );
WP_CLI::success( sprintf( 'Atti: %d. Introduction: %d, still draft; original ID, content, dates and comments preserved. Purge external caches separately.', $book_id, $id ) );
