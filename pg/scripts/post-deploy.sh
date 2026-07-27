#!/usr/bin/env bash

set -euo pipefail

cd wordpress

if ! wp core is-installed; then
	echo "WordPress is not installed yet; skipping post-deploy tasks."
	exit 0
fi

# Stamp production or sanitize a preview after a data clone/sync when needed.
wp upsun sanitize --if-needed
