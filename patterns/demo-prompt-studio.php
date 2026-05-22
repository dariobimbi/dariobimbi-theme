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

.ps-toast-host { position: fixed; right: 1rem; bottom: 1rem; display: flex; flex-direction: column; gap: 0.5rem; z-index: 9999; }
.ps-toast { background: var(--wp--preset--color--foreground); color: var(--wp--preset--color--background); padding: 0.65rem 1rem; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.2); max-width: 22rem; font-size: var(--wp--preset--font-size--small); }
.ps-toast-warn { background: var(--wp--preset--color--accent); }
</style>

<script>
(function () {
  'use strict';

  document.body.classList.add('has-prompt-studio-page');

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
  const PLACEHOLDER_VARIANTS = {};
  // Picks one placeholder set per page load. Field renderer reads from RUBRICS
  // directly today, so cycling here just means choosing a stable variant once
  // on init for cosmetic freshness. Future variants can replace these.
  function pickPlaceholders() {
    // Intentionally a no-op for v1: RUBRICS placeholders are stable strings.
    // Wired through so we can add per-field arrays of examples later without
    // touching renderField.
  }
  pickPlaceholders();

  // ---- Toast ----
  function showToast(message, kind) {
    const k = kind ? kind : 'info';
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
    const rawHash = location.hash ? location.hash : '';
    const raw = rawHash.replace(/^#/, '');
    if (!raw) { return { type:null, mode:null, templateId:null }; }
    const [path, query] = raw.split('?');
    const safePath = path ? path : '';
    const parts = safePath.split('/');
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
    // Avoid || here — WordPress entity-encoding inside inline <script> can
    // mangle it the same way it mangles &&. Use explicit ternary checks.
    const targetType = type !== null ? type : state.currentType;
    const targetMode = mode !== null ? mode : state.currentMode;
    const targetTemplateId = templateId !== null ? templateId : null;
    const newHash = buildHash(targetType, targetMode, targetTemplateId);
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
    if (state.currentMode === 'build') { renderBuildView(); }
  }

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
    const placeholder = escapeHtml(field.placeholder).replace(/"/g, '&quot;');
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
  function scheduleSave()  { /* Task 7 fills this in */ }

  // ---- Init ----
  routeFromHash();
})();
</script>
