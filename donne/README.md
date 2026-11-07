# Donne per la Grazia

Source for `donne.pergrazia.com`, the women's journal of the Per Grazia family.
It is a separate WordPress installation; this directory holds its theme.

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

## Setting up the installation

The theme is not yet wired into a build. When the second installation is
created, mirror `pg/`: a Composer project with `roots/wordpress`, a `postbuild`
step that copies `themes/donne-per-la-grazia` into
`wordpress/wp-content/themes/`, and either a second application in
`.upsun/config.yaml` with its own database and a `https://donne.{default}/`
route, or a separate Upsun project. Then:

```bash
wp theme activate donne-per-la-grazia
wp option update posts_per_page 7
```

Menus are edited in the Site Editor (*Navigation*); until one exists, the
header and footer list published pages.
