# Color Token Consolidation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Route every site-chrome color through theme.json tokens (translucency via `color-mix`), warm the stale cool-palette leftovers, and give each self-contained demo its own scoped local-token block — so a future palette change propagates cleanly and brand art stays deliberately literal.

**Architecture:** Four layers. (1) `theme.json` core button → tokens. (2) `style.css` site chrome → tokens + a thin `:root` semantic layer (`--color-success`). (3) `style.css` demos-hub preview art → fix the `.demo-card` accent system, warm neutral preview chrome, comment brand art. (4) `demo-*.php` → per-file scoped local-token block (own prefix), warm stale chrome, leave canvas/sprite art literal.

**Tech Stack:** Plain CSS + theme.json (WordPress block theme). No build step, no test suite. Verification is grep (no surviving stale values), `php -l` (PHP demos), curl entity-check, and logged-out visual spot-check at desktop + mobile widths.

**Conventions for every task:**
- Edits are by exact string match (the executor reads the file first, then edits). Line numbers below are approximate anchors from the audit and will drift as edits land — match on the selector/value, not the line number.
- `color-mix` mapping rule: an `rgba(R,G,B,A)` that duplicates token `T` becomes `color-mix(in srgb, var(--wp--preset--color--T) {A*100}%, transparent)`. Eyeball the high-visibility ones; alpha≈percent is close but not identical.
- Token value reference: `background #12100c`, `surface #1b1712`, `subtle #2a241c`, `foreground #efe9df`, `muted #a89f8f`, `primary #f43f5e`, `primary-soft #fda4af`, `accent #e11d48`, `accent-soft #fecdd3`, `highlight #facc15`.
- **Neutral overlays stay literal:** `rgba(255,255,255,X)` and `rgba(0,0,0,X)` used for elevation/scrim/masks are lighting effects, not palette colors — do NOT touch them.
- PHP lint command (real interpreter path; confirm version folder with `Get-ChildItem c:/wamp64/bin/php` if it fails): `c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/<file>.php`
- Local site URL for curl checks: `http://localhost/dariobimbi.com/`

---

### Task 1: theme.json core button → tokens

**Files:**
- Modify: `theme.json` (`styles.elements.button`, ~lines 99-106)

- [ ] **Step 1: Make the edit**

Replace the button block. Keep button **text pure white** (decided). Only background + hover/focus route to tokens.

Old:
```json
            "button": {
                "color":   { "background": "#e11d48", "text": "#ffffff" },
                "border":  { "radius": "999px" },
                "spacing": { "padding": { "top": "0.85rem", "bottom": "0.85rem", "left": "1.6rem", "right": "1.6rem" } },
                "typography": { "fontWeight": "700", "fontSize": "var(--wp--preset--font-size--medium)" },
                ":hover":  { "color": { "background": "#f43f5e" } },
                ":focus":  { "color": { "background": "#f43f5e" } }
            }
```

New:
```json
            "button": {
                "color":   { "background": "var(--wp--preset--color--accent)", "text": "#ffffff" },
                "border":  { "radius": "999px" },
                "spacing": { "padding": { "top": "0.85rem", "bottom": "0.85rem", "left": "1.6rem", "right": "1.6rem" } },
                "typography": { "fontWeight": "700", "fontSize": "var(--wp--preset--font-size--medium)" },
                ":hover":  { "color": { "background": "var(--wp--preset--color--primary)" } },
                ":focus":  { "color": { "background": "var(--wp--preset--color--primary)" } }
            }
```

- [ ] **Step 2: Validate JSON**

Run: `c:/wamp64/bin/php/php8.2.29/php.exe -r "echo json_decode(file_get_contents('theme.json'))===null?'INVALID':'OK';"`
Expected: `OK`

- [ ] **Step 3: Commit**

```bash
git add theme.json
git commit -m "refactor(theme): route core button bg/hover to accent/primary tokens"
```

---

### Task 2: style.css — add :root semantic layer, fix ::selection + utility pills

**Files:**
- Modify: `style.css` (header comment ~line 20; `::selection` ~line 51; `.status-pill` ~108-109; `.skill-pill*` ~133-161)

- [ ] **Step 1: Insert the semantic `:root` layer**

Immediately after the opening comment block (after line ~20, before the View Transitions section), insert:

```css
/* ============================================================
   Functional (non-brand) tokens. Brand colors live in theme.json;
   these are semantic states that intentionally do NOT recolor with
   the brand. Translucent brand tints are produced inline with
   color-mix() against the theme.json preset vars — no rgba triplets.
   ============================================================ */
:root {
    --color-success: #22c55e;
}
```

