---
description: Audit user-facing copy for AI/clever-portfolio tells per the dariobimbi voice guidelines. Use before publishing new copy or when reviewing pattern PHP / template HTML for voice drift.
---

## Instructions

Mechanical pass over the rules in `CLAUDE.md`'s "Voice and copy" section. Most hits need human judgment to fix — surface them, don't auto-rewrite.

### Scope

Default: `patterns/*.php` and `templates/*.html`. If the user names a path, scan only that.

Skip: `vendor/`, `assets/`, `tools/`, `node_modules/`, files under `inc/` (those are admin-only and never reach visitors).

### What to grep

For each in-scope file, check:

1. **Em-dashes and en-dashes** (`—` `–`). Allowed only inside PHP `/** */` and `//` comments and HTML `<!-- -->` comments. Flag every other hit.
2. **Hype words** (case-insensitive, whole-word): `robust`, `leverage`, `seamless`, `delve`, `foster`, `embrace`, `journey`, `realm`, `landscape`, `comprehensive`, `intricate`, `meticulous`, `holistic`, `harness`, `streamline`, `empower`, `cultivate`, `captivating`, `vibrant`. Also flag the phrase `dive into` and the cadences `not just X but Y` / `it's not X, it's Y`.
3. **"Yes, X. Yes, Y." parallelism**: `Yes,` appearing twice within ~3 lines.
4. **Three-item rhetorical lists**: `X, Y, and Z` where each item is a clause or phrase ≥3 words. Heuristic — surface for human review, don't auto-flag short comma lists.
5. **Date-range en-dashes**: pattern like `2017 – Present` or `Jan 2014 – Dec 2014`. Should use `to`.
6. **Em-dash placeholder glyphs in dynamic UI**: `>—<` inside HTML / template strings. Should be `·` or a sensible default.
7. **CV-style fragments**: sentence-initial `Strongest in`, `Comfortable in`, `Working chops in`.

### How to run the greps

Use the `Grep` tool. For #1, the simplest pass is to grep `—|–` content-mode with `-n`, then for each hit decide if it's inside a comment by inspecting surrounding context. For #2, run a single regex with all hype words OR'd together. For #5, regex `\d{4}\s*[–-]\s*(Present|\d{4})`.

Do not invoke `bash grep` or `rg` directly — use the `Grep` tool.

### Report format

```
patterns/foo.php
  L42  em-dash in copy: "...the player — a robot — wanders..."
  L88  hype word "leverage": "...we leverage the existing schema..."
  L120 three-item list (review): "how a button feels, how fast a page loads, how clearly the copy reads"

style.css
  (none)

Total: 3 hits across 1 file.
```

If zero hits, say so plainly. End with a one-line summary.

### Don't

- Don't auto-rewrite. The voice doc says reflection > punchline; mechanical replacement loses nuance.
- Don't flag em-dashes inside PHP/JS/HTML comments — those are admin-only.
- Don't flag in `vendor/`, `assets/`, `inc/`, or `tools/`.
- Don't propose a fix for a "review" hit (three-item list) without showing the user the full sentence; some genuinely have three items.
