# dariobimbi.com theme

The WordPress block theme behind [dariobimbi.com](https://dariobimbi.com), my portfolio site. I wrote it from scratch, with no page builder, no JavaScript framework, no jQuery, no external fonts and no build step, so the code is part of what the site shows.

## How it's put together

- `theme.json` (version 3) is the design system. Colors, type sizes and spacing are named tokens, and the templates use those instead of raw values.
- `templates/*.html` are thin shells. WordPress doesn't run PHP inside block templates, so anything that needs a query or dynamic data lives in a pattern under `patterns/` that the template calls.
- `inc/` holds the pieces most sites install plugins for: SEO meta and JSON-LD (`seo.php`), the XML sitemap, robots.txt, the contact form, security headers and the favicon set.
- `inc/auto-pages.php` creates the Pages for each demo and project from what's on disk, because creating them by hand is how the live site ended up with the wrong slug more than once.
- `inc/demos.php` is the one list of demo categories that both the demos hub and the header menu read. They used to keep separate lists, and those drifted apart.
- `inc/justtcg-proxy.php` is a REST proxy for the Card Browser. The JustTCG API key lives in `wp-config.php` and never reaches the browser, and responses are cached to stay under the free tier's rate limit.

## The demos

Each demo is one PHP pattern with its own scoped CSS and JavaScript, so any of them can be copied out on its own as a starting point. They're all live on [dariobimbi.com](https://dariobimbi.com).

| Demo | What it does |
| --- | --- |
| Card Browser | Live Pokémon card and sealed product prices from JustTCG, English and Japanese sets, through the server-side proxy |
| Pokédex | All 1,025 Pokémon by generation, with every TCG card for the one you pick, plus sorting, filters and search |
| Prompt Studio | Builds a prompt from structured fields or audits an existing one; drafts stay in the browser |
| Sorting race | Four sorting algorithms on one shuffled array, each written as a step generator that yields after every comparison so the canvas can draw between steps |
| Contrast checker | WCAG contrast ratios for any foreground and background pair |
| Conway's Life | A toroidal grid (gliders wrap around), click or drag to seed, with the classic patterns as presets |
| Web Audio | An oscillator or your microphone through a filter chain |
| Weather | A real API fetch chain, with city and state or country disambiguation |
| Live editor | A two-pane CSS editor with a hand-built syntax highlighter and a live preview isolated in a Shadow DOM, no CodeMirror or Prism |
| Ant Buster | Tower defense: drag a cannon onto the picnic blanket and defend the cake |
| Pug Attack | A Processing sketch from a DePaul level design class, ported by hand to vanilla JavaScript and Canvas |
| Microgame | A maze game from an intro class at DePaul, ported the same way |

## Running it

Copy the folder into `wp-content/themes/`, activate it, and load any admin page once so `auto-pages.php` can create the demo and project Pages. It needs WordPress 6.4 or later and PHP 7.4 or later.

The Card Browser needs `define( 'JUSTTCG_API_KEY', 'your-key' );` in `wp-config.php` and shows a notice until it's set. `composer.json` only pulls in WordPress stubs so the editor can type-check; nothing from it ships.

## How I work on it

The `docs/` folder holds the specs and plans I write with Claude Code before building a feature, and `.claude/` holds this project's own Claude Code skills and agents, like the schema validator and the Lighthouse guardrails.

## License

GPL v2 or later, the same as WordPress.
