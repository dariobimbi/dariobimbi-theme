# Color Token Consolidation — Design

**Date:** 2026-06-02
**Status:** Approved (design)
**Goal:** Make every site-chrome color originate from a design-token variable so a future palette change propagates cleanly, and organize the intentional one-off colors so they read as deliberate choices rather than misses. Triggered by the warm-palette (Dragon Pink) refresh leaving "disconnected items" behind.

## Background

`theme.json` defines the brand palette and most of the theme references it via `var(--wp--preset--color--<slug>)`. The recent cool→warm recolor exposed places where colors were hardcoded instead of token-referenced, so they never updated. This is a consolidation/refactor pass, not a visual redesign — the *intended* end state looks the same (warm), but stale leftovers get corrected and everything becomes recolor-safe.

Palette tokens (theme.json): `background #12100c`, `surface #1b1712`, `subtle #2a241c`, `foreground #efe9df`, `muted #a89f8f`, `primary #f43f5e`, `primary-soft #fda4af`, `accent #e11d48`, `accent-soft #fecdd3`, `hot #f43f5e`, `highlight #facc15`. Gradients: `brand`, `brand-soft`, `hot`, `surface-card`.

## Approved decisions

- **Scope:** Full coverage — site chrome, demos-hub preview art, and the self-contained demo pages all get addressed.
- **Brand art stays literal.** A future site recolor must NOT turn the Pokédex pink or the Game Boy screen green. Brand-accurate colors (Pokémon red/yellow, Game Boy LCD green, weather sky blue, game sprite palettes) remain literal values, marked with a `/* brand-fidelity — intentional, do not tokenize */` comment so they read as deliberate.
- **Decision A — translucency via `color-mix`.** Replace hand-written `rgba(244,63,94,0.14)`-style values that duplicate a token with `color-mix(in srgb, var(--wp--preset--color--<slug>) <pct>%, transparent)`. Single source of truth; no parallel RGB-triplet data to drift. This also retires the `--demo-accent-rgb` triplet pattern that stranded the old blue.
- **Decision B — semantic color layer.** Keep `theme.json` brand-only (editor color picker stays clean; recolor story stays in one file). Add a thin `:root{}` layer at the top of `style.css` for functional, non-brand colors. For site chrome this is currently just `--color-success` (the status-pill / contact-success green, `#22c55e`). Error states already use the brand pink, so no chrome `--color-danger` is introduced.

## Layered treatment

