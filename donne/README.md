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
3. Set the administrator's email as a project variable before the first
   deploy (or redeploy afterwards). It is kept out of git because this
   repository is public:

   ```bash
   upsun variable:create --level=project --name=env:DONNE_ADMIN_EMAIL --value='<email>'
   ```

   The username defaults to `manuela`; `env:DONNE_ADMIN_USER` overrides it.

On a deploy where WordPress is not installed, `scripts/deploy.sh`:

- installs it at the app's route (picked by `scripts/site-url.php`), in
  Italian, with the Rome time zone and `/%postname%/` permalinks;
- activates the plugins and the theme listed under `extra.distro` in
  `composer.json`;
- generates a random 20-character password and prints it in the deploy log
  (Upsun console → the environment's activity);
- on the production environment only, emails the username, password and login
  link to the administrator;
- flags the account so that `mu-plugins/donne-first-login.php` sends it to the
  profile page, and keeps it there, until a new password is saved.

Without `env:DONNE_ADMIN_EMAIL` the install is skipped and the deploy still
succeeds. The web installer (`install.php`, `setup-config.php`) is blocked at
the router, so an empty site cannot be claimed from a browser. Preview
environments clone `main`'s data; before `main` has a Donne site, a preview
installs its own and prints, but does not email, its password.

## Localisation

Everything is written in English and translated:

- theme strings, pattern titles and `theme.json` names (colours, sizes,
  families, templates, style variations) in
  `themes/donne-per-la-grazia/languages/` (text domain `donne-per-la-grazia`);
- the first-login notice and email in `mu-plugins/languages/` (text domain
  `donne-site`);
- WordPress core and plugins in Italian through `wp-translation-downloader`.

Each catalogue ships `.pot`, `it_IT.po`, `.mo` and `.l10n.php`. After changing
a string, regenerate them, e.g. with `wp i18n make-pot`, `make-mo` and
`make-php`.

## Favicon

Until a Site Icon is chosen in *Impostazioni → Generali*, the theme serves the
arch mark: `favicon.svg` (with a dark-mode palette), a 32 px PNG and a 180 px
`apple-touch-icon.png`, on the site, the login page and the dashboard.

Menus are edited in the Site Editor (*Navigation*); until one exists, the
header and footer list published pages.