- [ ] **Step 2: Fix `::selection`**

Old: `::selection { background: var(--wp--preset--color--accent); color: #fff; }`
New: `::selection { background: var(--wp--preset--color--accent); color: var(--wp--preset--color--foreground); }`

- [ ] **Step 3: Route status-pill to `--color-success`**

Old:
```css
    background: #22c55e;
    box-shadow: 0 0 0 4px rgba(34,197,94,0.18);
```
New:
```css
    background: var(--color-success);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-success) 18%, transparent);
```

- [ ] **Step 4: Route skill-pill borders/text/glows**

Apply these replacements in the `.skill-pill*` block:
- `.is-primary` border `rgba(244,63,94,0.45)` → `color-mix(in srgb, var(--wp--preset--color--primary) 45%, transparent)`
- `.is-accent` border `rgba(225,29,72,0.45)` → `color-mix(in srgb, var(--wp--preset--color--accent) 45%, transparent)`
- `.is-hot` border `rgba(244,63,94,0.45)` → `color-mix(in srgb, var(--wp--preset--color--hot) 45%, transparent)`; its `color: #fda4af` → `color: var(--wp--preset--color--primary-soft)`
- `.is-yellow` border `rgba(250,204,21,0.45)` → `color-mix(in srgb, var(--wp--preset--color--highlight) 45%, transparent)`; its `color: #fde68a` → `color: color-mix(in srgb, var(--wp--preset--color--highlight) 55%, white)`
- The `.is-primary`/`.is-hot` hover-glow trio (`rgba(244,63,94,0.14)`, `rgba(244,63,94,0.85)`, `rgba(244,63,94,0.55)`) → primary at 14% / 85% / 55%
- The `.is-accent` trio (`rgba(225,29,72,0.14/0.85/0.55)`) → accent at 14% / 85% / 55%
- The `.is-yellow` trio (`rgba(250,204,21,0.14/0.85/0.5)`) → highlight at 14% / 85% / 50%

- [ ] **Step 5: Verify no stale pill values remain**

Run: `grep -nE "rgba\((244,63,94|225,29,72|250,204,21|34,197,94)" style.css` (lines 1-170 only)
Expected: no matches in the utilities section (matches further down are handled by later tasks).

- [ ] **Step 6: Commit**

```bash
git add style.css
git commit -m "refactor(css): add --color-success, tokenize ::selection + skill/status pills"
```

---

### Task 3: style.css — cards + contact form

**Files:**
- Modify: `style.css` (`.dario-card` ~184, 223-225; contact form ~252-307)

- [ ] **Step 1: Card shadows/glows → color-mix**

- `.dario-card` shadow `rgba(244,63,94,0.35)` → `color-mix(in srgb, var(--wp--preset--color--primary) 35%, transparent)`
- The hover-glow trio `rgba(244,63,94,0.45)`, `rgba(244,63,94,0.5)`, `rgba(250,204,21,0.4)` → primary 45%, primary 50%, highlight 40%

- [ ] **Step 2: Contact form**

- Focus ring `rgba(244, 63, 94, 0.25)` → `color-mix(in srgb, var(--wp--preset--color--primary) 25%, transparent)`
- Success state: bg `rgba(34, 197, 94, 0.12)` → `color-mix(in srgb, var(--color-success) 12%, transparent)`; border `rgba(34, 197, 94, 0.45)` → `color-mix(in srgb, var(--color-success) 45%, transparent)`; text `#bbf7d0` → `color-mix(in srgb, var(--color-success) 45%, white)`
- Error state: bg `rgba(244, 63, 94, 0.12)` → `color-mix(in srgb, var(--wp--preset--color--primary) 12%, transparent)`; border `rgba(244, 63, 94, 0.45)` → `color-mix(in srgb, var(--wp--preset--color--primary) 45%, transparent)`; text `#fecaca` → `var(--wp--preset--color--accent-soft)`
- **Leave** the `rgba(255,255,255,0.04)`, `rgba(255,255,255,0.06)`, `rgba(255,255,255,0.14)` neutral input overlays untouched.

- [ ] **Step 3: Commit**

```bash
git add style.css
git commit -m "refactor(css): tokenize card glows + contact form success/error/focus"
```

---

### Task 4: style.css — hero + section meshes

**Files:**
- Modify: `style.css` (section meshes ~396, 444, 498-499; hero grid/dots/ant ~561-562, 706, 713-715)

