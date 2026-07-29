<?php
/**
 * Activate the first-party Pergrazia theme after it has been assembled.
 */

return static function () {
	$theme = wp_get_theme( 'pergrazia' );

	if ( ! $theme->exists() ) {
		throw new RuntimeException( 'The Pergrazia theme is missing from the assembled WordPress tree.' );
	}

	if ( 'pergrazia' !== get_stylesheet() ) {
		switch_theme( 'pergrazia' );
	}
};
