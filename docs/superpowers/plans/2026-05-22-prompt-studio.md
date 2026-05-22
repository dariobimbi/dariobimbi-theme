# Prompt Studio Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a fully client-side, single-file demo at `/demos/prompt-studio/` that lets visitors author, audit, and learn from prompts for text LLMs, image-generation models, and review/critique workflows. Three prompt types × three modes (Build / Lint / Library), shared draft state per type, deep-linkable views, no API calls, no new `inc/` modules.

**Architecture:** One WordPress pattern file (`patterns/demo-prompt-studio.php`) carries all markup, scoped CSS, and a single IIFE that owns a state object plus an idempotent hash-based router (`#<type>/<mode>?t=<id>`). Three view containers (`#view-build`, `#view-lint`, `#view-library`) toggle visibility via CSS. Drafts persist to `localStorage` debounced ~200ms; view state is intentionally ephemeral. localStorage writes never touch DOM inputs to preserve focus and caret. Inline-script discipline forbids logical `&&` (WordPress entity-encodes it inside `<script>`); nested `if` is the substitute.

**Tech Stack:** Plain PHP + JavaScript inside a WordPress block pattern. No build step, no test framework. Manual incognito-browser QA, `php -l` syntax checks, and `curl | grep '&#038;&#038;'` validation on every JS-touching commit.

**Reference spec:** [docs/superpowers/specs/2026-05-22-prompt-studio-design.md](../specs/2026-05-22-prompt-studio-design.md) (commit `1004835`).

---

## Files

- Modify: `theme.json` — append `page-demo-prompt-studio` to `customTemplates`
- Modify: `style.css` line 7 — bump theme Version (`0.17.0` → `0.18.0`)
- Create: `templates/page-demo-prompt-studio.html` — thin shell invoking the pattern
- Create: `patterns/demo-prompt-studio.php` — all markup + scoped CSS + IIFE with state, router, three views, lint engine, 27 templates

No `inc/` changes. Admin page provisioning is automatic via `inc/auto-pages.php` once theme version is bumped and admin is visited.

---

## Phase 1 — Scaffolding, routing, full-bleed wrapper

Goal: page exists at `/demos/prompt-studio/`, type chips and mode tabs are interactive, URL hash reflects state, back-button works, body gets the full-bleed background class. No content yet.

---

### Task 1: Register the page (theme.json + template shell + version bump)

**Files:**
- Modify: `theme.json`
- Modify: `style.css` (line 7)
- Create: `templates/page-demo-prompt-studio.html`

- [ ] **Step 1: Read current theme.json customTemplates to find insertion point**

Run:
```
grep -n "page-demo" theme.json
```

Note the line number of the last `page-demo-*` customTemplate entry. The new entry appends to that array.

- [ ] **Step 2: Append `page-demo-prompt-studio` to customTemplates in `theme.json`**

Insert after the existing last `page-demo-*` entry inside the `customTemplates` array:

```json
{
  "name": "page-demo-prompt-studio",
  "title": "Demo: Prompt Studio",
  "postTypes": ["page"]
}
```

Mind the comma on the entry above it.

- [ ] **Step 3: Bump theme Version in `style.css`**

Open `style.css`. Find the `Version:` header (line ~7). Bump it one minor: `0.17.0` → `0.18.0`. Save.

- [ ] **Step 4: Create the thin template shell**

Create `templates/page-demo-prompt-studio.html` with exactly this content:

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
<!-- wp:pattern {"slug":"dariobimbi/demo-prompt-studio"} /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

- [ ] **Step 5: Syntax-check theme.json**

Run:
```
c:/wamp64/bin/php/php8.2.29/php.exe -r "json_decode(file_get_contents('theme.json')); echo json_last_error_msg();"
```

Expected output: `No error`

- [ ] **Step 6: Commit**

```bash
git add theme.json style.css templates/page-demo-prompt-studio.html
git commit -m "feat(prompt-studio): register page template and bump theme version"
```

---

### Task 2: Pattern PHP skeleton with view containers and scoped CSS reset

**Files:**
- Create: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Create the pattern file with header, page-shell wrapper, and view containers**

Create `patterns/demo-prompt-studio.php`:

```php
<?php
/**
 * Title: Demo: Prompt Studio
 * Slug: dariobimbi/demo-prompt-studio
 * Categories: dariobimbi
 * Description: Single-file client-side prompt builder, linter, and library demo.
 * Inserter: no
 */
?>
<!-- wp:group {"align":"full","className":"content-page prompt-studio","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page prompt-studio" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

<p class="eyebrow">demo</p>
<h1 class="has-gradient-text">Prompt Studio</h1>
<p class="lede">Build a prompt from structured fields, audit one you already have, or borrow a template. Three prompt types: writing, image generation, and review. No API calls, no sign-in. Your drafts stay in this browser.</p>

<div class="ps-toolbar">
  <div class="ps-type-bar" role="tablist" aria-label="Prompt type">
    <button type="button" class="ps-type-chip is-active" role="tab" data-type="write" aria-selected="true">Write</button>
    <button type="button" class="ps-type-chip" role="tab" data-type="image" aria-selected="false">Image</button>
    <button type="button" class="ps-type-chip" role="tab" data-type="review" aria-selected="false">Review</button>
  </div>
  <div class="ps-mode-bar" role="tablist" aria-label="Mode">
    <button type="button" class="ps-mode-tab is-active" role="tab" data-mode="build" aria-selected="true">Build</button>
    <button type="button" class="ps-mode-tab" role="tab" data-mode="lint" aria-selected="false">Lint</button>
    <button type="button" class="ps-mode-tab" role="tab" data-mode="library" aria-selected="false">Library</button>
  </div>
</div>

<section id="view-build" class="ps-view" aria-label="Build">
  <p class="ps-stub">Build view goes here.</p>
</section>
<section id="view-lint" class="ps-view" aria-label="Lint" hidden>
  <p class="ps-stub">Lint view goes here.</p>
</section>
<section id="view-library" class="ps-view" aria-label="Library" hidden>
  <p class="ps-stub">Library view goes here.</p>
</section>

</div>
<!-- /wp:group -->

<style>
body.has-prompt-studio-page { background-color: var(--wp--preset--color--surface) !important; }

.prompt-studio { color: var(--wp--preset--color--foreground); }
.prompt-studio .eyebrow { text-transform: uppercase; letter-spacing: 0.18em; font-size: var(--wp--preset--font-size--small); color: var(--wp--preset--color--muted); margin: 0 0 var(--wp--preset--spacing--10); }
.prompt-studio .lede { color: var(--wp--preset--color--muted); max-width: 60ch; margin-top: var(--wp--preset--spacing--20); }

.prompt-studio .ps-toolbar { display: flex; flex-direction: column; gap: var(--wp--preset--spacing--20); margin: var(--wp--preset--spacing--40) 0 var(--wp--preset--spacing--50); }
.prompt-studio .ps-type-bar, .prompt-studio .ps-mode-bar { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.prompt-studio .ps-type-chip, .prompt-studio .ps-mode-tab { appearance: none; border: 1px solid var(--wp--preset--color--subtle); background: var(--wp--preset--color--surface); color: var(--wp--preset--color--foreground); padding: 0.55rem 1rem; border-radius: 999px; font: inherit; cursor: pointer; }
.prompt-studio .ps-type-chip:hover, .prompt-studio .ps-mode-tab:hover { border-color: var(--wp--preset--color--primary); }
.prompt-studio .ps-type-chip.is-active[data-type="write"] { background: var(--wp--preset--color--primary); color: var(--wp--preset--color--background); border-color: var(--wp--preset--color--primary); }
.prompt-studio .ps-type-chip.is-active[data-type="image"] { background: var(--wp--preset--color--accent); color: var(--wp--preset--color--background); border-color: var(--wp--preset--color--accent); }
.prompt-studio .ps-type-chip.is-active[data-type="review"] { background: var(--wp--preset--color--hot); color: var(--wp--preset--color--background); border-color: var(--wp--preset--color--hot); }
.prompt-studio .ps-mode-tab.is-active { background: var(--wp--preset--color--foreground); color: var(--wp--preset--color--background); border-color: var(--wp--preset--color--foreground); }

.prompt-studio .ps-view { background: var(--wp--preset--color--surface); border: 1px solid var(--wp--preset--color--subtle); border-radius: 18px; padding: var(--wp--preset--spacing--40); }
.prompt-studio .ps-view[hidden] { display: none; }
.prompt-studio .ps-stub { color: var(--wp--preset--color--muted); font-style: italic; margin: 0; }

@media (max-width: 480px) {
  .prompt-studio .ps-type-chip { flex: 1 1 auto; text-align: center; }
}
</style>

<script>
(function () {
  'use strict';
  // Phase 1 placeholder. Router and state arrive in Task 3.
  document.body.classList.add('has-prompt-studio-page');
})();
</script>
```

- [ ] **Step 2: Syntax-check the new file**

Run:
```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
```

Expected: `No syntax errors detected in patterns/demo-prompt-studio.php`

- [ ] **Step 3: Hit the WP admin to trigger auto-pages reconciliation**

In a browser, open `http://localhost/dariobimbi.com/wp-admin/`. The version bump from Task 1 causes `inc/auto-pages.php` to side-load the new `prompt-studio` page under the existing "demos" parent. Confirm by visiting Pages and finding "Prompt Studio" as a child of "Demos".

- [ ] **Step 4: Load the page and verify the skeleton**

Visit `http://localhost/dariobimbi.com/demos/prompt-studio/`. Expected:
- Eyebrow "demo", gradient h1 "Prompt Studio", lede paragraph.
- Type bar with Write (highlighted blue) / Image / Review.
- Mode bar with Build (highlighted) / Lint / Library.
- One "Build view goes here." stub block, others hidden.
- Background paints edge to edge in the surface color (no gutters of the page background showing at the viewport edges).

- [ ] **Step 5: `curl | grep` check for the encoding bug**

Run:
```
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```

Expected: `0`

