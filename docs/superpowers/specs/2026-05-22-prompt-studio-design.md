# Prompt Studio: client-side prompt builder, linter, and library demo

Date: 2026-05-22
Status: design, awaiting review
Scope: new single-file demo at `patterns/demo-prompt-studio.php`, served at `/demos/prompt-studio/`

## Context

The portfolio site hosts a growing set of single-file interactive demos under `/demos/<slug>/`, each implemented as one `patterns/demo-<slug>.php` file that bundles its own scoped CSS and JS. The Pokédex, contrast, and Ant Buster demos all follow this shape.

The next demo is **Prompt Studio**: a fully client-side tool that helps visitors author, audit, and learn from prompts for large language models and image-generation models. It runs entirely in the browser, makes no API calls, and persists work in localStorage.

The demo serves three audiences at once via a single top-level switch: people writing **text** prompts, people writing **image-generation** prompts, and people writing **review / critique** prompts. Each type has a different rubric, different lint rules, and a different template library, so the demo doubles as a teaching tool about how prompt anatomy changes with the output medium.

## Goals

1. Ship a polished, production-grade demo that holds up next to the Pokédex and Ant Buster in scope and finish.
2. Give visitors three modes (Build, Lint, Library) across three prompt types (Write, Image, Review) inside one cohesive page.
3. Stay client-side only: no API keys, no server proxy, no rate limiting, no privacy footprint beyond localStorage.
4. Persist work-in-progress drafts across reloads, per type.
5. Make every view deep-linkable through the URL hash.
6. Respect every constraint the existing codebase has surfaced through prior demos (inline-script encoding bug, var-hoisting, full-bleed background, mobile-portrait playability).

## Non-goals

- Live model calls of any kind (no OpenAI / Anthropic / Stability / Midjourney calls).
- User accounts, saved-prompts library, or cross-device sync.
- Export / import of drafts as JSON.
- Undo / version history.
- Multi-language prompt support.
- Server-side rendering of the assembled prompt or templates.
- Any new PHP module under `inc/`. All logic lives in the pattern file.

## Design

### 1. Two-level navigation

The page exposes two orthogonal axes:

- **Prompt type** — segmented control at the top: `Write · Image · Review`. Selecting a type swaps the rubric (Build fields), the lint rules, and the template library underneath.
- **Mode** — tab strip beneath the type bar: `Build · Lint · Library`. Operates within the current type.

So `Write + Build` is the landing view. `Image + Library` is one click away. Nine total views, but the structure stays consistent so the cognitive load is one mental model, not nine.

The two axes carry into the URL hash as `#<type>/<mode>`. Examples: `#image/library`, `#review/build`. An optional query segment `?t=<templateId>` after the hash opens a specific template modal (`#write/library?t=write-code-pr-review`).

### 2. State model

One module-scope state object inside the IIFE:

```js
const state = {
  currentType: 'write',                          // 'write' | 'image' | 'review'
  currentMode: 'build',                          // 'build' | 'lint' | 'library'
  drafts: {
    write:  { role, task, context, outputFormat, constraints,
              examples, audience, tone, advancedOpen },
    image:  { subject, style, composition, medium, colorLighting,
              cameraLens, mood, negative, aspectRatio, advancedOpen },
    review: { content, criteria, audience, outputFormat,
              severity, tone, examples, advancedOpen },
  },
  lint:    { input, detectedType, lastResult },
  library: { search, category },
};
```

Persistence rules:

- localStorage key: `dariobimbi.promptStudio.v1`.
- Persisted slice: `currentType`, `currentMode`, `drafts`. View state (`lint.*`, `library.*`) is intentionally not persisted; it resets on reload.
- Saves debounced ~200ms after any draft keystroke or mode switch.
- The save function reads from `state` and writes to localStorage. It never touches DOM inputs. DOM input values are written by JS **only** on initial page load and when a user explicitly clicks "Use as starting point" from a library card modal.

Init order on page load:

1. Parse the URL hash → set `currentType` and `currentMode`.
2. For anything the hash did not provide, fall back to localStorage.
3. For anything still missing, fall back to defaults (`write` / `build`).

The URL always wins.

### 3. Routing

The router is **idempotent**. The pattern:

