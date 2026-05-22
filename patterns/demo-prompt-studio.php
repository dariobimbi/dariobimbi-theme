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
    const commaMatches = text.match(/,/g);
    const commaCount = commaMatches ? commaMatches.length : 0;
    const sentenceMatches = text.match(/[.!?]/g);
    const sentenceCount = sentenceMatches ? sentenceMatches.length : 0;
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
    scheduleSave();
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
    if (state.currentMode === 'build')   { renderBuildView(); }
    if (state.currentMode === 'lint')    { renderLintView(); }
    if (state.currentMode === 'library') { renderLibraryView(); }
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

  // ---- Lint view ----
  function renderLintView() {
    const root = document.getElementById('view-lint');
    if (!root) { return; }
    const inputValue = state.lint.input ? state.lint.input : '';
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
      let icon;
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

  function renderLibraryView() { /* Task 11 fills this in */ }

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
  });

  function resetDraft(type) {
    const fresh = {
      write:  { role:'', task:'', context:'', outputFormat:'', constraints:'', examples:'', audience:'', tone:'', advancedOpen:false },
      image:  { subject:'', style:'', composition:'', medium:'', colorLighting:'', cameraLens:'', mood:'', negative:'', aspectRatio:'', advancedOpen:false },
      review: { content:'', criteria:'', audience:'', outputFormat:'', severity:'', tone:'', examples:'', advancedOpen:false },
    };
    state.drafts[type] = fresh[type];
  }

  function refineFromLint() {
    const text = state.lint.input ? state.lint.input : '';
    const type = state.lint.detectedType ? state.lint.detectedType : detectPromptType(text);
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
    if (!fencedMatch) {
      if (matched === 0) {
        fields.content = text.trim();
        return { fields: fields, lossy: true };
      }
    }
    return { fields: fields, lossy: false };
  }

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
    { id:'soft_verb',      appliesTo:['write'], label:'Soft task verb', detail:'Rewrite "help me with..." or "I want..." as an imperative.', test: function (t) { return /(help me (with|to)|i want|i need|can you|could you)/i.test(t); } },
    { id:'dont_bias',      appliesTo:['write'], label:'"Don\'t" bias', detail:'LLMs follow affirmative instructions better than negatives. Pair "don\'t X" with "instead, do Y".', test: function (t) {
        const dontMatches = t.match(/\b(do not|don'?t|never|avoid)\b/gi);
        const dontCount = dontMatches ? dontMatches.length : 0;
        if (dontCount < 2) { return false; }
        const insteadMatches = t.match(/\b(instead|rather|prefer)\b/gi);
        const insteadCount = insteadMatches ? insteadMatches.length : 0;
        return insteadCount === 0;
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

  // ---- Lint runner ----
  function runLint() {
    const rawInput = state.lint.input;
    const text = rawInput ? rawInput : '';
    const rawType = state.lint.detectedType;
    const type = rawType ? rawType : detectPromptType(text);
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

  // ---- Init ----
  // Order matters: load storage first (defaults for type/mode), then routeFromHash()
  // — the URL hash wins over storage per spec §2.
  loadFromStorage();
  routeFromHash();
})();
</script>