- [ ] **Step 6: Commit**

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): add page skeleton with type chips and mode tabs"
```

---

### Task 3: State object + idempotent hash router + render dispatch

**Files:**
- Modify: `patterns/demo-prompt-studio.php` (the `<script>` block)

- [ ] **Step 1: Replace the placeholder `<script>` with the state + router foundation**

Find the existing `<script>` block at the bottom and replace its contents with:

```html
<script>
(function () {
  'use strict';

  document.body.classList.add('has-prompt-studio-page');

  // ---- State ----
  const TYPES = ['write', 'image', 'review'];
  const MODES = ['build', 'lint', 'library'];

  const state = {
    currentType: 'write',
    currentMode: 'build',
    drafts: {
      write:  { role:'', task:'', context:'', outputFormat:'', constraints:'', examples:'', audience:'', tone:'', advancedOpen:false },
      image:  { subject:'', style:'', composition:'', medium:'', colorLighting:'', cameraLens:'', mood:'', negative:'', aspectRatio:'', advancedOpen:false },
      review: { content:'', criteria:'', audience:'', outputFormat:'', severity:'', tone:'', examples:'', advancedOpen:false },
    },
    lint:    { input:'', detectedType:null, lastResult:null },
    library: { search:'', category:'all', openTemplateId:null },
  };

  // ---- Hash parsing ----
  function parseHash() {
    const raw = (location.hash || '').replace(/^#/, '');
    if (!raw) { return { type:null, mode:null, templateId:null }; }
    const [path, query] = raw.split('?');
    const parts = (path || '').split('/');
    const type = TYPES.indexOf(parts[0]) === -1 ? null : parts[0];
    const mode = MODES.indexOf(parts[1]) === -1 ? null : parts[1];
    let templateId = null;
    if (query) {
      const params = new URLSearchParams(query);
      templateId = params.get('t');
    }
    return { type: type, mode: mode, templateId: templateId };
  }

  function buildHash(type, mode, templateId) {
    let h = '#' + type + '/' + mode;
    if (templateId) { h += '?t=' + encodeURIComponent(templateId); }
    return h;
  }

  // ---- Router (idempotent) ----
  // Click handlers update the hash via pushState. A single popstate listener
  // reads the hash, overwrites state.currentType/currentMode, and renders.
  function routeFromHash() {
    const parsed = parseHash();
    if (parsed.type) { state.currentType = parsed.type; }
    if (parsed.mode) { state.currentMode = parsed.mode; }
    state.library.openTemplateId = parsed.templateId;
    render();
  }

  function pushRoute(type, mode, templateId) {
    const targetType = type || state.currentType;
    const targetMode = mode || state.currentMode;
    const newHash = buildHash(targetType, targetMode, templateId || null);
    if (location.hash !== newHash) {
      history.pushState(null, '', newHash);
    }
    routeFromHash();
  }

  // ---- Render ----
  function render() {
    // Type chips
    const typeChips = document.querySelectorAll('.ps-type-chip');
    for (let i = 0; i < typeChips.length; i++) {
      const chip = typeChips[i];
      const isActive = chip.getAttribute('data-type') === state.currentType;
      chip.classList.toggle('is-active', isActive);
      chip.setAttribute('aria-selected', isActive ? 'true' : 'false');
    }
    // Mode tabs
    const modeTabs = document.querySelectorAll('.ps-mode-tab');
    for (let i = 0; i < modeTabs.length; i++) {
      const tab = modeTabs[i];
      const isActive = tab.getAttribute('data-mode') === state.currentMode;
      tab.classList.toggle('is-active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    }
    // View containers
    const views = { build: document.getElementById('view-build'), lint: document.getElementById('view-lint'), library: document.getElementById('view-library') };
    for (const key in views) {
      if (views[key]) { views[key].hidden = (key !== state.currentMode); }
    }
    // Per-view renderers arrive in later tasks. For now the stub text is fine.
  }

  // ---- Click handlers (push hash only, do not render directly) ----
  document.addEventListener('click', function (ev) {
    const typeChip = ev.target.closest('.ps-type-chip');
    if (typeChip) {
      pushRoute(typeChip.getAttribute('data-type'), null, null);
      return;
    }
    const modeTab = ev.target.closest('.ps-mode-tab');
    if (modeTab) {
      pushRoute(null, modeTab.getAttribute('data-mode'), null);
      return;
    }
  });

  // ---- Keyboard nav on tablists ----
  document.addEventListener('keydown', function (ev) {
    const target = ev.target;
    if (!target) { return; }
    if (target.classList.contains('ps-type-chip')) {
      handleTabArrow(ev, target, 'ps-type-chip', TYPES, 'data-type', function (v) { pushRoute(v, null, null); });
      return;
    }
    if (target.classList.contains('ps-mode-tab')) {
      handleTabArrow(ev, target, 'ps-mode-tab', MODES, 'data-mode', function (v) { pushRoute(null, v, null); });
    }
  });

  function handleTabArrow(ev, target, klass, values, attr, apply) {
    const key = ev.key;
    if (key !== 'ArrowLeft') {
      if (key !== 'ArrowRight') { return; }
    }
    ev.preventDefault();
    const current = target.getAttribute(attr);
    const idx = values.indexOf(current);
    let nextIdx;
    if (key === 'ArrowRight') { nextIdx = (idx + 1) % values.length; }
    else { nextIdx = (idx - 1 + values.length) % values.length; }
    const nextValue = values[nextIdx];
    apply(nextValue);
    const newTarget = document.querySelector('.' + klass + '[' + attr + '="' + nextValue + '"]');
    if (newTarget) { newTarget.focus(); }
  }

  // ---- popstate ----
  window.addEventListener('popstate', routeFromHash);

  // ---- Init ----
  routeFromHash();
})();
</script>
```

- [ ] **Step 2: Syntax-check**

Run:
```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Browser verification — manual nav**

Reload `http://localhost/dariobimbi.com/demos/prompt-studio/`. Expected:
- Click Image chip → URL becomes `…/prompt-studio/#image/build`, type bar shows Image highlighted in violet.
- Click Library tab → URL becomes `…/prompt-studio/#image/library`, Library stub shown, Build hidden.
- Press the browser Back button → URL reverts to `…/prompt-studio/#image/build`, Build stub shown.
- Direct-visit `…/prompt-studio/#review/lint` → loads with Review chip and Lint tab active, Lint stub shown.
- Tab into a type chip, press Right arrow → focus moves to next chip and URL/visual updates.

- [ ] **Step 4: `curl | grep` check**

Run:
```
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```

Expected: `0`. If non-zero, locate the offending `&&` in the script and rewrite as nested `if`.

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): add state object and idempotent hash router"
```

---

## Phase 2 — Build mode

Goal: visitor can fill out a prompt for any of the three types, see an assembled prompt update live, see a token estimate, persist their work to localStorage, and hit Copy / Lint this / Reset draft.

---

### Task 4: RUBRICS constant + Build form rendering per type

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Add the RUBRICS constant at the top of the IIFE**

Insert immediately after the `'use strict';` line and the body-class line, before the existing `// ---- State ----` block:

```js
  // ---- Rubrics (field definitions per type) ----
  const RUBRICS = {
    write: {
      visible: [
        { key:'role',          label:'Role',          help:'Who the model is acting as.',           placeholder:'You are a senior staff engineer who reviews Python.' },
        { key:'task',          label:'Task',          help:'Explicit verb and object.',             placeholder:'Refactor the function below to remove the duplicated branches.' },
        { key:'context',       label:'Context',       help:'Background the model needs.',           placeholder:'This runs on a Raspberry Pi, memory matters.' },
        { key:'outputFormat',  label:'Output format', help:'Shape of the answer.',                  placeholder:'Return a unified diff, no prose.' },
        { key:'constraints',   label:'Constraints',   help:'What to avoid or stay within.',         placeholder:'Do not change the public function signature.' },
      ],
      advanced: [
        { key:'examples',      label:'Examples',      help:'Few-shot input / output pairs.',        placeholder:'Input: …  Output: …' },
        { key:'audience',      label:'Audience',      help:'Who the output is for.',                placeholder:'The junior dev who wrote the code.' },
        { key:'tone',          label:'Tone',          help:'Voice for the answer.',                 placeholder:'Direct but kind, no hedging.' },
      ],
      multiline: ['context','examples','outputFormat','constraints'],
      softTokenCap: 400,
    },
    image: {
      visible: [
        { key:'subject',       label:'Subject',       help:'The main thing.',                       placeholder:'A red fox curled asleep on a worn leather chair.' },
        { key:'style',         label:'Style',         help:'Artistic style.',                       placeholder:'Oil painting, Sargent influence.' },
        { key:'composition',   label:'Composition',   help:'Framing and angle.',                    placeholder:'Close-up, low angle, rule of thirds.' },
        { key:'medium',        label:'Medium',        help:'Physical or digital medium.',           placeholder:'Watercolor on rough paper.' },
        { key:'colorLighting', label:'Color & light', help:'Palette and light.',                    placeholder:'Warm amber light, deep shadows.' },
      ],
      advanced: [
        { key:'cameraLens',    label:'Camera / lens', help:'For photoreal shots.',                  placeholder:'35mm, f/1.8, shallow depth of field.' },
        { key:'mood',          label:'Mood',          help:'Feel of the image.',                    placeholder:'Quiet, contemplative.' },
        { key:'negative',      label:'Negative prompt', help:'What to exclude.',                    placeholder:'no text, no watermark, no extra limbs' },
        { key:'aspectRatio',   label:'Parameters',    help:'Aspect ratio and model flags.',         placeholder:'--ar 16:9 --v 6' },
      ],
      multiline: ['subject','negative'],
      softTokenCap: 80,
    },
    review: {
      visible: [
        { key:'content',       label:'Content to review', help:'Paste the thing being critiqued.',  placeholder:'Paste your code, copy, or document here.' },
        { key:'criteria',      label:'Criteria',      help:'What to evaluate against.',             placeholder:'Clarity, correctness, performance.' },
        { key:'audience',      label:'Audience for feedback', help:'Who reads the review.',         placeholder:'The junior dev who wrote it.' },
        { key:'outputFormat',  label:'Output format', help:'Shape of the review.',                  placeholder:'Bulleted list grouped by severity, no rewrite.' },
      ],
      advanced: [
        { key:'severity',      label:'Severity scale', help:'Define levels.',                       placeholder:'blocker / major / minor / nit' },
        { key:'tone',          label:'Tone',          help:'Voice of the feedback.',                placeholder:'Direct but kind, no hedging.' },
        { key:'examples',      label:'Examples of good / bad', help:'Calibration anchors.',          placeholder:'Good: …  Bad: …' },
      ],
      multiline: ['content','examples','criteria','outputFormat'],
      softTokenCap: 600,
      contentSoftCap: 3000,
    },
  };

  // Placeholder cycling (chosen once on page load, never while typing)
  const PLACEHOLDER_VARIANTS = {}; // populated by pickPlaceholders() — single-shot
```

- [ ] **Step 2: Add the Build view renderer**

After the `function render()` definition, add:

```js
  // ---- Build view ----
  function renderBuildView() {
    const root = document.getElementById('view-build');
    if (!root) { return; }
    const type = state.currentType;
    const rubric = RUBRICS[type];
    const draft = state.drafts[type];

    const parts = [];
    parts.push('<div class="ps-build">');
    parts.push('<form class="ps-form" autocomplete="off" novalidate>');

    // Visible fields
    for (let i = 0; i < rubric.visible.length; i++) {
      parts.push(renderField(rubric.visible[i], draft, rubric.multiline));
    }

    // Advanced toggle + fields
    parts.push('<button type="button" class="ps-advanced-toggle" aria-expanded="' + (draft.advancedOpen ? 'true' : 'false') + '" data-action="toggle-advanced">' + (draft.advancedOpen ? 'Hide advanced' : 'Show advanced') + '</button>');
    parts.push('<div class="ps-advanced" ' + (draft.advancedOpen ? '' : 'hidden') + '>');
    for (let i = 0; i < rubric.advanced.length; i++) {
      parts.push(renderField(rubric.advanced[i], draft, rubric.multiline));
    }
    parts.push('</div>');
    parts.push('</form>');

    // Preview + actions
    parts.push('<aside class="ps-preview">');
    parts.push('  <header class="ps-preview-header"><span>Assembled prompt</span><span class="ps-token-meter" id="ps-token-meter"></span></header>');
    parts.push('  <pre class="ps-preview-text" id="ps-preview-text" aria-live="polite"></pre>');
    parts.push('  <div class="ps-preview-actions">');
    parts.push('    <button type="button" data-action="copy-prompt">Copy</button>');
    parts.push('    <button type="button" data-action="lint-this">Lint this</button>');
    parts.push('    <button type="button" class="ps-link-button" data-action="reset-draft">Reset draft</button>');
    parts.push('  </div>');
    parts.push('</aside>');

    parts.push('</div>');
    root.innerHTML = parts.join('');
    refreshPreview();
  }

  function renderField(field, draft, multilineKeys) {
    const id = 'ps-field-' + field.key;
    const value = (draft[field.key] != null ? String(draft[field.key]) : '');
    const isMultiline = multilineKeys.indexOf(field.key) !== -1;
    const placeholder = field.placeholder.replace(/"/g, '&quot;');
    const help = field.help;
    let input;
    if (isMultiline) {
      input = '<textarea id="' + id + '" name="' + field.key + '" rows="3" placeholder="' + placeholder + '" aria-describedby="' + id + '-help">' + escapeHtml(value) + '</textarea>';
    } else {
      input = '<input id="' + id + '" name="' + field.key + '" type="text" value="' + escapeHtml(value).replace(/"/g, '&quot;') + '" placeholder="' + placeholder + '" aria-describedby="' + id + '-help" />';
    }
    return '<label class="ps-field" for="' + id + '"><span class="ps-field-label">' + escapeHtml(field.label) + '</span><span class="ps-field-help" id="' + id + '-help">' + escapeHtml(help) + '</span>' + input + '</label>';
  }

  function escapeHtml(s) {
    if (s == null) { return ''; }
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
```

- [ ] **Step 3: Call `renderBuildView` from `render()` and wire input events**

In `render()`, replace the `// Per-view renderers arrive in later tasks.` comment line with:

```js
    if (state.currentMode === 'build') { renderBuildView(); }
```

And at the bottom of the IIFE (just before the closing `})();`), wire input events that mutate state without re-rendering the form:

```js
  // Field input → mutate state.drafts[type][field] + refresh preview only
  document.addEventListener('input', function (ev) {
    const target = ev.target;
    if (!target) { return; }
    const name = target.getAttribute('name');
    if (!name) { return; }
    const type = state.currentType;
    if (!state.drafts[type]) { return; }
    if (!(name in state.drafts[type])) { return; }
    state.drafts[type][name] = target.value;
    refreshPreview();
    scheduleSave();
  });

  // Action buttons (advanced toggle, copy, lint-this, reset)
  document.addEventListener('click', function (ev) {
    const btn = ev.target.closest('[data-action]');
    if (!btn) { return; }
    const action = btn.getAttribute('data-action');
    if (action === 'toggle-advanced') {
      state.drafts[state.currentType].advancedOpen = !state.drafts[state.currentType].advancedOpen;
      renderBuildView();
      scheduleSave();
      return;
    }
    if (action === 'reset-draft') {
      const ok = confirm('Reset this draft? All fields for the current type will be cleared.');
      if (ok) {
        resetDraft(state.currentType);
        renderBuildView();
        scheduleSave();
      }
      return;
    }
    // copy-prompt, lint-this wired in Task 6.
  });

  function resetDraft(type) {
    const fresh = {
      write:  { role:'', task:'', context:'', outputFormat:'', constraints:'', examples:'', audience:'', tone:'', advancedOpen:false },
      image:  { subject:'', style:'', composition:'', medium:'', colorLighting:'', cameraLens:'', mood:'', negative:'', aspectRatio:'', advancedOpen:false },
      review: { content:'', criteria:'', audience:'', outputFormat:'', severity:'', tone:'', examples:'', advancedOpen:false },
    };
    state.drafts[type] = fresh[type];
  }

  // Stubs that arrive in later tasks. Defined now so the renderer can call them.
  function refreshPreview() { /* Task 5 fills this in */ }
  function scheduleSave()  { /* Task 7 fills this in */ }
```

- [ ] **Step 4: Add scoped CSS for the Build view**

Inside the existing `<style>` block, append before the closing `</style>`:

```css
.prompt-studio .ps-build { display: grid; grid-template-columns: 1fr; gap: var(--wp--preset--spacing--40); }
@media (min-width: 900px) { .prompt-studio .ps-build { grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); } }

.prompt-studio .ps-form { display: flex; flex-direction: column; gap: var(--wp--preset--spacing--30); margin: 0; }
.prompt-studio .ps-field { display: flex; flex-direction: column; gap: 0.35rem; }
.prompt-studio .ps-field-label { font-weight: 600; color: var(--wp--preset--color--foreground); }
.prompt-studio .ps-field-help { color: var(--wp--preset--color--muted); font-size: var(--wp--preset--font-size--small); }
.prompt-studio .ps-field input, .prompt-studio .ps-field textarea { width: 100%; padding: 0.6rem 0.75rem; border-radius: 10px; border: 1px solid var(--wp--preset--color--subtle); background: var(--wp--preset--color--background); color: var(--wp--preset--color--foreground); font: inherit; box-sizing: border-box; }
.prompt-studio .ps-field input:focus, .prompt-studio .ps-field textarea:focus { outline: 2px solid var(--wp--preset--color--primary); outline-offset: 1px; border-color: var(--wp--preset--color--primary); }
.prompt-studio .ps-field textarea { resize: vertical; min-height: 4.5rem; font-family: inherit; }

.prompt-studio .ps-advanced-toggle { align-self: flex-start; appearance: none; background: transparent; border: 1px dashed var(--wp--preset--color--subtle); color: var(--wp--preset--color--foreground); padding: 0.4rem 0.85rem; border-radius: 999px; cursor: pointer; font: inherit; }
.prompt-studio .ps-advanced { display: flex; flex-direction: column; gap: var(--wp--preset--spacing--30); }
.prompt-studio .ps-advanced[hidden] { display: none; }

.prompt-studio .ps-preview { background: var(--wp--preset--color--surface-card, var(--wp--preset--color--background)); background-image: var(--wp--preset--gradient--surface-card, none); border: 1px solid var(--wp--preset--color--subtle); border-radius: 14px; padding: var(--wp--preset--spacing--30); display: flex; flex-direction: column; gap: var(--wp--preset--spacing--20); position: sticky; top: var(--wp--preset--spacing--20); align-self: start; }
.prompt-studio .ps-preview-header { display: flex; justify-content: space-between; align-items: center; font-weight: 600; }
.prompt-studio .ps-preview-text { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.9rem; white-space: pre-wrap; word-break: break-word; background: var(--wp--preset--color--background); color: var(--wp--preset--color--foreground); border-radius: 10px; padding: var(--wp--preset--spacing--20); margin: 0; min-height: 6rem; max-height: 24rem; overflow: auto; }
.prompt-studio .ps-preview-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.prompt-studio .ps-preview-actions button { appearance: none; border: 1px solid var(--wp--preset--color--primary); background: var(--wp--preset--color--primary); color: var(--wp--preset--color--background); padding: 0.5rem 1rem; border-radius: 999px; cursor: pointer; font: inherit; }
.prompt-studio .ps-preview-actions button.ps-link-button { background: transparent; color: var(--wp--preset--color--muted); border-color: transparent; text-decoration: underline; padding: 0.5rem; }
.prompt-studio .ps-token-meter { font-size: var(--wp--preset--font-size--small); color: var(--wp--preset--color--muted); }
.prompt-studio .ps-token-meter.is-amber { color: var(--wp--preset--color--accent); }
.prompt-studio .ps-token-meter.is-red { color: var(--wp--preset--color--hot); }
```

- [ ] **Step 5: Syntax-check, browser-verify, curl-grep**

Run in sequence:
```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
```
Reload the page. Expected: Build view renders all five visible Write fields, "Show advanced" toggle reveals/hides Examples / Audience / Tone. Type-switch to Image → form rebuilds with Subject / Style / etc. Type-switch to Review → Content (textarea) / Criteria / Audience / Output format. Preview pane is present but empty (Task 5).

```
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```
Expected: `0`

- [ ] **Step 6: Commit**

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): add per-type Build form with progressive disclosure"
```

---

### Task 5: Assembled prompt preview + token meter (focus-preserving)

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Replace the `refreshPreview()` stub with the assembler**

Locate the existing `function refreshPreview() { /* Task 5 fills this in */ }` stub and replace with:

```js
  function refreshPreview() {
    const preview = document.getElementById('ps-preview-text');
    const meter = document.getElementById('ps-token-meter');
    if (!preview) { return; }
    const type = state.currentType;
    const assembled = assemblePrompt(type, state.drafts[type]);
    preview.textContent = assembled.text;
    if (meter) {
      const cap = RUBRICS[type].softTokenCap;
      if (type === 'review') {
        const instrTokens = tokenEstimate(assembled.instructionsOnly);
        const contentTokens = tokenEstimate(assembled.contentOnly);
        const contentCap = RUBRICS.review.contentSoftCap;
        meter.textContent = '~ Instructions: ' + instrTokens + ' tok · Content: ' + contentTokens + ' tok';
        meter.classList.remove('is-amber', 'is-red');
        if (instrTokens > cap) { meter.classList.add('is-red'); }
        else if (instrTokens > cap * 0.75) { meter.classList.add('is-amber'); }
        if (contentTokens > contentCap) { meter.classList.add('is-red'); }
      } else {
        const total = tokenEstimate(assembled.text);
        meter.textContent = '~ ' + total + ' tok';
        meter.classList.remove('is-amber', 'is-red');
        if (total > cap) { meter.classList.add('is-red'); }
        else if (total > cap * 0.75) { meter.classList.add('is-amber'); }
      }
    }
  }

  function tokenEstimate(text) {
    if (!text) { return 0; }
    return Math.ceil(text.length / 4);
  }

  function assemblePrompt(type, draft) {
    if (type === 'write')  { return assembleWrite(draft); }
    if (type === 'image')  { return assembleImage(draft); }
    if (type === 'review') { return assembleReview(draft); }
    return { text: '', instructionsOnly: '', contentOnly: '' };
  }

  function assembleWrite(d) {
    const lines = [];
    if (d.role)         { lines.push('Role: ' + d.role); }
    if (d.task)         { lines.push('Task: ' + d.task); }
    if (d.context)      { lines.push('Context: ' + d.context); }
    if (d.examples)     { lines.push('Examples:\n' + d.examples); }
    if (d.outputFormat) { lines.push('Output format: ' + d.outputFormat); }
    if (d.constraints)  { lines.push('Constraints: ' + d.constraints); }
    if (d.tone)         { lines.push('Tone: ' + d.tone); }
    if (d.audience)     { lines.push('Audience: ' + d.audience); }
    const text = lines.join('\n\n');
    return { text: text, instructionsOnly: text, contentOnly: '' };
  }

  function assembleImage(d) {
    const descriptors = [];
    if (d.subject)       { descriptors.push(d.subject); }
    if (d.style)         { descriptors.push(d.style); }
    if (d.composition)   { descriptors.push(d.composition); }
    if (d.medium)        { descriptors.push(d.medium); }
    if (d.colorLighting) { descriptors.push(d.colorLighting); }
    if (d.cameraLens)    { descriptors.push(d.cameraLens); }
    if (d.mood)          { descriptors.push(d.mood); }
    let main = descriptors.join(', ');
    if (d.aspectRatio)   { main = (main ? main + ' ' : '') + d.aspectRatio.trim(); }
    let text = main;
    if (d.negative)      { text = (text ? text + '\n' : '') + 'Negative: ' + d.negative; }
    return { text: text, instructionsOnly: text, contentOnly: '' };
  }

  function assembleReview(d) {
    const instr = [];
    instr.push('Review the following content.');
    if (d.criteria)     { instr.push('Criteria: ' + d.criteria); }
    if (d.audience)     { instr.push('Audience for feedback: ' + d.audience); }
    if (d.outputFormat) { instr.push('Output format: ' + d.outputFormat); }
    if (d.severity)     { instr.push('Severity scale: ' + d.severity); }
    if (d.tone)         { instr.push('Tone: ' + d.tone); }
    if (d.examples)     { instr.push('Examples:\n' + d.examples); }
    const instructionsOnly = instr.join('\n\n');
    const contentOnly = d.content ? d.content : '';
    let text = instructionsOnly;
    if (contentOnly) {
      text += '\n\n```\n' + contentOnly + '\n```';
    }
    text += '\n\nBegin your review now based on the criteria above.';
    return { text: text, instructionsOnly: instructionsOnly, contentOnly: contentOnly };
  }
```

- [ ] **Step 2: Syntax-check, browser-verify**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
```
Reload. Expected:
- Write: type `Role: senior dev` and `Task: refactor`. Preview shows `Role: senior dev\n\nTask: refactor`. Token meter shows `~ 8 tok` (approx).
- Image: type into Subject and Style. Preview shows comma-separated descriptors.
- Review: paste a 1500-char string into Content. Meter shows `~ Instructions: N tok · Content: M tok`. Type a couple instruction fields → instructions count rises, content count stays put.

Caret should never jump while typing. Focus stays in the field across every keystroke.

- [ ] **Step 3: curl-grep**

```
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```
Expected: `0`

- [ ] **Step 4: Commit**

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): assemble live prompt preview with token meter"
```

---

### Task 6: Copy / Lint-this / Reset-draft + placeholder cycling

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Add placeholder cycling and toast helper at IIFE top**

Right after the `PLACEHOLDER_VARIANTS` declaration, replace the empty declaration with a single-shot picker:

```js
  // Picks one placeholder set per page load. Field renderer reads from RUBRICS
  // directly today, so cycling here just means choosing a stable variant once
  // on init for cosmetic freshness. Future variants can replace these.
  function pickPlaceholders() {
    // Intentionally a no-op for v1: RUBRICS placeholders are stable strings.
    // Wired through so we can add per-field arrays of examples later without
    // touching renderField.
  }
  pickPlaceholders();