- Click handlers (type chip, mode tab) do **not** mutate `state.currentType` / `state.currentMode` and render directly. They update the hash via `history.pushState` and return.
- A single `popstate` listener reads the hash, overwrites `state.currentType` / `state.currentMode`, and triggers the render path.
- Initial page load fires the same router from the parsed hash.

This guarantees a single render path, prevents double-renders, and gives back-button navigation for free.

Template modal open/close uses `history.replaceState` instead of `pushState`, so opening and closing a modal does not add history entries.

### 4. Build mode

One form per type, with **progressive disclosure**: a compact set of visible fields and an Advanced toggle that reveals 2–3 deeper fields. Each field has a real `<label>`, a one-line helper text linked by `aria-describedby`, and an example placeholder cycled once per page load (never while the user is typing).

Below the form sits a live **Assembled Prompt** preview pane: monospace, surface-card background, a Copy-to-clipboard button, and a "Lint this" button that pushes the assembled prompt to the Lint tab and switches modes. Alongside the preview, a **token meter** chip estimates token count using `Math.ceil(text.length / 4)`. The Review pane shows two numbers side by side: `Instructions: N tok · Content: M tok`, so a long pasted artifact does not slam the instruction cap. Soft caps with green / amber / red banding:

- Write: ~400 tokens.
- Image: ~80 tokens.
- Review instructions: ~600 tokens. Content: warning band only above ~3000 tokens.

#### 4a. Write rubric

**Visible**

- Role — "You are a…"
- Task — explicit verb + object
- Context — background the model needs
- Output Format — shape of the answer
- Constraints — what to avoid or stay within

**Advanced** (Examples first because few-shotting is the highest-leverage fix)

- Examples (few-shot) — repeatable input/output pair rows
- Audience — who the output is for
- Tone — voice for the answer

**Assembly order**: `Role → Task → Context → Examples → Output Format → Constraints → Tone/Audience`. Constraints sits right before Tone/Audience to exploit model recency bias. Empty fields are skipped silently (no `Role: (empty)` clutter).

#### 4b. Image rubric

**Visible**

- Subject — the main thing
- Style — artistic style
- Composition — framing, angle
- Medium — physical or digital medium
- Color & Lighting — palette + light

**Advanced**

- Camera / Lens — for photorealistic shots
- Mood / Atmosphere — feel
- Negative prompt — what to exclude
- Aspect Ratio / Parameters — e.g. `--ar 16:9 --v 6`, `--niji`

**Assembly order**: comma-separated descriptors `Subject, Style, Composition, Medium, Color & Lighting, Camera, Mood`. The Negative prompt renders on its own line under `Negative:`. The Aspect Ratio / Parameters field appends to the end of the main line as platform flags. This shape works for Midjourney and Stable Diffusion; DALL-E users get a usable but slightly comma-heavy prompt.

#### 4c. Review rubric

**Visible**

- Content to review — large textarea where the user pastes the artifact being critiqued
- Review criteria — what to evaluate against
- Audience for feedback — who reads the review
- Output format — shape of the review

**Advanced**

- Severity scale — define levels
- Tone of feedback — voice
- Examples of good/bad — anchors for calibration

**Assembly order**: `(implicit Role: "Review the following content.") → Criteria → Audience → Output Format → Severity → Tone → Content (fenced with triple backticks) → closing CTA "Begin your review now based on the criteria above."` The closing CTA keeps the model anchored on the task after a long content block, and the triple-backtick fence protects against the model confusing instructions and material.

### 5. Lint mode

#### 5a. Input panel

- Textarea labeled "Paste a prompt to analyze."
- Type-detection chip row underneath. On paste or significant edit, an auto-detector picks a type and the chip animates to show `Auto-detected: <Type> · click to override`. The user can click another chip to override.
- Explicit **Analyze** button. The lint does not run on every keystroke.

**Detection heuristics** (case-insensitive throughout):

- **Image** wins on any of: comma-heavy descriptors with few full sentences; Midjourney / Stable Diffusion flags (`--ar`, `--v\s*\d`, `--niji`, `--style`); recognized style keywords (`photorealistic`, `oil painting`, `35mm`, `watercolor`, `octane render`, `midjourney`, `stable diffusion`).
- **Review** wins on any of: a triple-backtick fenced block; a triple-quote fenced block (users mix these up); words `review`, `critique`, `feedback`, `evaluate`, `rate`.
- **Write** is the default fallback.

