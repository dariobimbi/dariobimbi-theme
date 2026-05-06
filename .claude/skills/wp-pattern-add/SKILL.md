---
description: Add a new WordPress pattern + custom page template to the dariobimbi theme. Use when creating a new standard page (Privacy, Sitemap, project page, demo page, etc.) that needs both a pattern PHP file and a registered custom template.
---

## Instructions

Adding a new page-backed pattern is a multi-step ritual. Skipping any step leaves a half-broken page.

### Step 1 — Create the pattern at `patterns/<slug>.php`

Pattern files **are PHP-evaluated**. Use `home_url()`, `get_stylesheet_directory_uri()`, conditionals, queries as needed. Required header + standard wrapper:

```php
<?php
/**
 * Title: <Human-readable title>
 * Slug: dariobimbi/<slug>
 * Categories: dariobimbi
 * Description: <one-sentence description>
 */
?>
<!-- wp:group {"align":"full","className":"content-page <page-class>","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page <page-class>" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">
  ...content blocks...
</div>
<!-- /wp:group -->
```

Conventional intro: `<p class="eyebrow">small label</p>` followed by `<h1 class="wp-block-heading has-gradient-text">Title</h1>`. See `patterns/demo-contrast.php` or `patterns/project-lost-probe.php` for the canonical shape.

### Step 2 — Create the thin template at `templates/page-<slug>.html`

Templates are **NOT PHP-evaluated** — anything dynamic must live in the pattern. The template just pulls the pattern in:

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
  <!-- wp:pattern {"slug":"dariobimbi/<slug>"} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

### Step 3 — Register in `theme.json` under `customTemplates`

```json
{ "name": "page-<slug>", "title": "Page — <Title>", "postTypes": [ "page" ] }
```

Without this, WP will not offer the template in the page editor's Template dropdown.

### Step 4 — Bump the theme `Version:` in `style.css`

**Critical.** WP caches the pattern list keyed by the theme Version. Without a bump, the new pattern exists on disk but `<!-- wp:pattern -->` renders as empty. Symptom: header + empty `<main>` + footer.

Use the `bump-theme-version` skill, or edit by hand.

### Step 5 — Admin: create the Page

In wp-admin: **Pages → Add New**, set the slug, in the right sidebar's **Template** dropdown pick "Page — <Title>", save. The page body stays empty; content lives in the pattern. (For demo and project pages, `inc/auto-pages.php` provisions the Page automatically after the version bump.)

### Variants

**Demo pages** live at `/demos/<slug>/`. Need a parent Page with slug `demos` (empty body). Template name: `page-demo-<slug>`. Demos conventionally bundle their own scoped `<style>` and `<script>` inside the pattern so the whole thing is one copyable file.

**Project pages** live at `/projects/<slug>/`. Parent Page slug `projects`. Template name: `page-project-<slug>`. Drop a 1200×630 `banner.png` in `assets/projects/<slug>/`. Optionally a wider `banner-hero.png` next to it. The `inc/auto-pages.php` reconciliation will side-load `banner.png` as Featured Image after the version bump.

**Game projects** need one extra step: append the slug to `DARIOBIMBI_GAME_PROJECTS` in `inc/seo.php`. That switches the JSON-LD from generic `CreativeWork` to `VideoGame` for the project page.

### Inline `<script>` gotchas

If the pattern has an inline `<script>` (single-file demo style):

- **Avoid `&&`** — WP's content filters HTML-encode it inconsistently to `&#038;&#038;`, which throws `Uncaught SyntaxError`. Use nested `if`, `indexOf` checks (`['a','b'].indexOf(x) === -1`), or helper variables. Verify by curling the served page and grepping for `&#038;&#038;`.
- **Don't rely on `var` data tables being value-hoisted.** Move data tables to the top of the IIFE, or guard reads with `typeof MAP !== 'undefined'`.
- Use `function foo()` declarations (hoisted) over `var foo = function () {}` (not hoisted) for shared helpers.

### Voice check

If the pattern contains user-facing copy, run the `copy-voice-check` skill before committing.