```

- [ ] **Step 2: Implement the toast queue**

Add somewhere in the IIFE (above `render`):

```js
  // ---- Toast ----
  function showToast(message, kind) {
    const k = kind || 'info';
    let host = document.getElementById('ps-toast-host');
    if (!host) {
      host = document.createElement('div');
      host.id = 'ps-toast-host';
      host.className = 'ps-toast-host';
      document.body.appendChild(host);
    }
    const el = document.createElement('div');
    el.className = 'ps-toast ps-toast-' + k;
    el.setAttribute('role', k === 'warn' ? 'alert' : 'status');
    el.textContent = message;
    host.appendChild(el);
    const duration = k === 'warn' ? 5000 : 2000;
    setTimeout(function () {
      if (el.parentNode) { el.parentNode.removeChild(el); }
    }, duration);
  }
```

- [ ] **Step 3: Wire `copy-prompt` and `lint-this` actions**

In the existing action `click` handler, replace the trailing comment line `// copy-prompt, lint-this wired in Task 6.` with:

```js
    if (action === 'copy-prompt') {
      const assembled = assemblePrompt(state.currentType, state.drafts[state.currentType]).text;
      copyToClipboard(assembled);
      return;
    }
    if (action === 'lint-this') {
      const assembled = assemblePrompt(state.currentType, state.drafts[state.currentType]).text;
      state.lint.input = assembled;
      state.lint.detectedType = state.currentType;
      state.lint.lastResult = null;
      pushRoute(null, 'lint', null);
      return;
    }
```

Then add helpers below `showToast`:

```js
  function copyToClipboard(text) {
    if (navigator.clipboard) {
      if (navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () { showToast('Copied.'); }).catch(function () { fallbackCopy(text); });
        return;
      }
    }
    fallbackCopy(text);
  }
  function fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'absolute';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); showToast('Copied.'); }
    catch (e) { showToast('Copy failed.', 'warn'); }
    document.body.removeChild(ta);
  }
```

- [ ] **Step 4: Add toast CSS**

Append to the `<style>` block:

```css
.ps-toast-host { position: fixed; right: 1rem; bottom: 1rem; display: flex; flex-direction: column; gap: 0.5rem; z-index: 9999; }
.ps-toast { background: var(--wp--preset--color--foreground); color: var(--wp--preset--color--background); padding: 0.65rem 1rem; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.2); max-width: 22rem; font-size: var(--wp--preset--font-size--small); }
.ps-toast-warn { background: var(--wp--preset--color--accent); }
```

- [ ] **Step 5: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
```
Reload. Type a few fields. Click Copy → toast "Copied." appears bottom-right, clipboard contains the assembled prompt. Click Lint this → URL becomes `#write/lint`, Lint stub view appears (Lint UI itself is Task 8). Click Reset draft → confirm dialog → on accept, all fields clear, preview empties.

```
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```
Expected: `0`

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): wire copy, lint-this, reset-draft actions and toasts"
```

---

### Task 7: localStorage persistence layer

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Replace the `scheduleSave()` stub and add load-on-init**

Replace the existing `function scheduleSave() { /* Task 7 fills this in */ }` stub with:

```js
  // ---- Persistence ----
  const STORAGE_KEY = 'dariobimbi.promptStudio.v1';
  let saveTimer = null;

  function scheduleSave() {
    if (saveTimer) { clearTimeout(saveTimer); }
    saveTimer = setTimeout(saveNow, 200);
  }

  function saveNow() {
    saveTimer = null;
    try {
      const payload = {
        currentType: state.currentType,
        currentMode: state.currentMode,
        drafts: state.drafts,
      };
      localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
    } catch (e) {
      // Quota or disabled storage: silently drop.
    }
  }

  function loadFromStorage() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) { return; }
      const parsed = JSON.parse(raw);
      if (!parsed) { return; }
      if (TYPES.indexOf(parsed.currentType) !== -1) {
        state.currentType = parsed.currentType;
      }
      if (MODES.indexOf(parsed.currentMode) !== -1) {
        state.currentMode = parsed.currentMode;
      }
      if (parsed.drafts) {
        for (let i = 0; i < TYPES.length; i++) {
          const t = TYPES[i];
          if (parsed.drafts[t]) {
            for (const k in state.drafts[t]) {
              if (k in parsed.drafts[t]) {
                state.drafts[t][k] = parsed.drafts[t][k];
              }
            }
          }
        }
      }
    } catch (e) {
      // Corrupt JSON or denied access: ignore.
    }
  }
```

- [ ] **Step 2: Reorder init so storage loads before the router runs**

Find the existing init block at the bottom of the IIFE:

```js
  // ---- Init ----
  routeFromHash();
```

Replace with:

```js
  // ---- Init ----
  // Order matters: load storage first (defaults for type/mode), then routeFromHash()
  // — the URL hash wins over storage per spec §2.
  loadFromStorage();
  routeFromHash();
```

- [ ] **Step 3: Trigger save on mode/type pushes too**

Inside `pushRoute`, after `routeFromHash();`, add:

```js
    scheduleSave();
```

So the function becomes:

```js
  function pushRoute(type, mode, templateId) {
    const targetType = type || state.currentType;
    const targetMode = mode || state.currentMode;
    const newHash = buildHash(targetType, targetMode, templateId || null);
    if (location.hash !== newHash) {
      history.pushState(null, '', newHash);
    }
    routeFromHash();
    scheduleSave();
  }
```

- [ ] **Step 4: Verify**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
```
Reload. Type into multiple fields across Write, Image, Review (switch types between). Reload the browser. Expected: each type's fields are restored, current type and mode also restored.

