<?php
/**
 * Print this application's canonical URL from PLATFORM_ROUTES, using the
 * same preference as wp-config.php: a www route, then HTTPS.
 */

$routes = json_decode( base64_decode( (string) getenv( 'PLATFORM_ROUTES' ) ), true );
$app    = (string) getenv( 'PLATFORM_APPLICATION_NAME' );
$best   = '';
$score  = -1;

foreach ( is_array( $routes ) ? $routes : array() as $url => $route ) {
	if (
		'upstream' !== ( $route['type'] ?? '' )
		|| ! in_array( $route['upstream'] ?? '', array( $app, $app . ':http' ), true )
	) {
		continue;
	}

	$host   = (string) parse_url( $url, PHP_URL_HOST );
	$points = ( str_starts_with( $host, 'www.' ) ? 2 : 0 )
		+ ( 'https' === parse_url( $url, PHP_URL_SCHEME ) ? 1 : 0 );

	if ( $host && $points > $score ) {
		$best  = rtrim( $url, '/' );
		$score = $points;
	}
}

if ( '' === $best ) {
	fwrite( STDERR, "No upstream route found for application {$app}.\n" );
	exit( 1 );
}

echo $best;
