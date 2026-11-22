#!/usr/bin/env bash

set -euo pipefail

cd wordpress

if ! wp core is-installed; then
	# First deploy on an empty schema: install unattended. The administrator
	# comes from Upsun variables (see README.md); without them, skip and leave
	# the site uninstalled. The web installer is blocked at the router.
	if [ -z "${DONNE_ADMIN_USER:-}" ] || [ -z "${DONNE_ADMIN_EMAIL:-}" ]; then
		echo "WordPress is not installed and env:DONNE_ADMIN_USER / env:DONNE_ADMIN_EMAIL are not set; skipping install."
		exit 0
	fi

	site_url="$(php ../scripts/site-url.php)"
	admin_password="${DONNE_ADMIN_PASSWORD:-$(php -r 'echo bin2hex(random_bytes(24));')}"

	wp core install \
		--url="$site_url" \
		--title='Donne per la Grazia' \
		--admin_user="$DONNE_ADMIN_USER" \
		--admin_email="$DONNE_ADMIN_EMAIL" \
		--admin_password="$admin_password" \
		--locale=it_IT \
		--skip-email
	unset admin_password

	wp site switch-language it_IT
	wp option update timezone_string 'Europe/Rome'
	wp rewrite structure '/%postname%/'
	# shellcheck disable=SC2046
	wp plugin activate $(php -r '$d = json_decode(file_get_contents("../composer.json"), true); echo implode(" ", $d["extra"]["distro"]["enable-plugins"]);')
	wp theme activate "$(php -r '$d = json_decode(file_get_contents("../composer.json"), true); echo $d["extra"]["distro"]["default-theme"];')"
	echo "Installed WordPress at ${site_url} for ${DONNE_ADMIN_USER}."
fi

wp core update-db

# Apply ordered, once-per-database project migrations before traffic switches.
wp upsun migrate

# Redis and individual cron failures must not make an otherwise valid deploy fail.
wp redis enable || true
wp cron event run --due-now || true
