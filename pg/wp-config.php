<?php
/**
 * Upsun-aware WordPress configuration. Composer copies this file into
 * wordpress/ during each build. Off Upsun, configuration comes from the
 * gitignored wp-config-local.php in the project root.
 */

use Platformsh\ConfigReader\Config;

require __DIR__ . '/../vendor/autoload.php';

$config = new Config();

$site_host   = 'localhost';
$site_scheme = 'http';

if ( isset( $_SERVER['HTTP_HOST'] ) ) {
	$site_host = $_SERVER['HTTP_HOST'];
}

$forwarded_proto = isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] )
	? strtolower( trim( explode( ',', $_SERVER['HTTP_X_FORWARDED_PROTO'] )[0] ) )
	: '';

if (
	( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( (string) $_SERVER['HTTPS'] ) )
	|| 'https' === $forwarded_proto
) {
	$site_scheme      = 'https';
	$_SERVER['HTTPS'] = 'on';
}

if ( $config->isValidPlatform() ) {
	if ( $config->hasRelationship( 'database' ) ) {
		$database = $config->credentials( 'database' );

		define( 'DB_NAME', $database['path'] );
		define( 'DB_USER', $database['username'] );
		define( 'DB_PASSWORD', $database['password'] );
		define( 'DB_HOST', $database['host'] . ':' . $database['port'] );
		define( 'DB_CHARSET', 'utf8mb4' );
		define( 'DB_COLLATE', '' );
	}

	if ( $config->routes() ) {
		$application_upstreams = array(
			$config->applicationName,
			$config->applicationName . ':http',
		);
		$selected_route_score  = -1;

		foreach ( $config->routes() as $url => $route ) {
			if (
				'upstream' !== ( $route['type'] ?? '' )
				|| ! in_array( $route['upstream'] ?? '', $application_upstreams, true )
			) {
				continue;
			}

			$route_host   = parse_url( $url, PHP_URL_HOST );
			$route_scheme = parse_url( $url, PHP_URL_SCHEME ) ?: 'http';

			if ( ! $route_host ) {
				continue;
			}

			// Prefer the canonical www route, then HTTPS, regardless of route order.
			$route_score = ( str_starts_with( $route_host, 'www.' ) ? 2 : 0 )
				+ ( 'https' === $route_scheme ? 1 : 0 );

			if ( $route_score > $selected_route_score ) {
				$site_host            = $route_host;
				$site_scheme          = $route_scheme;
				$selected_route_score = $route_score;
			}
		}
	}

	// Derive independent, deterministic values from Upsun's project entropy.
	if ( $config->projectEntropy ) {
		foreach ( array(
			'AUTH_KEY',
			'SECURE_AUTH_KEY',
			'LOGGED_IN_KEY',
			'NONCE_KEY',
			'AUTH_SALT',
			'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT',
			'NONCE_SALT',
		) as $key ) {
			if ( ! defined( $key ) ) {
				define( $key, hash( 'sha256', $config->projectEntropy . $key ) );
			}
		}
	}

	if ( $config->hasRelationship( 'rediscache' ) ) {
		$redis = $config->credentials( 'rediscache' );

		define( 'WP_REDIS_CLIENT', 'phpredis' );
		define( 'WP_REDIS_HOST', $redis['host'] );
		define( 'WP_REDIS_PORT', $redis['port'] );
		if ( ! empty( $redis['password'] ) ) {
			define( 'WP_REDIS_PASSWORD', $redis['password'] );
		}

		define( 'WP_REDIS_PREFIX', 'wp:' . $config->environment . ':' );
		define( 'WP_CACHE_KEY_SALT', WP_REDIS_PREFIX );
		define( 'WP_REDIS_SELECTIVE_FLUSH', true );
		define( 'WP_REDIS_GRACEFUL', true );
		define( 'WP_REDIS_IGBINARY', true );
		define( 'WP_REDIS_TIMEOUT', 0.5 );
		define( 'WP_REDIS_READ_TIMEOUT', 0.5 );
		define( 'WP_REDIS_DISABLE_METRICS', true );
		define( 'WP_REDIS_DISABLE_ADMINBAR', true );
		define( 'WP_REDIS_DISABLE_BANNERS', true );
		define( 'WP_REDIS_DISABLE_COMMENT', true );
		define( 'WP_REDIS_DISABLE_DROPIN_CHECK', true );
		define( 'WP_REDIS_DISABLE_DROPIN_AUTOUPDATE', true );
	}

	if ( ! defined( 'WP_DEBUG' ) ) {
		define( 'WP_DEBUG', false );
	}

	if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) ) {
		$wp_environment_type = match ( $config->environmentType ) {
			'production' => 'production',
			'staging'    => 'staging',
			default      => 'development',
		};
		define( 'WP_ENVIRONMENT_TYPE', $wp_environment_type );
	}

	// Composer owns the read-only application tree on Upsun.
	if ( ! defined( 'DISALLOW_FILE_MODS' ) ) {
		define( 'DISALLOW_FILE_MODS', true );
	}
} elseif ( file_exists( dirname( __FILE__, 2 ) . '/wp-config-local.php' ) ) {
	include dirname( __FILE__, 2 ) . '/wp-config-local.php';
}

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

if ( ! defined( 'DB_CHARSET' ) ) {
	define( 'DB_CHARSET', 'utf8mb4' );
}

if ( ! defined( 'DB_COLLATE' ) ) {
	define( 'DB_COLLATE', '' );
}

if ( ! defined( 'WP_HOME' ) ) {
	define( 'WP_HOME', $site_scheme . '://' . $site_host );
}

if ( ! defined( 'WP_SITEURL' ) ) {
	define( 'WP_SITEURL', WP_HOME );
}

define( 'WP_CONTENT_DIR', __DIR__ . '/wp-content' );
define( 'WP_CONTENT_URL', WP_HOME . '/wp-content' );

if ( ! defined( 'WP_TEMP_DIR' ) ) {
	define( 'WP_TEMP_DIR', sys_get_temp_dir() );
}

if ( ! defined( 'FS_METHOD' ) ) {
	define( 'FS_METHOD', 'direct' );
}

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

// Upsun invokes due events every five minutes; disable loopback cron.
if ( ! defined( 'DISABLE_WP_CRON' ) ) {
	define( 'DISABLE_WP_CRON', true );
}

if ( ! defined( 'UPSUN_MIGRATIONS_DIR' ) ) {
	define( 'UPSUN_MIGRATIONS_DIR', dirname( __DIR__ ) . '/migrations' );
}

$table_prefix = 'wp_';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
