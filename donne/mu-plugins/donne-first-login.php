<?php
/**
 * Plugin Name: Donne per la Grazia first login
 * Description: Sends an account's initial credentials and makes its owner choose a new password at the first login.
 */

defined( 'ABSPATH' ) || exit;

/**
 * User meta flag set by the deploy hook when it creates an account with a
 * generated password. Cleared once the owner sets a password of their own.
 */
const DONNE_MUST_CHANGE_PASSWORD = 'donne_must_change_password';

add_action(
	'muplugins_loaded',
	static function () {
		load_muplugin_textdomain( 'donne-site', 'languages' );
	}
);

/**
 * Whether the user still has to replace the generated password.
 */
function donne_must_change_password( WP_User $user ): bool {
	return $user->exists() && (bool) get_user_meta( $user->ID, DONNE_MUST_CHANGE_PASSWORD, true );
}

/**
 * Where to send a user who must change the password.
 */
function donne_password_change_url(): string {
	return admin_url( 'profile.php?donne-password=required#password' );
}

/**
 * Email the account's username, initial password and login link.
 */
function donne_send_initial_credentials( WP_User $user, string $password ): bool {
	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	/* translators: %s: site name. */
	$subject = sprintf( __( '[%s] Your account', 'donne-site' ), $site );

	$lines = array(
		/* translators: %s: user's display name. */
		sprintf( __( 'Hello %s,', 'donne-site' ), $user->display_name ),
		'',
		/* translators: %s: site name. */
		sprintf( __( 'Your administrator account on %s is ready.', 'donne-site' ), $site ),
		'',
		/* translators: %s: username. */
		sprintf( __( 'Username: %s', 'donne-site' ), $user->user_login ),
		/* translators: %s: temporary password. */
		sprintf( __( 'Temporary password: %s', 'donne-site' ), $password ),
		/* translators: %s: login URL. */
		sprintf( __( 'Log in: %s', 'donne-site' ), wp_login_url() ),
		'',
		__( 'At your first login you will be asked to choose a new password. The temporary one stops working as soon as you do.', 'donne-site' ),
	);

	return wp_mail( $user->user_email, $subject, implode( "\n", $lines ) );
}

/**
 * Send a flagged user straight to the password field after logging in.
 */
add_filter(
	'login_redirect',
	static function ( $redirect_to, $requested, $user ) {
		if ( $user instanceof WP_User && donne_must_change_password( $user ) ) {
			return donne_password_change_url();
		}

		return $redirect_to;
	},
	10,
	3
);

/**
 * Keep a flagged user on their profile until the password is changed.
 */
add_action(
	'admin_init',
	static function () {
		global $pagenow;

		if ( wp_doing_ajax() || 'profile.php' === $pagenow || ! donne_must_change_password( wp_get_current_user() ) ) {
			return;
		}

		wp_safe_redirect( donne_password_change_url() );
		exit;
	}
);

add_action(
	'admin_notices',
	static function () {
		global $pagenow;

		if ( 'profile.php' !== $pagenow || ! donne_must_change_password( wp_get_current_user() ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'Choose a new password.', 'donne-site' ),
			esc_html__( 'You are using a temporary password. Set a new password below and save your profile to continue.', 'donne-site' )
		);
	}
);

/**
 * Clear the flag once the stored password hash changes.
 */
add_action(
	'profile_update',
	static function ( $user_id, $old_user_data ) {
		$user = get_userdata( $user_id );

		if ( $user && $old_user_data instanceof WP_User && $user->user_pass !== $old_user_data->user_pass ) {
			delete_user_meta( $user_id, DONNE_MUST_CHANGE_PASSWORD );
		}
	},
	10,
	2
);

add_action(
	'after_password_reset',
	static function ( $user ) {
		delete_user_meta( $user->ID, DONNE_MUST_CHANGE_PASSWORD );
	}
);
