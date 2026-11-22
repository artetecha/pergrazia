# Donne per la Grazia

Source for `donne.pergrazia.com`, the women's journal of the Per Grazia family.
It is a separate WordPress installation, deployed as the `donne` application
in the same Upsun project as `www.pergrazia.com`.

## Theme: `themes/donne-per-la-grazia`

A block theme. There is no PHP templating: layout and styling are declared in
files that the Site Editor reads and can override.

| Path | Role |
| --- | --- |
| `theme.json` | Palette, type scale, spacing, layout widths, element and block styles |
| `templates/*.html` | Home, single, page, archive, search, 404, plus *Page without title* and *Wide page* |
| `parts/*.html` | Header and footer; each loads a pattern so its strings are translatable |
| `patterns/*.php` | Sections and content blocks; hidden ones feed the templates |
| `styles/*.json` | Global style variations: *Sera* (dark) and *Salvia* (sage) |
| `styles/blocks`, `styles/sections` | Block style variations: Arch image, Card, Scripture quote, Eyebrow, Blush and Ink sections |
| `style.css` | Theme header and the few rules `theme.json` cannot express |
| `functions.php` | Text domain, stylesheet, pattern category; nothing else |
| `languages/` | `.pot`, Italian `.po`, `.mo` and `.l10n.php` |

Design notes:

- The motif is an arched doorway: the open access "into this grace wherein we
  stand" (Rom 5:2). It appears in the hero artwork, the mark beside the site
  title and the *Arch* style on featured images.
- The palette is warm (ivory, blush, pomegranate) and keeps the Per Grazia
  teal as `accent-2` to signal the family link. A thin bar above the header
  links back to `www.pergrazia.com`, and the home page closes with a banner
  pointing to Per Grazia and its newsletter.
- Headings use Fraunces (SIL OFL, bundled under `assets/fonts/fraunces`, no
  third-party requests). Body text uses the same serif stack as Per Grazia.
- On the first blog page the newest article spans the grid as a feature;
  later pages are a plain grid. The query inherits from *Settings → Reading*;
  7 posts per page fills the first page exactly (one feature plus two rows).

Editorial patterns in the inserter, under *Donne per la Grazia*: home hero,
Per Grazia banner, Scripture card (Eph 2:8, Riveduta 1924) and Questions for
reflection.

## The `donne` application

`donne/` is a Composer project built like `pg/`: `roots/wordpress`, the
`upsun-wp` MU plugin, Akismet and Redis Object Cache, Italian translations,
and a `postbuild` step that copies this theme into
`wordpress/wp-content/themes/`. Do not edit the generated `wordpress/` tree.

On Upsun it runs as a second application in the Per Grazia project
(`.upsun/config.yaml`):

- **Route:** `https://donne.{default}/`, i.e. `donne.pergrazia.com` in
  production. `www.{default}` stays the primary route.
- **Database:** schema `donne` on the shared `pgdb` MariaDB service, through
  its own `donne` endpoint. The pg app keeps schema `main` via `mysql`.
- **Redis:** shared service (8.8); keys are prefixed `wp:donne:<environment>:`.
- **Salts:** derived from the project entropy plus the application name, so
  they differ from the pg app's.
- **Uploads:** a `storage` mount (`uploads`); cache files on `instance`.
- **Deploy hooks:** install WordPress on the first deploy (see below), then
  behave like pg's.
- **Migrations:** `migrations/20261122_0001_activate_donne_theme.php`
  activates the theme and sets seven posts per page.

### Going live

1. Deploy to a preview environment first and check that the pg site still
   reaches its data (the MariaDB service now declares its schemas explicitly).
2. Add the domain to the project and point DNS at it:

   ```bash
   upsun domain:add donne.pergrazia.com
   ```

   then create a `CNAME donne.pergrazia.com → <main environment's target>`
   at the DNS provider (`upsun domain:get donne.pergrazia.com` shows it).
3. Set the administrator as project variables, then redeploy if the app
   already deployed without them (they are read only while WordPress is
   not yet installed):

   ```bash
   upsun variable:create --level=project --name=env:DONNE_ADMIN_USER --value='<admin-user>'
   upsun variable:create --level=project --name=env:DONNE_ADMIN_EMAIL --value='<admin-email>'
   upsun variable:create --level=project --name=env:DONNE_ADMIN_PASSWORD --sensitive=true --value='<password>'
   ```

   The password is optional. Without it the deploy sets a random one that is
   never shown; use *Password dimenticata?* on the login page, or
   `upsun ssh --app=donne -- wp --path=wordpress user reset-password <admin-user>`.

On a deploy where WordPress is not installed, `scripts/deploy.sh` installs it
at the app's route (picked by `scripts/site-url.php`), in Italian with the
Rome time zone and `/%postname%/` permalinks, activates the plugins and the
theme listed under `extra.distro` in `composer.json`, then runs the
migrations as usual. Without the two required variables it skips the install
and the deploy still succeeds. The web installer (`install.php`,
`setup-config.php`) is blocked at the router, so an empty site cannot be
claimed from the browser.

Preview environments clone `main`'s data. Before `main` has a Donne site, a
preview with the variables set installs its own.

Menus are edited in the Site Editor (*Navigation*); until one exists, the
header and footer list published pages.
