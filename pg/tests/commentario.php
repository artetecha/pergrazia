<?php
/**
 * Integration checks against a disposable WordPress installation with this MU plugin/theme.
 * wp eval-file /project/tests/commentario.php
 * PG_COMMENTARIO_TEST_OUTPUT=/tmp/fixtures.json retains fixtures for HTTP/browser checks.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'These tests require a disposable local WordPress database.' );
}
wp_set_current_user( 1 );
$ids = array();
$checks = 0;
$assert = static function ( $condition, string $message ) use ( &$checks ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	++$checks;
};
$create = static function ( array $data ) use ( &$ids ) {
	$id = wp_insert_post( array_merge( array( 'post_type' => 'pg_commentario', 'post_status' => 'publish', 'post_content' => '<p>PGQASEARCH Contenuto di verifica.</p>' ), $data ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	$ids[] = $id;
	return $id;
};
$rest = static function ( string $method, string $path, array $data ) {
	$request = new WP_REST_Request( $method, '/wp/v2/pg_commentario' . $path );
	$request->set_body_params( $data );
	return rest_do_request( $request );
};
$render = static function ( int $id ): string {
	global $wp_query, $wp_the_query, $post;
	$previous = array( $wp_query, $wp_the_query, $post );
	$wp_query = new WP_Query( array( 'post_type' => 'pg_commentario', 'p' => $id ) );
	$wp_the_query = $wp_query;
	$wp_query->the_post();
	$html = apply_filters( 'the_content', get_the_content() );
	list( $wp_query, $wp_the_query, $post ) = $previous;
	wp_reset_postdata();
	return $html;
};
$keep = false;
try {
	$range = PG_Commentario::parse_reference( '1:1-14', 44 );
	$assert( array( 'start' => 44001001, 'end' => 44001014, 'slug' => '1-1-14' ) === $range, 'Same-chapter parser' );
	$range = PG_Commentario::parse_reference( '1:12–2:4', 44 );
	$assert( 44001012 === $range['start'] && 44002004 === $range['end'] && '1-12-2-4' === $range['slug'], 'Cross-chapter parser' );
	$assert( '2-1-1' === PG_Commentario::parse_reference( '2:1', 44 )['slug'], 'Single verse' );
	foreach ( array( 'bad', '1:14-1', '2:1-1:9', '0:1-2', '1:0-4', '1:1-14 extra', '1000:1-2' ) as $bad ) {
		$assert( is_wp_error( PG_Commentario::parse_reference( $bad, 44 ) ), 'Reject malformed reference: ' . $bad );
	}
	$book = $create( array( 'post_title' => 'Atti', 'post_name' => 'atti', 'meta_input' => array( 'pg_book' => 44 ) ) );
	$assert( 44 === (int) get_post_field( 'menu_order', $book ), 'Book canon order' );
	$assert( false === update_post_meta( $book, 'pg_book', -44 ) && 44 === (int) get_post_meta( $book, 'pg_book', true ), 'Negative book number is rejected, not silently made positive' );
	$intro = $create( array( 'post_title' => 'Introduzione pubblica di verifica', 'post_parent' => $book, 'meta_input' => array( 'pg_kind' => 'introduzione' ) ) );
	$assert( 0 === (int) get_post_field( 'menu_order', $intro ), 'Introduction order' );
	// Insert in reverse passage order to expose date-order regressions.
	$second = $create( array( 'post_title' => 'Il testimone scelto', 'post_parent' => $book, 'meta_input' => array( 'pg_kind' => 'puntata', 'pg_ref' => '1:15-26' ) ) );
	$excursus = $create( array( 'post_title' => 'Il regno', 'post_parent' => $book, 'meta_input' => array( 'pg_kind' => 'excursus', 'pg_ref' => '1:6-8' ) ) );
	$first = $create( array( 'post_title' => 'La promessa dello Spirito', 'post_parent' => $book, 'meta_input' => array( 'pg_kind' => 'puntata', 'pg_ref' => '1:1-14' ) ) );
	$assert( '1-1-14' === get_post_field( 'post_name', $first ), 'Requested slug' );
	$assert( 44001001 === (int) get_post_meta( $first, 'pg_ref_start', true ) && 44001014 === (int) get_post_meta( $first, 'pg_ref_end', true ), 'Derived bounds' );
	$assert( 44001001 === (int) get_post_field( 'menu_order', $first ), 'Native passage order' );
	$assert( home_url( '/commentari/atti/1-1-14/' ) === get_permalink( $first ), 'Hierarchical permalink without dated front' );
	$assert( 'excursus-il-regno' === get_post_field( 'post_name', $excursus ), 'Excursus slug' );
	$index = $render( $book );
	$assert( strpos( $index, get_the_title( $intro ) ) < strpos( $index, get_the_title( $first ) ) && strpos( $index, get_the_title( $first ) ) < strpos( $index, get_the_title( $second ) ), 'Automatic index passage order' );
	$assert( strpos( $index, get_the_title( $second ) ) < strpos( $index, get_the_title( $excursus ) ) && str_contains( $index, '1:1-14' ), 'Excursus separate group and visible references' );
	$nav = $render( $first );
	$assert( str_contains( $nav, get_permalink( $intro ) ) && str_contains( $nav, get_permalink( $second ) ) && ! str_contains( $nav, get_permalink( $excursus ) ), 'Sibling navigation excludes excursus' );
	$assert( ! str_contains( $render( $excursus ), 'Puntata precedente' ) && str_contains( $render( $excursus ), 'Indice del libro:' ), 'Excursus has index only' );
	$draft = $create( array( 'post_title' => 'Puntata riservata', 'post_parent' => $book, 'post_status' => 'draft', 'meta_input' => array( 'pg_kind' => 'puntata', 'pg_ref' => '2:1-4' ) ) );
	$assert( ! str_contains( $render( $book ), 'Puntata riservata' ), 'Draft excluded from index' );
	$assert( false === update_post_meta( $first, 'pg_ref', '1:broken' ), 'Direct invalid meta rejected' );
	$assert( false === update_post_meta( $first, 'pg_ref', '' ), 'Published passage cannot lose its reference' );
	$assert( false === delete_post_meta( $first, 'pg_ref' ), 'Required reference cannot be deleted' );
	$assert( false === delete_post_meta( $first, 'pg_kind' ), 'Published kind cannot be deleted' );
	$assert( false === delete_post_meta( $book, 'pg_book' ), 'Published book number cannot be deleted' );
	$assert( '1:1-14' === get_post_meta( $first, 'pg_ref', true ), 'Last valid reference retained' );
	$assert( false === update_post_meta( $first, 'pg_ref_start', 1 ), 'Derived metadata cannot be entered by hand' );
	$response = $rest( 'POST', '/' . $first, array( 'title' => 'Should not save', 'meta' => array( 'pg_ref' => '1:14-1' ) ) );
	$assert( 400 === $response->get_status() && 'La promessa dello Spirito' === get_post_field( 'post_title', $first ), 'Invalid REST request rejected atomically' );
	$response = $rest( 'POST', '/' . $first, array( 'meta' => array( 'pg_ref_start' => 7 ) ) );
	$assert( 400 === $response->get_status(), 'REST rejects derived metadata edits' );
	$response = $rest( 'POST', '', array( 'title' => 'Da un capitolo all’altro', 'status' => 'publish', 'parent' => $book, 'meta' => array( 'pg_book' => 0, 'pg_kind' => 'puntata', 'pg_ref' => '1:12-2:4' ) ) );
	$assert( 201 === $response->get_status(), 'REST first publication' );
	$cross = $response->get_data()['id'];
	$ids[] = $cross;
	$assert( '1-12-2-4' === get_post_field( 'post_name', $cross ) && 44002004 === (int) get_post_meta( $cross, 'pg_ref_end', true ), 'REST cross-chapter derived values' );
	$response = $rest( 'POST', '/' . $cross, array( 'meta' => array( 'pg_ref' => '2:1-4' ) ) );
	$assert( 200 === $response->get_status() && 44002001 === (int) get_post_field( 'menu_order', $cross ) && '1-12-2-4' === get_post_field( 'post_name', $cross ), 'Edits recalculate order but preserve permanent URL' );
	// A legacy panel submission must reject invalid metadata and produce a notice.
	$_POST = array( 'pg_commentario_nonce' => wp_create_nonce( 'pg_commentario' ), 'pg_book' => '', 'pg_kind' => 'puntata', 'pg_ref' => 'nonsense' );
	PG_Commentario::save_panel( $first, get_post( $first ) );
	$assert( '1:1-14' === get_post_meta( $first, 'pg_ref', true ) && (bool) get_transient( 'pg_commentario_notice_1' ), 'Invalid legacy panel retains reference and shows notice' );
	$_POST = array();
	// A rejected publication must not save the rejected metadata after reverting to draft.
	$unready = $create( array( 'post_title' => 'Bozza incompleta', 'post_status' => 'draft', 'post_parent' => $book ) );
	$_POST = array( 'pg_commentario_nonce' => wp_create_nonce( 'pg_commentario' ), 'pg_book' => '', 'pg_kind' => 'puntata', 'pg_ref' => '' );
	wp_update_post( array( 'ID' => $unready, 'post_status' => 'publish' ) );
	$assert( 'draft' === get_post_status( $unready ) && '' === get_post_meta( $unready, 'pg_kind', true ), 'Rejected legacy panel does not save metadata after status fallback' );
	$_POST = array();
	$other = $create( array( 'post_title' => 'Luca', 'post_name' => 'luca', 'meta_input' => array( 'pg_book' => 42 ) ) );
	wp_update_post( array( 'ID' => $cross, 'post_parent' => $other ) );
	$assert( 42002001 === (int) get_post_meta( $cross, 'pg_ref_start', true ), 'Reparenting recalculates book encoding' );
	update_post_meta( $other, 'pg_book', 43 );
	$assert( 43002001 === (int) get_post_meta( $cross, 'pg_ref_start', true ), 'Book correction recalculates children' );
	$collision = $create( array( 'post_title' => 'Duplicate range', 'post_parent' => $book, 'meta_input' => array( 'pg_kind' => 'puntata', 'pg_ref' => '1:1-14' ) ) );
	$assert( '1-1-14-2' === get_post_field( 'post_name', $collision ), 'Slug collisions are retained safely' );
	wp_delete_post( $collision, true );
	$ids = array_values( array_diff( $ids, array( $collision ) ) );
	// Ordinary blog control and never-published introduction for the migration rehearsal.
	$blog = $create( array( 'post_type' => 'post', 'post_title' => 'Articolo del blog PGQABLOG' ) );
	$blog_archive = '/' . get_post_time( 'Y/m', false, $blog ) . '/';
	$source = $create( array( 'post_type' => 'post', 'post_title' => 'Atti degli Apostoli — Introduzione', 'post_status' => 'draft', 'post_name' => '', 'post_date' => '2026-11-11 07:57:32', 'post_date_gmt' => '2026-11-11 06:57:32' ) );
	$comment = wp_insert_comment( array( 'comment_post_ID' => $source, 'comment_content' => 'Commento da preservare', 'comment_approved' => 1, 'comment_author' => 'QA' ) );
	$home = new WP_Query( array( 'posts_per_page' => -1 ) );
	$assert( in_array( $blog, wp_list_pluck( $home->posts, 'ID' ), true ) && ! in_array( $first, wp_list_pluck( $home->posts, 'ID' ), true ), 'Default homepage excludes commentary' );
	$search = new WP_Query( array( 's' => 'PGQASEARCH', 'posts_per_page' => -1 ) );
	$assert( in_array( $first, wp_list_pluck( $search->posts, 'ID' ), true ) && ! in_array( $draft, wp_list_pluck( $search->posts, 'ID' ), true ), 'Site search includes published commentary only' );
	WP_CLI::success( "$checks commentary assertions passed." );
	$output = getenv( 'PG_COMMENTARIO_TEST_OUTPUT' );
	if ( $output ) {
		file_put_contents( $output, wp_json_encode( compact( 'ids', 'book', 'intro', 'first', 'second', 'excursus', 'draft', 'other', 'cross', 'blog', 'blog_archive', 'source', 'comment' ), JSON_PRETTY_PRINT ) );
		$keep = true;
	}
} finally {
	$_POST = array();
	if ( ! $keep ) {
		foreach ( array_reverse( $ids ) as $id ) { wp_delete_post( $id, true ); }
	}
}
