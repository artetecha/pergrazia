# Pergrazia WordPress on Upsun

Pergrazia is a Composer-managed WordPress application based on the
`artetecha/wordpress-upsun-starter` architecture. WordPress core, the
first-party Pergrazia theme, plugins, translations, the `upsun-wp` MU plugin, and the
Redis object-cache drop-in are assembled into the ignored `wordpress/`
directory. Do not edit generated files there.

## Local installation

From this directory:

```bash
composer install
cp example.wp-config-local.php wp-config-local.php
```

Create a local MariaDB database, replace every placeholder in
`wp-config-local.php`, and generate unique WordPress salts. The local file is
gitignored and is loaded only outside Upsun. Install WordPress with your own
URL and administrator credentials:

```bash
wp --path=wordpress core install \
  --url=http://localhost:8080 \
  --title=Pergrazia \
  --admin_user='<admin-user>' \
  --admin_email='<admin-email>' \
  --prompt=admin_password
```

Serve `wordpress/` as the document root. Re-run `composer install` after
switching branches or when `composer.lock` changes.

## Composer-managed application tree

`composer install` and `composer update` both run the `postbuild` script. It:

- copies `wp-config.php` into WordPress;
- installs the project `mu-plugins/` files plus the staged `upsun-wp` plugin
  and loader;
- installs the Redis object-cache drop-in;
- copies the first-party `themes/pergrazia/` theme into WordPress; and
- removes WordPress's unmanaged bundled plugin and themes.

Add WordPress plugins and third-party themes to `composer.json`; do not install
or update them from wp-admin on Upsun. The Pergrazia theme is maintained in
`themes/pergrazia/`. Italian translations are downloaded during the Composer
build, and the theme ships its own Italian catalogue.

## Upsun runtime

The `pg` application uses PHP 8.4 and relationships named `database` and
`rediscache` for MariaDB and Redis. Uploads and WordPress cache files use
persistent storage mounts. `www.pergrazia.com` is canonical and the apex route
redirects to it.

`wp-config.php` obtains credentials and routes from Upsun, isolates Redis keys
by environment, disables dashboard file changes, and disables WordPress's
loopback cron. Local values belong only in `wp-config-local.php`.

## Deployment lifecycle

The deploy hook safely exits when WordPress has not been installed. On an
existing site it updates the WordPress database schema, applies pending
versioned migrations, enables the Redis drop-in, and runs due cron events.
The post-deploy hook runs `wp upsun sanitize --if-needed`, which stamps
production or applies preview-environment safeguards after a clone or sync.

No deployment hook installs WordPress, creates users, or changes administrator
credentials.

## First installation on a new Upsun project

After the first successful build, connect to the target environment and run an
explicit, credential-driven installation:

```bash
upsun ssh --environment='<environment>'
cd wordpress
wp core install \
  --url='https://www.example.com/' \
  --title='Pergrazia' \
  --admin_user='<admin-user>' \
  --admin_email='<admin-email>' \
  --prompt=admin_password
wp theme activate "$(jq -r '.extra.distro["default-theme"]' ../composer.json)"
jq -r '.extra.distro["enable-plugins"][]' ../composer.json | xargs wp plugin activate
```

Replace every placeholder and store the generated or entered password in the
project's credential manager. This procedure is only for a genuinely new,
empty database. Existing environments retain their current database and users.

## Migrations

Put ordered migration files in `migrations/` using this name format:

```text
YYYYMMDD_NNNN_short_name.php
```

Each file returns a callable. Throwing an exception or returning `false`
aborts deployment and leaves the migration pending:

```php
<?php

return static function () {
	update_option( 'some_option', 'value' );
};
```

Successful migrations are recorded in the database and are not repeated on
cloned environments. Use migrations for runtime state changes such as plugin
activation; Composer remains responsible for installing code.

## Cron, CI, and backups

Upsun runs due WordPress events every five minutes. The deploy hook also makes
a best-effort cron run, while `DISABLE_WP_CRON` prevents request-driven
loopbacks.

The CI workflow validates Composer metadata, installs exactly from
`composer.lock`, verifies the assembled WordPress tree, and lints project PHP
on PHP 8.4. The backup workflow runs daily (and manually), uses a pinned and
checksum-verified Upsun CLI, and backs up the `main` environment. It requires
the `UPSUN_PROJECT` and `UPSUN_CLI_TOKEN` repository secrets.