- [ ] **Step 1: Section/mesh brand tints → color-mix**

- `~396` gradient `rgba(244, 63, 94, 0.18)`, `rgba(225, 29, 72, 0.18)` → primary 18%, accent 18%
- `~444` `rgba(244, 63, 94, 0.12)` → primary 12%
- `~498-499` mesh `rgba(244,63,94,0.10)`, `rgba(225,29,72,0.10)` → primary 10%, accent 10%

- [ ] **Step 2: Hero details**

- `~561-562` grid texture `rgba(168, 159, 143, 0.06)` (×2) → `color-mix(in srgb, var(--wp--preset--color--muted) 6%, transparent)`
- `~706` dotted trail `rgba(244, 63, 94, 0.4)` → `color-mix(in srgb, var(--wp--preset--color--primary) 40%, transparent)`
- `~713, 715` hero ant body `#2a1f1c` (3 occurrences across `background` + the two `box-shadow` offsets) → `var(--wp--preset--color--subtle)` (near-identical warm tone, keeps the easter-egg ant recolor-safe)
- **Leave** the `mask-image` `#000` and `rgba(0,0,0,0.65)` shadow untouched (neutral mask/scrim).

- [ ] **Step 3: Commit**

```bash
git add style.css
git commit -m "refactor(css): tokenize hero + section mesh brand tints"
```

---

### Task 5: style.css — side-rail (warm the navy) + project cards

**Files:**
- Modify: `style.css` (side-rail ~1121-1155; project card ~1278)

- [ ] **Step 1: Warm the side-rail frosted glass (stale cool navy)**

- `~1121` `rgba(10, 14, 26, 0.85)` → `color-mix(in srgb, var(--wp--preset--color--background) 85%, transparent)`
- `~1132` `rgba(10, 14, 26, 0.55)` → `color-mix(in srgb, var(--wp--preset--color--background) 55%, transparent)`
- `~1154` active border `rgba(244, 63, 94, 0.5)` → `color-mix(in srgb, var(--wp--preset--color--primary) 50%, transparent)`
- **Leave** `rgba(255,255,255,0.06)` and the `rgba(0,0,0,…)` shadows.

- [ ] **Step 2: Project card hover shadow**

- `~1278` `rgba(244, 63, 94, 0.45)` → `color-mix(in srgb, var(--wp--preset--color--primary) 45%, transparent)`

- [ ] **Step 3: Verify the navy is gone**

Run: `grep -nE "rgba\(10, ?14, ?26" style.css`
Expected: no matches.

- [ ] **Step 4: Commit**

```bash
git add style.css
git commit -m "refactor(css): warm side-rail glass off old cool navy; tokenize project card glow"
```

---

### Task 6: style.css — fix the `.demo-card` accent system (Layer 3)

**Files:**
- Modify: `style.css` (`.demo-card` token block ~1449-1463; `rgba(var(--demo-accent-rgb)…)` usages ~1484, 2318, 2353)

- [ ] **Step 1: Rewrite the accent token block**

Remove the retired `--blue`/`--purple` variants and the stranded RGB triplet. Source everything from the palette.

Old:
```css
.demo-card { --demo-accent: var(--wp--preset--color--primary); --demo-accent-soft: var(--wp--preset--color--primary-soft); --demo-accent-rgb: 59, 130, 246; --demo-gradient: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); }
.demo-card--blue   { --demo-accent: #3b82f6; --demo-accent-soft: #60a5fa; --demo-accent-rgb: 59, 130, 246;
                     --demo-gradient: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); }
.demo-card--purple { --demo-accent: #a855f7; --demo-accent-soft: #c084fc; --demo-accent-rgb: 168, 85, 247;
                     --demo-gradient: linear-gradient(135deg, #a855f7 0%, #c084fc 100%); }
.demo-card--pink   { --demo-accent: #f43f5e; --demo-accent-soft: #fb7185; --demo-accent-rgb: 244, 63, 94;
                     --demo-gradient: linear-gradient(135deg, #f43f5e 0%, #fb7185 100%); }
.demo-card--yellow { --demo-accent: #facc15; --demo-accent-soft: #fde68a; --demo-accent-rgb: 250, 204, 21;
                     --demo-gradient: linear-gradient(135deg, #facc15 0%, #fde68a 100%); }
```