#### 5b. Results panel

After Analyze, the panel renders:

- A **score ring** 0–100 with color band (emerald ≥ 80, amber 50–79, red < 50) and a short verdict line ("Strong" / "Workable" / "Needs work"). Numeric value rendered in text alongside the ring so color is never the only signal.
- A **rubric checklist** — one row per field in the active type's rubric, each row a check / tilde / x icon + field name + one-line explanation of what was or wasn't detected.
- A stack of **anti-pattern flags**, each a short title + one-line rationale:
  - *Polite filler* — `please`, `thanks in advance`. LLMs do not weight courtesy as instruction.
  - *Vague qualifier* — `comprehensive`, `thoroughly`, `in detail`. Replace with a concrete length or list.
  - *Soft task verb* — `help me with…`, `I want…`. Restate as an imperative.
  - *"Don't" bias* (Write) — `do not …`, `don't …`, `avoid …` without an affirmative alternative. Suggest rephrasing as a positive instruction.
  - *Conflicting styles* (Image) — `photorealistic anime`, `watercolor 3D render`.
  - *Style soup* (Image) — more than 5 comma-separated style/quality keywords (`8k`, `octane render`, `trending on artstation`, `masterpiece`, `hyperdetailed`). Modern models ignore the noise or degrade in quality.
  - *Missing aspect ratio* (Image) — most platforms expect `--ar` or equivalent.
  - *Unfenced content* (Review) — content runs into instructions; model will conflate the two.
- **Actions**: **Refine in Builder** parses the prompt back into Builder fields and switches to Build. **Copy results** copies score + suggestions as plain text. **Clear** resets the input.

#### 5c. Parse-back behavior

Best-effort parser, never lossy:

- **Write**: regex-match section headers (`Role:`, `Task:`, `Context:`, etc.). If the prompt is unstructured, dump the entire prompt into the Task field and leave the rest empty.
- **Image**: split on commas, bucket into Subject (first 1–2 chunks) / Style / Composition / Lighting / Camera by keyword matching. Anything after `Negative:` goes into the Negative prompt field. Aspect ratio flags (`--ar 16:9`, `--v 6`) pulled into the Aspect Ratio field.
- **Review**: locate the fenced block (triple backticks or triple quotes) → Content. Instructions above the fence get split into Criteria / Audience / Output Format by keyword. Trailing CTA stripped.
- Confirm-if-dirty: if the Build draft for that type already has content, show "This will replace your current draft. Continue?"
- Fallback toast: an **amber 5-second alert toast** ("Could not auto-split into fields. Loaded into Task for manual editing.") when the parser falls back to the dump-everything path. Five seconds is long enough for the user to read and understand why their text was not distributed.

### 6. Library mode

#### 6a. Categories (locked, 3 per type)

- **Write**: Code · Creative/Long-form · Strategy/Learning
- **Image**: Photorealistic · Illustration/Art · UI/Design Assets
- **Review**: Code/Diffs · Copy/Marketing · Structural/Documents

#### 6b. Top of view

- Filter chips for the current type's three categories, plus an "All" chip that clears the filter.
- Search input — substring match on template title and summary, filters the grid live.

#### 6c. Grid of template cards

- Layout: 1 column on mobile (≤ 720px), 2 columns 721–1023px, 3 columns ≥ 1024px.
- Each card: title, 1-line summary, small category badge, category-colored stripe along the top, surface-card background, hover lift.
- Click anywhere on the card → preview modal opens.

#### 6d. Preview modal

- Full-screen on mobile, centered dialog ≥ 1024px.
- Title and summary at top.
- Read-only assembled prompt rendered in a monospace panel — exactly what the user would get if they clicked Copy without further edits.
- Two buttons:
  - **Copy** — copies the assembled prompt to clipboard, closes modal, toast `Copied.`
  - **Use as starting point** — loads the template's structured fields into the Builder draft for that type and switches to Build. Confirm-if-dirty.
- **Focus management**: when the modal opens, the JS caches `document.activeElement` into a local variable. ESC, the close button, or "Copy" closing the modal restores focus by explicitly calling `.focus()` on the cached element. Focus is trapped inside the modal while open (tab cycles within the modal).

#### 6e. Template object shape

