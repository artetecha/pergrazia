#!/usr/bin/env bash

set -euo pipefail

cd wordpress

if ! wp core is-installed; then
	# First deploy on an empty schema: install unattended. The web installer
	# is blocked at the router. The administrator's email is personal data and
	# this repository is public, so it comes from env:DONNE_ADMIN_EMAIL.
	admin_user="${DONNE_ADMIN_USER:-manuela}"

	if [ -z "${DONNE_ADMIN_EMAIL:-}" ]; then
		echo "WordPress is not installed and env:DONNE_ADMIN_EMAIL is not set; skipping install."
		exit 0
	fi

	site_url="$(php ../scripts/site-url.php)"
	admin_password="$(php -r '$c = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789"; $p = ""; for ($i = 0; $i < 20; $i++) { $p .= $c[random_int(0, strlen($c) - 1)]; } echo $p;')"

	wp core install \
		--url="$site_url" \
		--title='Donne per la Grazia' \
		--admin_user="$admin_user" \
		--admin_email="$DONNE_ADMIN_EMAIL" \
		--admin_password="$admin_password" \
		--locale=it_IT \
		--skip-email

	wp site switch-language it_IT
	wp option update timezone_string 'Europe/Rome'
	wp rewrite structure '/%postname%/'
	# shellcheck disable=SC2046
	wp plugin activate $(php -r '$d = json_decode(file_get_contents("../composer.json"), true); echo implode(" ", $d["extra"]["distro"]["enable-plugins"]);')
	wp theme activate "$(php -r '$d = json_decode(file_get_contents("../composer.json"), true); echo $d["extra"]["distro"]["default-theme"];')"

	# The generated password is temporary: mu-plugins/donne-first-login.php
	# makes the owner replace it at the first login.
	wp user meta update "$admin_user" donne_must_change_password 1

	echo "Installed WordPress at ${site_url}."
	echo "Administrator: ${admin_user}"
	echo "Temporary password: ${admin_password}"

	# Email the credentials from production only, so a preview's install
	# never reaches the administrator's inbox.
	if [ "${PLATFORM_ENVIRONMENT_TYPE:-}" = "production" ]; then
		if DONNE_ADMIN_LOGIN="$admin_user" DONNE_INITIAL_PASSWORD="$admin_password" wp eval '
			$user = get_user_by( "login", getenv( "DONNE_ADMIN_LOGIN" ) );
			exit( $user && donne_send_initial_credentials( $user, (string) getenv( "DONNE_INITIAL_PASSWORD" ) ) ? 0 : 1 );
		'; then
			echo "Credentials emailed to the administrator."
		else
			echo "Could not email the credentials; use the password above."
		fi
	fi
	unset admin_password
fi

wp core update-db

# Apply ordered, once-per-database project migrations before traffic switches.
wp upsun migrate

# Redis and individual cron failures must not make an otherwise valid deploy fail.
wp redis enable || true
wp cron event run --due-now || true
