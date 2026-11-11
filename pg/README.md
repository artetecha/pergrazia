# Pergrazia WordPress on Upsun

Pergrazia is a Composer-managed WordPress application based on the
`artetecha/wordpress-upsun-starter` architecture. WordPress core, the
first-party Pergrazia theme, plugins, translations, the `upsun-wp` MU plugin, and the
Redis object-cache drop-in are assembled into the ignored `wordpress/`
directory. Do not edit generated files there.

WordPress core comes from `roots/wordpress`, which installs the official
WordPress archive without bundled themes or plugins through
`roots/wordpress-core-installer`. The lockfile installs WordPress 7.1.2; the
Composer constraint permits subsequent patches in the 7.1 series. The
standalone Pergrazia theme is source-controlled and has no third-party parent
theme.

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

When migrating a local installation from the John P. Bloch packages, use a
fresh checkout and install from the lockfile. Composer removes the old core
package's installation directory, which can also remove nested plugin, theme,
and upload files. Keep the old checkout and any local configuration or uploads
until the new checkout is ready. Upsun builds already start with a fresh
application tree; persistent uploads are mounted at deployment.

## Composer-managed application tree

`composer install` and `composer update` both run the `postbuild` script. It:

- requires `wordpress/wp-includes/version.php` so an incomplete core archive
  fails the build;
- copies `wp-config.php` into WordPress;
- installs the project `mu-plugins/` files plus the staged `upsun-wp` plugin
  and loader;
- installs the Redis object-cache drop-in; and
- creates the themes directory and copies the first-party `themes/pergrazia/`
  theme into WordPress.

Core supplies no bundled themes or plugins. Composer installs third-party
plugins explicitly; no third-party theme is required by this site.

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

## Commentario

`mu-plugins/pg-commentario.php` registers the hierarchical `pg_commentario`
post type. Its book archive is `/commentario/`; a book such as Atti lives at
`/commentario/atti/`. The first-party theme has dedicated archive and single
PHP templates, without dates or chronological article navigation. Search
results use commentary cards without dates. The theme version is bumped when
its CSS changes so browser caches receive the new styles.

In **Commentario → Aggiungi**, use the **Libro e brano** panel:

- For a book, leave the parent empty, select **Libro**, and enter its Protestant
  canon number (1–66; Atti = 44). Book entries sort by this number.
- For an introduction, select the book as parent, choose **Introduzione**, and
  leave the book number and reference empty.
- For a puntata, select the book and **Puntata**, then enter a continuous range
  such as `1:1-14` or `1:12-2:4`. A single verse such as `2:1` is accepted too.
- For an excursus, select the book and **Excursus**. Its reference is optional;
  a new empty slug becomes `excursus-` followed by the title slug.

The reference parser validates syntax, positive chapter/verse numbers and
range order; it does not contain a verse-count database for every biblical
book. Derived `pg_ref_start` and `pg_ref_end` metadata use BBCCCVVV, and
`menu_order` follows the start reference. Existing slugs are preserved when
references change. WordPress slug collisions produce an editorial warning,
not an unsafe override of its uniqueness checks. Invalid REST requests return
an Italian error before the post is changed; an invalid legacy panel leaves
its previous valid metadata in place and displays an admin notice.

The book index and previous/next links use published entries only. Excursuses
have a separate index group and never enter the previous/next chain. The main
blog, date archives and `/feed/` exclude commentary; public site search and
`/commentario/feed/` include published commentary. Drafts remain private,
including the introduction while it awaits publication.

### Explicit introduction migration

The production introduction is **ID 5126**, titled **Atti degli Apostoli —
Introduzione**. It has **never been published**. Keep it as a draft: there is
no historical dated permalink to redirect. Its existing `?p=5126` alias is
recorded in `pg_commentario_redirects`, but a 301 activates only if the entry
is subsequently published. Draft URLs and previews are not redirected.

The migration is intentionally outside `migrations/` and is **not run by
any deployment hook**. Rehearse on local/staging first. Get explicit approval
before production deployment/database changes, then take both an Upsun backup
and a fresh database export. From the deployed application root (`/app`):

```bash
wp --path=wordpress db export /tmp/commentario-before.sql
wp --path=wordpress eval-file scripts/migrate-commentario.php 5126 /tmp/commentario-before.sql
```

The command above refuses production. **Only after explicit production
approval**, append `production-approved` to the migration command. The SQL
export must exist, be readable and nonempty, and be less than an hour old.
Store backups securely outside the web root and retain them for rollback.

The script creates the published Atti book (`pg_book=44`) if absent, converts
the introduction in place, and verifies that its ID, draft status, title,
content, author, original dates, and comment count/status remain unchanged.
It sets parent Atti, slug `introduzione`, kind `introduzione`, and order 0;
repeated runs reuse the book. It runs `wp rewrite flush` and `wp cache flush`.

After approval and migration, purge Cloudflare through the deployed Upsun MU
plugin (`wp --path=wordpress upsun cloudflare purge --all`, with configured
purge credentials). The Upsun router has no purge API; invalidate its cached
responses by redeploying or wait for their TTL, then verify fresh responses.
Check `/commentario/` and `/commentario/atti/`. The introduction should still
be absent from the public index, search and **both feeds**. Its pretty URL
should not expose the draft. After a future publication, verify its pretty
URL, commentary-feed inclusion, and the 301 from `?p=5126`.

### Commentary integration checks

Use a **disposable local database** (`WP_ENVIRONMENT_TYPE=local`), admin user
ID 1, this theme, this MU plugin, and a date-based permalink structure. The
suite creates real posts and exercises normal saves, the legacy panel, REST
validation, passage sorting, slug collisions, hierarchy changes, and content
navigation. It removes its fixtures by default:

```bash
wp --path=wordpress eval-file tests/commentario.php
```

For HTTP checks, run the local site and retain fixtures temporarily:

```bash
PG_COMMENTARIO_TEST_OUTPUT=/tmp/commentario-fixtures.json \
  wp --path=wordpress eval-file tests/commentario.php
python3 tests/commentario-http.py http://localhost:8080 /tmp/commentario-fixtures.json
```

Do not run the fixture suite against an existing Atti book. Rehearse the
migration with the generated `source` ID and a fresh local backup, verify
that its original comment and draft status survive, and test a 301 with a
separate published fixture. Delete the IDs in the fixture JSON after HTTP
checks, or discard the disposable database. Never publish post 5126 as part
of acceptance testing.
