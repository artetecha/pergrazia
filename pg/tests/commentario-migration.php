<?php
/** Exercise the real Upsun runner against a disposable local database. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'local' !== wp_get_environment_type() || get_post( 5126 ) ) {
	throw new RuntimeException( 'Requires a disposable local database without post 5126.' );
}
if ( ! class_exists( 'Upsun\Migrations' ) ) {
	require dirname( __DIR__ ) . '/composer-mu-plugins/upsun/src/Migrations.php';
}
$id = '20261112_0001_commentario';
$marker = 'upsun_migration_' . $id;
$directory = sys_get_temp_dir() . '/pg-migration-test-' . wp_generate_password( 8, false, false );
mkdir( $directory, 0700 );
copy( dirname( __DIR__ ) . '/migrations/' . $id . '.php', $directory . '/' . $id . '.php' );
$filter = static fn () => $directory;
add_filter( 'upsun_migrations_dir', $filter );
$backups = dirname( rtrim( ABSPATH, '/' ) ) . '/private/migration-backups/';
$before_backups = glob( $backups . '*.sql' ) ?: array();
$book_id = 0;
$checks = 0;
$assert = static function ( $test, string $message ) use ( &$checks ) {
	if ( ! $test ) { throw new RuntimeException( $message ); }
	++$checks;
};
try {
	$source = wp_insert_post( array(
		'import_id' => 5126, 'post_type' => 'post', 'post_status' => 'draft',
		'post_title' => 'Atti degli Apostoli — Introduzione',
		'post_content' => '<p>Contenuto originale.</p>', 'post_author' => 1,
		'post_date' => '2026-11-11 07:57:32', 'post_date_gmt' => '0000-00-00 00:00:00',
	), true );
	$assert( 5126 === $source, 'Migration fixture ID' );
	$comment = wp_insert_comment( array( 'comment_post_ID' => 5126, 'comment_content' => 'Da conservare', 'comment_approved' => 1 ) );
	$original = get_post( 5126 );
	$assert( '0000-00-00 00:00:00' === $original->post_date_gmt, 'Fixture reproduces a never-published draft with zero GMT date' );
	delete_option( $marker );
	$result = Upsun\Migrations::run();
	$assert( null === $result['error'], 'Migration succeeds: ' . ( $result['error'] ?? '' ) );
	$assert( array( $id ) === $result['applied'] && (bool) get_option( $marker ), 'Runner records successful migration' );
	$post = get_post( 5126 );
	$book_id = $post->post_parent;
	$assert( 'pg_commentario' === $post->post_type && 'draft' === $post->post_status && 'introduzione' === $post->post_name, 'Introduction is migrated and remains a draft' );
	foreach ( array( 'post_date', 'post_date_gmt', 'post_content', 'post_author', 'comment_count' ) as $field ) {
		$assert( $original->$field === $post->$field, 'Preserve ' . $field );
	}
	$assert( 5126 === (int) get_comment( $comment )->comment_post_ID, 'Original comment remains attached' );
	$assert( 'publish' === get_post_status( $book_id ) && 'atti' === get_post_field( 'post_name', $book_id ) && 44 === (int) get_post_meta( $book_id, 'pg_book', true ), 'Atti book created' );
	$new_backups = array_values( array_diff( glob( $backups . '*.sql' ), $before_backups ) );
	$assert( 1 === count( $new_backups ) && filesize( $new_backups[0] ) > 100, 'Automatic database export retained' );
	$assert( 0600 === ( fileperms( $new_backups[0] ) & 0777 ), 'Backup file is private' );
	$result = Upsun\Migrations::run();
	$assert( null === $result['error'] && array() === $result['applied'], 'Second deploy skips migration' );
	$assert( 1 === count( array_diff( glob( $backups . '*.sql' ), $before_backups ) ), 'Second deploy does not repeat backup' );
	WP_CLI::success( "$checks automatic migration assertions passed." );
} finally {
	wp_delete_post( 5126, true );
	if ( $book_id ) { wp_delete_post( $book_id, true ); }
	delete_option( $marker );
	delete_option( 'pg_commentario_redirects' );
	remove_filter( 'upsun_migrations_dir', $filter );
	unlink( $directory . '/' . $id . '.php' );
	rmdir( $directory );
	foreach ( array_diff( glob( $backups . '*.sql' ) ?: array(), $before_backups ) as $backup ) { unlink( $backup ); }
}