New:
```css
.demo-card {
    --demo-accent: var(--wp--preset--color--primary);
    --demo-accent-soft: var(--wp--preset--color--primary-soft);
    --demo-gradient: var(--wp--preset--gradient--brand);
}
.demo-card--pink   { --demo-accent: var(--wp--preset--color--primary); --demo-accent-soft: var(--wp--preset--color--primary-soft); --demo-gradient: var(--wp--preset--gradient--brand); }
.demo-card--yellow { --demo-accent: var(--wp--preset--color--highlight); --demo-accent-soft: color-mix(in srgb, var(--wp--preset--color--highlight) 55%, white); --demo-gradient: linear-gradient(135deg, var(--wp--preset--color--highlight) 0%, color-mix(in srgb, var(--wp--preset--color--highlight) 55%, white) 100%); }
```

- [ ] **Step 2: Convert the `--demo-accent-rgb` rgba() usages**

Each `rgba(var(--demo-accent-rgb), A)` becomes `color-mix(in srgb, var(--demo-accent) {A*100}%, transparent)`:
- `~1484` `rgba(var(--demo-accent-rgb), 0.45)` → `color-mix(in srgb, var(--demo-accent) 45%, transparent)`
- `~2318` `rgba(var(--demo-accent-rgb), 0.45)` → `color-mix(in srgb, var(--demo-accent) 45%, transparent)`
- `~2353` `rgba(var(--demo-accent-rgb), 0.4)` → `color-mix(in srgb, var(--demo-accent) 40%, transparent)`

- [ ] **Step 3: Find any HTML still using the removed variant classes**

Run: `grep -rnE "demo-card--(blue|purple)" patterns/ templates/`
Expected: review each hit. If `demos-hub.php` (or wherever the cards are built) assigns `demo-card--blue`/`--purple` to a card, change those to `demo-card--pink` or `--yellow` so no card silently loses its accent. (Record which cards changed in the commit message.)

- [ ] **Step 4: Verify**

Run: `grep -nE "demo-accent-rgb|#3b82f6|#a855f7|#60a5fa|#c084fc|#fb7185" style.css`
Expected: no matches inside the `.demo-card` token block (matches elsewhere in preview art are handled in Task 7).

- [ ] **Step 5: Commit**

```bash
git add style.css patterns/
git commit -m "refactor(css): retire demo-card blue/purple, source accents from palette"
```

---

### Task 7: style.css — warm neutral preview chrome + annotate brand art (Layer 3)

**Files:**
- Modify: `style.css` (demos-hub preview art, ~1500-2400)

**Decision rule for this task:** A preview color is CHROME (→ token) only if it is a generic "dark UI window" backdrop or neutral text/border that is NOT depicting the demo's subject. If the color depicts the demo's look (Game Boy LCD green, weather sky blue, Pokémon red/yellow, pug tans, one-dark code syntax, microgame actor green/red/orange, audio spectrum), it is ART → stays literal, gets a brand-fidelity comment.

- [ ] **Step 1: Wrap each brand-art cluster with a marker comment**

For each cluster below, add `/* brand-fidelity — intentional, do not tokenize */` on the line above the cluster's first rule and `/* end brand-fidelity */` after its last:
- Game Boy LCD greens (`#9bbc0f`, `#8bac0f`, `#0f380f`, `#538a3e`, `#7bb661`) — pokedex + microgame previews
- Pokémon red/yellow (`#ee1c25`, `#ffcb05`, `#f59e0b`, `#1d1d1f`) — card-browser preview
- Weather preview sky (`#1e3a8a`→`#3b82f6`, `#0a1f3a`, `#60a5fa`→`#c084fc`) — these are the weather demo's sky, ART
- Pug-attack scene (`#6fb6e8`, `#8fbb55`, `#5d8838`, `#d2b48c`, `#8b6f4e`, `#1a1a1a`, `#1f1f1f`) and ant (`#2a1f1c`)
- Sort/live-editor one-dark syntax (`#8b9bbd`, `#f43f5e`, `#60a5fa`, `#facc15`, `#a855f7`, `#cdd5e6`, `#5e6a85`)
- Microgame actors (`#2dff5a`, `#ff8c1a`, `#ff4848`, `#050a07`)

(Leaving these literal is the point — the comment makes it a documented choice, not a miss.)

- [ ] **Step 2: Warm genuinely-neutral preview window backdrops**

Only where the backdrop is a plain "dark app window" not tied to a specific demo's palette, route to tokens:
- Generic preview card body backdrops currently `#0a0e1a` that are NOT inside a brand-art cluster above → `var(--wp--preset--color--background)`
- Generic neutral inner-surface `#050813` not inside a brand-art cluster → `var(--wp--preset--color--surface)`

