<?php
/**
 * Comments template.
 *
 * @package Pergrazia
 */

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="comments-area">
	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title">
			<?php
			$pergrazia_comment_count = get_comments_number();
			printf(
				/* translators: %s: number of comments. */
				esc_html( _n( '%s comment', '%s comments', $pergrazia_comment_count, 'pergrazia' ) ),
				esc_html( number_format_i18n( $pergrazia_comment_count ) )
			);
			?>
		</h2>
		<ol class="comment-list">
			<?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 48 ) ); ?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p><?php esc_html_e( 'Comments are closed.', 'pergrazia' ); ?></p>
	<?php endif; ?>

	<?php comment_form(); ?>
</section>
