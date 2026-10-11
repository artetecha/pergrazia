<?php
/**
 * Plugin Name: Pergrazia newsletter signup guard
 * Description: Stops bots subscribing third-party addresses through the Mailchimp for WordPress form, and turns off XML-RPC.
 */

defined( 'ABSPATH' ) || exit;

/**
 * XML-RPC is blocked at the router too (.upsun/config.yaml); this keeps it
 * closed if that rule is ever loosened.
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Successful signups allowed per client IP per day. A real reader subscribes
 * once; the October 2026 flood sent 25 from a single IP.
 */
const PERGRAZIA_SIGNUPS_PER_IP_PER_DAY = 3;

/**
 * Cloudflare Turnstile keys, set as Upsun variables `env:TURNSTILE_SITE_KEY`
 * and `env:TURNSTILE_SECRET_KEY` (sensitive). Without both, the challenge is
 * skipped and only the per-IP limit applies.
 *
 * @return array{site: string, secret: string}|null
 */
function pergrazia_turnstile_keys() {
	$site   = (string) getenv( 'TURNSTILE_SITE_KEY' );
	$secret = (string) getenv( 'TURNSTILE_SECRET_KEY' );

	return ( '' !== $site && '' !== $secret ) ? array( 'site' => $site, 'secret' => $secret ) : null;
}

function pergrazia_signup_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
}

/**
 * Render the Turnstile widget after the form fields. "interaction-only" keeps
 * it invisible unless Cloudflare wants a human to click, so the layout holds.
 */
add_filter( 'mc4wp_form_after_fields', function ( $html ) {
	$keys = pergrazia_turnstile_keys();
	if ( null === $keys ) {
		return $html;
	}

	wp_enqueue_script(
		'cloudflare-turnstile',
		'https://challenges.cloudflare.com/turnstile/v0/api.js',
		array(),
		null,
		array(
			'in_footer' => true,
			'strategy'  => 'async',
		)
	);

	return $html . sprintf(
		'<div class="cf-turnstile" data-sitekey="%s" data-appearance="interaction-only" style="margin-top:1rem"></div>',
		esc_attr( $keys['site'] )
	);
} );

/**
 * Validate after MC4WP's own honeypot/timestamp checks. Error keys are
 * namespaced under `spam.` so the form shows its generic error message.
 */
add_filter( 'mc4wp_form_errors', function ( array $errors ) {
	if ( ! empty( $errors ) ) {
		return $errors;
	}

	$ip = pergrazia_signup_ip();

	$keys = pergrazia_turnstile_keys();
	if ( null !== $keys ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- MC4WP forms carry no nonce; Turnstile is the check.
		$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
		if ( '' === $token ) {
			return array( 'spam.turnstile' );
		}

		$response = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			array(
				'timeout' => 5,
				'body'    => array(
					'secret'   => $keys['secret'],
					'response' => $token,
					'remoteip' => $ip,
				),
			)
		);

		// Fail open if Cloudflare is unreachable: the per-IP limit still applies.
		if ( is_wp_error( $response ) ) {
			error_log( 'pergrazia-signup-guard: Turnstile siteverify failed: ' . $response->get_error_message() );
		} else {
			$result = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( empty( $result['success'] ) ) {
				return array( 'spam.turnstile' );
			}
		}
	}

	if ( '' !== $ip ) {
		$key   = 'pergrazia_signups_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= PERGRAZIA_SIGNUPS_PER_IP_PER_DAY ) {
			return array( 'spam.rate_limit' );
		}
		set_transient( $key, $count + 1, DAY_IN_SECONDS );
	}

	return $errors;
}, 100 );
