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
