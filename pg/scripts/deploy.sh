#!/usr/bin/env bash

set -euo pipefail

cd wordpress

if ! wp core is-installed; then
	echo "WordPress is not installed yet; see README.md for the explicit first-install procedure."
	exit 0
fi

wp core update-db

# Apply ordered, once-per-database project migrations before traffic switches.
wp upsun migrate

# Redis and individual cron failures must not make an otherwise valid deploy fail.
wp redis enable || true
wp cron event run --due-now || true
