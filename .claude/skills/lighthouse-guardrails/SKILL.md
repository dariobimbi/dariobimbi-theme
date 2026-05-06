---
description: Apply Lighthouse-aware checks when adding or changing images, headings, decorative DOM, security headers, or theme assets. Use when modifying any pattern that affects what loads on the homepage or any page Dario links from his portfolio.
---

## Instructions

This is a checklist-on-trigger skill, not an audit pass. When you're about to make one of the changes below, walk the corresponding subsection and surface anything that needs attention before writing the code. Most items are one-line edits; the value is catching them on the way in rather than during the next Lighthouse re-run.

The site's published Lighthouse target on prod is Performance ≥ 90, Accessibility 100, Best Practices ≥ 90, SEO 100. The biggest historical regressions have come from images, headings under decorative wrappers, and security-header drift.

### Trigger 1 — Adding or replacing an image in a pattern

If the new image is **above the fold** (hero, project banner, anything in the first viewport):

- `width=` and `height=` attrs are mandatory. Lighthouse CLS audit will fail without them.
- Add `fetchpriority="high"` and `loading="eager"`. WordPress core auto-adds `loading="lazy"` to images in `the_content` when no `loading` attribute is present, and that's exactly wrong for the LCP element. Writing `loading="eager"` explicitly opts out.
- Add `decoding="async"`.
- If the source PNG is over ~200 KB, generate a WebP variant and serve via `<picture>`. The pattern lives in `patterns/hero.php` — copy that shape.
- If the rendered display size varies meaningfully across viewports (true for any full-bleed image), add `srcset` with at least an 800w and 1600w variant plus `sizes="100vw"`. Generate the variants with a one-shot GD script using the same approach as `tools/render-banners.php`.

If the new image is **below the fold**:

- `width=` and `height=` still mandatory (CLS).
- `loading="lazy"` is fine; do **not** add `fetchpriority="high"`.
- WebP and srcset still recommended for anything over ~200 KB but less load-bearing.

For **all** images: alt text is required and should be descriptive, not "image of X". The OG fallback at `assets/banner.png` is intentionally kept large for social-card quality and is *not* served on the homepage anymore — the homepage uses `assets/banner-hero.{webp,jpg}` derivatives.

### Trigger 1b — Adding gradient-clipped text (`has-gradient-text` or similar)

Lighthouse 13's axe-core scores `color: transparent` as a 0:0 contrast fail, even when the visible glyph is painted by `background-clip: text` + `-webkit-text-fill-color: transparent`. Older axe versions ignored this; 13 does not.

The fix in `style.css` is to set `color:` to one of the gradient's endpoint colors (the `.has-gradient-text` rule uses `primary-soft` to match the brand-soft gradient's blue endpoint). axe inspects the computed `color` and scores ~7:1 against the dark navy background; the property never actually paints because `-webkit-text-fill-color: transparent` overrides it. If you author a new gradient-clipped text utility, give it a real fallback `color:` from the same family — never `transparent`.

The other surface this could reappear on: any inline `style="color:transparent"` on text, or any custom utility that mimics the gradient-clip pattern. Same fix applies.

### Trigger 2 — Adding a heading to a pattern

The homepage heading flow is fixed: hero `h1` → section `h2` (about, experience, projects, skills, contact) → card `h3` (individual experience entries, project cards, skill category labels). Sub-cards inside an `h3` card use `h4`, but the homepage doesn't currently have any.

Rules to enforce:

- Never skip a level. `h2 → h4` will fail Lighthouse's "Heading elements are not in a sequentially-descending order" audit. The skills pattern was an example of this exact bug — its category labels were `h4` directly under the `h2` "The toolbox.", with no `h3` between them.
- The page's outermost `h1` always lives in `patterns/hero.php`. Project pages and demo pages have their own `h1` in their respective patterns. There must be exactly one `h1` per page.
- A pattern that's a section of the homepage uses `h2` for its title, not `h1`.

### Trigger 3 — Adding decorative DOM (particles, code rain, hero mesh, animated background)

axe-core's color-contrast rule has a known quirk where it sometimes evaluates text inside an `aria-hidden="true"` ancestor anyway, especially when opacity inheritance is in play. The `.code-rain` element in `patterns/experience.php` was flagged for this reason and required defensive markup.

Apply belt-and-suspenders a11y attributes:

- `aria-hidden="true"` on the wrapper element.
- `role="presentation"` on the wrapper element.
- `aria-hidden="true"` on each child element that contains text (even glyphs like `</>` or `{ }`).
- The decorative content should not be focusable — no `tabindex`, no anchors.

If the decoration uses opacity-based fade (parent `opacity: 0.18` with children inheriting), be aware that axe may still calculate effective contrast against the background and flag elements even when the parent is aria-hidden. The fix above is what works.

### Trigger 4 — Adding or modifying response headers

`inc/security-headers.php` is the one place that emits security response headers. If you need to add a header:

- Add it to the existing `send_headers` callback in that file.
- Don't create a parallel hook elsewhere — competing `send_headers` callbacks make later debugging miserable.
- The CSP header is intentionally not sent. There's a long comment block at the bottom of `security-headers.php` listing every origin a working CSP would need to allowlist. If the change you're making adds a new third-party origin (a new analytics provider, a new font CDN, a new API the demos hit cross-origin), append to that comment block so the eventual CSP work has the up-to-date allowlist.
- HSTS is at `max-age=31536000; includeSubDomains` (1 year). This is sticky in browsers — adding a new HTTP-only subdomain for testing is no longer free. Use a different hostname or set up HTTPS first.

### Trigger 5 — Adding new CSS or JS to the theme

The theme has no build step (intentional — part of the "hand-built" positioning). Lighthouse will flag `dariobimbi/style.css` and `dariobimbi.js` for unminified content; this is accepted cost.

When adding to these files:

- Single-page demo CSS/JS belongs scoped in the demo's pattern PHP (`<style>` and `<script>` blocks), not in the theme's global files. See `patterns/demo-card-browser.php` for the canonical shape.
- Site-wide additions go in `style.css` or `assets/dariobimbi.js`. Both are enqueued from `functions.php`.
- Bump the theme `Version:` (use the `bump-theme-version` skill) — this also doubles as a cache-bust on the `?ver=` query string. Lighthouse runs against CDN edges that may have cached the old version, and a stale `style.css` can poison a re-test for hours.

Inline `<script>` blocks inside pattern PHP have a known WordPress content-filter gotcha: the `&&` operator gets HTML-encoded to `&#038;&#038;` inconsistently, breaking the JS parser. CLAUDE.md's "Inline `<script>` gotchas inside pattern PHP" section is the reference. This is a Lighthouse-adjacent concern because broken JS often manifests as silent failure, not an error Lighthouse can catch.

### Trigger 6 — Running a Lighthouse audit and reporting numbers

Lighthouse on this site has two large sources of noise that need filtering before believing the score:

1. **Logged-in audits inflate JS payload by ~1.5 MB.** Site Kit, the wp-admin toolbar, Dashicons, and hoverintent all load only for authenticated users. Bluehost's "Site Insights" feature inside wp-admin runs Lighthouse logged-in. **Always re-run in incognito** before drawing conclusions about Performance.
2. **Chrome extensions inflate TBT and main-thread time.** Grammarly, LastPass, Capital One Shopping, etc., show up as "long tasks" in the audit. Run in a clean profile or incognito with extensions disabled.

When reporting findings to the user, separate "real public-visitor issues" from "logged-in / extension noise" explicitly. Don't propose fixes for the noise.

### Accepted-cost audits — do not chase

These keep showing up in Best Practices and are not theme-fixable. Acknowledge them, don't propose fixes:

- **"Uses deprecated APIs" naming `SharedStorage`, `StorageType.persistent`, or `Fledge` in `main.js`.** That `main.js` is Bluehost-plugin's Newfold script (`fe6331af5207/main.js`), probing Privacy Sandbox APIs for the host's ad-tech detection. Third-party plugin code we can't patch. The audit will clear if Bluehost-plugin is ever deactivated, but CLAUDE.md's rule is "don't fight Bluehost-plugin or Jetpack from theme code."
- **"Ensure CSP is effective against XSS attacks."** Deferred until the report-only campaign at the bottom of `inc/security-headers.php` happens.
- **"Mitigate DOM-based XSS with Trusted Types."** Same deferral as CSP — Trusted Types needs a CSP directive to enforce.
- **"Use a strong HSTS policy."** Already at `max-age=31536000; includeSubDomains` in `inc/security-headers.php`. Lighthouse sometimes reads stale until the new max-age has been observed for a while; re-run later.
- **"Page prevented back/forward cache restoration"** with `Cache-Control: no-store`. The header comes from Bluehost-plugin, not the theme. Not fixable from theme code.

Combined, these keep Best Practices around 80–85 on prod. That ceiling is the cost of using a Bluehost host with their auto-installed plugin and our chosen CSP-deferral strategy. Don't propose theme-side workarounds.

## Don't

- Don't add WebP fallback to images smaller than ~200 KB. Below that threshold the markup overhead isn't worth it.
- Don't write a `Content-Security-Policy` header without doing the report-only campaign first (see comment at bottom of `inc/security-headers.php`). A broken CSP is worse than no CSP.
- Don't disable Bluehost-plugin or Jetpack from theme code. The host installs them; we work around their behavior, we don't fight it.
- Don't try to fix the bfcache-disabled issue from theme code. The `Cache-Control: no-store` header comes from a host-installed plugin and won't go away without disabling that plugin.
- Don't propose lazy-loading the hero image to "save bytes". It's the LCP element. Eager + fetchpriority high is correct.