### Layer 1 — `theme.json`
- Core button element (`styles.elements.button`) currently hardcodes `#e11d48` (bg), `#ffffff` (text), `#f43f5e` (hover/focus). Route to `var(--wp--preset--color--accent)`, `var(--wp--preset--color--foreground)` (or `#fff` → foreground), and `var(--wp--preset--color--primary)` respectively.
- Palette and gradients otherwise unchanged. (The unused `hot` violet→pink gradient and the duplicate `hot`=`primary` color slug are pre-existing and out of scope — note but don't touch.)

### Layer 2 — Site chrome in `style.css`
Sections: utilities (`.eyebrow`, `.status-pill`, `.skill-pill*`), cards (`.dario-card`), contact form, header/branding, site-nav, hero, side-rail, projects hub/detail, 404, content pages.

- Every color that exactly duplicates a token routes back to the token.
- Translucent uses (`rgba(244,63,94,…)`=primary, `rgba(225,29,72,…)`=accent, `rgba(250,204,21,…)`=highlight, `#fda4af`=primary-soft, `#fde68a`≈highlight-soft) → `color-mix` against the token.
- **Stale leftovers corrected:** side-rail frosted glass `rgba(10,14,26,…)` (old cool navy) → warm equivalent derived from `background`/`surface`; `::selection { color:#fff }` → `foreground`.
- **New semantic token:** add `:root{ --color-success: #22c55e; }` (plus any soft/translucent derivations via `color-mix`); route status-pill (`#22c55e`, `rgba(34,197,94,…)`) and contact success (`rgba(34,197,94,…)`, text `#bbf7d0`) through it. Contact error stays on brand pink.
- Neutral overlays (`rgba(255,255,255,X)`, `rgba(0,0,0,X)`) used purely for elevation/scrim are NOT tokenized — they are lighting effects, not palette colors. Leave as-is.

### Layer 3 — Demos-hub preview art in `style.css` (~lines 1450–2400)
These mini CSS-art cards live on the demos hub (site chrome) but depict each demo's own look.

- **`.demo-card` accent system:** drop the retired `--blue` (`#3b82f6`) and `--purple` (`#a855f7`) variants and the stranded `--demo-accent-rgb: 59,130,246` default. Derive the default accent from the palette (`primary`/`primary-soft`); convert `rgba(var(--demo-accent-rgb),…)` usages to `color-mix` against `--demo-accent`. Keep `--pink`/`--yellow` (they already match palette) but source them from tokens.
- **Genuinely-neutral chrome** inside previews (old-navy `#0a0e1a`/`#050813` "dark UI" backgrounds that are just standing in for "the site dark") → route to `surface`/`background` tokens so they go warm.
- **Brand art stays literal + commented:** Game Boy LCD greens (`#9bbc0f`, `#8bac0f`, `#0f380f`), Pokémon red/yellow (`#ee1c25`, `#ffcb05`), pug-attack tans/sky, weather sky blues, one-dark code-editor syntax tokens, microgame green/red/orange actors. Each cluster gets a brand-fidelity comment.

### Layer 4 — Self-contained demo pages (`demo-*.php`)
Respect the single-file-demo convention (CLAUDE.md): demos must stay copy-paste portable, so they are NOT wired to global `var(--wp--preset--*)` tokens.

- Each demo gets a **scoped local-token block at the top of its own `<style>`** — the `--dcb-*` model `demo-card-browser.php` already uses. The demo's internal *chrome* (backgrounds, surfaces, text, borders, its own accent) routes to those locals; single-source within the file.
- **Canvas/sprite/game-art and brand palettes stay literal**, marked brand-fidelity. (Ant Buster's thousands of canvas hex values, Pokédex sprite tints, etc. are not tokenized.)
- **Stale old-navy chrome** (e.g. demo-contrast/weather/audio defaults using `#0a0e1a`, `#050813`, `#3b82f6`) gets a warm local-token default so it stops reading as disconnected — unless the cool look is intentional to that demo's subject, in which case it stays and is commented.
- `demo-card-browser.php` is already correct (Pokémon Center palette via `--dcb-*`); leave as the reference example.

## Verification

- After edits, `curl` the served pages and grep the inline `<script>`/`<style>` for HTML-entity mangling (`&#038;`) per CLAUDE.md's inline-script gotcha; `color-mix(in srgb, …)` contains no `&`/`<`/`>` so it is entity-safe, but verify anyway.
- Spot-check each touched page in a logged-out browser at desktop + mobile widths (Dario's bar: responsive correctness at all widths). Confirm nothing visually regressed and the warm palette is now uniform (no surviving cool-navy or blue/violet accents in chrome).
- `php -l` any edited PHP demo files.
- Bump theme `Version:` in `style.css` (touches patterns indirectly; also cache-busts the stylesheet).

## Out of scope

- No palette redesign — colors' *intended* values don't change, only their source.
- Neutral elevation/scrim overlays stay literal.
- The pre-existing unused `hot` gradient and duplicate `hot` slug are left alone.
- No new plugin or build step (theme-only, per project convention).

## Risk / non-obvious notes

- `style.css` is ~2,569 lines; the bulk of the work is mechanical find-and-route, but Layer 3's neutral-vs-brand judgement call per color is the part that needs care.
- The demo pages vary enormously in size (ant-buster ~7,600 lines). Layer 4 effort is dominated by *deciding* which colors are chrome vs. art, not by the edits. Game canvas art is the large majority and is explicitly left literal, which keeps the change bounded.
- `color-mix` percentages must be tuned to visually match the existing `rgba` alpha (alpha 0.14 ≠ mix 14% exactly for all colors against all backdrops, but is close; eyeball the high-visibility ones).