(If a given `#0a0e1a`/`#050813` is inside one of Step 1's clusters — e.g. it's the dark backing of the weather/live-editor scene — leave it; it is art.)

- [ ] **Step 3: Verify nothing neutral-stale slipped through**

Run: `grep -nE "#0a0e1a|#050813" style.css`
Expected: every remaining match is inside a `/* brand-fidelity … */` block (eyeball the list). Note the count in the commit message.

- [ ] **Step 4: Commit**

```bash
git add style.css
git commit -m "refactor(css): warm neutral demo-preview chrome, annotate brand-art clusters"
```

---

### Task 8: demo-weather.php — scoped local tokens + warm chrome (Layer 4)

**Files:**
- Modify: `patterns/demo-weather.php` (`<style>` ~79-386)

- [ ] **Step 1: Add a scoped local-token block**

At the very top of the `<style>` block, add (the demo's own `--wx-` namespace, values = warm palette literals so the file stays copy-paste portable):

```css
  .demo-weather {
    --wx-bg: #12100c;        /* = background */
    --wx-surface: #1b1712;   /* = surface */
    --wx-code-bg: #14110d;   /* deep surface for code panes */
    --wx-fg: #efe9df;        /* = foreground */
    --wx-accent: #f43f5e;    /* = primary */
    --wx-accent-2: #e11d48;  /* = accent */
  }
```
(If the `<style>` rules are not already scoped under `.demo-weather`, confirm the demo's root wrapper class — match it to whatever the markup uses; the audit shows `.wx-*` element classes, so the wrapper is the pattern's outer group. Place the token block on that wrapper.)

- [ ] **Step 2: Route chrome to the locals**

- `.wx-input` bg `#0a0e1a` → `var(--wx-bg)`
- `.wx-hint code` bg `#050813` → `var(--wx-code-bg)`
- `.wx-btn--primary` bg `linear-gradient(135deg, #3b82f6, #a855f7)` → `linear-gradient(135deg, var(--wx-accent), var(--wx-accent-2))`; keep `color: #fff`
- `.wx-spin` `border-top-color: #fff` → leave (neutral white spinner)
- `.wx-day` bg `#0a0e1a` → `var(--wx-surface)`
- `.wx-json` bg `#0a0e1a` → `var(--wx-surface)`
- `.wx-json-body` bg `#050813` → `var(--wx-code-bg)`; `color #e8ecf6` → `var(--wx-fg)`

- [ ] **Step 3: Leave as ART (do not touch)**

`.wx-error` pink (`#fda4af`, `rgba(244,63,94,…)`); the `.wx-now` blue/violet "current conditions" card gradient (sky motif); the JSON syntax-highlight colors `#61afef`/`#98c379`/`#d19a66`/`#c678dd`/`#8a93a6`. Add a `/* brand-fidelity — sky + syntax colors, intentional */` comment above the `.wx-now` and `.wx-json-body .jk` clusters.

- [ ] **Step 4: Lint + verify**

Run: `c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-weather.php`
Expected: `No syntax errors detected`
Run: `grep -nE "#0a0e1a|#050813|#3b82f6, #a855f7" patterns/demo-weather.php`
Expected: no matches.

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-weather.php
git commit -m "refactor(weather demo): scoped --wx- tokens, warm chrome off old navy"
```

---

### Task 9: demo-audio.php — scoped local tokens + warm chrome (Layer 4)

**Files:**
- Modify: `patterns/demo-audio.php` (`<style>` ~96-241)

- [ ] **Step 1: Add scoped local-token block** on the demo's wrapper class (audit shows `.av-*` element classes):

```css
  .demo-audio {
    --av-bg: #12100c;       /* = background */
    --av-surface: #1b1712;  /* = surface */
    --av-stage: #14110d;    /* deep canvas backdrop */
    --av-accent: #f43f5e;   /* = primary */
    --av-accent-2: #e11d48; /* = accent */
  }
```

- [ ] **Step 2: Route chrome to the locals**

- `.av-stage` bg `#050813` → `var(--av-stage)`
- `.av-stage-overlay` `rgba(5, 8, 19, 0.85)` → `color-mix(in srgb, var(--av-stage) 85%, transparent)`
- `.av-btn--primary` bg `linear-gradient(135deg, #3b82f6, #a855f7)` → `linear-gradient(135deg, var(--av-accent), var(--av-accent-2))`; keep `color: #fff`
- `.av-mode` bg `#0a0e1a` → `var(--av-surface)`
- `.av-mode-btn.is-on` bg `linear-gradient(135deg, rgba(59,130,246,0.4), rgba(168,85,247,0.4))` → `linear-gradient(135deg, color-mix(in srgb, var(--av-accent) 40%, transparent), color-mix(in srgb, var(--av-accent-2) 40%, transparent))`
- `.av-knob select, .av-knob input[type="range"]` bg `#0a0e1a` → `var(--av-surface)`

- [ ] **Step 3: Leave as ART**

The canvas draw colors in the `<script>` (`drawBars` `#3b82f6`/`#a855f7`/`#f43f5e`, `drawWave` `#60a5fa`/`#c084fc`/`#fb7185`, the `hsl(...)` ring) are the audio visualization — leave literal. Add a `/* brand-fidelity — visualizer spectrum, intentional */` comment above `drawBars`.

- [ ] **Step 4: Lint + verify**

Run: `c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-audio.php` → `No syntax errors detected`
Run: `grep -nE "#0a0e1a|#050813|rgba\(5, ?8, ?19" patterns/demo-audio.php` (CSS block only, ~96-241) → no matches.

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-audio.php
git commit -m "refactor(audio demo): scoped --av- tokens, warm chrome off old navy"
```

---

### Task 10: demo-sort.php — scoped local tokens + warm chrome (Layer 4)

**Files:**
- Modify: `patterns/demo-sort.php` (`<style>` ~109-312)

- [ ] **Step 1: Add scoped local-token block** on the `.demo-sort` wrapper (audit shows `.sr-*` classes):

```css
  .demo-sort {
    --sr-bg: #12100c;
    --sr-panel: #14110d;
    --sr-accent: #f43f5e;
    --sr-accent-2: #e11d48;
  }
```

- [ ] **Step 2: Route chrome**

- `.sr-knob input[type="range"]` bg `#0a0e1a` → `var(--sr-bg)`
- `.sr-btn--go` bg `linear-gradient(135deg, #3b82f6, #a855f7)` → `linear-gradient(135deg, var(--sr-accent), var(--sr-accent-2))`; its two box-shadows `rgba(168,85,247,0.55/0.7)` → `color-mix(in srgb, var(--sr-accent-2) 55%, transparent)` / `… 70% …`
- `.sr-btn--go[data-state="running"]` bg `linear-gradient(135deg, #f43f5e, #a855f7)` → `linear-gradient(135deg, var(--sr-accent), var(--sr-accent-2))`; its box-shadows `rgba(244,63,94,0.55/0.7)` → `color-mix(in srgb, var(--sr-accent) 55%/70%, transparent)`
- `.sr-btn:hover` `color: #fff` → leave
- `.sr-panel` bg `#050813` → `var(--sr-panel)`
- `.sr-panel canvas` bg `#050813` → `var(--sr-panel)`

- [ ] **Step 3: Leave as ART**

The medal colors — gold (`#facc15`, `#fde68a`, `rgba(250,204,21,…)`), silver (`#cbd5e1`, `#e2e8f0`, `rgba(203,213,225,…)`), bronze (`#d97706`, `#fbbf24`, `rgba(217,119,6,…)`) and the neutral 4th-place `rgba(148,163,184,0.25)` — are podium semantics. Comment the medal cluster `/* brand-fidelity — podium medals, intentional */`.

- [ ] **Step 4: Lint + verify**

Run: `c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-sort.php` → `No syntax errors detected`
Run: `grep -nE "#0a0e1a|#050813|#3b82f6" patterns/demo-sort.php` → no matches.

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-sort.php
git commit -m "refactor(sort demo): scoped --sr- tokens, warm chrome + button gradient"
```

---

### Task 11: demo-life / demo-microgame / demo-pug-attack — warm stage backdrops (Layer 4)

**Files:**
- Modify: `patterns/demo-life.php` (`<style>` ~74-188)
- Modify: `patterns/demo-microgame.php` (`<style>` ~45-87)
- Modify: `patterns/demo-pug-attack.php` (`<style>` ~45-76)

These three have only a stale stage backdrop (and life also has a button gradient). Small, isolated — use direct warm literals (no token block needed for a single value, keeps the file minimal and portable).

- [ ] **Step 1: demo-life.php**

- `.gl-stage` bg `#050813` → `#14110d`
- `.gl-btn--primary` bg `linear-gradient(135deg, #3b82f6, #a855f7)` → `linear-gradient(135deg, #f43f5e, #e11d48)`

- [ ] **Step 2: demo-microgame.php**

- `.mg-stage` bg `#050813` → `#14110d`
- Leave the `<script>` game colors (`#00ff00` wall, `#D68000` goal, `#ff0000` player, `#fff` HUD, `#000`) — ART.

- [ ] **Step 3: demo-pug-attack.php**

- `.pa-stage` bg `#050813` → `#14110d`
- Leave the `<script>` scene colors and `#cbd5e1` instruction text — ART.

- [ ] **Step 4: Lint all three**

Run: `c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-life.php; c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-microgame.php; c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-pug-attack.php`
Expected: `No syntax errors detected` ×3.
Run: `grep -nE "#050813|#3b82f6, #a855f7" patterns/demo-life.php patterns/demo-microgame.php patterns/demo-pug-attack.php` → no matches.

- [ ] **Step 5: Commit**

```bash
git add patterns/demo-life.php patterns/demo-microgame.php patterns/demo-pug-attack.php
git commit -m "refactor(life/microgame/pug demos): warm stage backdrops off old navy"
```

---

### Task 12: demo-pokedex.php — warm the stale chrome inside the existing token block (Layer 4)

**Files:**
- Modify: `patterns/demo-pokedex.php` (`<style>` ~120-1032; already has a `.demo-pokedex { --pdex-* }` block at 123-135)

This file already routes most chrome through `--pdex-*` tokens (good). Only a few hardcoded stale values remain.

- [ ] **Step 1: Warm the stale hardcoded chrome**

- `.pdex-modal-backdrop` bg `rgba(10, 14, 26, 0.85)` → `color-mix(in srgb, var(--wp--preset--color--background) 85%, transparent)`
- `.pdex-type-badge` `color: #0a0e1a` → `color: var(--wp--preset--color--background)`
- `.pdex-modal-tcg-link` `color: #0a0e1a` → `color: var(--wp--preset--color--background)`
- `.pdex-tile:hover` shadow `rgba(59, 130, 246, 0.15)` → `color-mix(in srgb, var(--wp--preset--color--primary) 15%, transparent)`
- `.pdex-card:hover` shadow `rgba(168, 85, 247, 0.18)` → `color-mix(in srgb, var(--wp--preset--color--primary) 18%, transparent)`

- [ ] **Step 2: Leave as ART**

The 18 Pokémon type-badge colors (~427-444), gen-tint gradients (~903-911), type-glow radials (~913-930), type-accent vars (~945-962), sparkline `#10b981`/`#ef4444` (up/down trend). Add `/* brand-fidelity — Pokémon type colors, intentional */` above the type-badge color table.

- [ ] **Step 3: Lint + verify**

Run: `c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-pokedex.php` → `No syntax errors detected`
Run: `grep -nE "#0a0e1a|rgba\(10, ?14, ?26|rgba\(59, ?130, ?246|rgba\(168, ?85, ?247" patterns/demo-pokedex.php` → no matches.

- [ ] **Step 4: Commit**

```bash
git add patterns/demo-pokedex.php
git commit -m "refactor(pokedex demo): warm stale modal/badge/hover chrome; keep type colors"
```

---

### Task 13: demo-ant-buster.php — warm the two chrome tokens (Layer 4)

**Files:**
- Modify: `patterns/demo-ant-buster.php` (`<style>` ~33-85; token block 34-37)

The file already has a `.demo-ant-buster { --ab-bg; --ab-frame }` block — both are old cool values. Everything else is canvas/sprite art (leave).

- [ ] **Step 1: Warm the two tokens**

Old:
```css
  --ab-bg: #1a1c2c;
  --ab-frame: #2a2f3e;
```
New:
```css
  --ab-bg: #14110d;      /* warm canvas backdrop */
  --ab-frame: #2a241c;   /* = subtle, warm frame */
```

- [ ] **Step 2: Confirm the rest is art**

Leave the `rgba(0,0,0,0.3)` stage shadow and all `<script>` canvas/sprite hex. (No comment needed — the JS sprite tables are self-evidently game art.)

- [ ] **Step 3: Lint + verify**

Run: `c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-ant-buster.php` → `No syntax errors detected`
Run: `grep -nE "#1a1c2c|#2a2f3e" patterns/demo-ant-buster.php` → no matches.

- [ ] **Step 4: Commit**

```bash
git add patterns/demo-ant-buster.php
git commit -m "refactor(ant-buster demo): warm --ab-bg/--ab-frame chrome tokens"
```

---

### Task 14: demo-contrast.php + demo-prompt-studio.php — scope semantic state colors (Layer 4)

**Files:**
- Modify: `patterns/demo-contrast.php` (`<style>` ~107-323)
- Modify: `patterns/demo-prompt-studio.php` (`<style>` ~60-329)

Neither has stale cool chrome — both only use semantic pass/warn/fail colors, which are subject ART (a contrast checker's whole point is showing pass/fail; a prompt scorer shows green/amber/red). Per the brand-art-literal decision these stay literal; this task just **documents** them so they read as intentional and aren't mistaken for misses in a future sweep.

- [ ] **Step 1: demo-contrast.php** — add `/* brand-fidelity — WCAG pass/fail semantics, intentional */` above the `.cc-grade`/`.cc-tile` color rules (the `#4ade80` green, `#facc15` AA yellow, `#f43f5e` fail pink, and their rgba tile states). No value changes. The lone neutral `rgba(148,163,184,0.15)` tile-mark backdrop → leave (neutral overlay).

- [ ] **Step 2: demo-prompt-studio.php** — add `/* brand-fidelity — score semantics, intentional */` above the `.ps-score-*` / `.ps-check-*` rules (`#16a34a`, `#d97706`, `#dc2626`). Leave the `rgba(0,0,0,…)` shadows/backdrops (neutral).

- [ ] **Step 3: Lint**

Run: `c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-contrast.php; c:/wamp64/bin/php/php8.2.29/php.exe -l patterns/demo-prompt-studio.php`
Expected: `No syntax errors detected` ×2.

- [ ] **Step 4: Commit**

```bash
git add patterns/demo-contrast.php patterns/demo-prompt-studio.php
git commit -m "docs(contrast/prompt demos): annotate semantic state colors as brand-fidelity"
```

---

### Task 15: Version bump + full verification sweep

**Files:**
- Modify: `style.css` (`Version:` header, line 7)

- [ ] **Step 1: Bump the theme version**

Old: `Version: 0.33.0`
New: `Version: 0.34.0`

- [ ] **Step 2: Global stale-color sweep (chrome only)**

Run: `grep -nE "#3b82f6|#a855f7|#60a5fa|#c084fc|rgba\(10, ?14, ?26|#0a0e1a|#050813|#1a1c2c|#2a2f3e" style.css patterns/*.php`
Expected: every remaining hit is inside a `/* brand-fidelity … */` block (weather sky, live-editor one-dark, etc.). Read each hit and confirm. List the surviving (intentional) count.

- [ ] **Step 3: HTML-entity safety check on served pages**

Start WAMP, then run:
`curl -s "http://localhost/dariobimbi.com/demos/weather/" | grep -E "&#038;|color-mix"`
Expected: `color-mix(` appears; `&#038;` does NOT. Repeat for `/demos/audio/`, `/demos/sort/`, the homepage `/dariobimbi.com/`, and `/demos/`.
(`color-mix(in srgb, …)` has no `&`/`<`/`>`, so it is entity-safe, but confirm no existing inline-script `&&` got mangled by the edits.)

- [ ] **Step 4: Visual spot-check (logged-out, incognito)**

Confirm at desktop AND a narrow mobile width:
- Homepage: hero, skill pills, status pill, contact form, side-rail all warm; no cool-navy glass on the side rail.
- Demos hub: every card has a pink or yellow accent (no blue/purple), previews intact.
- Each touched demo page: backgrounds warm, primary buttons pink-gradient, subject art (Game Boy green, weather sky, podium medals, Pokémon types) unchanged.
- Buttons still have crisp white text on pink.

- [ ] **Step 5: Commit**

```bash
git add style.css
git commit -m "chore(theme): bump version to 0.34.0 for color token consolidation"
```

---

## Self-review notes

- **Spec coverage:** Layer 1 → Task 1. Layer 2 → Tasks 2-5. Layer 3 → Tasks 6-7. Layer 4 → Tasks 8-14. Verification + version bump → Task 15. All spec sections covered.
- **Button text:** kept pure `#ffffff` per the user's correction (Task 1).
- **Per-demo prefixes:** weather `--wx-`, audio `--av-`, sort `--sr-`, pokedex `--pdex-` (existing), ant-buster `--ab-` (existing), card-browser `--dcb-` (existing, untouched). No prefix collisions.
- **color-mix percentages** are `alpha × 100`; Task 15 visual check is the backstop for any that read off.
- **Brand-art-literal** honored throughout: every literal art cluster gets a `/* brand-fidelity */` marker rather than a value change.
- **No test suite exists** (plain PHP/CSS) — verification is grep + `php -l` + curl entity-check + logged-out visual, which matches the project's actual workflow.