Hard-load `#image/library`. Expected: Image / Library is the view regardless of what storage said (URL wins).

Open DevTools → Application → Local Storage. Look for key `dariobimbi.promptStudio.v1` with a JSON payload containing `currentType`, `currentMode`, `drafts`.

```
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```
Expected: `0`

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): persist drafts and view state to localStorage"
```

---

## Phase 3 — Lint mode

Goal: paste a prompt, auto-detect type with override, hit Analyze, see a 0–100 score with rubric checklist and anti-pattern flags. Parse back to the Builder cleanly when possible, fall back with an amber toast otherwise.

---

### Task 8: Lint input panel + type detection

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Add Lint constants (style keywords, detection helpers)**

Insert near the top of the IIFE, after the `RUBRICS` block:

```js
  // ---- Lint helpers ----
  const IMAGE_STYLE_KEYWORDS = ['photorealistic','oil painting','watercolor','35mm','octane render','unreal engine','midjourney','stable diffusion','digital art','concept art','anime','pixel art','low poly','hyperdetailed','masterpiece','trending on artstation','8k','cinematic'];
  const IMAGE_FLAG_REGEX = /(--ar\s+\d|--v\s*\d|--niji|--style|--chaos|--stylize|--quality)/i;
  const REVIEW_WORDS = ['review','critique','feedback','evaluate','rate','assess'];

  function detectPromptType(text) {
    if (!text) { return 'write'; }
    const t = text.toLowerCase();
    // Image checks
    if (IMAGE_FLAG_REGEX.test(text)) { return 'image'; }
    let styleHits = 0;
    for (let i = 0; i < IMAGE_STYLE_KEYWORDS.length; i++) {
      if (t.indexOf(IMAGE_STYLE_KEYWORDS[i]) !== -1) { styleHits += 1; }
    }
    const commaCount = (text.match(/,/g) || []).length;
    const sentenceCount = (text.match(/[.!?]/g) || []).length;
    if (styleHits >= 2) { return 'image'; }
    if (commaCount >= 4) {
      if (sentenceCount <= 1) { return 'image'; }
    }
    // Review checks
    if (text.indexOf('```') !== -1) { return 'review'; }
    if (text.indexOf('"""') !== -1) { return 'review'; }
    for (let i = 0; i < REVIEW_WORDS.length; i++) {
      if (t.indexOf(REVIEW_WORDS[i]) !== -1) { return 'review'; }
    }
    return 'write';
  }
```

- [ ] **Step 2: Add `renderLintView()` and call it from `render()`**

In `render()`, replace the existing build dispatch line:

```js
    if (state.currentMode === 'build') { renderBuildView(); }
```

with:

```js
    if (state.currentMode === 'build')   { renderBuildView(); }
    if (state.currentMode === 'lint')    { renderLintView(); }
    if (state.currentMode === 'library') { renderLibraryView(); }
```

Then add the renderer:

```js
  // ---- Lint view ----
  function renderLintView() {
    const root = document.getElementById('view-lint');
    if (!root) { return; }
    const inputValue = state.lint.input || '';
    const detected = state.lint.detectedType;
    const overrideHint = detected ? ('Auto-detected: ' + capitalize(detected) + ' · click a chip to override') : 'Type-detect runs on paste.';

    let chips = '';
    for (let i = 0; i < TYPES.length; i++) {
      const t = TYPES[i];
      const isActive = (detected === t);
      chips += '<button type="button" class="ps-lint-chip' + (isActive ? ' is-active' : '') + '" data-action="lint-set-type" data-type="' + t + '">' + capitalize(t) + '</button>';
    }

    const resultHtml = state.lint.lastResult ? renderLintResults(state.lint.lastResult) : '<p class="ps-stub">Click Analyze to score the prompt above.</p>';

    root.innerHTML =
      '<div class="ps-lint">' +
        '<label class="ps-field"><span class="ps-field-label">Paste a prompt to analyze</span>' +
          '<textarea id="ps-lint-input" rows="8" placeholder="Paste a prompt here.">' + escapeHtml(inputValue) + '</textarea>' +
        '</label>' +
        '<div class="ps-lint-detect">' +
          '<div class="ps-lint-chips" role="tablist" aria-label="Detected type">' + chips + '</div>' +
          '<p class="ps-lint-hint">' + escapeHtml(overrideHint) + '</p>' +
        '</div>' +
        '<div class="ps-lint-actions">' +
          '<button type="button" data-action="lint-analyze">Analyze</button>' +
          '<button type="button" data-action="lint-clear" class="ps-link-button">Clear</button>' +
        '</div>' +
        '<div class="ps-lint-results" id="ps-lint-results">' + resultHtml + '</div>' +
      '</div>';
  }

  function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
  function renderLintResults(result) { return '<pre>' + escapeHtml(JSON.stringify(result, null, 2)) + '</pre>'; } // placeholder, Task 9 replaces
```

- [ ] **Step 3: Wire the Lint input + chip actions**

In the existing `input` handler at the bottom, add a special case for the Lint textarea. Replace the handler body with:

```js
  document.addEventListener('input', function (ev) {
    const target = ev.target;
    if (!target) { return; }

    // Lint input
    if (target.id === 'ps-lint-input') {
      state.lint.input = target.value;
      state.lint.detectedType = detectPromptType(target.value);
      // Update only the detect chips + hint, not the textarea (preserve focus).
      const chips = document.querySelectorAll('.ps-lint-chip');
      for (let i = 0; i < chips.length; i++) {
        const chip = chips[i];
        chip.classList.toggle('is-active', chip.getAttribute('data-type') === state.lint.detectedType);
      }
      const hint = document.querySelector('.ps-lint-hint');
      if (hint) { hint.textContent = 'Auto-detected: ' + capitalize(state.lint.detectedType) + ' · click a chip to override'; }
      return;
    }

    // Builder field
    const name = target.getAttribute('name');
    if (!name) { return; }
    const type = state.currentType;
    if (!state.drafts[type]) { return; }
    if (!(name in state.drafts[type])) { return; }
    state.drafts[type][name] = target.value;
    refreshPreview();
    scheduleSave();
  });
```

In the action `click` handler, add cases for `lint-set-type`, `lint-analyze`, `lint-clear`:

```js
    if (action === 'lint-set-type') {
      state.lint.detectedType = btn.getAttribute('data-type');
      renderLintView();
      return;
    }
    if (action === 'lint-analyze') {
      runLint();
      return;
    }
    if (action === 'lint-clear') {
      state.lint.input = '';
      state.lint.detectedType = null;
      state.lint.lastResult = null;
      renderLintView();
      return;
    }
```

And add a stub `runLint()` (real logic in Task 9):

```js
  function runLint() {
    state.lint.lastResult = { stub: true, type: state.lint.detectedType, length: (state.lint.input || '').length };
    renderLintView();
  }
