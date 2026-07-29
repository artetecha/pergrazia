<?php
/**
 * Search form.
 *
 * @package Pergrazia
 */
?>
<?php $pergrazia_search_id = wp_unique_id( 'search-field-' ); ?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $pergrazia_search_id ); ?>"><?php esc_html_e( 'Search for:', 'pergrazia' ); ?></label>
	<input id="<?php echo esc_attr( $pergrazia_search_id ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search…', 'pergrazia' ); ?>">
	<input type="submit" value="<?php esc_attr_e( 'Search', 'pergrazia' ); ?>">
</form>
