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

<div class="ps-card">

  <header class="ps-card-head">
    <p class="eyebrow">demo</p>
    <h1 class="ps-title has-gradient-text">Prompt Studio</h1>
    <p class="lede">Build a prompt from structured fields, audit a prompt you already have, or start from a template. Drafts stay in this browser.</p>
  </header>

  <hr class="ps-divider" aria-hidden="true">

  <div class="ps-toolbar">
    <div class="ps-type-bar" role="tablist" aria-label="Prompt type">
      <button type="button" class="ps-type-chip is-active" role="tab" data-type="write" aria-selected="true">
        <span class="ps-type-label">Write</span>
        <span class="ps-type-sub">Draft text</span>
      </button>
      <button type="button" class="ps-type-chip" role="tab" data-type="image" aria-selected="false">
        <span class="ps-type-label">Image</span>
        <span class="ps-type-sub">Visuals</span>
      </button>
      <button type="button" class="ps-type-chip" role="tab" data-type="review" aria-selected="false">
        <span class="ps-type-label">Review</span>
        <span class="ps-type-sub">Audit a prompt</span>
      </button>
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

</div>
<!-- /wp:group -->

<style>
body.has-prompt-studio-page { background-color: var(--wp--preset--color--surface) !important; }

.prompt-studio { color: var(--wp--preset--color--foreground); }
.prompt-studio .eyebrow { text-transform: uppercase; letter-spacing: 0.18em; font-size: var(--wp--preset--font-size--small); color: var(--wp--preset--color--muted); margin: 0; }
.prompt-studio .lede { color: var(--wp--preset--color--muted); max-width: 60ch; margin: 0; }

.prompt-studio .ps-card {
  background: var(--wp--preset--color--surface);
  border: 1px solid var(--wp--preset--color--subtle);
  border-radius: 22px;
  padding: clamp(1.25rem, 3vw, 2.25rem);
  display: flex;
  flex-direction: column;
  gap: var(--wp--preset--spacing--30);
  max-width: 1100px;
  margin: 0 auto;
  box-shadow: 0 10px 32px rgba(0, 0, 0, 0.18);
}
.prompt-studio .ps-card-head { display: flex; flex-direction: column; gap: 0.4rem; }
.prompt-studio .ps-card-head .ps-title { margin: 0; font-size: clamp(1.75rem, 4vw, 2.5rem); line-height: 1.15; }
.prompt-studio .ps-divider { border: none; border-top: 1px solid var(--wp--preset--color--subtle); margin: 0; }

.prompt-studio .ps-toolbar { display: flex; flex-direction: column; gap: var(--wp--preset--spacing--20); }

/* Type tabs: bigger cards with title + subtitle */
.prompt-studio .ps-type-bar { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.5rem; }
.prompt-studio .ps-type-chip {
  appearance: none;
  border: 1px solid var(--wp--preset--color--subtle);
  background: var(--wp--preset--color--background);
  color: var(--wp--preset--color--foreground);
  padding: 0.7rem 0.95rem;
  border-radius: 14px;
  font: inherit;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.1rem;
  text-align: left;
  transition: border-color 0.15s ease, background-color 0.15s ease, transform 0.1s ease;
}
.prompt-studio .ps-type-chip:hover { border-color: var(--wp--preset--color--primary); }
.prompt-studio .ps-type-chip:active { transform: translateY(1px); }
.prompt-studio .ps-type-label { font-weight: 700; font-size: var(--wp--preset--font-size--medium); }
.prompt-studio .ps-type-sub { color: var(--wp--preset--color--muted); font-size: var(--wp--preset--font-size--small); }
.prompt-studio .ps-type-chip.is-active .ps-type-sub { color: inherit; opacity: 0.85; }
.prompt-studio .ps-type-chip.is-active[data-type="write"]  { background: var(--wp--preset--color--primary); color: var(--wp--preset--color--background); border-color: var(--wp--preset--color--primary); }
.prompt-studio .ps-type-chip.is-active[data-type="image"]  { background: var(--wp--preset--color--accent);  color: var(--wp--preset--color--background); border-color: var(--wp--preset--color--accent);  }
.prompt-studio .ps-type-chip.is-active[data-type="review"] { background: var(--wp--preset--color--hot);     color: var(--wp--preset--color--background); border-color: var(--wp--preset--color--hot);     }

/* Mode tabs: thinner row with active underline */
.prompt-studio .ps-mode-bar { display: flex; gap: 0.25rem; border-bottom: 1px solid var(--wp--preset--color--subtle); flex-wrap: wrap; }
.prompt-studio .ps-mode-tab {
  appearance: none;
  border: none;
  background: transparent;
  color: var(--wp--preset--color--muted);
  padding: 0.55rem 0.95rem;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  border-radius: 0;
}
.prompt-studio .ps-mode-tab:hover { color: var(--wp--preset--color--foreground); }
.prompt-studio .ps-mode-tab.is-active {
  color: var(--wp--preset--color--foreground);
  border-bottom-color: var(--wp--preset--color--foreground);
}

/* View panel: now inside the card, no border of its own */
.prompt-studio .ps-view { background: transparent; border: none; border-radius: 0; padding: 0; }
.prompt-studio .ps-view[hidden] { display: none; }
.prompt-studio .ps-stub { color: var(--wp--preset--color--muted); font-style: italic; margin: 0; }