Templates live as a `const TEMPLATES = [...]` literal at the top of the IIFE, immediately after the rubric definitions. 27 entries (3 categories × 3 templates × 3 types), banner-commented by type for scan navigation.

```js
{
  id: 'write-code-pr-review',
  type: 'write',
  category: 'code',
  title: 'PR review request',
  summary: 'Ask the LLM to review a pull request diff against a rubric.',
  fields: {
    role: '…', task: '…', context: '…', outputFormat: '…',
    constraints: '…', examples: '', audience: '', tone: ''
  }
}
```

Roughly 250–300 lines for the array, dense and scan-friendly, separated from runtime logic by a banner comment.

### 7. Rendering approach

- Three view containers in the DOM (`#view-build`, `#view-lint`, `#view-library`). CSS toggles `hidden` per current mode.
- The Build form rebuilds its fields fully on type switch (different field set per type). On a within-type keystroke, only the assembled-prompt preview and the token meter re-render; the form fields are never touched, so focus and caret position are preserved.
- The Lint and Library views each have one container that swaps contents per type.
- A single `render(state)` function knows how to draw the whole page from `state`. The router calls it after parsing the hash. Action handlers do not call `render` directly; they either update the hash (for type/mode changes) or mutate `state` and call `render` (for in-view interactions like opening a modal).

### 8. Inline-script discipline (CLAUDE.md tax)

Three known booby-traps for inline `<script>` in pattern PHP. Implementation must obey these strictly:

1. **No logical `&&` anywhere in the inline JS.** WordPress's content filters inconsistently encode `&&` to `&#038;&#038;` inside `<script>` blocks, and browsers do not decode entities inside scripts. Instead of `if (a && b) { … }`, write:
   ```js
   if (a) {
     if (b) { … }
   }
   ```
   Or use `indexOf` checks, helper booleans, or early returns. The single allowed form of conjunction is nested `if`. (Logical `||` and other operators that overlap with entities are also suspect. Where they appear, prefer the more verbose form.)
2. **All large data structures declared `const` at the top of the IIFE**, before any function that reads them. The current planned blocks are `RUBRICS`, `TEMPLATES`, `LINT_RULES`, `STYLE_KEYWORDS`, `ANTI_PATTERNS`. Function declarations are hoisted; `var fn = function () {}` is not, so shared helpers use function declarations.
3. **Verify after edit**: `curl` the served page once and grep the script tags for `&#038;&#038;` before declaring done. Any hit means a `&&` slipped through and must be rewritten.

### 9. File layout and WordPress wiring

New files:

- `patterns/demo-prompt-studio.php` — single file with all markup, CSS, JS, and templates.
- `templates/page-demo-prompt-studio.html` — thin shell invoking the pattern via `<!-- wp:pattern {"slug":"dariobimbi/demo-prompt-studio"} /-->`.

Edited files:

- `theme.json` — append to `customTemplates`:
  ```json
  {
    "name": "page-demo-prompt-studio",
    "title": "Demo: Prompt Studio",
    "postTypes": ["page"]
  }
  ```
- `style.css` — bump `Version:`. This cache-busts the enqueued stylesheet and re-fires reconciliation in `inc/auto-pages.php` so the admin-side page provisioning runs on the next admin pageview.

Admin one-time setup (happens automatically via `inc/auto-pages.php` on the next admin pageview, or can be done manually): create a child Page under the existing "demos" parent, slug `prompt-studio`, template "Demo: Prompt Studio", body left empty.

### 10. Full-bleed background quirk

The page-pattern wrapper convention paints inside the constrained layout, but this demo wants a soft surface tint edge-to-edge to separate the workshop from the rest of the site. Per the recipe in CLAUDE.md:

- On init, the JS adds `document.body.classList.add('has-prompt-studio-page')`.
- The pattern's scoped CSS includes `body.has-prompt-studio-page { background-color: var(--wp--preset--color--surface) !important; }`.
- The `!important` is required to outrank the body background generated from `theme.json`.

### 11. Accessibility

- Type chips: `role="tablist"`, each chip `role="tab"`, `aria-selected` reflects current selection. Left/right arrow keys cycle.
- Mode tabs: same tablist pattern.
- Build form fields: each has a real `<label>` and a helper line linked via `aria-describedby`. Advanced toggle uses `aria-expanded`.
- Modal: focus trap, ESC closes, focus restored to the cached `document.activeElement` on close.
- Score ring: numeric value rendered in text alongside the colored ring (color is not the only signal).
- Toasts: `role="status"` for the green success toast; `role="alert"` for the amber parse-back warning toast.
- All clickable elements reachable by Tab in DOM order. No `tabindex` traps beyond the modal's intentional one.

