# Hero refresh + warm Dragon-Pink palette migration

**Date:** 2026-06-01
**Status:** Approved 2026-06-01 (two CTAs, mesh dialed down, lead reworded to drop the "I build" echo)
**Identity:** Warm, hand-built craftsperson. Promote the site's own Dragon Pink to the
lead brand color, warm the whole canvas, and kill the blue → violet gradient axis that
was the actual "AI-built" tell. Keep the playful demo colors exactly as they are.

## Why

The thing that read as AI-generated was never that the site had color. It was the
specific electric-blue → violet gradient, the gradient-text headline, and the cool
monitor-blue neutrals — the 2024–2026 SaaS/generative-UI default. Dario's warmth and his
playful pops (Dragon Pink, Sticky Yellow) were never the problem. So we keep them, warm
the room, and retire the gradient.

## Scope and blast radius

Surgical. We touch the **cool axis only** plus a temperature nudge on the neutrals.
Nothing structural breaks because we **migrate slug values, never delete slugs**.

- `theme.json` — palette retune, button element repaint, brand gradients flattened.
- `style.css` — one `.hero-stage h1` rule (mono headline). Everything else recolors
  automatically through the preset vars.
- `patterns/hero.php` — targeted edits (headline text/class, CTA links). **Not** a rewrite.
- `style.css` theme `Version:` bump (cache-buster for the enqueued stylesheet).

**Site-wide effects to be aware of (intended, no extra work):**
- Every `.has-gradient-text` headline recolors to near-solid pink (it's driven by
  `gradient--brand-soft`, style.css:58). Coherent — pink headlines site-wide.
- The `.hero-mesh` blobs recolor to pink (they pull `primary` / `accent`, style.css:508/514).
- Links recolor to soft pink, brightening on hover (they already reference `primary-soft`
  / `accent-soft` in `theme.json`).

## 1. theme.json — palette (migrate values, keep slug names)

| Slug | From | To | Name |
|---|---|---|---|
| `background` | `#0a0e1a` | `#12100c` | Warm Near-Black |
| `surface` | `#11172a` | `#1b1712` | Warm Slate Surface |
| `subtle` | `#1a2138` | `#2a241c` | Warm Border |
| `foreground` | `#e8ecf6` | `#efe9df` | Warm Off-White |
| `muted` | `#94a3b8` | `#a89f8f` | Warm Gray |
| `primary` | `#3b82f6` | `#f43f5e` | Dragon Pink |
| `primary-soft` | `#60a5fa` | `#fda4af` | Dragon Pink — Soft (link resting) |
| `accent` | `#a855f7` | `#e11d48` | Deep Pink |
| `accent-soft` | `#c084fc` | `#fecdd3` | Dragon Pink — Bright (link hover) |

**Left untouched:** `hot` (`#f43f5e`), `highlight` (`#facc15`) — these art-direct the
demos (Ant Buster, Sort Race, Contrast); changing them buys zero de-templating.

Note: `primary` now equals `hot` exactly. Intentional — the whole site is Dragon-Pink-led
now, demos included, so the brand and the demo pop are one warm family.

## 2. theme.json — gradients (flatten to pink tone-on-tone)

```json
{ "slug": "brand",      "name": "Dragon Pink",      "gradient": "linear-gradient(135deg, #f43f5e 0%, #e11d48 100%)" },
{ "slug": "brand-soft", "name": "Dragon Pink Soft", "gradient": "linear-gradient(135deg, #fda4af 0%, #fecdd3 100%)" }
```
Leave `hot` and `surface-card` gradients alone.

## 3. theme.json — button element (THE catch)

The button is hardcoded blue and is not tied to `primary`, so the palette migration alone
leaves the primary CTA electric blue. Repaint it. White-on-`#f43f5e` is only ~3.9:1, so the
background is the deeper `#e11d48` (passes AA for bold button text), brightening on hover.

```jsonc
"button": {
  "color":  { "background": "#e11d48", "text": "#ffffff" },
  ":hover": { "color": { "background": "#f43f5e" } },
  ":focus": { "color": { "background": "#f43f5e" } }
}
```
(Leave radius, padding, typography as-is.)

## 4. style.css — hero headline

Drop the gradient on the hero H1 (it inherits warm off-white `foreground`) and set the
heavy monospace at `xx-large`, not `display`.

```css
.hero-stage h1 {
    font-family: var(--wp--preset--font-family--mono);
    font-size: var(--wp--preset--font-size--xx-large);
    letter-spacing: -0.02em; /* monospace runs wide; pull it in a hair */
}
```
Mono headline is scoped to the hero for now (low blast radius). Rolling it to all section
headings is a possible follow-up after we see it live.

Also dial the `.hero-mesh` blobs down from `opacity: 0.55` to `0.3` (style.css:504) so the
now-pink glow reads as ambient warm light rather than a marketing asset.

## 5. patterns/hero.php — targeted edits only

Keep the `<picture>` banner (LCP, `loading="eager"`, `fetchpriority="high"`), the
`.hero-mesh`, the eyebrow, and the status pill. Change these things:

1. **Headline:** `Build. Design.<br>Solve. Improve.` → `I build things for the web that actually work.`
   and remove the `has-gradient-text` class from the H1 (the `.hero-stage h1` rule styles it).
2. **Lead reword:** change only `I build custom WordPress themes` → `My focus is custom
   WordPress themes` so the H1's "I build" and the lead's opening don't stutter. Rest of the
   lead is unchanged.
3. **Both CTAs replaced (two buttons total — confirmed).** The current `#projects` "View
   projects →" and `#contact` "Get in touch" buttons become:
   ```php
   <?php $ant  = get_page_by_path( 'demos/ant-buster' ); ?>
   <?php $self = get_page_by_path( 'projects/dariobimbi-com' ); ?>
   <?php if ( $ant ) : ?>
     <a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( get_permalink( $ant ) ); ?>">Play Ant Buster</a>
   <?php endif; ?>
   <?php if ( $self ) : ?>
     <a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( get_permalink( $self ) ); ?>">See how this site is built</a>
   <?php endif; ?>
   ```
   No third "Get in touch" button — contact stays reachable via the nav and footer.

## Copy (final — to pass copy-voice-check before ship)

- **H1:** "I build things for the web that actually work."
- **Lead (one word changed):** "I'm Dario Bimbi, a web developer at Clicks Web Design. My
  focus is custom WordPress themes, plugins, and web apps, and after about ten years of
  doing this work I have a pretty clear idea of what makes a site good and what makes a
  site just look good."

## Verification (localhost, logged out, incognito)

1. Homepage hero: banner image present and is the LCP; headline is warm off-white mono;
   "Play Ant Buster" button is pink and links to `/demos/ant-buster/`; "See how this site
   is built" links to `/projects/dariobimbi-com/`.
2. Links: soft pink at rest, brighter on hover (not duller).
3. No electric blue or violet anywhere on the page.
4. Spot-check two demos (Ant Buster, Sort Race): `hot`/`highlight` unchanged, art intact.
5. Confirm no empty `href`s (both `get_page_by_path` calls resolve).

## Out of scope (later, if we want)

- Direction 2's live interactive hero (playable demo embedded in the hero).
- Global mono headings beyond the hero.
- Any light-mode / editorial pivot.