@media (max-width: 540px) {
  .prompt-studio .ps-type-bar { grid-template-columns: 1fr; }
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

.prompt-studio .ps-advanced-toggle {
  align-self: flex-start;
  appearance: none;
  background: var(--wp--preset--color--background);
  border: 1px solid var(--wp--preset--color--subtle);
  color: var(--wp--preset--color--foreground);
  padding: 0.55rem 1rem;
  border-radius: 12px;
  cursor: pointer;
  font: inherit;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: border-color 0.15s ease, background-color 0.15s ease, transform 0.1s ease;
}
.prompt-studio .ps-advanced-toggle:hover { border-color: var(--wp--preset--color--primary); background: var(--wp--preset--color--surface); }
.prompt-studio .ps-advanced-toggle:active { transform: translateY(1px); }
.prompt-studio .ps-advanced-toggle .ps-chev { display: inline-flex; align-items: center; transition: transform 0.2s ease; opacity: 0.8; }
.prompt-studio .ps-advanced-toggle .ps-chev svg { width: 12px; height: 9px; display: block; }
.prompt-studio .ps-advanced-toggle[aria-expanded="true"] .ps-chev { transform: rotate(180deg); }
.prompt-studio .ps-advanced { display: flex; flex-direction: column; gap: var(--wp--preset--spacing--30); }
.prompt-studio .ps-advanced[hidden] { display: none; }

/* Custom combobox: full-width animated dropdown panel for suggestion fields. */
.prompt-studio .ps-combo { position: relative; }
.prompt-studio .ps-combo input { padding-right: 2.6rem; }
.prompt-studio .ps-combo-toggle {
  position: absolute;
  right: 0.45rem;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: var(--wp--preset--color--muted);
  cursor: pointer;
  padding: 0;
  width: 2rem;
  height: 2rem;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease, color 0.15s ease, background-color 0.15s ease;
}
.prompt-studio .ps-combo-toggle svg { width: 14px; height: 10px; display: block; }
.prompt-studio .ps-combo-toggle:hover { color: var(--wp--preset--color--foreground); background: var(--wp--preset--color--subtle); }
.prompt-studio .ps-combo[data-open="true"] .ps-combo-toggle { transform: translateY(-50%) rotate(180deg); color: var(--wp--preset--color--primary); }
.prompt-studio .ps-combo input:focus + .ps-combo-toggle { color: var(--wp--preset--color--primary); }

.prompt-studio .ps-combo-panel {
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 6px);
  background: var(--wp--preset--color--surface);
  border: 1px solid var(--wp--preset--color--primary);
  border-radius: 12px;
  box-shadow: 0 14px 36px rgba(0, 0, 0, 0.4);
  z-index: 20;
  max-height: 18rem;
  overflow-y: auto;
  padding: 0.35rem;
  opacity: 0;
  transform: translateY(-6px);
  transition: opacity 0.18s ease, transform 0.18s ease;
  pointer-events: none;
}
.prompt-studio .ps-combo[data-open="true"] .ps-combo-panel {
  opacity: 1;
  transform: translateY(0);
  pointer-events: auto;
}
.prompt-studio .ps-combo-option {
  display: block;
  width: 100%;
  text-align: left;
  background: transparent;
  border: none;
  color: var(--wp--preset--color--foreground);
  padding: 0.55rem 0.85rem;
  border-radius: 8px;
  font: inherit;
  font-size: var(--wp--preset--font-size--small);
  cursor: pointer;
  transition: background-color 0.1s ease;
}
.prompt-studio .ps-combo-option:hover,
.prompt-studio .ps-combo-option.is-active {
  background: var(--wp--preset--color--primary);
  color: var(--wp--preset--color--background);
}
.prompt-studio .ps-combo-option[hidden] { display: none; }
.prompt-studio .ps-combo-empty { padding: 0.55rem 0.85rem; color: var(--wp--preset--color--muted); font-size: var(--wp--preset--font-size--small); }

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
.prompt-studio .ps-lint-detect { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.prompt-studio .ps-lint-detected-label { color: var(--wp--preset--color--muted); font-size: var(--wp--preset--font-size--small); margin: 0; }
.prompt-studio .ps-lint-detected-label strong { color: var(--wp--preset--color--foreground); font-weight: 700; }
.prompt-studio .ps-lint-switch { appearance: none; border: none; background: transparent; padding: 0; color: var(--wp--preset--color--primary); text-decoration: underline; cursor: pointer; font: inherit; font-size: var(--wp--preset--font-size--small); }
.prompt-studio .ps-lint-switch:hover { color: var(--wp--preset--color--accent); }
.prompt-studio .ps-lint-actions { display: flex; gap: 0.5rem; }
.prompt-studio .ps-lint-actions button { appearance: none; border: 1px solid var(--wp--preset--color--primary); background: var(--wp--preset--color--primary); color: var(--wp--preset--color--background); padding: 0.5rem 1rem; border-radius: 999px; cursor: pointer; font: inherit; }
.prompt-studio .ps-lint-actions button.ps-link-button { background: transparent; color: var(--wp--preset--color--muted); border-color: transparent; text-decoration: underline; }
@keyframes psPulse { 0% { transform: scale(0.95); } 100% { transform: scale(1); } }

.prompt-studio .ps-lint-result { display: flex; flex-direction: column; gap: var(--wp--preset--spacing--30); }
.prompt-studio .ps-lint-result-head { display: flex; align-items: center; gap: var(--wp--preset--spacing--30); }
.prompt-studio .ps-score-wrap { position: relative; width: 100px; height: 100px; display: grid; place-items: center; }
.prompt-studio .ps-score-ring { position: absolute; inset: 0; width: 100%; height: 100%; }
/* brand-fidelity — score semantics, intentional */
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
</style>

<script>
(function () {
  'use strict';

  document.body.classList.add('has-prompt-studio-page');

  // ---- Rubrics (field definitions per type) ----
  const RUBRICS = {
    write: {
      visible: [
        { key:'role',          label:'Role',          help:'Who the model is acting as.',           placeholder:'You are a senior staff engineer who reviews Python.',
          suggestions: ['You are a senior staff engineer', 'You are a technical writer', 'You are a senior product designer', 'You are an experienced editor', 'You are a domain expert', 'You are a thoughtful tutor', 'You are a copy editor for marketing', 'You are a startup strategist', 'You are an expert copywriter', 'You are a critical reviewer looking for edge cases'] },
        { key:'task',          label:'Task',          help:'Explicit verb and object.',             placeholder:'Refactor the function below to remove the duplicated branches.',
          suggestions: ['Summarize the content below', 'Refactor the code below', 'Critique the writing below', 'Generate 5 ideas on the topic below', 'Translate the text below to plain English', 'Outline a 1000-word post on the topic below', 'Compare and contrast the options below', 'Brainstorm names for the thing below'] },
        { key:'context',       label:'Context',       help:'Background the model needs.',           placeholder:'This runs on a Raspberry Pi, memory matters.' },
        { key:'outputFormat',  label:'Output format', help:'Shape of the answer.',                  placeholder:'Return a unified diff, no prose.',
          suggestions: ['Bulleted list, one idea per line', 'Numbered steps', 'Single code block, no prose', 'Markdown table', 'JSON object with named keys', 'Three short paragraphs', 'Single paragraph, under 100 words', 'Plain text, no headers'] },
        { key:'constraints',   label:'Constraints',   help:'What to avoid or stay within.',         placeholder:'Do not change the public function signature.',
          suggestions: ['Stay within 200 words', 'Use plain language, avoid jargon', 'Do not apologize or hedge', 'Be specific, do not generalize', 'No more than 5 items', 'Match the existing voice, do not paraphrase', 'Cite sources inline, never summarize them', 'Start with the answer only, no preamble'] },
      ],
      advanced: [
        { key:'examples',      label:'Examples',      help:'Few-shot input / output pairs.',        placeholder:'Input: …  Output: …' },
        { key:'audience',      label:'Audience',      help:'Who the output is for.',                placeholder:'The junior dev who wrote the code.',
          suggestions: ['A mid-level engineer', 'A junior developer', 'A non-technical executive', 'A curious technical reader', 'The person who wrote the code', 'A potential customer', 'A new hire', 'An academic audience'] },
        { key:'tone',          label:'Tone',          help:'Voice for the answer.',                 placeholder:'Direct but kind, no hedging.',
          suggestions: ['Direct but kind, no hedging', 'Neutral and precise', 'Warm and encouraging', 'Authoritative and concise', 'Playful but informative', 'Formal and academic', 'Conversational', 'Terse and technical'] },
      ],
      multiline: ['context','examples','outputFormat','constraints'],
      softTokenCap: 400,
    },
    image: {
      visible: [
        { key:'subject',       label:'Subject',       help:'The main thing.',                       placeholder:'A red fox curled asleep on a worn leather chair.',
          suggestions: ['A red fox curled asleep on a worn leather chair', 'A neon-lit street market at night', 'A vintage typewriter on a sun-lit desk', 'A robot tending a garden of glowing flowers', 'An astronaut floating in zero gravity, watching Earth', 'A medieval blacksmith\'s workshop, sparks flying', 'A dragon perched on a snow-covered peak', 'A child\'s tea party with stuffed animals in a forest clearing'] },
        { key:'style',         label:'Style',         help:'Artistic style.',                       placeholder:'Oil painting, Sargent influence.',
          suggestions: ['Photorealistic', 'Cyberpunk', 'Art Deco', 'Impressionist', 'Synthwave / Vaporwave', 'Surrealism', 'Studio Ghibli', 'Film noir', 'Anime / manga', 'Concept art'] },
        { key:'composition',   label:'Composition',   help:'Framing and angle.',                    placeholder:'Close-up, low angle, rule of thirds.',
          suggestions: ['Close-up portrait', 'Wide establishing shot', 'Rule of thirds', 'Centered symmetry', 'Low angle', 'Top-down', 'Dutch angle', 'Macro'] },
        { key:'medium',        label:'Medium',        help:'Physical or digital medium.',           placeholder:'Watercolor on rough paper.',
          suggestions: ['Oil on canvas', 'Watercolor on rough paper', 'Octane render', 'Unreal Engine 5', '3D clay model', 'Graphite sketch', 'Ink wash', 'Screen print', 'Gouache', 'Vector illustration'] },
        { key:'colorLighting', label:'Color & light', help:'Palette and light.',                    placeholder:'Warm amber light, deep shadows.',
          suggestions: ['Warm golden hour', 'Cool blue moonlight', 'High-key bright', 'Low-key dramatic', 'Neon cyberpunk', 'Soft overcast', 'Backlit silhouette', 'Candlelit'] },
      ],
      advanced: [
        { key:'cameraLens',    label:'Camera / lens', help:'For photoreal shots.',                  placeholder:'35mm, f/1.8, shallow depth of field.',
          suggestions: ['35mm, f/1.8, shallow DOF', '85mm portrait', '24mm wide', 'Macro 100mm', 'Fisheye', 'Telephoto compression', 'Shot on 35mm film', 'IMAX 70mm', 'Vintage Polaroid'] },
        { key:'mood',          label:'Mood',          help:'Feel of the image.',                    placeholder:'Quiet, contemplative.',
          suggestions: ['Cinematic', 'Dreamy', 'Melancholic', 'Peaceful', 'Tense', 'Whimsical', 'Gritty', 'Nostalgic'] },
        { key:'negative',      label:'Negative prompt', help:'What to exclude.',                    placeholder:'no text, no watermark, no extra limbs' },
        { key:'aspectRatio',   label:'Parameters',    help:'Aspect ratio and model flags.',         placeholder:'--ar 16:9 --v 6',
          suggestions: ['--ar 16:9', '--ar 9:16', '--ar 1:1', '--ar 4:3', '--v 6', '--v 6 --style raw', '--niji 6'] },
      ],
      multiline: ['subject','negative'],
      softTokenCap: 80,
    },
    review: {
      visible: [
        { key:'content',       label:'Content to review', help:'Paste the thing being critiqued.',  placeholder:'Paste your code, copy, or document here.' },
        { key:'criteria',      label:'Criteria',      help:'What to evaluate against.',             placeholder:'Clarity, correctness, performance.',
          suggestions: ['Clarity, correctness, performance', 'Accuracy and completeness', 'Tone and audience fit', 'Logical consistency', 'Reproducibility', 'Edge cases and error handling', 'Readability and naming'] },
        { key:'audience',      label:'Audience for feedback', help:'Who reads the review.',         placeholder:'The junior dev who wrote it.',
          suggestions: ['The person who wrote it', 'A mid-level engineer', 'A junior developer', 'A senior reviewer', 'A peer on the team', 'A new hire', 'A non-technical stakeholder'] },
        { key:'outputFormat',  label:'Output format', help:'Shape of the review.',                  placeholder:'Bulleted list grouped by severity, no rewrite.',
          suggestions: ['Bulleted list grouped by severity', 'Numbered list, one issue per line', 'Markdown table with columns Issue / Severity / Fix', 'Inline annotations with line refs', 'Two-section split: blockers / nice-to-haves'] },
      ],
      advanced: [
        { key:'severity',      label:'Severity scale', help:'Define levels.',                       placeholder:'blocker / major / minor / nit',
          suggestions: ['blocker', 'critical', 'major', 'minor', 'nit', 'high', 'medium', 'low', 'must fix', 'should fix'] },
        { key:'tone',          label:'Tone',          help:'Voice of the feedback.',                placeholder:'Direct but kind, no hedging.',
          suggestions: ['Direct but kind, no hedging', 'Neutral and precise', 'Warm and encouraging', 'Constructive and specific', 'Terse and technical', 'Formal and academic', 'Blunt and objective', 'Socratic, asking guiding questions'] },
        { key:'examples',      label:'Examples of good / bad', help:'Calibration anchors.',          placeholder:'Good: …  Bad: …' },
      ],
      multiline: ['content','examples','criteria','outputFormat'],
      softTokenCap: 600,
      contentSoftCap: 3000,
    },
  };

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
    {
      id: 'write-code-refactor',
      type: 'write',
      category: 'code',
      title: 'Refactor with constraints',
      summary: 'Remove duplication from a function while keeping its public signature intact.',
      fields: {
        role: 'You are a senior engineer doing a focused refactor pass before a code review.',
        task: 'Refactor the function below to remove duplicated logic without changing its public signature or observable behavior.',
        context: 'The codebase uses TypeScript. The function has grown through several feature additions and now repeats the same null-check and formatting block in three branches. The public signature must stay identical so callers do not need updating.',
        outputFormat: 'A unified diff (--- a/file +++ b/file format). No prose explanation unless a decision is non-obvious, in which case add a short inline comment.',
        constraints: 'Do not change the public function signature. Do not add new dependencies. Do not make formatter-only changes (whitespace, semicolons). Every branch in the original must still be reachable in the refactored version.',
        examples: '',
        audience: 'The reviewer reading the next pull request.',
        tone: 'Terse and precise, no filler.',
        advancedOpen: false,
      },
    },
    {
      id: 'write-code-test-gen',
      type: 'write',
      category: 'code',
      title: 'Generate unit tests',
      summary: 'Generate Vitest or Jest tests covering the happy path, two edge cases, and one error case.',
      fields: {
        role: 'You are a senior engineer writing unit tests for a code review submission.',
        task: 'Generate Vitest-compatible unit tests for the function pasted below.',
        context: 'The project uses Vitest with the default config. The function is pure (no side effects, no I/O). Tests should be clear enough that a future maintainer understands intent without reading the implementation.',
        outputFormat: 'A single code block containing the complete test file. No prose, no commentary outside the code block. Use descriptive test names in the form: describe(\'functionName\', () => { it(\'...\', ...) }).',
        constraints: 'Cover exactly: one happy-path case, two distinct edge cases (boundary values or unusual inputs), and one error case (invalid input that should throw or return an error sentinel). Do not test implementation details — test observable behavior only.',
        examples: '',
        audience: 'Future maintainers who will extend or debug the function.',
        tone: 'Neutral and precise.',
        advancedOpen: false,
      },
    },
    {
      id: 'write-creative-blog-outline',
      type: 'write',
      category: 'creative',
      title: 'Blog post outline',
      summary: 'Outline a 1200-word post with an H2/H3 structure and a one-line hook per section.',
      fields: {
        role: 'You are a technical writer who has published extensively on software and product topics.',
        task: 'Outline a 1200-word blog post on the topic provided below.',
        context: 'The post will live on a personal developer blog. The reader arrives from a search or a link shared by a colleague. They are curious and technical but not necessarily an expert on this exact topic. The outline must be tight enough to write from directly, not a vague list of headings.',
        outputFormat: 'H2 and H3 headings arranged as a document outline. Under each heading, one sentence: the hook or argument for that section. Estimate word count per section in parentheses. No full prose yet.',
        constraints: 'The outline must fit a 1200-word post, so the total estimated word count across sections should sum to roughly 1200. No more than four H2 sections. Each H3 must nest under an H2. Include an intro hook (one paragraph) and a closing takeaway.',
        examples: '',
        audience: 'A curious technical reader who arrived via search or a shared link.',
        tone: 'Clear and direct, with genuine interest in the subject.',
        advancedOpen: false,
      },
    },
    {
      id: 'write-creative-rewrite-voice',
      type: 'write',
      category: 'creative',
      title: 'Rewrite in a target voice',
      summary: 'Rewrite a passage in a named voice while keeping every fact intact.',
      fields: {
        role: 'You are an editor skilled at voice mimicry and style transformation.',
        task: 'Rewrite the passage below in the voice described in the Context field.',
        context: 'Target voice: [describe the voice here, e.g. "Patrick McKenzie writing a business-strategy post", "a ship captain\'s log from the 1840s", or "a dry academic abstract"]. The passage contains factual claims that must all survive the rewrite unchanged. Only the register, sentence structure, and word choice should shift.',
        outputFormat: 'The rewritten passage only. No commentary before or after, no explanation of what changed. Same approximate length as the original.',
        constraints: 'Keep every factual claim. Do not drop or add information. Do not editorialize or summarize. If the target voice would normally shorten or expand, match the original length instead.',
        examples: '',
        audience: 'The author who wants to see their own words in a different register.',
        tone: 'Matches the target voice exactly.',
        advancedOpen: false,
      },
    },
    {
      id: 'write-creative-fiction-scene',
      type: 'write',
      category: 'creative',
      title: 'Short fiction scene',
      summary: 'Write a 300-400 word scene with a stated setting, two characters, and one tension.',
      fields: {
        role: 'You are a fiction writer known for economical prose and strong scene work.',
        task: 'Write a short scene of 300 to 400 words using the setting, characters, and tension described in the Context field.',
        context: 'Setting: [describe the place and time]. Characters: [Character A — one trait] and [Character B — one trait]. Tension: [what they want from each other, or what stands between them]. The scene should begin in motion, land on the tension, and end at a moment of unresolved pressure rather than a resolution.',
        outputFormat: 'Prose only. No scene heading, no stage directions, no character labels. Write as a literary short story, not a screenplay. 300 to 400 words.',
        constraints: 'No exposition dumps in the first sentence. No genre clichés (no dark-and-stormy-night openings, no "little did she know"). Ground every beat in specific sensory detail rather than abstract emotion. Do not resolve the tension.',
        examples: '',
        audience: 'A reader who enjoys literary short fiction.',
        tone: 'Controlled and precise, with attention to rhythm.',
        advancedOpen: false,
      },
    },
    {
      id: 'write-strategy-eli12',
      type: 'write',
      category: 'strategy',
      title: 'Explain like I\'m 12',
      summary: 'Explain a complex topic to a 12-year-old without losing the key insight.',
      fields: {
        role: 'You are a teacher who specializes in making hard ideas accessible without dumbing them down.',
        task: 'Explain the topic below to a 12-year-old reader.',
        context: 'Topic: [state the topic here]. The reader is smart and curious but has no background in this subject. The goal is not to simplify away the insight — the key mechanism or idea must survive the explanation intact. Do not condescend.',
        outputFormat: '4 to 6 short paragraphs (3-5 sentences each). Include one concrete analogy in its own paragraph, introduced with "Think of it like this:". End with one sentence that names the real-world implication.',
        constraints: 'No jargon without an immediate plain-English follow-up. No bullet lists. No rhetorical questions directed at the reader ("Have you ever wondered...?"). The analogy must actually map the mechanism, not just share a surface feature.',
        examples: '',
        audience: 'A curious 12-year-old with no background in the subject.',
        tone: 'Warm, direct, and respectful.',
        advancedOpen: false,
      },
    },
    {
      id: 'write-strategy-decision-memo',
      type: 'write',
      category: 'strategy',
      title: 'Decision memo',
      summary: 'Produce a one-page decision memo with context, options, recommendation, and risks.',
      fields: {
        role: 'You are a senior product manager writing a decision memo for a leadership review.',
        task: 'Write a one-page decision memo for the proposal described below.',
        context: 'Proposal: [describe the decision to be made, the team or org affected, and any constraints already known]. The memo will be read by stakeholders who have limited time and need to understand the recommendation and its trade-offs in under five minutes.',
        outputFormat: 'Markdown with five sections: Context (2-3 sentences), Options Considered (bulleted, 2-3 options with one-line pros/cons each), Recommendation (one sentence, then a short paragraph of rationale), Risks (bulleted, 2-3 items with mitigation notes), What Would Change This Recommendation (1-2 conditions). Total: approximately 400 words.',
        constraints: 'No executive-summary preamble. Start directly with the Context section. Be concrete about trade-offs — do not hedge every statement. The recommendation must be a single clear choice, not "it depends".',
        examples: '',
        audience: 'Stakeholders who will approve or push back on the decision.',
        tone: 'Clear and confident, with honest acknowledgment of uncertainty.',
        advancedOpen: false,
      },
    },
    {
      id: 'write-strategy-study-plan',
      type: 'write',
      category: 'strategy',
      title: '30-day study plan',
      summary: 'Build a 30-day learning plan for a stated skill at a stated starting level.',
      fields: {
        role: 'You are a curriculum designer who builds self-directed learning plans for working professionals.',
        task: 'Produce a 30-day study plan for the skill and starting level described below.',
        context: 'Skill to learn: [state the skill]. Starting level: [beginner / some exposure / intermediate]. Available time: approximately 30 minutes per day. The learner has a day job and needs a plan that is achievable in short focused sessions, not a bootcamp.',
        outputFormat: 'A markdown table with four columns: Day (or Day Range), Focus Area, Concrete Deliverable (something the learner produces or completes), and 30-Min Check (a specific question or test to confirm the session stuck). Group days into weekly blocks with a one-line weekly goal above each block.',
        constraints: 'Each deliverable must be specific and completable in one 30-minute session. No "read chapter X" as a deliverable — it must produce something (a summary, a small implementation, a flash card set). The plan must build incrementally: later days depend on earlier ones.',
        examples: '',
        audience: 'A working professional learning a new skill in short daily sessions.',
        tone: 'Practical and encouraging, without cheerleading.',
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
    {
      id: 'image-photo-street',
      type: 'image',
      category: 'photorealistic',
      title: 'Street photography candid',
      summary: 'A candid street photo capturing a decisive moment with natural light.',
      fields: {
        subject: 'A street vendor arranging fruit stalls at dawn, steam rising from a nearby cart',
        style: 'photorealistic, documentary street photography',
        composition: 'leading lines from cobblestones, subject placed at first third, decisive moment',
        medium: 'digital photograph',
        colorLighting: 'cool blue pre-dawn ambient, warm tungsten spill from stall lamp',
        cameraLens: '35mm, f/2.8',
        mood: 'quiet industry, city waking up',
        negative: 'no staged poses, no text overlays, no watermark',
        aspectRatio: '--ar 3:2 --v 6',
        advancedOpen: false,
      },
    },
    {
      id: 'image-photo-product',
      type: 'image',
      category: 'photorealistic',
      title: 'Product hero shot',
      summary: 'A product on a plain studio backdrop with controlled three-point lighting.',
      fields: {
        subject: 'A matte-black glass water bottle with a brushed-aluminum cap, condensation on the surface',
        style: 'photorealistic, commercial product photography',
        composition: '3/4 angle, product centered, minimal shadow beneath',
        medium: 'digital photograph, studio setup',
        colorLighting: 'soft white key light, subtle fill from left, clean white backdrop',
        cameraLens: '90mm macro, f/8',
        mood: 'clean, premium, minimal',
        negative: 'no logos, no reflections, no background clutter, no watermark',
        aspectRatio: '--ar 1:1 --v 6',
        advancedOpen: false,
      },
    },
    {
      id: 'image-illust-cover',
      type: 'image',
      category: 'illustration',
      title: 'Book cover illustration',
      summary: 'A digital-paint book cover with a clear title area and defined genre mood.',
      fields: {
        subject: 'An ancient lighthouse on a rocky coast at night, beam sweeping across stormy water',
        style: 'digital painting, atmospheric fantasy illustration',
        composition: 'lighthouse occupies upper two-thirds, title-safe negative space in lower third',
        medium: 'digital paint, soft brush textures',
        colorLighting: 'deep navy sky, green-teal bioluminescent waves, single warm beam of light',
        cameraLens: '',
        mood: 'eerie, lonely, mysterious',
        negative: 'no text, no watermark, no people',
        aspectRatio: '--ar 2:3 --v 6',
        advancedOpen: false,
      },
    },
    {
      id: 'image-illust-character',
      type: 'image',
      category: 'illustration',
      title: 'Character concept sheet',
      summary: 'Full-body character on a neutral background, 3/4 turn, anime-influenced palette.',
      fields: {
        subject: 'A young botanist in a worn field coat, holding a glass specimen jar with a glowing plant inside',
        style: 'anime-influenced illustration, soft cel shading',
        composition: 'full body, 3/4 turn, centered on neutral grey background',
        medium: 'digital illustration',
        colorLighting: 'soft diffuse lighting, muted warm palette, pale lavender accent from the glowing jar',
        cameraLens: '',
        mood: 'curious, gentle, focused',
        negative: 'no extra limbs, no text, no background details, no watermark',
        aspectRatio: '--ar 3:4 --niji 6',
        advancedOpen: false,
      },
    },
    {
      id: 'image-illust-pattern',
      type: 'image',
      category: 'illustration',
      title: 'Repeating decorative pattern',
      summary: 'A seamless tileable decorative motif with a four-color palette and no focal point.',
      fields: {
        subject: 'Botanical motif with stylized fern fronds, small berries, and leaf clusters evenly distributed',
        style: 'flat vector illustration, folk-art inspired',
        composition: 'seamless tile, elements distributed evenly, no central focal point',
        medium: 'digital illustration, flat fill',
        colorLighting: 'four-color palette: sage green, terracotta, cream, charcoal; no gradients',
        cameraLens: '',
        mood: 'calm, decorative, craft-like',
        negative: 'no text, no watermark, no shadows, no central composition',
        aspectRatio: '--ar 1:1 --v 6 --tile',
        advancedOpen: false,
      },
    },
    {
      id: 'image-ui-app-icon',
      type: 'image',
      category: 'ui-design',
      title: 'App icon, flat style',
      summary: 'A square app icon with one focal motif, flat fills, no shadows or gradients.',
      fields: {
        subject: 'A stylized leaf inside a rounded square, single clean silhouette',
        style: 'flat UI icon, Material-influenced, no depth effects',
        composition: 'centered motif, equal padding on all sides, icon occupies 60% of canvas',
        medium: 'digital vector illustration',
        colorLighting: 'two-tone flat fill: deep teal background, white foreground motif',
        cameraLens: '',
        mood: 'clean, modern, friendly',
        negative: 'no text, no drop shadows, no gradients beyond flat, no watermark',
        aspectRatio: '--ar 1:1 --v 6',
        advancedOpen: false,
      },
    },
    {
      id: 'image-ui-hero-bg',
      type: 'image',
      category: 'ui-design',
      title: 'Hero background, abstract',
      summary: 'An abstract gradient mesh for a marketing hero section, no text, wide format.',
      fields: {
        subject: 'Soft flowing color fields suggesting gentle motion, no distinct objects or figures',
        style: 'abstract digital art, smooth mesh gradient, marketing aesthetic',
        composition: 'wide landscape, color transitions from left to right, no focal point',
        medium: 'digital illustration',
        colorLighting: 'cool violet to warm coral gradient, soft light blooms near center-right',
        cameraLens: '',
        mood: 'calm, optimistic, open',
        negative: 'no text, no faces, no sharp edges, no watermark',
        aspectRatio: '--ar 16:9 --v 6',
        advancedOpen: false,
      },
    },
    {
      id: 'image-ui-spot-illust',
      type: 'image',
      category: 'ui-design',
      title: 'Spot illustration, empty state',
      summary: 'A small friendly object on an off-white background for an empty-state UI panel.',
      fields: {
        subject: 'A small open cardboard box with a few floating sparkles above it, nothing inside',
        style: 'flat spot illustration, editorial UI style',
        composition: 'object centered, generous whitespace around it, no background scene',
        medium: 'digital illustration, flat fill with subtle line weight',
        colorLighting: 'off-white background, one coral accent color on the box flaps, pale grey lines',
        cameraLens: '',
        mood: 'friendly, low-contrast, approachable',
        negative: 'no text, no watermark, no complex backgrounds, no drop shadows',
        aspectRatio: '--ar 4:3 --v 6',
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
    {
      id: 'review-code-test-gaps',
      type: 'review',
      category: 'code-diffs',
      title: 'Test coverage gap review',
      summary: 'Find what test cases are missing from a diff, without proposing code.',
      fields: {
        content: 'PASTE_DIFF_HERE',
        criteria: 'What behaviors, edge cases, and error paths exist in the diff but have no test.',
        audience: 'The engineer writing the tests.',
        outputFormat: 'Bulleted list of test cases that should be written. No code, only the case descriptions.',
        severity: '',
        tone: 'Direct but kind, no hedging.',
        examples: '',
        advancedOpen: false,
      },
    },
    {
      id: 'review-code-readability',
      type: 'review',
      category: 'code-diffs',
      title: 'Readability pass',
      summary: 'Review code for naming, complexity, and structure only, ranked by impact.',
      fields: {
        content: 'PASTE_CODE_HERE',
        criteria: 'Naming clarity, function length, nesting depth, comment quality, module structure.',
        audience: 'The engineer who will maintain this code.',
        outputFormat: 'Ranked list: each item has the change, the file location, and a one-sentence rationale.',
        severity: '',
        tone: 'Direct but kind, no hedging.',
        examples: '',
        advancedOpen: false,
      },
    },
    {
      id: 'review-copy-landing',
      type: 'review',
      category: 'copy-marketing',
      title: 'Landing-page copy review',
      summary: 'Check landing copy against a value-prop and audience, section by section.',
      fields: {
        content: 'PASTE_LANDING_COPY_HERE',
        criteria: 'Does each section support the stated value-prop? Is the language right for the audience? Are claims specific and credible?',
        audience: 'The copywriter or marketer who wrote the page.',
        outputFormat: 'Section-by-section call-out: section name, what is wrong, and a rewrite suggestion for each flagged line.',
        severity: 'blocker / major / minor / nit',
        tone: 'Direct but kind, no hedging.',
        examples: '',
        advancedOpen: false,
      },
    },
    {
      id: 'review-copy-voice-match',
      type: 'review',
      category: 'copy-marketing',
      title: 'Voice consistency check',
      summary: 'Find phrases in a draft that drift from the reference voice, with a fix each.',
      fields: {
        content: 'PASTE_DRAFT_HERE\n\n---REFERENCE VOICE SAMPLE---\nPASTE_VOICE_SAMPLE_HERE',
        criteria: 'Sentence rhythm, word choice, formality level, and any phrases that sound like template copy rather than the reference voice.',
        audience: 'The writer who authored the draft.',
        outputFormat: 'Bulleted list: each item has the drifting phrase, a one-line explanation of the drift, and a suggested replacement.',
        severity: '',
        tone: 'Direct but kind, no hedging.',
        examples: '',
        advancedOpen: false,
      },
    },
    {
      id: 'review-copy-call-to-action',
      type: 'review',
      category: 'copy-marketing',
      title: 'CTA conversion review',
      summary: 'Rate each CTA on a page: keep, sharpen, or drop, with reasoning.',
      fields: {
        content: 'PASTE_PAGE_COPY_HERE',
        criteria: 'Clarity of the ask, urgency, specificity, placement relative to surrounding copy, and whether the action matches what the page promises.',
        audience: 'The marketer or designer who built the page.',
        outputFormat: 'One row per CTA: CTA text | Verdict (keep / sharpen / drop) | Reasoning | Suggested replacement if sharpen or drop.',
        severity: '',
        tone: 'Direct but kind, no hedging.',
        examples: '',
        advancedOpen: false,
      },
    },
    {
      id: 'review-doc-spec',
      type: 'review',
      category: 'structural',
      title: 'Spec gap review',
      summary: 'Find ambiguity, missing acceptance criteria, and unstated assumptions in a spec.',
      fields: {
        content: 'PASTE_SPEC_HERE',
        criteria: 'Ambiguous requirements, missing acceptance criteria, unstated assumptions, conflicting statements, and undefined terms.',
        audience: 'The product manager or tech lead who owns the spec.',
        outputFormat: 'Numbered list of gaps, each with: gap description, severity (blocker / major / minor), and a question that would resolve it.',
        severity: 'blocker / major / minor',
        tone: 'Direct but kind, no hedging.',
        examples: '',
        advancedOpen: false,
      },
    },
    {
      id: 'review-doc-resume',
      type: 'review',
      category: 'structural',
      title: 'Resume review',
      summary: 'Review each resume bullet against a target role, with a one-line rewrite.',
      fields: {
        content: 'PASTE_RESUME_HERE',
        criteria: 'Does each bullet use action verbs, include a measurable result, and connect to the stated target role? Are there gaps or irrelevant items?',
        audience: 'The person who wrote the resume.',
        outputFormat: 'Per-bullet verdict: bullet text | Verdict (strong / needs work / cut) | One-line rewrite if not strong.',
        severity: '',
        tone: 'Direct but kind, no hedging.',
        examples: '',
        advancedOpen: false,
      },
    },
    {
      id: 'review-doc-readme',
      type: 'review',
      category: 'structural',
      title: 'README review',
      summary: 'Find friction a first-time reader hits in the first ten minutes with a README.',
      fields: {
        content: 'PASTE_README_HERE',
        criteria: 'Time-to-first-run, clarity of prerequisites, accuracy of quick-start steps, and whether the purpose and audience are stated up front.',
        audience: 'A developer reading this README for the first time.',
        outputFormat: 'Ordered list of changes to make, prioritized by the order a new reader would hit them.',
        severity: '',
        tone: 'Direct but kind, no hedging.',
        examples: '',
        advancedOpen: false,
      },
    },
  ];

  // ---- Lint helpers ----
  const IMAGE_STYLE_KEYWORDS = ['photorealistic','oil painting','watercolor','35mm','octane render','unreal engine','midjourney','stable diffusion','digital art','concept art','anime','manga','pixel art','low poly','hyperdetailed','masterpiece','trending on artstation','8k','cinematic','cyberpunk','art deco','impressionist','synthwave','vaporwave','surrealism','surrealist','studio ghibli','ghibli','film noir','noir','gouache','ink wash','screen print','vector','clay model','graphite'];
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
    const advLabel = draft.advancedOpen ? 'Hide advanced' : 'Show advanced';
    parts.push('<button type="button" class="ps-advanced-toggle" aria-expanded="' + (draft.advancedOpen ? 'true' : 'false') + '" data-action="toggle-advanced">' + advLabel + ' <span class="ps-chev" aria-hidden="true"><svg viewBox="0 0 12 8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 1.5 6 6.5 11 1.5"/></svg></span></button>');
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
    parts.push('    <button type="button" data-action="randomize" title="Fill suggestion fields with random picks">Randomize</button>');
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
    let hasSuggestions = false;
    if (!isMultiline) {
      if (field.suggestions) {
        if (field.suggestions.length > 0) { hasSuggestions = true; }
      }
    }
    let inputOrCombo;
    if (isMultiline) {
      inputOrCombo = '<textarea id="' + id + '" name="' + field.key + '" rows="3" placeholder="' + placeholder + '" aria-describedby="' + id + '-help">' + escapeHtml(value) + '</textarea>';
    } else if (hasSuggestions) {
      const panelId = 'ps-combo-panel-' + field.key;
      let options = '';
      for (let i = 0; i < field.suggestions.length; i++) {
        const sv = escapeHtml(field.suggestions[i]).replace(/"/g, '&quot;');
        options += '<button type="button" class="ps-combo-option" role="option" data-action="combo-pick" data-field="' + field.key + '" data-value="' + sv + '">' + escapeHtml(field.suggestions[i]) + '</button>';
      }
      inputOrCombo =
        '<div class="ps-combo" data-field="' + field.key + '" data-open="false">' +
          '<input id="' + id + '" name="' + field.key + '" type="text" value="' + escapeHtml(value).replace(/"/g, '&quot;') + '" placeholder="' + placeholder + '" aria-describedby="' + id + '-help" role="combobox" aria-controls="' + panelId + '" aria-expanded="false" aria-autocomplete="list" autocomplete="off" />' +
          '<button type="button" class="ps-combo-toggle" data-action="combo-toggle" data-field="' + field.key + '" tabindex="-1" aria-label="Show suggestions"><svg viewBox="0 0 12 8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="1 1.5 6 6.5 11 1.5"/></svg></button>' +
          '<div class="ps-combo-panel" id="' + panelId + '" role="listbox">' + options + '<p class="ps-combo-empty" hidden>No matches. Type your own.</p></div>' +
        '</div>';
    } else {
      inputOrCombo = '<input id="' + id + '" name="' + field.key + '" type="text" value="' + escapeHtml(value).replace(/"/g, '&quot;') + '" placeholder="' + placeholder + '" aria-describedby="' + id + '-help" />';
    }
    return '<label class="ps-field" for="' + id + '"><span class="ps-field-label">' + escapeHtml(field.label) + '</span><span class="ps-field-help" id="' + id + '-help">' + escapeHtml(help) + '</span>' + inputOrCombo + '</label>';
  }

  function escapeHtml(s) {
    if (s == null) { return ''; }
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  // ---- Lint view ----
  function buildLintDetectHtml() {
    const input = state.lint.input ? state.lint.input : '';
    const detected = state.lint.detectedType;
    const current = state.currentType;
    const curLabel = escapeHtml(capitalize(current));
    if (!input || !detected) {
      return '<p class="ps-lint-detected-label">Linting against the ' + curLabel + ' rubric. Paste a prompt to auto-detect its type.</p>';
    }
    const detLabel = escapeHtml(capitalize(detected));
    if (detected === current) {
      return '<p class="ps-lint-detected-label">Detected: <strong>' + detLabel + '</strong>. Linting against the ' + curLabel + ' rubric.</p>';
    }
    return '<p class="ps-lint-detected-label">Detected: <strong>' + detLabel + '</strong>. Currently linting as <strong>' + curLabel + '</strong>. <button type="button" class="ps-lint-switch" data-action="lint-switch-type" data-type="' + escapeHtml(detected) + '">Switch to ' + detLabel + '</button></p>';
  }

  function renderLintView() {
    const root = document.getElementById('view-lint');
    if (!root) { return; }
    const inputValue = state.lint.input ? state.lint.input : '';

    const resultHtml = state.lint.lastResult ? renderLintResults(state.lint.lastResult) : '<p class="ps-stub">Click Analyze to score the prompt above.</p>';

    root.innerHTML =
      '<div class="ps-lint">' +
        '<label class="ps-field"><span class="ps-field-label">Paste a prompt to analyze</span>' +
          '<textarea id="ps-lint-input" rows="8" placeholder="Paste a prompt here.">' + escapeHtml(inputValue) + '</textarea>' +
        '</label>' +
        '<div class="ps-lint-detect">' + buildLintDetectHtml() + '</div>' +
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

  // ---- Library view ----
  function renderLibraryView() {
    const root = document.getElementById('view-library');
    if (!root) { return; }
    const type = state.currentType;
    const cats = libraryCategoriesFor(type);
    const search = (state.library.search ? state.library.search : '').toLowerCase();
    const active = state.library.category ? state.library.category : 'all';

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
      let tmpl = null;
      for (let i = 0; i < TEMPLATES.length; i++) {
        if (TEMPLATES[i].id === state.library.openTemplateId) { tmpl = TEMPLATES[i]; break; }
      }
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

  function labelFor(cats, key) {
    for (let i = 0; i < cats.length; i++) {
      if (cats[i].key === key) { return cats[i].label; }
    }
    return key;
  }

  // Module-scope so close handlers can find it.
  // modalCachedFocus captures whatever had focus at modal open (used as a fallback,
  // e.g. for deep links where there's no opener card). modalOpenerCardId captures
  // the library card's stable data-id BEFORE the library re-renders — restoring
  // by id survives the innerHTML replacement that openTemplateModal happens after.
  let modalCachedFocus = null;
  let modalKeyHandler = null;
  let modalOpenerCardId = null;

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
    // Restore focus to the element that opened the modal. Prefer re-querying
    // by the captured card id, because the library grid is re-rendered when
    // the modal opens (innerHTML replace), so modalCachedFocus may point at a
    // detached node. The id-based lookup finds the live replacement card.
    let restored = false;
    if (modalOpenerCardId) {
      const liveCard = document.querySelector('.ps-lib-card[data-id="' + modalOpenerCardId.replace(/"/g, '\\"') + '"]');
      if (liveCard) {
        try { liveCard.focus(); restored = true; } catch (e) { /* live card un-focusable */ }
      }
      modalOpenerCardId = null;
    }
    if (!restored) {
      if (modalCachedFocus) {
        try { modalCachedFocus.focus(); } catch (e) { /* element gone */ }
      }
    }
    modalCachedFocus = null;
    // Strip the ?t= from the URL via replaceState (modal close should not add history)
    if (state.library.openTemplateId) {
      state.library.openTemplateId = null;
      const newHash = buildHash(state.currentType, state.currentMode, null);
      history.replaceState(null, '', newHash);
    }
  }

  function root_querySafe(selector) {
    try { return document.querySelector(selector); } catch (e) { return null; }
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

    // Library search input
    if (target.id === 'ps-lib-search') {
      state.library.search = target.value;
      // Only re-render the grid, not the input. Preserve caret position.
      const grid = root_querySafe('.ps-lib-grid');
      if (grid) {
        const focused = document.activeElement;
        const caret = focused ? focused.selectionStart : null;
        renderLibraryView();
        const newFocused = document.getElementById('ps-lib-search');
        if (newFocused) {
          newFocused.focus();
          if (caret != null) { newFocused.setSelectionRange(caret, caret); }
        }
      }
      return;
    }

    // Lint input
    if (target.id === 'ps-lint-input') {
      state.lint.input = target.value;
      state.lint.detectedType = target.value ? detectPromptType(target.value) : null;
      // Update only the detected-type indicator, not the textarea (preserve focus).
      const indicator = document.querySelector('.ps-lint-detect');
      if (indicator) { indicator.innerHTML = buildLintDetectHtml(); }
      return;
    }

    // Builder field
    const name = target.getAttribute('name');
    if (!name) { return; }
    const type = state.currentType;
    if (!state.drafts[type]) { return; }
    if (!(name in state.drafts[type])) { return; }
    state.drafts[type][name] = target.value;
    // If this input is inside a combo, ensure it's open and filter the options.
    const comboParent = target.closest ? target.closest('.ps-combo') : null;
    if (comboParent) {
      const fieldKey = comboParent.getAttribute('data-field');
      if (comboParent.getAttribute('data-open') !== 'true') {
        openCombo(fieldKey);
      } else {
        filterCombo(fieldKey, target.value);
      }
    }
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
    if (action === 'randomize') {
      const type = state.currentType;
      const rubric = RUBRICS[type];
      const draft = state.drafts[type];
      const pickRandom = function (field) {
        if (!field.suggestions) { return; }
        if (field.suggestions.length === 0) { return; }
        const idx = Math.floor(Math.random() * field.suggestions.length);
        draft[field.key] = field.suggestions[idx];
      };
      for (let i = 0; i < rubric.visible.length; i++) { pickRandom(rubric.visible[i]); }
      for (let i = 0; i < rubric.advanced.length; i++) { pickRandom(rubric.advanced[i]); }
      // Open the advanced section so newly-filled advanced fields are visible.
      draft.advancedOpen = true;
      renderBuildView();
      scheduleSave();
      showToast('Suggestion fields randomized.', 'info');
      return;
    }
    if (action === 'copy-prompt') {
      const assembled = assemblePrompt(state.currentType, state.drafts[state.currentType]).text;
      copyToClipboard(assembled);
      return;
    }
    if (action === 'combo-toggle') {
      const fieldKey = btn.getAttribute('data-field');
      const combo = document.querySelector('.ps-combo[data-field="' + fieldKey + '"]');
      if (!combo) { return; }
      const isOpen = combo.getAttribute('data-open') === 'true';
      if (isOpen) {
        closeCombo(fieldKey);
      } else {
        openCombo(fieldKey);
        const input = combo.querySelector('input');
        if (input) { input.focus(); }
      }
      return;
    }
    if (action === 'combo-pick') {
      const fieldKey = btn.getAttribute('data-field');
      const newValue = btn.getAttribute('data-value');
      const input = document.getElementById('ps-field-' + fieldKey);
      if (input) {
        input.value = newValue;
        input.dispatchEvent(new Event('input', { bubbles: true }));
      }
      closeCombo(fieldKey);
      return;
    }
    if (action === 'lint-this') {
      const assembled = assemblePrompt(state.currentType, state.drafts[state.currentType]).text;
      state.lint.input = assembled;
      state.lint.detectedType = assembled ? detectPromptType(assembled) : null;
      state.lint.lastResult = null;
      pushRoute(null, 'lint', null);
      return;
    }
    if (action === 'lint-switch-type') {
      const newType = btn.getAttribute('data-type');
      pushRoute(newType, 'lint', null);
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
    if (action === 'lib-cat') {
      state.library.category = btn.getAttribute('data-cat');
      renderLibraryView();
      return;
    }
    if (action === 'lib-open') {
      const id = btn.getAttribute('data-id');
      // Capture the opener id BEFORE pushRoute triggers the library re-render,
      // which detaches the card element that has focus. closeTemplateModal()
      // re-queries the new card by this id to restore focus correctly.
      modalOpenerCardId = id;
      pushRoute(state.currentType, 'library', id);
      return;
    }
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
    const type = state.currentType;
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
      { key:'composition', label:'Composition',     check: function (t) { return checkPattern(t, /(close-?up|wide|low angle|high angle|portrait|landscape|rule of thirds|symmetr|over the shoulder|aerial|top-?down|dutch angle|macro|centered)/i, 'Frames the shot'); } },
      { key:'lighting',    label:'Color / light',   check: function (t) { return checkPattern(t, /(light|lit|sunset|dawn|shadow|amber|cool|warm|neon|backlit|golden hour|moody|high-?key|low-?key|overcast|silhouette|candle|dramatic|bright)/i, 'Mentions light or color'); } },
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
    { id:'unfenced',       appliesTo:['review'], label:'Unfenced content', detail:'Content runs into instructions. Wrap the artifact in triple backticks.', test: function (t) {
      if (/```|"""/.test(t)) { return false; }
      // Skip when the prompt is short enough to be just the rubric scaffolding (no content pasted yet).
      if (t.length < 350) { return false; }
      return true;
    } },
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
    // Imperative verbs at the start of a sentence, after a newline, or after a "Task:"-style label.
    if (/(?:^|[\.\n]\s*|:\s*)(refactor|review|generate|explain|write|summarize|translate|extract|compare|classify|debug|fix|optimize|design|name|brainstorm|outline|rewrite|critique|evaluate|analyze)\b/i.test(text)) {
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
    const type = state.currentType;
    state.lint.detectedType = text ? detectPromptType(text) : null;

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

  // ---- Combobox helpers (custom dropdown for suggestion fields) ----
  function openCombo(fieldKey) {
    closeAllCombosExcept(fieldKey);
    const combo = document.querySelector('.ps-combo[data-field="' + fieldKey + '"]');
    if (!combo) { return; }
    combo.setAttribute('data-open', 'true');
    const input = combo.querySelector('input');
    if (input) { input.setAttribute('aria-expanded', 'true'); }
    filterCombo(fieldKey, input ? input.value : '');
  }
  function closeCombo(fieldKey) {
    const combo = document.querySelector('.ps-combo[data-field="' + fieldKey + '"]');
    if (!combo) { return; }
    combo.setAttribute('data-open', 'false');
    const input = combo.querySelector('input');
    if (input) { input.setAttribute('aria-expanded', 'false'); }
  }
  function closeAllCombos() {
    const combos = document.querySelectorAll('.ps-combo[data-open="true"]');
    for (let i = 0; i < combos.length; i++) {
      combos[i].setAttribute('data-open', 'false');
      const input = combos[i].querySelector('input');
      if (input) { input.setAttribute('aria-expanded', 'false'); }
    }
  }
  function closeAllCombosExcept(fieldKey) {
    const combos = document.querySelectorAll('.ps-combo[data-open="true"]');
    for (let i = 0; i < combos.length; i++) {
      if (combos[i].getAttribute('data-field') !== fieldKey) {
        combos[i].setAttribute('data-open', 'false');
        const input = combos[i].querySelector('input');
        if (input) { input.setAttribute('aria-expanded', 'false'); }
      }
    }
  }
  function filterCombo(fieldKey, query) {
    const combo = document.querySelector('.ps-combo[data-field="' + fieldKey + '"]');
    if (!combo) { return; }
    const q = (query ? String(query) : '').toLowerCase().trim();
    const options = combo.querySelectorAll('.ps-combo-option');
    let visible = 0;
    for (let i = 0; i < options.length; i++) {
      const opt = options[i];
      const text = opt.textContent.toLowerCase();
      let match;
      if (!q) { match = true; }
      else { match = text.indexOf(q) !== -1; }
      if (match) { opt.hidden = false; visible = visible + 1; }
      else { opt.hidden = true; }
    }
    const empty = combo.querySelector('.ps-combo-empty');
    if (empty) { empty.hidden = (visible > 0); }
  }

  // Open combo on click into its input. Close all combos on outside click.
  document.addEventListener('click', function (ev) {
    const t = ev.target;
    if (!t) { return; }
    if (t.tagName === 'INPUT') {
      const combo = t.closest ? t.closest('.ps-combo') : null;
      if (combo) {
        openCombo(combo.getAttribute('data-field'));
        return;
      }
    }
    if (t.closest && t.closest('.ps-combo')) { return; }
    closeAllCombos();
  });

  // When focus moves between fields via Tab, close combos in fields we left.
  document.addEventListener('focusin', function (ev) {
    const t = ev.target;
    if (!t) { return; }
    const combo = t.closest ? t.closest('.ps-combo') : null;
    if (combo) {
      closeAllCombosExcept(combo.getAttribute('data-field'));
    } else {
      closeAllCombos();
    }
  });

  // Escape closes the open combo and returns focus to its input.
  document.addEventListener('keydown', function (ev) {
    if (ev.key !== 'Escape') { return; }
    const open = document.querySelector('.ps-combo[data-open="true"]');
    if (!open) { return; }
    const fieldKey = open.getAttribute('data-field');
    closeCombo(fieldKey);
    const input = open.querySelector('input');
    if (input) { input.focus(); }
    ev.preventDefault();
  });

  // ---- Init ----
  // Order matters: load storage first (defaults for type/mode), then routeFromHash()
  // — the URL hash wins over storage per spec §2.
  loadFromStorage();
  routeFromHash();
})();
</script>
