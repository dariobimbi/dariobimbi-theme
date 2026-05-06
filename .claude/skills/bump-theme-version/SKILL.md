---
description: Bump the dariobimbi theme Version in style.css. Use after adding or renaming a pattern or template, or when forcing a re-run of admin reconciliation logic in inc/auto-pages.php and inc/sitemap.php.
---

## Instructions

The `Version:` line in `wp-content/themes/dariobimbi/style.css` is the canonical cache key for several things at once. Bump it whenever you've added or renamed a pattern or template, or when you want to force admin-side reconciliation to re-run.

### What the version bump invalidates

1. **WP's pattern cache.** Without a bump, a new `patterns/foo.php` exists on disk but `<!-- wp:pattern {"slug":"dariobimbi/foo"} /-->` renders as nothing. Symptom: header + empty `<main>` + footer.
2. **The enqueued stylesheet's `?ver=` query string** (browser cache-buster).
3. **`inc/auto-pages.php`** — re-walks `customTemplates` and provisions any missing demo/project Pages, side-loads project banners as Featured Images.
4. **`inc/sitemap.php`** — re-flushes rewrite rules so the custom `/sitemap.xml` keeps responding.

### How to bump

Read `style.css` (line 7 has `Version: X.Y.Z`). Increment:

- **Patch (Z)** for routine pattern adds, copy edits, single-file CSS tweaks.
- **Minor (Y)** for module-level changes — a new `inc/` file, a new schema type, a new theme.json design token, a new admin reconciliation step.
- **Major (X)** only for theme-wide redesigns or breaking layout changes.

Edit only that one line. Don't touch the rest of the header.

### After bumping

The next admin pageview at `http://localhost/dariobimbi.com/wp-admin/` fires the version-cached reconciliation automatically. Visitors get fresh CSS on their next page load (the `?ver=X.Y.Z` query string changes).

If the change must reflect immediately and no admin pageview is imminent, the user can hit any wp-admin URL once to trigger reconciliation.

### Don't

- Don't bump for backend-only changes that don't touch patterns, templates, or theme.json (e.g. tweaking a comment in `inc/seo.php`).
- Don't reset the version. Bumping is a one-way ratchet — if `inc/auto-pages.php` already ran for `0.7.7`, going back to `0.7.6` won't re-run it.
- Don't bump multiple steps at once unless the change genuinely warrants it. The version doubles as the public theme version visible in `wp-admin → Appearance → Themes`.
