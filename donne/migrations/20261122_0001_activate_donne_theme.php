<?php
/**
 * Activate the Donne per la Grazia theme and size the blog page for its
 * layout: one featured article plus two rows of three.
 */

return static function () {
	$theme = wp_get_theme( 'donne-per-la-grazia' );

	if ( ! $theme->exists() ) {
		throw new RuntimeException( 'The Donne per la Grazia theme is missing from the assembled WordPress tree.' );
	}

	if ( 'donne-per-la-grazia' !== get_stylesheet() ) {
		switch_theme( 'donne-per-la-grazia' );
	}

	update_option( 'posts_per_page', 7 );
};
