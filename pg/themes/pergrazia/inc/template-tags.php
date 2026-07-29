<?php
/**
 * Reusable presentation helpers.
 *
 * @package Pergrazia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print a compact post byline.
 */
function pergrazia_post_meta( bool $include_reading_time = true ): void {
	printf(
		'<span class="posted-on"><span class="screen-reader-text">%1$s </span><a href="%2$s" rel="bookmark"><time datetime="%3$s">%4$s</time></a></span>',
		esc_html__( 'Published on', 'pergrazia' ),
		esc_url( get_permalink() ),
		esc_attr( get_the_date( DATE_W3C ) ),
		esc_html( get_the_date() )
	);

	printf(
		'<span class="byline"><span class="screen-reader-text">%1$s </span><a href="%2$s">%3$s</a></span>',
		esc_html__( 'By', 'pergrazia' ),
		esc_url( get_author_posts_url( (int) get_the_author_meta( 'ID' ) ) ),
		esc_html( get_the_author() )
	);

	if ( $include_reading_time ) {
		$content = wp_strip_all_tags( (string) get_the_content() );
		$words   = preg_split( '/\s+/u', trim( $content ), -1, PREG_SPLIT_NO_EMPTY );
		$minutes = max( 1, (int) ceil( count( is_array( $words ) ? $words : array() ) / 220 ) );

		printf(
			'<span class="reading-time">%s</span>',
			esc_html(
				sprintf(
					/* translators: %s: estimated number of minutes. */
					_n( '%s minute read', '%s minutes read', $minutes, 'pergrazia' ),
					number_format_i18n( $minutes )
				)
			)
		);
	}
}

/**
 * Print the first category for visual hierarchy.
 */
function pergrazia_primary_category(): void {
	$categories = get_the_category();

	if ( empty( $categories ) ) {
		return;
	}

	printf(
		'<p class="entry-kicker"><a href="%1$s">%2$s</a></p>',
		esc_url( get_category_link( $categories[0]->term_id ) ),
		esc_html( $categories[0]->name )
	);
}