### 12. Responsive breakpoints

Following Ant Buster's mobile lessons:

- **≤ 480px (portrait)**: type chips stacked full-width, mode tabs scroll horizontally if they overflow, Build form single column, library grid 1 column, modal full-screen.
- **481–720px**: type chips inline, library grid still 1 column.
- **721–1023px**: library grid 2 columns.
- **≥ 1024px**: library grid 3 columns, modal becomes a centered dialog.

### 13. Visual treatment

Follows site design-token conventions in `theme.json`. Spacing scale (`var:preset|spacing|*`), color palette (`primary`, `accent`, `surface`, `surface-card`, etc.), gradient slugs for headings.

- Page-pattern shell: standard `content-page prompt-studio` wrapper with eyebrow paragraph and `has-gradient-text` h1.
- Type segmented control: `Write` keyed to `primary` (blue), `Image` to `accent` (violet), `Review` to `hot` (gold/pink). Each chip carries its color when active so the type is visually clear at a glance.
- Mode tabs: chip-style like the Pokédex generation chips, neutral until selected.
- Assembled-prompt preview pane: monospace, surface-card gradient background, rounded corners.
- Token meter: small pill, color matches band (emerald/amber/red).
- Score ring: SVG arc with band color, numeric value centered.
- Library cards: surface-card background, category-colored stripe across the top edge.
- Library modal: surface background, max-width 720px on desktop.

## Risks and known constraints

- **Template-array authoring is the long pole.** 27 high-quality templates require deliberate writing across nine cells of the type × category grid. Each must reflect the rubric of its type and feel like something a real user would want to start from. This is content work, not code work, and it cannot be shortcut.
- **The inline `<script>` `&&` trap is silent.** A working demo can break in production with no console error until a visitor hits the wrong code path. The `curl + grep` verification step is mandatory before merge.
- **localStorage is not free.** Three drafts × ~8 string fields each can hit ~10 KB if a user pastes a long Review content. Well under the 5 MB cap, but the save function should not write on every keystroke (debounce ~200ms).
- **The token estimator is a rough estimate.** `chars / 4` matches the OpenAI BPE rule of thumb but is wrong for non-English text and for very short prompts. Acceptable for a UX hint, not for billing math. The UI says "~ N tokens" with the tilde, never "N tokens" without it.
- **Type detection in Lint can misfire.** A short Write prompt with a comma list could read as Image. The override chip is the escape hatch; the auto-detect is best-effort, not authoritative.

## Out of scope (v1, deferred)

- Live model calls of any kind.
- User accounts, server-stored prompts, share links beyond the templated deep link.
- Export / import of drafts as JSON.
- Cross-device sync.
- Undo / version history.
- Multi-language prompt support.
- Server-side rendering of the assembled prompt.
- New `inc/` PHP modules.

## Acceptance

The demo is shippable when:

1. `/demos/prompt-studio/` loads with the Write/Build view by default on a fresh visit.
2. All nine view combinations (3 types × 3 modes) render correctly.
3. Hash deep links (`#image/library`, `#write/library?t=write-code-pr-review`, `#review/build`) load directly into the matching view.
4. Build drafts persist across reload for all three types.
5. Build → Lint round trip works for all three types.
6. Lint → Build parse-back works for structured prompts and falls back cleanly with the amber toast on unstructured input.
7. Library → Build "Use as starting point" loads template fields and switches modes, with a confirm-if-dirty prompt when appropriate.
8. All 27 templates are authored and render correctly in the grid and modal.
9. `curl` of the live page contains no `&#038;&#038;` in any `<script>` block.
10. The demo is usable on a 375 × 667 iPhone SE viewport (the Ant Buster mobile benchmark).
11. Keyboard nav reaches every interactive element. ESC closes the modal and restores focus to the opener.
12. The full-bleed surface background paints edge to edge with no theme.json bleed at the viewport edges.
13. Theme `Version:` is bumped; admin reconciliation creates the page automatically on next admin pageview.