```

- [ ] **Step 4: Add Lint CSS**

Append to the `<style>` block:

```css
.prompt-studio .ps-lint { display: flex; flex-direction: column; gap: var(--wp--preset--spacing--30); }
.prompt-studio .ps-lint-detect { display: flex; flex-direction: column; gap: 0.4rem; }
.prompt-studio .ps-lint-chips { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.prompt-studio .ps-lint-chip { appearance: none; border: 1px solid var(--wp--preset--color--subtle); background: var(--wp--preset--color--background); color: var(--wp--preset--color--foreground); padding: 0.35rem 0.75rem; border-radius: 999px; cursor: pointer; font: inherit; font-size: var(--wp--preset--font-size--small); }
.prompt-studio .ps-lint-chip.is-active { background: var(--wp--preset--color--foreground); color: var(--wp--preset--color--background); border-color: var(--wp--preset--color--foreground); animation: psPulse 0.5s ease-out; }
.prompt-studio .ps-lint-hint { color: var(--wp--preset--color--muted); font-size: var(--wp--preset--font-size--small); margin: 0; }
.prompt-studio .ps-lint-actions { display: flex; gap: 0.5rem; }
.prompt-studio .ps-lint-actions button { appearance: none; border: 1px solid var(--wp--preset--color--primary); background: var(--wp--preset--color--primary); color: var(--wp--preset--color--background); padding: 0.5rem 1rem; border-radius: 999px; cursor: pointer; font: inherit; }
.prompt-studio .ps-lint-actions button.ps-link-button { background: transparent; color: var(--wp--preset--color--muted); border-color: transparent; text-decoration: underline; }
@keyframes psPulse { 0% { transform: scale(0.95); } 100% { transform: scale(1); } }
```

- [ ] **Step 5: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
```
Reload, click Lint tab. Expected: textarea, three type chips, hint, Analyze + Clear buttons.
- Paste `A red fox curled asleep, oil painting, close-up, warm light` → Image chip lights up with animation, hint reads `Auto-detected: Image · …`.
- Paste a fenced block ` ``` def foo(): pass ``` ` → Review chip lights up.
- Type `Refactor this function` → Write chip is active.
- Click Image chip manually → it stays Image even if the text would auto-detect Write.
- Click Analyze → results panel shows the placeholder JSON.

```
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```
Expected: `0`

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): add Lint input panel with type auto-detect"
```

---

### Task 9: Score ring + rubric checklist + anti-pattern flags

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Define the lint rule sets**

Insert near the top of the IIFE, after the `IMAGE_STYLE_KEYWORDS` block:

```js
  // ---- Lint rules ----
  // Each rule: { key, label, check(text) → { present:bool, strength:0..1, detail:'' } }
  const LINT_RULES = {
    write: [
      { key:'role',        label:'Role / persona',  check: function (t) { return checkPattern(t, /(you are|act as)/i, 'Mentions a role'); } },
      { key:'task',        label:'Clear task',      check: function (t) { return checkTaskVerb(t); } },
      { key:'context',     label:'Context',         check: function (t) { return checkLength(t, 60, 'Has enough context'); } },
      { key:'outputFormat',label:'Output format',   check: function (t) { return checkPattern(t, /(format|as (a |an )?(json|markdown|list|table|bulleted|csv|diff))/i, 'Specifies output format'); } },
      { key:'constraints', label:'Constraints',     check: function (t) { return checkPattern(t, /(do not|don'?t|never|must not|avoid|without|exclude|only|stay within|no more than)/i, 'Lists constraints'); } },
    ],
    image: [
      { key:'subject',     label:'Subject',         check: function (t) { return { present: t.length > 0, strength: t.length > 12 ? 1 : 0.5, detail: t.length > 0 ? 'Subject implied' : 'No subject' }; } },
      { key:'style',       label:'Style',           check: function (t) { return checkAnyKeyword(t, IMAGE_STYLE_KEYWORDS, 'Mentions a recognized style'); } },
      { key:'composition', label:'Composition',     check: function (t) { return checkPattern(t, /(close-?up|wide|low angle|high angle|portrait|landscape|rule of thirds|symmetrical|over the shoulder|aerial)/i, 'Frames the shot'); } },
      { key:'lighting',    label:'Color / light',   check: function (t) { return checkPattern(t, /(light|lit|sunset|dawn|shadow|amber|cool|warm|neon|backlit|golden hour|moody)/i, 'Mentions light or color'); } },
      { key:'params',      label:'Parameters',      check: function (t) { return checkPattern(t, /(--ar\s+\d|--v\s*\d|--niji|--style|aspect|\d{3,4}\s*x\s*\d{3,4})/i, 'Includes platform parameters'); } },
    ],
    review: [
      { key:'content',     label:'Content present', check: function (t) { return { present: /```|"""/.test(t), strength: /```[\s\S]+```|"""[\s\S]+"""/.test(t) ? 1 : 0.4, detail: /```|"""/.test(t) ? 'Fenced block detected' : 'No fenced content block' }; } },
      { key:'criteria',    label:'Criteria',        check: function (t) { return checkPattern(t, /(criteria|evaluate|assess|judge|grade|rubric)/i, 'Names criteria'); } },
      { key:'audience',    label:'Audience',        check: function (t) { return checkPattern(t, /(audience|for the|reader|reviewer)/i, 'Names the audience'); } },
      { key:'outputFormat',label:'Output format',   check: function (t) { return checkPattern(t, /(bulleted|list|table|grouped by|severity|format)/i, 'Specifies output format'); } },
    ],
  };

  const ANTI_PATTERNS = [
    { id:'polite_filler',  appliesTo:['write','review'], label:'Polite filler', detail:'LLMs do not weight courtesy as instruction. Drop the pleasantries.', test: function (t) { return /\b(please|thanks in advance|thank you|kindly)\b/i.test(t); } },
    { id:'vague_qualifier',appliesTo:['write','review'], label:'Vague qualifier', detail:'Replace "comprehensive" / "thoroughly" / "in detail" with a concrete length or list.', test: function (t) { return /\b(comprehensive|thoroughly|in detail|extensively|holistic)\b/i.test(t); } },
    { id:'soft_verb',      appliesTo:['write'], label:'Soft task verb', detail:'Rewrite "help me with…" or "I want…" as an imperative.', test: function (t) { return /(help me (with|to)|i want|i need|can you|could you)/i.test(t); } },
    { id:'dont_bias',      appliesTo:['write'], label:'"Don\'t" bias', detail:'LLMs follow affirmative instructions better than negatives. Pair "don\'t X" with "instead, do Y".', test: function (t) {
        const dontCount = (t.match(/\b(do not|don'?t|never|avoid)\b/gi) || []).length;
        const insteadCount = (t.match(/\b(instead|rather|prefer)\b/gi) || []).length;
        return dontCount >= 2 && insteadCount === 0;
      } },
    { id:'conflict_style', appliesTo:['image'], label:'Conflicting styles', detail:'Mixing incompatible style keywords confuses the model.', test: function (t) {
        const tl = t.toLowerCase();
        const pairs = [['photorealistic','anime'],['watercolor','3d render'],['oil painting','vector'],['pixel art','photorealistic']];
        for (let i = 0; i < pairs.length; i++) {
          if (tl.indexOf(pairs[i][0]) !== -1) {
            if (tl.indexOf(pairs[i][1]) !== -1) { return true; }
          }
        }
        return false;
      } },
    { id:'style_soup',     appliesTo:['image'], label:'Style soup', detail:'More than 5 quality / style keywords. Modern models ignore the noise or degrade.', test: function (t) {
        const tl = t.toLowerCase();
        const soup = ['8k','octane render','unreal engine','trending on artstation','masterpiece','hyperdetailed','best quality','award winning','intricate','ultra detailed'];
        let hits = 0;
        for (let i = 0; i < soup.length; i++) {
          if (tl.indexOf(soup[i]) !== -1) { hits += 1; }
        }
        return hits > 5;
      } },
    { id:'missing_ar',     appliesTo:['image'], label:'Missing aspect ratio', detail:'Most platforms expect --ar or equivalent.', test: function (t) { return !IMAGE_FLAG_REGEX.test(t); } },
    { id:'unfenced',       appliesTo:['review'], label:'Unfenced content', detail:'Content runs into instructions. Wrap the artifact in triple backticks.', test: function (t) { return !/```|"""/.test(t); } },
  ];

  function checkPattern(text, regex, presentDetail) {
    const ok = regex.test(text);
    return { present: ok, strength: ok ? 1 : 0, detail: ok ? presentDetail : 'Not detected' };
  }
  function checkAnyKeyword(text, list, presentDetail) {
    const tl = text.toLowerCase();
    for (let i = 0; i < list.length; i++) {
      if (tl.indexOf(list[i]) !== -1) {
        return { present: true, strength: 1, detail: presentDetail };
      }
    }
    return { present: false, strength: 0, detail: 'Not detected' };
  }
  function checkLength(text, minChars, presentDetail) {
    if (text.length >= minChars) {
      return { present: true, strength: 1, detail: presentDetail };
    }
    if (text.length > 0) {
      return { present: true, strength: 0.4, detail: 'Some text, but might be too thin (' + text.length + ' chars)' };
    }
    return { present: false, strength: 0, detail: 'Empty' };
  }
  function checkTaskVerb(text) {
    // Imperative verbs at the start of a sentence
    if (/(?:^|[\.\n]\s*)(refactor|review|generate|explain|write|summarize|translate|extract|compare|classify|debug|fix|optimize|design|name|brainstorm|outline|rewrite|critique|evaluate|analyze)\b/i.test(text)) {
      return { present: true, strength: 1, detail: 'Imperative task verb' };
    }
    if (text.length > 0) {
      return { present: true, strength: 0.3, detail: 'No clear task verb' };
    }
    return { present: false, strength: 0, detail: 'Empty' };
  }
```

- [ ] **Step 2: Replace `runLint()` with the real scorer**

Replace the existing `runLint` stub with:

```js
  function runLint() {
    const text = state.lint.input || '';
    const type = state.lint.detectedType || detectPromptType(text);
    state.lint.detectedType = type;

    const rules = LINT_RULES[type];
    const checks = [];
    let totalStrength = 0;
    for (let i = 0; i < rules.length; i++) {
      const r = rules[i].check(text);
      checks.push({ key: rules[i].key, label: rules[i].label, present: r.present, strength: r.strength, detail: r.detail });
      totalStrength += r.strength;
    }
    const maxScore = rules.length;
    let score = maxScore === 0 ? 0 : Math.round((totalStrength / maxScore) * 100);

    const flags = [];
    for (let i = 0; i < ANTI_PATTERNS.length; i++) {
      const ap = ANTI_PATTERNS[i];
      if (ap.appliesTo.indexOf(type) === -1) { continue; }
      if (ap.test(text)) {
        flags.push({ id: ap.id, label: ap.label, detail: ap.detail });
        score = Math.max(0, score - 8);
      }
    }

    let verdict;
    if (score >= 80) { verdict = 'Strong'; }
    else if (score >= 50) { verdict = 'Workable'; }
    else { verdict = 'Needs work'; }

    state.lint.lastResult = { type: type, score: score, verdict: verdict, checks: checks, flags: flags };
    renderLintView();
  }
```

- [ ] **Step 3: Replace `renderLintResults` placeholder with the real UI**

Replace the previous `renderLintResults` placeholder with:

```js
  function renderLintResults(result) {
    if (!result) { return ''; }
    const score = result.score;
    let band = 'red';
    if (score >= 80) { band = 'green'; }
    else if (score >= 50) { band = 'amber'; }

    // SVG score ring (simple stroke arc)
    const radius = 38;
    const circumference = 2 * Math.PI * radius;
    const dash = (score / 100) * circumference;
    const ringHtml =
      '<svg class="ps-score-ring ps-score-' + band + '" viewBox="0 0 100 100" aria-hidden="true">' +
        '<circle cx="50" cy="50" r="' + radius + '" fill="none" stroke="currentColor" stroke-opacity="0.15" stroke-width="10" />' +
        '<circle cx="50" cy="50" r="' + radius + '" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round" stroke-dasharray="' + dash + ' ' + circumference + '" transform="rotate(-90 50 50)" />' +
      '</svg>';

    let checklist = '';
    for (let i = 0; i < result.checks.length; i++) {
      const c = result.checks[i];
      let icon = 'x';
      if (c.strength >= 0.8) { icon = '✓'; }
      else if (c.strength > 0) { icon = '~'; }
      else { icon = '✗'; }
      checklist += '<li class="ps-check ps-check-' + (c.strength >= 0.8 ? 'ok' : (c.strength > 0 ? 'partial' : 'fail')) + '"><span class="ps-check-icon" aria-hidden="true">' + icon + '</span><span class="ps-check-label">' + escapeHtml(c.label) + '</span><span class="ps-check-detail">' + escapeHtml(c.detail) + '</span></li>';
    }

    let flagsHtml = '';
    for (let i = 0; i < result.flags.length; i++) {
      const f = result.flags[i];
      flagsHtml += '<div class="ps-flag"><h4>' + escapeHtml(f.label) + '</h4><p>' + escapeHtml(f.detail) + '</p></div>';
    }

    return (
      '<div class="ps-lint-result">' +
        '<header class="ps-lint-result-head">' +
          '<div class="ps-score-wrap">' + ringHtml + '<span class="ps-score-value">' + score + '</span></div>' +
          '<div class="ps-verdict"><strong>' + escapeHtml(result.verdict) + '</strong><br><span>(' + escapeHtml(capitalize(result.type)) + ' rubric)</span></div>' +
        '</header>' +
        '<ul class="ps-checklist">' + checklist + '</ul>' +
        (flagsHtml ? '<section class="ps-flags"><h3>Flags</h3>' + flagsHtml + '</section>' : '') +
        '<div class="ps-lint-result-actions">' +
          '<button type="button" data-action="lint-refine">Refine in Builder</button>' +
          '<button type="button" data-action="lint-copy-results" class="ps-link-button">Copy results</button>' +
        '</div>' +
      '</div>'
    );
  }
```

- [ ] **Step 4: Add lint-result CSS**

Append:

```css
.prompt-studio .ps-lint-result { display: flex; flex-direction: column; gap: var(--wp--preset--spacing--30); }
.prompt-studio .ps-lint-result-head { display: flex; align-items: center; gap: var(--wp--preset--spacing--30); }
.prompt-studio .ps-score-wrap { position: relative; width: 100px; height: 100px; display: grid; place-items: center; }
.prompt-studio .ps-score-ring { position: absolute; inset: 0; width: 100%; height: 100%; }
.prompt-studio .ps-score-green { color: #16a34a; }
.prompt-studio .ps-score-amber { color: #d97706; }
.prompt-studio .ps-score-red   { color: #dc2626; }
.prompt-studio .ps-score-value { font-size: 1.6rem; font-weight: 700; }
.prompt-studio .ps-verdict { font-size: 1rem; color: var(--wp--preset--color--muted); }
.prompt-studio .ps-checklist { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.4rem; }
.prompt-studio .ps-check { display: grid; grid-template-columns: 1.5rem auto 1fr; gap: 0.5rem; align-items: baseline; padding: 0.3rem 0; border-bottom: 1px dashed var(--wp--preset--color--subtle); }
.prompt-studio .ps-check-ok .ps-check-icon { color: #16a34a; }
.prompt-studio .ps-check-partial .ps-check-icon { color: #d97706; }
.prompt-studio .ps-check-fail .ps-check-icon { color: #dc2626; }
.prompt-studio .ps-check-detail { color: var(--wp--preset--color--muted); font-size: var(--wp--preset--font-size--small); }
.prompt-studio .ps-flags { display: flex; flex-direction: column; gap: 0.5rem; }
.prompt-studio .ps-flag { background: var(--wp--preset--color--background); border-left: 3px solid var(--wp--preset--color--accent); padding: 0.6rem 0.85rem; border-radius: 6px; }
.prompt-studio .ps-flag h4 { margin: 0 0 0.2rem; font-size: var(--wp--preset--font-size--medium); }
.prompt-studio .ps-flag p { margin: 0; color: var(--wp--preset--color--muted); font-size: var(--wp--preset--font-size--small); }
.prompt-studio .ps-lint-result-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.prompt-studio .ps-lint-result-actions button { appearance: none; border: 1px solid var(--wp--preset--color--primary); background: var(--wp--preset--color--primary); color: var(--wp--preset--color--background); padding: 0.5rem 1rem; border-radius: 999px; cursor: pointer; font: inherit; }
.prompt-studio .ps-lint-result-actions .ps-link-button { background: transparent; color: var(--wp--preset--color--muted); border-color: transparent; text-decoration: underline; padding: 0.5rem; }
```

- [ ] **Step 5: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
```
Reload, go to Lint tab. Test cases:
- Paste `Please refactor this thoroughly: function foo() {}`. Analyze → Write rubric, low-to-mid score, flags: Polite filler, Vague qualifier, missing several rubric items.
- Paste `A red fox curled asleep, oil painting, close-up, warm light, 8k, octane render, trending on artstation, masterpiece, hyperdetailed, ultra detailed, best quality --ar 16:9`. Analyze → Image rubric, Style soup flag.
- Paste a Review-style prompt with a fenced code block and "review this code" → Review rubric, content-present check passes.

```
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```
Expected: `0`

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): score prompts with rubric checks and anti-pattern flags"
```

---

### Task 10: Parse-back to Builder + Copy results + Confirm-if-dirty

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Add parsers per type**

Add near the other lint helpers:

```js
  // ---- Parse-back ----
  // Returns { fields: {...rubric fields}, lossy: bool }
  function parsePromptBack(text, type) {
    if (type === 'write')  { return parseWriteBack(text); }
    if (type === 'image')  { return parseImageBack(text); }
    if (type === 'review') { return parseReviewBack(text); }
    return { fields: {}, lossy: true };
  }

  function parseWriteBack(text) {
    const fields = { role:'', task:'', context:'', outputFormat:'', constraints:'', examples:'', audience:'', tone:'' };
    const sections = {
      role: /(?:^|\n\s*)Role:\s*([\s\S]*?)(?=\n\s*(?:Task|Context|Examples|Output format|Constraints|Tone|Audience):|$)/i,
      task: /(?:^|\n\s*)Task:\s*([\s\S]*?)(?=\n\s*(?:Role|Context|Examples|Output format|Constraints|Tone|Audience):|$)/i,
      context: /(?:^|\n\s*)Context:\s*([\s\S]*?)(?=\n\s*(?:Role|Task|Examples|Output format|Constraints|Tone|Audience):|$)/i,
      examples: /(?:^|\n\s*)Examples?:\s*([\s\S]*?)(?=\n\s*(?:Role|Task|Context|Output format|Constraints|Tone|Audience):|$)/i,
      outputFormat: /(?:^|\n\s*)Output format:\s*([\s\S]*?)(?=\n\s*(?:Role|Task|Context|Examples|Constraints|Tone|Audience):|$)/i,
      constraints: /(?:^|\n\s*)Constraints?:\s*([\s\S]*?)(?=\n\s*(?:Role|Task|Context|Examples|Output format|Tone|Audience):|$)/i,
      tone: /(?:^|\n\s*)Tone:\s*([\s\S]*?)(?=\n\s*(?:Role|Task|Context|Examples|Output format|Constraints|Audience):|$)/i,
      audience: /(?:^|\n\s*)Audience:\s*([\s\S]*?)(?=\n\s*(?:Role|Task|Context|Examples|Output format|Constraints|Tone):|$)/i,
    };
    let matched = 0;
    for (const k in sections) {
      const m = text.match(sections[k]);
      if (m) { fields[k] = m[1].trim(); matched += 1; }
    }
    if (matched === 0) {
      fields.task = text.trim();
      return { fields: fields, lossy: true };
    }
    return { fields: fields, lossy: false };
  }

  function parseImageBack(text) {
    const fields = { subject:'', style:'', composition:'', medium:'', colorLighting:'', cameraLens:'', mood:'', negative:'', aspectRatio:'' };
    let workText = text;
    // Extract Negative
    const negMatch = workText.match(/(?:^|\n)Negative:\s*([\s\S]+?)(?:\n|$)/i);
    if (negMatch) {
      fields.negative = negMatch[1].trim();
      workText = workText.replace(negMatch[0], '');
    }
    // Extract aspect-ratio / param flags
    const flagMatch = workText.match(/(--ar\s+[\d:]+(?:\s+--[a-z]+\s*[\w\.:]*)*|\s--v\s*\d+|\s--niji\d*|\s--style\s+\w+)/i);
    if (flagMatch) {
      fields.aspectRatio = flagMatch[0].trim();
      workText = workText.replace(flagMatch[0], '');
    }
    // Split on commas
    const chunks = workText.split(',').map(function (s) { return s.trim(); }).filter(function (s) { return s.length > 0; });
    if (chunks.length === 0) { return { fields: fields, lossy: true }; }
    fields.subject = chunks[0];
    for (let i = 1; i < chunks.length; i++) {
      const c = chunks[i];
      const cl = c.toLowerCase();
      if (matchesAny(cl, IMAGE_STYLE_KEYWORDS)) { fields.style = appendTo(fields.style, c); continue; }
      if (/(close-?up|wide|low angle|high angle|portrait|landscape|rule of thirds|symmetrical|over the shoulder|aerial)/i.test(c)) { fields.composition = appendTo(fields.composition, c); continue; }
      if (/(35mm|f\/\d|lens|depth of field|bokeh|telephoto|wide-angle|macro)/i.test(c)) { fields.cameraLens = appendTo(fields.cameraLens, c); continue; }
      if (/(light|lit|sunset|dawn|shadow|amber|cool|warm|neon|backlit|golden hour|moody|color)/i.test(c)) { fields.colorLighting = appendTo(fields.colorLighting, c); continue; }
      if (/(watercolor|oil|paper|canvas|digital|3d|render|ink|pencil)/i.test(c)) { fields.medium = appendTo(fields.medium, c); continue; }
      // Otherwise treat as extra subject detail
      fields.subject = appendTo(fields.subject, c);
    }
    return { fields: fields, lossy: false };
  }
  function matchesAny(s, list) { for (let i = 0; i < list.length; i++) { if (s.indexOf(list[i]) !== -1) { return true; } } return false; }
  function appendTo(existing, next) { return existing ? existing + ', ' + next : next; }

  function parseReviewBack(text) {
    const fields = { content:'', criteria:'', audience:'', outputFormat:'', severity:'', tone:'', examples:'' };
    // Locate the fenced block (``` or """)
    const fencedMatch = text.match(/(```|""")([\s\S]+?)\1/);
    if (fencedMatch) {
      fields.content = fencedMatch[2].trim();
    }
    // Instructions = everything before the fenced block, minus a trailing CTA
    let instr = fencedMatch ? text.slice(0, text.indexOf(fencedMatch[0])) : text;
    instr = instr.replace(/begin your review now[\s\S]*$/i, '').trim();
    instr = instr.replace(/^review the following content\.?\s*/i, '').trim();
    const sections = {
      criteria: /(?:^|\n\s*)Criteria:\s*([\s\S]*?)(?=\n\s*(?:Audience(?: for feedback)?|Output format|Severity scale|Tone|Examples):|$)/i,
      audience: /(?:^|\n\s*)Audience(?: for feedback)?:\s*([\s\S]*?)(?=\n\s*(?:Criteria|Output format|Severity scale|Tone|Examples):|$)/i,
      outputFormat: /(?:^|\n\s*)Output format:\s*([\s\S]*?)(?=\n\s*(?:Criteria|Audience(?: for feedback)?|Severity scale|Tone|Examples):|$)/i,
      severity: /(?:^|\n\s*)Severity(?: scale)?:\s*([\s\S]*?)(?=\n\s*(?:Criteria|Audience(?: for feedback)?|Output format|Tone|Examples):|$)/i,
      tone: /(?:^|\n\s*)Tone:\s*([\s\S]*?)(?=\n\s*(?:Criteria|Audience(?: for feedback)?|Output format|Severity scale|Examples):|$)/i,
      examples: /(?:^|\n\s*)Examples?:\s*([\s\S]*?)(?=\n\s*(?:Criteria|Audience(?: for feedback)?|Output format|Severity scale|Tone):|$)/i,
    };
    let matched = 0;
    for (const k in sections) {
      const m = instr.match(sections[k]);
      if (m) { fields[k] = m[1].trim(); matched += 1; }
    }
    if (!fencedMatch && matched === 0) {
      fields.content = text.trim();
      return { fields: fields, lossy: true };
    }
    return { fields: fields, lossy: false };
  }
```

- [ ] **Step 2: Wire `lint-refine` and `lint-copy-results` actions**

Add to the existing action `click` handler:

```js
    if (action === 'lint-refine') {
      refineFromLint();
      return;
    }
    if (action === 'lint-copy-results') {
      const result = state.lint.lastResult;
      if (!result) { return; }
      let out = 'Score: ' + result.score + ' (' + result.verdict + ', ' + result.type + ' rubric)\n\n';
      out += 'Checks:\n';
      for (let i = 0; i < result.checks.length; i++) {
        const c = result.checks[i];
        out += '  - ' + c.label + ': ' + c.detail + '\n';
      }
      if (result.flags.length) {
        out += '\nFlags:\n';
        for (let i = 0; i < result.flags.length; i++) {
          out += '  - ' + result.flags[i].label + ': ' + result.flags[i].detail + '\n';
        }
      }
      copyToClipboard(out);
      return;
    }
```

Then add the refine function:

```js
  function refineFromLint() {
    const text = state.lint.input || '';
    const type = state.lint.detectedType || detectPromptType(text);
    if (!text) { showToast('Nothing to refine.', 'warn'); return; }
    if (isDraftDirty(type)) {
      const ok = confirm('This will replace your current ' + capitalize(type) + ' draft. Continue?');
      if (!ok) { return; }
    }
    const parsed = parsePromptBack(text, type);
    const draft = state.drafts[type];
    for (const k in parsed.fields) {
      if (k in draft) { draft[k] = parsed.fields[k]; }
    }
    scheduleSave();
    pushRoute(type, 'build', null);
    if (parsed.lossy) {
      showToast('Could not auto-split into fields. Loaded into ' + (type === 'image' ? 'Subject' : (type === 'review' ? 'Content' : 'Task')) + ' for manual editing.', 'warn');
    }
  }

  function isDraftDirty(type) {
    const draft = state.drafts[type];
    for (const k in draft) {
      if (k === 'advancedOpen') { continue; }
      if (typeof draft[k] === 'string') {
        if (draft[k].trim().length > 0) { return true; }
      }
    }
    return false;
  }
```

- [ ] **Step 3: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
```
Reload. Test cases:
- Paste a fully-structured Write prompt with `Role: …\n\nTask: …\n\nOutput format: …` → Analyze → click Refine in Builder → switches to Build/Write, fields are populated, no toast.
- Paste an unstructured Write blob: `do the thing thoroughly please`. Analyze → Refine → switches to Build/Write, the whole text lands in Task, **amber toast** shows for 5 seconds.
- Build/Write has draft content → Lint with structured input → Refine → confirm prompt asks "This will replace your current Write draft. Continue?" → cancel → no replacement, no nav.
- Image: paste `red fox, oil painting, close-up, warm light, --ar 16:9` → Refine → Subject = `red fox`, Style = `oil painting`, etc.
- Review: paste an instructions block + fenced content → Refine → Content holds the fenced text, other fields populated.

```
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```
Expected: `0`

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): parse lint input back into Builder fields"
```

---

## Phase 4a — Library UI and stub templates

Goal: Library view renders a filterable, searchable card grid. Clicking a card opens a focus-trapped modal. Three stub templates (one per type) prove the wiring before the heavy authoring phase.

---

### Task 11: TEMPLATES stub array + Library view

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Add the TEMPLATES constant with 3 stubs**

Insert near the top of the IIFE, after the `RUBRICS` block:

```js
  // ---- Templates ----
  const TEMPLATES = [
    // ─── WRITE TEMPLATES ──────────────────────────────────────────
    {
      id: 'write-code-pr-review',
      type: 'write',
      category: 'code',
      title: 'PR review request',
      summary: 'Ask the LLM to review a pull-request diff against a clear rubric.',
      fields: {
        role: 'You are a senior staff engineer reviewing a teammate\'s pull request.',
        task: 'Review the diff below and surface anything that would block merge or that the author should reconsider.',
        context: 'The repo is a TypeScript React app. The team values readable diffs over clever code. The author is mid-level.',
        outputFormat: 'Bulleted list grouped by severity (blocker, major, minor, nit). One line per item, with a line reference where possible.',
        constraints: 'Do not rewrite the code yourself. Only flag and explain. Do not comment on formatting issues that a linter would catch.',
        examples: '',
        audience: 'The mid-level engineer who wrote the diff.',
        tone: 'Direct but kind, no hedging.',
        advancedOpen: false,
      },
    },
    // ─── IMAGE TEMPLATES ──────────────────────────────────────────
    {
      id: 'image-photo-fox',
      type: 'image',
      category: 'photorealistic',
      title: 'Animal portrait, golden-hour photo',
      summary: 'A photorealistic animal portrait with cinematic lighting.',
      fields: {
        subject: 'A red fox curled asleep on a worn leather chair, paws tucked under chest',
        style: 'photorealistic, editorial photography',
        composition: 'close-up, low angle, shallow depth of field',
        medium: 'digital photograph',
        colorLighting: 'warm amber backlight, deep shadows, golden hour',
        cameraLens: '85mm, f/1.8',
        mood: 'quiet, contemplative',
        negative: 'no text, no watermark, no extra limbs',
        aspectRatio: '--ar 3:2 --v 6',
        advancedOpen: false,
      },
    },
    // ─── REVIEW TEMPLATES ─────────────────────────────────────────
    {
      id: 'review-code-diff',
      type: 'review',
      category: 'code-diffs',
      title: 'Code-diff review with severity',
      summary: 'Have the LLM grade a code diff against a fixed rubric with severity levels.',
      fields: {
        content: 'PASTE_DIFF_HERE',
        criteria: 'Correctness, readability, error handling, performance, test coverage.',
        audience: 'The engineer who authored the diff.',
        outputFormat: 'Markdown table with columns: Severity | Location | Issue | Suggested fix.',
        severity: 'blocker / major / minor / nit',
        tone: 'Direct but kind, no hedging.',
        examples: '',
        advancedOpen: false,
      },
    },
  ];
```

- [ ] **Step 2: Add `renderLibraryView`**

Add after `renderLintView`:

```js
  // ---- Library view ----
  function renderLibraryView() {
    const root = document.getElementById('view-library');
    if (!root) { return; }
    const type = state.currentType;
    const cats = libraryCategoriesFor(type);
    const search = (state.library.search || '').toLowerCase();
    const active = state.library.category || 'all';

    let chipsHtml = '<button type="button" class="ps-lib-chip' + (active === 'all' ? ' is-active' : '') + '" data-action="lib-cat" data-cat="all">All</button>';
    for (let i = 0; i < cats.length; i++) {
      chipsHtml += '<button type="button" class="ps-lib-chip' + (active === cats[i].key ? ' is-active' : '') + '" data-action="lib-cat" data-cat="' + escapeHtml(cats[i].key) + '">' + escapeHtml(cats[i].label) + '</button>';
    }

    const templates = TEMPLATES.filter(function (t) {
      if (t.type !== type) { return false; }
      if (active !== 'all') { if (t.category !== active) { return false; } }
      if (search) {
        const blob = (t.title + ' ' + t.summary).toLowerCase();
        if (blob.indexOf(search) === -1) { return false; }
      }
      return true;
    });

    let cardsHtml = '';
    if (templates.length === 0) {
      cardsHtml = '<p class="ps-stub">No templates match. Try clearing the search or category.</p>';
    } else {
      for (let i = 0; i < templates.length; i++) {
        const t = templates[i];
        cardsHtml += '<button type="button" class="ps-lib-card" data-action="lib-open" data-id="' + escapeHtml(t.id) + '">' +
          '<span class="ps-lib-card-stripe ps-lib-stripe-' + escapeHtml(t.type) + '"></span>' +
          '<span class="ps-lib-card-cat">' + escapeHtml(labelFor(cats, t.category)) + '</span>' +
          '<span class="ps-lib-card-title">' + escapeHtml(t.title) + '</span>' +
          '<span class="ps-lib-card-summary">' + escapeHtml(t.summary) + '</span>' +
        '</button>';
      }
    }

    root.innerHTML =
      '<div class="ps-library">' +
        '<div class="ps-lib-toolbar">' +
          '<div class="ps-lib-chips">' + chipsHtml + '</div>' +
          '<input type="search" id="ps-lib-search" placeholder="Search templates" value="' + escapeHtml(search).replace(/"/g, '&quot;') + '" aria-label="Search templates" />' +
        '</div>' +
        '<div class="ps-lib-grid">' + cardsHtml + '</div>' +
      '</div>';

    // If a template is open in the URL, render modal too
    if (state.library.openTemplateId) {
      const tmpl = TEMPLATES.find(function (t) { return t.id === state.library.openTemplateId; });
      if (tmpl) {
        if (tmpl.type === state.currentType) { openTemplateModal(tmpl); }
      }
    }
  }

  function libraryCategoriesFor(type) {
    if (type === 'write')  { return [ {key:'code', label:'Code'}, {key:'creative', label:'Creative / Long-form'}, {key:'strategy', label:'Strategy / Learning'} ]; }
    if (type === 'image')  { return [ {key:'photorealistic', label:'Photorealistic'}, {key:'illustration', label:'Illustration / Art'}, {key:'ui-design', label:'UI / Design Assets'} ]; }
    if (type === 'review') { return [ {key:'code-diffs', label:'Code / Diffs'}, {key:'copy-marketing', label:'Copy / Marketing'}, {key:'structural', label:'Structural / Documents'} ]; }
    return [];
  }
  function labelFor(cats, key) { for (let i = 0; i < cats.length; i++) { if (cats[i].key === key) { return cats[i].label; } } return key; }
```

- [ ] **Step 3: Wire Library actions and search input**

In the existing `input` handler, add a branch for the search field above the Builder field branch:

```js
    if (target.id === 'ps-lib-search') {
      state.library.search = target.value;
      // Only re-render the grid, not the input.
      const grid = root_querySafe('.ps-lib-grid');
      if (grid) {
        // Trigger a partial re-render by calling the renderer, which is cheap.
        // Save caret position before/after.
        const focused = document.activeElement;
        const caret = focused ? focused.selectionStart : null;
        renderLibraryView();
        const newFocused = document.getElementById('ps-lib-search');
        if (newFocused) { newFocused.focus(); if (caret != null) { newFocused.setSelectionRange(caret, caret); } }
      }
      return;
    }
```

And add `root_querySafe` helper somewhere above:

```js
  function root_querySafe(selector) { try { return document.querySelector(selector); } catch (e) { return null; } }
```

In the action `click` handler, add:

```js
    if (action === 'lib-cat') {
      state.library.category = btn.getAttribute('data-cat');
      renderLibraryView();
      return;
    }
    if (action === 'lib-open') {
      const id = btn.getAttribute('data-id');
      pushRoute(state.currentType, 'library', id);
      return;
    }
```

Add a stub `openTemplateModal` (real modal in Task 12):

```js
  function openTemplateModal(/* tmpl */) {
    // Real modal arrives in Task 12.
  }
```

- [ ] **Step 4: Add Library CSS**

Append:

```css
.prompt-studio .ps-library { display: flex; flex-direction: column; gap: var(--wp--preset--spacing--30); }
.prompt-studio .ps-lib-toolbar { display: flex; flex-direction: column; gap: 0.6rem; }
@media (min-width: 720px) { .prompt-studio .ps-lib-toolbar { flex-direction: row; justify-content: space-between; align-items: center; } }
.prompt-studio .ps-lib-chips { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.prompt-studio .ps-lib-chip { appearance: none; border: 1px solid var(--wp--preset--color--subtle); background: var(--wp--preset--color--background); color: var(--wp--preset--color--foreground); padding: 0.35rem 0.75rem; border-radius: 999px; cursor: pointer; font: inherit; font-size: var(--wp--preset--font-size--small); }
.prompt-studio .ps-lib-chip.is-active { background: var(--wp--preset--color--foreground); color: var(--wp--preset--color--background); border-color: var(--wp--preset--color--foreground); }
.prompt-studio .ps-lib-grid { display: grid; grid-template-columns: 1fr; gap: var(--wp--preset--spacing--30); }
@media (min-width: 721px) { .prompt-studio .ps-lib-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1024px) { .prompt-studio .ps-lib-grid { grid-template-columns: repeat(3, 1fr); } }
.prompt-studio .ps-lib-card { display: grid; grid-template-columns: 1fr; gap: 0.45rem; text-align: left; appearance: none; background: var(--wp--preset--color--surface-card, var(--wp--preset--color--background)); background-image: var(--wp--preset--gradient--surface-card, none); border: 1px solid var(--wp--preset--color--subtle); border-radius: 14px; padding: var(--wp--preset--spacing--30); cursor: pointer; font: inherit; color: var(--wp--preset--color--foreground); transition: transform 0.15s ease, box-shadow 0.15s ease; position: relative; overflow: hidden; }
.prompt-studio .ps-lib-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(0,0,0,0.1); }
.prompt-studio .ps-lib-card-stripe { position: absolute; top: 0; left: 0; right: 0; height: 5px; }
.prompt-studio .ps-lib-stripe-write { background: var(--wp--preset--color--primary); }
.prompt-studio .ps-lib-stripe-image { background: var(--wp--preset--color--accent); }
.prompt-studio .ps-lib-stripe-review { background: var(--wp--preset--color--hot); }
.prompt-studio .ps-lib-card-cat { font-size: var(--wp--preset--font-size--small); color: var(--wp--preset--color--muted); text-transform: uppercase; letter-spacing: 0.08em; }
.prompt-studio .ps-lib-card-title { font-weight: 700; font-size: var(--wp--preset--font-size--medium); }
.prompt-studio .ps-lib-card-summary { color: var(--wp--preset--color--muted); font-size: var(--wp--preset--font-size--small); }
.prompt-studio .ps-lib-toolbar input[type="search"] { padding: 0.5rem 0.85rem; border: 1px solid var(--wp--preset--color--subtle); border-radius: 999px; background: var(--wp--preset--color--background); color: var(--wp--preset--color--foreground); font: inherit; min-width: 14rem; }
```

- [ ] **Step 5: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
```
Reload, switch to Library tab on Write → one card "PR review request". Switch to Image → one card "Animal portrait, golden-hour photo". Review → one card "Code-diff review with severity". Type "fox" into search on Image → card visible; type "kitten" → "No templates match." Click a card → URL gains `?t=image-photo-fox` (modal not yet rendered — Task 12).

```
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```
Expected: `0`

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): add Library view with category chips and search"
```

---

### Task 12: Template preview modal with focus trap

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Replace `openTemplateModal` stub with the real modal**

Replace the placeholder:

```js
  // Module-scope so close handlers can find it
  let modalCachedFocus = null;
  let modalKeyHandler = null;

  function openTemplateModal(tmpl) {
    const existing = document.getElementById('ps-modal-root');
    if (existing) { existing.remove(); }

    modalCachedFocus = document.activeElement;

    const assembled = assemblePrompt(tmpl.type, tmpl.fields).text;
    const cats = libraryCategoriesFor(tmpl.type);
    const catLabel = labelFor(cats, tmpl.category);

    const root = document.createElement('div');
    root.id = 'ps-modal-root';
    root.className = 'ps-modal-root';
    root.innerHTML =
      '<div class="ps-modal-backdrop" data-action="modal-close"></div>' +
      '<div class="ps-modal" role="dialog" aria-modal="true" aria-labelledby="ps-modal-title">' +
        '<header class="ps-modal-header">' +
          '<div>' +
            '<span class="ps-modal-cat">' + escapeHtml(catLabel) + '</span>' +
            '<h2 id="ps-modal-title">' + escapeHtml(tmpl.title) + '</h2>' +
            '<p>' + escapeHtml(tmpl.summary) + '</p>' +
          '</div>' +
          '<button type="button" class="ps-modal-close" data-action="modal-close" aria-label="Close">×</button>' +
        '</header>' +
        '<pre class="ps-modal-prompt" aria-label="Assembled prompt">' + escapeHtml(assembled) + '</pre>' +
        '<footer class="ps-modal-actions">' +
          '<button type="button" data-action="modal-copy" data-id="' + escapeHtml(tmpl.id) + '">Copy</button>' +
          '<button type="button" data-action="modal-use" data-id="' + escapeHtml(tmpl.id) + '">Use as starting point</button>' +
        '</footer>' +
      '</div>';
    document.body.appendChild(root);

    // Focus first interactive element
    const firstFocusable = root.querySelector('.ps-modal-close');
    if (firstFocusable) { firstFocusable.focus(); }

    // Trap Tab inside the modal
    modalKeyHandler = function (ev) {
      if (ev.key === 'Escape') {
        ev.preventDefault();
        closeTemplateModal();
        return;
      }
      if (ev.key !== 'Tab') { return; }
      const focusables = root.querySelectorAll('button');
      if (focusables.length === 0) { return; }
      const first = focusables[0];
      const last = focusables[focusables.length - 1];
      if (ev.shiftKey) {
        if (document.activeElement === first) {
          ev.preventDefault();
          last.focus();
        }
      } else {
        if (document.activeElement === last) {
          ev.preventDefault();
          first.focus();
        }
      }
    };
    document.addEventListener('keydown', modalKeyHandler);
  }

  function closeTemplateModal() {
    const root = document.getElementById('ps-modal-root');
    if (root) { root.remove(); }
    if (modalKeyHandler) {
      document.removeEventListener('keydown', modalKeyHandler);
      modalKeyHandler = null;
    }
    // Restore focus to the element that opened the modal
    if (modalCachedFocus) {
      try { modalCachedFocus.focus(); } catch (e) { /* element gone */ }
      modalCachedFocus = null;
    }
    // Strip the ?t= from the URL via replaceState (modal close should not add history)
    if (state.library.openTemplateId) {
      state.library.openTemplateId = null;
      const newHash = buildHash(state.currentType, state.currentMode, null);
      history.replaceState(null, '', newHash);
    }
  }
```

- [ ] **Step 2: Wire modal actions**

In the action `click` handler add:

```js
    if (action === 'modal-close') {
      closeTemplateModal();
      return;
    }
    if (action === 'modal-copy') {
      const id = btn.getAttribute('data-id');
      const tmpl = TEMPLATES.find(function (t) { return t.id === id; });
      if (!tmpl) { return; }
      const assembled = assemblePrompt(tmpl.type, tmpl.fields).text;
      copyToClipboard(assembled);
      closeTemplateModal();
      return;
    }
    if (action === 'modal-use') {
      const id = btn.getAttribute('data-id');
      const tmpl = TEMPLATES.find(function (t) { return t.id === id; });
      if (!tmpl) { return; }
      if (isDraftDirty(tmpl.type)) {
        const ok = confirm('Replace your current ' + capitalize(tmpl.type) + ' draft with this template?');
        if (!ok) { return; }
      }
      const draft = state.drafts[tmpl.type];
      for (const k in tmpl.fields) {
        if (k in draft) { draft[k] = tmpl.fields[k]; }
      }
      draft.advancedOpen = false;
      scheduleSave();
      closeTemplateModal();
      pushRoute(tmpl.type, 'build', null);
      return;
    }
```

- [ ] **Step 3: Add modal CSS**

Append:

```css
.ps-modal-root { position: fixed; inset: 0; z-index: 9000; display: flex; align-items: center; justify-content: center; padding: 0; }
.ps-modal-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.5); }
.ps-modal { position: relative; background: var(--wp--preset--color--surface); color: var(--wp--preset--color--foreground); border-radius: 0; max-width: none; width: 100%; height: 100%; padding: var(--wp--preset--spacing--40); overflow: auto; display: flex; flex-direction: column; gap: var(--wp--preset--spacing--20); }
@media (min-width: 1024px) {
  .ps-modal { max-width: 720px; width: auto; height: auto; max-height: 80vh; border-radius: 18px; }
}
.ps-modal-header { display: flex; justify-content: space-between; align-items: start; gap: 0.75rem; }
.ps-modal-cat { font-size: var(--wp--preset--font-size--small); color: var(--wp--preset--color--muted); text-transform: uppercase; letter-spacing: 0.08em; }
.ps-modal-header h2 { margin: 0.2rem 0; }
.ps-modal-header p { margin: 0; color: var(--wp--preset--color--muted); }
.ps-modal-close { appearance: none; border: none; background: transparent; font-size: 1.6rem; line-height: 1; cursor: pointer; color: var(--wp--preset--color--foreground); padding: 0 0.5rem; }
.ps-modal-prompt { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.9rem; background: var(--wp--preset--color--background); padding: var(--wp--preset--spacing--30); border-radius: 10px; white-space: pre-wrap; word-break: break-word; flex: 1 1 auto; overflow: auto; }
.ps-modal-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.ps-modal-actions button { appearance: none; border: 1px solid var(--wp--preset--color--primary); background: var(--wp--preset--color--primary); color: var(--wp--preset--color--background); padding: 0.55rem 1rem; border-radius: 999px; cursor: pointer; font: inherit; }
.ps-modal-actions button:first-child { background: transparent; color: var(--wp--preset--color--foreground); border-color: var(--wp--preset--color--subtle); }
```

- [ ] **Step 4: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
```
Reload, go to Library. Click a card → modal opens with title, summary, monospace assembled prompt, Copy + Use-as-starting-point buttons. URL contains `?t=<id>`.
- ESC closes the modal, focus returns to the card that opened it. URL strips `?t=`.
- Tab inside the modal cycles between Close → Copy → Use → Close.
- Copy → toast "Copied.", clipboard contains the assembled prompt, modal closes.
- Use → if no draft dirty, switches to Build with template fields filled. If dirty, confirm dialog.
- Direct-visit `…/prompt-studio/#image/library?t=image-photo-fox` → page loads with Image/Library and the modal open.

```
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```
Expected: `0`

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): add template preview modal with focus trap"
```

---

## Phase 4b — Template content authoring

Goal: replace the 3 stub templates with 27 fully written, high-quality templates spanning all 9 cells of the type × category grid. Each authoring task delivers 3 templates for one cell.

**Authoring contract** (applies to every template object below):

- Every field listed in the type's `RUBRICS.visible` must be a non-empty, realistic prompt string. Empty visible fields produce a weak template and a low Lint score when "Use as starting point" then "Lint this" is exercised.
- Advanced fields are filled where they add meaningful value, left as `''` otherwise.
- `id` format: `<type>-<category-short>-<slug>`. Category short forms used in IDs: write → `code`, `creative`, `strategy`; image → `photo`, `illust`, `ui`; review → `code`, `copy`, `doc`.
- `category` matches the `key` value from `libraryCategoriesFor` exactly (`'code'`, `'creative'`, `'strategy'`, `'photorealistic'`, `'illustration'`, `'ui-design'`, `'code-diffs'`, `'copy-marketing'`, `'structural'`).
- `summary` is one sentence, < 90 chars.
- `title` is < 38 chars so it doesn't truncate in the card.

Each Task 13–21 adds three templates to the `TEMPLATES` array in the appropriate banner-commented section. Replace stub entries from Task 11 with the first authored entry of the matching cell — the final array has exactly 27 entries, three per cell.

---

### Task 13: Write / Code templates (3)

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Replace the Write stub and add two more under the `WRITE TEMPLATES` banner**

Inside `TEMPLATES`, in the `WRITE TEMPLATES` section, ensure these three entries exist (the first replaces the existing stub):

Templates to author (titles + summaries are concrete; fields follow the contract above):

1. `write-code-pr-review` — "PR review request" — already authored in Task 11 stub. Keep as-is.
2. `write-code-refactor` — "Refactor with constraints" — refactor a function to remove duplication without changing its public signature. Output: unified diff. Constraints: preserve all public behavior, no formatter-only changes. Audience: the reviewer reading the next PR.
3. `write-code-test-gen` — "Generate unit tests" — generate Vitest/Jest-style tests for a pasted function. Output: code block only, no commentary. Constraints: cover the happy path, two edge cases, and one error case. Audience: future maintainers.

Author the two new objects following the exact shape used in Task 11's `write-code-pr-review`. Each must populate Role, Task, Context, Output Format, and Constraints with realistic strings.

- [ ] **Step 2: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
```
Reload Library on Write. Code chip filter → three cards. Open each → assembled prompt reads as a real prompt, not placeholder text.

```
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```
Expected: `0`

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): author Write/Code templates (3)"
```

---

### Task 14: Write / Creative templates (3)

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Add three creative-writing templates**

Append to the `WRITE TEMPLATES` section:

1. `write-creative-blog-outline` — "Blog post outline" — outline a 1200-word blog post on a topic the user supplies. Output: H2/H3 structure with a one-line hook per section. Audience: a curious technical reader.
2. `write-creative-rewrite-voice` — "Rewrite in a target voice" — rewrite a passage in a target voice (e.g. "Patrick McKenzie", "ship logbook", "academic abstract"). Output: rewritten passage only. Constraints: keep all facts, drop nothing material.
3. `write-creative-fiction-scene` — "Short fiction scene" — write a 300-400 word scene with a stated setting, two characters, and one tension. Output: prose only. Constraints: no exposition dumps, no genre clichés.

Follow the same field-population contract.

- [ ] **Step 2: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```
Expected: clean lint + `0`. Reload, Write Library, Creative chip → three cards.

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): author Write/Creative templates (3)"
```

---

### Task 15: Write / Strategy templates (3)

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Add three strategy / learning templates**

1. `write-strategy-eli12` — "Explain like I'm 12" — explain a complex topic to a 12-year-old without dumbing down the key insight. Output: 4-6 short paragraphs with one analogy.
2. `write-strategy-decision-memo` — "Decision memo" — produce a 1-page decision memo on a proposal: context, options considered, recommendation, risks, what would change the recommendation. Output: markdown, ~400 words.
3. `write-strategy-study-plan` — "30-day study plan" — produce a 30-day study plan for a stated skill at a stated starting level. Output: table with day, focus, deliverable, ~30-min check.

- [ ] **Step 2: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): author Write/Strategy templates (3)"
```

---

### Task 16: Image / Photorealistic templates (3)

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Replace the Image stub and add two more under `IMAGE TEMPLATES`**

1. `image-photo-fox` — "Animal portrait, golden-hour" — already authored in Task 11 stub. Keep as-is.
2. `image-photo-street` — "Street photography candid" — a candid street photo with a stated subject and time of day. Camera/Lens: 35mm, f/2.8. Composition: leading lines, decisive moment.
3. `image-photo-product` — "Product hero shot" — a product on a plain backdrop with controlled studio light. Composition: 3/4 angle. Negative: no logos, no reflections.

- [ ] **Step 2: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): author Image/Photorealistic templates (3)"
```

---

### Task 17: Image / Illustration templates (3)

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Add three illustration / art templates**

1. `image-illust-cover` — "Book cover illustration" — book cover with a stated title-area composition and genre style. Medium: digital paint. Mood: state explicitly.
2. `image-illust-character` — "Character concept sheet" — single character on a neutral background, full body, 3/4 turn. Style: anime-influenced, soft palette. Negative: no extra limbs, no text.
3. `image-illust-pattern` — "Repeating decorative pattern" — tileable decorative pattern with a stated motif and 4-color palette. Composition: seamless tile, no central focal point.

- [ ] **Step 2: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): author Image/Illustration templates (3)"
```

---

### Task 18: Image / UI-Design templates (3)

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Add three UI / design-asset templates**

1. `image-ui-app-icon` — "App icon, flat" — square app icon with one focal motif, flat colors, no shadows. Aspect: `--ar 1:1`. Negative: no text, no gradients beyond flat.
2. `image-ui-hero-bg` — "Hero background, abstract" — abstract gradient mesh for a marketing hero, no text, gentle motion implied. Aspect: `--ar 16:9`.
3. `image-ui-spot-illust` — "Spot illustration for an empty state" — single small object with one accent color on a transparent or off-white background. Mood: friendly, low-contrast.

- [ ] **Step 2: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): author Image/UI-Design templates (3)"
```

---

### Task 19: Review / Code-diffs templates (3)

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Replace the Review stub and add two more under `REVIEW TEMPLATES`**

1. `review-code-diff` — "Code-diff review with severity" — already authored in Task 11 stub. Keep as-is.
2. `review-code-test-gaps` — "Test coverage gap review" — review a diff focused only on what tests are missing. Output: bulleted list of cases that should be tested. Constraints: do not propose code, only the cases.
3. `review-code-readability` — "Readability pass" — review code for readability only (naming, complexity, structure). Output: ranked list of changes with rationale.

- [ ] **Step 2: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): author Review/Code-diffs templates (3)"
```

---

### Task 20: Review / Copy-Marketing templates (3)

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Add three copy / marketing review templates**

1. `review-copy-landing` — "Landing-page copy review" — review landing copy against a stated value-prop and audience. Output: section-by-section call-out with rewrite suggestions.
2. `review-copy-voice-match` — "Voice consistency check" — compare a draft against a reference voice sample. Output: list of phrases that drift, plus a one-line fix each.
3. `review-copy-call-to-action` — "CTA conversion review" — review the CTAs on a page. Output: per-CTA verdict (keep / sharpen / drop) with reasoning.

- [ ] **Step 2: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): author Review/Copy-Marketing templates (3)"
```

---

### Task 21: Review / Structural templates (3)

**Files:**
- Modify: `patterns/demo-prompt-studio.php`

- [ ] **Step 1: Add three structural / document review templates**

1. `review-doc-spec` — "Spec gap review" — review a design spec for ambiguity, missing acceptance criteria, and unstated assumptions. Output: numbered list of gaps with severity.
2. `review-doc-resume` — "Resume review" — review a resume against a stated target role. Output: per-bullet verdict with one-line rewrite.
3. `review-doc-readme` — "README review" — review a project README for first-time-reader friction. Output: ordered list of changes to make in the first 10 minutes of reading.

- [ ] **Step 2: Verify, curl-grep, commit**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```

Final TEMPLATES count check — open the file and count entries between `// ─── WRITE` / `// ─── IMAGE` / `// ─── REVIEW` / closing bracket. Each section should have exactly 9 entries (27 total).

```bash
git add patterns/demo-prompt-studio.php
git commit -m "feat(prompt-studio): author Review/Structural templates (3) — 27 total"
```

---

## Phase 5 — Hardening

Goal: enforce every acceptance criterion in the spec, walk the demo on a small viewport, exercise keyboard nav end to end, and run the final entity-encoding sweep.

---

### Task 22: Mobile portrait sweep (375 × 667)

**Files:**
- Modify: `patterns/demo-prompt-studio.php` (only if issues are found)

- [ ] **Step 1: Open DevTools device toolbar at 375 × 667 (iPhone SE) and walk every view**

Reload `http://localhost/dariobimbi.com/demos/prompt-studio/` in a private window with DevTools open and the device toolbar at iPhone SE.

Checklist:
- Type chips stack full-width.
- Mode tabs fit on one line or scroll horizontally without breaking layout.
- Build form is single column. Textareas don't blow past the viewport edge.
- Assembled prompt preview pane is stacked below the form (grid drops to one column).
- Lint view textarea fits within the viewport; Analyze button is reachable.
- Library grid is 1 column; cards full-width.
- Modal goes full-screen (no centered-dialog at this width).
- Modal Copy and Use buttons are reachable above the keyboard.

- [ ] **Step 2: Fix any layout issues found**

If anything overflows, breaks, or overlaps, fix the CSS inline in the pattern file. Common fixes:
- Add `min-width: 0` to a grid item that won't shrink.
- Add `overflow-x: auto` to a section that horizontally overflows.
- Add `word-break: break-word` to a long monospace block.

- [ ] **Step 3: Verify, curl-grep, commit (if changes made)**

If no fixes were needed, skip the commit. Otherwise:

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```

```bash
git add patterns/demo-prompt-studio.php
git commit -m "fix(prompt-studio): mobile portrait layout adjustments"
```

---

### Task 23: Keyboard accessibility sweep

**Files:**
- Modify: `patterns/demo-prompt-studio.php` (only if issues are found)

- [ ] **Step 1: Walk the page with keyboard only**

Reload. Click nothing — use only Tab, Shift+Tab, Enter, Space, arrow keys, Esc.

Checklist:
- Tab into the type chip bar → first chip focused.
- Right arrow cycles to next chip (URL updates), Left arrow goes back. Reaches all three.
- Tab → enters mode tabs. Right/Left arrows cycle modes.
- Tab → first field of Build form. Each field reachable in DOM order.
- Tab → Show advanced toggle. Enter expands; Tab continues into newly visible advanced fields.
- Tab → Copy / Lint this / Reset draft buttons.
- Switch to Lint mode, Tab into the textarea, type, Tab → chip row, Tab → Analyze.
- Switch to Library mode, Tab → category chips, Tab → search, Tab → cards. Enter on a card opens the modal.
- In modal, focus lands on Close. Tab cycles: Close → Copy → Use → Close. Shift+Tab cycles backwards.
- Esc closes modal, focus returns to the card that opened it (not the top of the page).

- [ ] **Step 2: Fix any focus issues**

If focus escapes the modal, the trap helper needs adjustment. If focus does not return to the card, the cached-element restore failed (check `modalCachedFocus`).

- [ ] **Step 3: Verify and commit (only if changes made)**

```
c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```

```bash
git add patterns/demo-prompt-studio.php
git commit -m "fix(prompt-studio): keyboard accessibility adjustments"
```

---

### Task 24: Final acceptance walkthrough

**Files:** none modified in this task (audit only). If any criterion fails, return to the relevant earlier task.

- [ ] **Step 1: Walk the spec's 13 acceptance criteria one by one**

For each item below, confirm by manual test:

1. `/demos/prompt-studio/` loads with Write/Build view by default on a fresh visit (clear localStorage in DevTools first).
2. All nine view combinations render correctly (3 types × 3 modes).
3. Direct deep links `#image/library`, `#write/library?t=write-code-pr-review`, `#review/build` load directly into the matching view.
4. Build drafts persist across reload for all three types.
5. Build → Lint round trip works for all three types (type text into Build, click Lint this, see the text in Lint).
6. Lint → Build parse-back works for structured prompts; unstructured prompts trigger the amber 5-second toast.
7. Library → Build "Use as starting point" loads template fields and switches modes, with confirm-if-dirty.
8. All 27 templates render in the grid and modal (3 per cell × 9 cells).
9. `curl -s …/prompt-studio/ | grep -c '&#038;&#038;'` returns `0`.
10. Demo is usable on 375 × 667 (iPhone SE) — verified in Task 22.
11. Keyboard nav reaches every interactive element; ESC closes modal and restores focus — verified in Task 23.
12. Full-bleed surface background paints edge to edge with no theme.json bleed at viewport edges.
13. Theme `Version:` bumped (`0.18.0`); admin reconciliation created the page automatically.

- [ ] **Step 2: If anything fails, file a follow-up task and return to the relevant earlier task to fix**

Do not commit a partial pass. The whole acceptance list must clear before merge.

- [ ] **Step 3: Final `curl | grep` from a clean browser session**

```
curl -s "http://localhost/dariobimbi.com/demos/prompt-studio/" | grep -c "&#038;&#038;"
```
Expected: `0`

- [ ] **Step 4: Confirm theme version + commit summary**

```
grep "Version:" style.css
```
Expected: `Version: 0.18.0`

Review `git log --oneline` since branching:

```
git log --oneline 114b9da..HEAD
```

Expected: roughly 22 commits, each one task from this plan, sequential and self-contained.

- [ ] **Step 5: Acceptance complete**

No commit. This task is a gate, not a change.

---

## Self-review

I walked the spec section by section and matched each requirement to a task:

- **§1 Two-level navigation** → Task 2 (chips/tabs in markup) + Task 3 (router + click handlers + keyboard).
- **§2 State model** → Task 3 (state object) + Task 7 (localStorage). DOM-input-write rule (Task 5: `refreshPreview` only re-renders preview, not inputs).
- **§3 Routing** → Task 3 (idempotent popstate router, click handlers only push hash).
- **§4 Build mode** → Task 4 (rubrics + form rendering), Task 5 (preview + token meter with Review split), Task 6 (Copy / Lint-this / Reset), Task 7 (persistence).
- **§5 Lint mode** → Task 8 (input + detection + chips), Task 9 (score ring + checklist + anti-patterns), Task 10 (parse-back + amber toast + confirm-if-dirty).
- **§6 Library mode** → Task 11 (categories + grid + search + stub templates), Task 12 (modal + focus trap), Tasks 13–21 (27 authored templates).
- **§7 Rendering approach** → Task 3 (single `render` dispatch), Task 4 (focus-preserving input handler).
- **§8 Inline-script discipline** → Every JS-touching task ends with the `curl | grep` check. No `&&` used in any code block in this plan.
- **§9 File layout / WordPress wiring** → Task 1.
- **§10 Full-bleed background quirk** → Task 2 (body class + `!important` CSS).
- **§11 Accessibility** → Tasks 2 / 3 / 4 (roles, aria-selected, aria-describedby, aria-expanded), Task 12 (modal trap + restore), Task 23 (audit).
- **§12 Responsive breakpoints** → CSS in Tasks 2, 4, 11, 12; audit in Task 22.
- **§13 Visual treatment** → CSS in Tasks 2 / 4 / 8 / 9 / 11 / 12.
- **Acceptance §1–§13** → Task 24 walks all 13 criteria.

No gaps. No placeholders. Field-name consistency holds: `outputFormat` (camelCase) used in RUBRICS, drafts, parse-back, and templates. `colorLighting`, `cameraLens`, `aspectRatio` used consistently across Image rubric, assembler, and parser. The Review draft uses `content`, `criteria`, `audience`, `outputFormat`, `severity`, `tone`, `examples` consistently.

Theme version `0.18.0` referenced consistently in Tasks 1 and 24.

Plan complete and saved to `docs/superpowers/plans/2026-05-22-prompt-studio.md`.
