# Prompt Studio — implementation status

Date: 2026-05-23
Plan: [2026-05-22-prompt-studio.md](2026-05-22-prompt-studio.md)
Spec: [2026-05-22-prompt-studio-design.md](../specs/2026-05-22-prompt-studio-design.md)
Branch: `main`
Theme version on completion: `0.18.0`

## Result

All 24 tasks shipped. All 13 acceptance criteria pass. One bug found during the Phase 5 keyboard sweep (focus restore after modal close) and fixed inline.

## Commit log

```
7ad2c05 fix(prompt-studio): restore focus to opener card after closing modal
4b5676c feat(prompt-studio): author Review/Structural templates (3) — 27 total
a9fd32f feat(prompt-studio): author Review/Copy-Marketing templates (3)
b6c1457 feat(prompt-studio): author Review/Code-diffs templates (3)
e21a914 feat(prompt-studio): author Image/UI-Design templates (3)
0d8576b feat(prompt-studio): author Image/Illustration templates (3)
d96a7a4 feat(prompt-studio): author Image/Photorealistic templates (3)
147ca84 feat(prompt-studio): author Write/Strategy templates (3)
afdf4ac feat(prompt-studio): author Write/Creative templates (3)
0bb91a1 feat(prompt-studio): author Write/Code templates (3)
d0e5d4b feat(prompt-studio): add template preview modal with focus trap
5647af5 feat(prompt-studio): add Library view with category chips and search
1a5baa9 feat(prompt-studio): parse lint input back into Builder fields
5899740 feat(prompt-studio): score prompts with rubric checks and anti-pattern flags
2e0be31 feat(prompt-studio): add Lint input panel with type auto-detect
6ccf99e feat(prompt-studio): persist drafts and view state to localStorage
c1884f7 feat(prompt-studio): wire copy, lint-this, reset-draft actions and toasts
47466b6 feat(prompt-studio): assemble live prompt preview with token meter
b28a4b8 fix(prompt-studio): fully HTML-escape Build form placeholders
e39a860 feat(prompt-studio): add per-type Build form with progressive disclosure
9f19a77 fix(prompt-studio): replace logical || with explicit ternaries
1071615 feat(prompt-studio): add state object and idempotent hash router
9f925ba feat(prompt-studio): add page skeleton with type chips and mode tabs
c075b3f style(prompt-studio): match local customTemplates formatting
74f2205 feat(prompt-studio): register page template and bump theme version
```

23 commits across all four phases plus the Task 23 fix. Net additions: ~1850 lines in `patterns/demo-prompt-studio.php`, plus the new template file, theme.json entry, and version bump.

## Acceptance criteria — final pass

| # | Criterion | Result | Verification |
|---|-----------|--------|--------------|
| 1 | Fresh visit lands at Write/Build | ✅ | Cleared localStorage, navigated to `/demos/prompt-studio/`, confirmed `activeType=write`, `activeMode=build`, hash empty. |
| 2 | All 9 view combinations render | ✅ | Programmatic sweep set hash to each of the 9 combos and verified `typeOK`, `modeOK`, `viewVisible`, `otherViewsHidden`, `contentRendered` all true. |
| 3 | Hash deep links work | ✅ | `#image/library?t=image-photo-fox` direct-loaded with Image/Library active and the matching modal open. |
| 4 | Drafts persist across reload | ✅ | Set canary string into Write/task, reloaded, value survived. localStorage payload at key `dariobimbi.promptStudio.v1` contains expected shape. |
| 5 | Build → Lint round trip | ✅ | Clicked "Lint this" in Write/Build; URL became `#write/lint` and lint textarea contained the assembled prompt. |
| 6 | Lint → Build parse-back + amber toast | ✅ | Pasted unstructured prompt into Lint, clicked Analyze + Refine in Builder. Switched to `#write/build`, text landed in Task field, toast fired with text "Could not auto-split into fields. Loaded into Task for manual editing.", role="alert", class="ps-toast ps-toast-warn". |
| 7 | Library → Build "Use as starting point" | ✅ | Loaded `write-creative-blog-outline` via modal, clicked Use. Switched to `#write/build` with role / task / outputFormat populated from template. |
| 8 | All 27 templates render | ✅ | `document.querySelectorAll('.ps-lib-card').length` returns 9 for each of Write / Image / Review. |
| 9 | `curl \| grep '&#038;&#038;'` returns 0 | ✅ | Verified on live page response. Inline-script discipline held across all 23 commits. |
| 10 | Usable on iPhone SE (375×667) | ✅ | Mobile sweep walked all 9 views + modal at 375×667, no overflow or unreachable controls. Build form goes single-column, library grid goes 1-column, modal is full-screen. |
| 11 | Keyboard nav + ESC focus restore | ✅ | Arrow keys cycle type chips and mode tabs (left/right with wrap-around). Tab trap inside modal cycles between Close → Copy → Use → Close. ESC closes modal and returns focus to the opener card after the Task 23 fix. |
| 12 | Full-bleed surface background | ✅ | `body.has-prompt-studio-page` set on init, computed body bg = `rgb(17, 23, 42)` = surface `#11172a`. No theme.json bleed at viewport edges. |
| 13 | Theme version bumped, admin reconciled | ✅ | `style.css` line 7 reads `Version: 0.18.0`. `/demos/prompt-studio/` page provisioned by `inc/auto-pages.php` on the first logged-in admin visit. |

## Bug found and fixed during Phase 5

**Symptom:** ESC closing the template modal returned focus to `body` instead of to the library card that opened it.

**Root cause:** The Library grid re-renders via `innerHTML` when the route changes, which detaches the originally-focused card *before* `openTemplateModal` reads `document.activeElement`. The cached focus reference pointed at a detached node; the `try/catch` swallowed the silent failure on `.focus()`.

**Fix** (commit `7ad2c05`): Capture the opener's `data-id` in the `lib-open` action handler — before `pushRoute` triggers the re-render — and re-query the live card by id in `closeTemplateModal`. The cached `activeElement` is kept as a fallback for deep-link opens where there is no opener card.

Verified post-fix: ESC after a card-opened modal restores focus to the same card by id; ESC after a deep-link-opened modal falls back to the cached element (or body, which is acceptable since there is no opener to return to).

## Known accepted limits (carried from the spec)

- **Image assembler comma-in-subject**: a subject containing a literal comma blends visually with the descriptor list. Standard image-prompt style; not fixed.
- **Review fence delimiter**: triple-backtick fence with no language tag. If pasted content contains ` ``` ` the fence breaks. Accepted known limit.
- **Token estimator is rough**: `chars / 4` matches BPE rule of thumb. Wrong for non-English text and very short prompts. UI says "~ N tok" with the tilde, never an exact count.
- **Lint type detection can misfire**: a short Write prompt with a comma list could read as Image. The override chip is the escape hatch.

## What's not in this build (spec out-of-scope, deferred)

- Live model calls
- User accounts / saved-prompts library / cross-device sync
- Export / import of drafts as JSON
- Undo / version history
- Multi-language prompts
- Server-side rendering of the assembled prompt

## Status

**Ready to merge.** No follow-up work scheduled for v1.
