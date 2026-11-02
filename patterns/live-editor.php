<?php
/**
 * Title: Demo — Live Editor
 * Slug: dariobimbi/demo-live-editor
 * Categories: dariobimbi
 * Description: A two-pane mini code editor with live CSS preview rendered inside a Shadow DOM. The textarea, custom syntax highlighter, and preview are all hand-built (no CodeMirror, no Prism).
 *
 * Note: the textarea below contains the initial CSS shown to visitors. Inside
 * <textarea>, content is RCDATA — `<` is verbatim, only `&` needs escaping, and
 * the block ends at `</textarea>`. The current snippet contains neither, so it
 * sits here as plain text without any encoding. The Reset button restores
 * whatever is in here (textarea.defaultValue).
 *
 * Single-file demo: the scoped <style> and inline <script> at the bottom are the
 * whole editor. Nothing here is wired to assets/dariobimbi.js or style.css,
 * except the .t-comment/.t-string/.t-func classes that the 404 page shares.
 */
?>
<!-- wp:group {"align":"full","className":"section","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--30)">

    <!-- wp:paragraph {"className":"eyebrow"} -->
    <p class="eyebrow">a live css editor</p>
    <!-- /wp:paragraph -->

    <!-- wp:heading {"level":1,"className":"has-gradient-text","style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <h1 class="wp-block-heading has-gradient-text" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)">Live Editor</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"textColor":"muted","style":{"typography":{"lineHeight":"1.55"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-muted-color has-text-color" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--40)">Edit the CSS on the left. The panel on the right re-renders on every keystroke. The preview lives inside a Shadow DOM, so your selectors only style the preview and not the rest of the page. The syntax highlighter is hand-rolled (no library), the tokenizer fits in about 30 lines, and the whole thing runs without a build step.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <div class="codepen" data-cp>

        <div class="codepen-tools" role="group" aria-label="Starting points">
            <span class="codepen-toolsLabel" aria-hidden="true">Start from</span>
            <button type="button" class="codepen-btn" data-preset="light">Light card</button>
            <button type="button" class="codepen-btn" data-preset="gradient">Gradient</button>
            <button type="button" class="codepen-btn" data-preset="outline">Outline</button>
            <button type="button" class="codepen-btn" data-preset="retro">Retro</button>
            <button type="button" class="codepen-btn codepen-btn--reset" data-reset>Reset</button>
        </div>

        <div class="codepen-shell">

            <div class="codepen-pane codepen-editor" role="region" aria-label="CSS code editor">
                <header class="codepen-bar" aria-hidden="true">
                    <span class="codepen-dot codepen-dot--r"></span>
                    <span class="codepen-dot codepen-dot--y"></span>
                    <span class="codepen-dot codepen-dot--g"></span>
                    <span class="codepen-filename">card.css</span>
                </header>
                <div class="codepen-editorWrap">
                    <pre class="codepen-highlight" aria-hidden="true"><code data-highlight></code></pre>
                    <label for="codepen-input-css" class="screen-reader-text">CSS source. Tab indents, Shift and Tab outdents. Press Escape and then Tab to leave the editor.</label>
                    <textarea id="codepen-input-css" class="codepen-input" spellcheck="false" autocorrect="off" autocapitalize="off" wrap="off" aria-describedby="codepen-input-help" data-input>/* Live preview, edit anything! */

:root {
  --bg:      #1b1712;
  --border:  #2a241c;
  --primary: #f43f5e;
  --accent:  #e11d48;
  --hot:     #f43f5e;
  --radius:  14px;
  --pad:     28px;
}

.demo {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: var(--pad);
  color: #efe9df;
  font-family: ui-sans-serif, system-ui, sans-serif;
  transition: all 220ms ease;
}

.demo h4 {
  background: linear-gradient(135deg, var(--primary), var(--accent));
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  margin: 0 0 12px;
  font-size: 1.4rem;
  letter-spacing: -0.01em;
}

.demo .tag {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  background: var(--hot);
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.demo p {
  color: #a89f8f;
  margin: 14px 0 0;
  line-height: 1.55;
}</textarea>
                </div>
            </div>

            <div class="codepen-pane codepen-preview" role="region" aria-label="Live preview">
                <header class="codepen-bar" aria-hidden="true">
                    <span class="codepen-filename">preview · shadow dom</span>
                </header>
                <div class="codepen-frame">
                    <div class="codepen-host" data-preview aria-live="polite" aria-atomic="false"></div>
                </div>
            </div>

        </div>

        <p id="codepen-input-help" class="codepen-hint">Try changing <code>--accent</code> to <code>#facc15</code>, <code>--radius</code> to <code>32px</code>, or the <code>linear-gradient</code> direction. Tab indents and Shift plus Tab outdents. To move on to the next control, press Escape and then Tab.</p>
    </div>

    <style>
    /* ---- Live editor ---------------------------------------------------- */
    .codepen {
        /* warm editor chrome, from the theme palette */
        --le-bg:      #12100c;
        --le-surface: #1b1712;
        --le-subtle:  #2a241c;
        --le-fg:      #efe9df;
        --le-muted:   #a89f8f;
        --le-caret:   #f43f5e;
        --le-focus:   #fda4af;
        /* shared by the textarea and the highlight layer so they can never drift */
        --le-fs:      16px; /* 16px stops iOS zooming on focus */
        --le-lh:      1.55;
        margin-top: var(--wp--preset--spacing--20);
    }
    @media (min-width: 480px) {
        .codepen { --le-fs: 0.85rem; }
    }

    .codepen-tools {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
    }
    .codepen-toolsLabel {
        margin-right: 4px;
        font-size: 0.85rem;
        color: var(--le-muted);
    }
    .codepen-btn {
        font: inherit;
        font-size: 0.85rem;
        font-weight: 600;
        min-height: 40px;
        padding: 0 16px;
        color: var(--le-fg);
        background: var(--le-surface);
        border: 1px solid var(--le-subtle);
        border-radius: 999px;
        cursor: pointer;
        transition: border-color 150ms ease, background-color 150ms ease;
    }
    .codepen-btn:hover {
        background: var(--le-subtle);
        border-color: var(--le-caret);
    }
    .codepen-btn--reset { margin-left: auto; }

    .codepen-shell {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        border-radius: 14px;
    }
    @media (max-width: 880px) {
        .codepen-shell { grid-template-columns: 1fr; }
    }

    .codepen-pane {
        background: var(--le-surface);
        border: 1px solid var(--le-subtle);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-width: 0; /* let pre/textarea actually overflow horizontally */
    }
    .codepen-pane:focus-within {
        box-shadow: 0 0 0 2px var(--le-focus);
    }

    .codepen-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        background: var(--le-bg);
        border-bottom: 1px solid var(--le-subtle);
    }

    .codepen-dot {
        width: 11px;
        height: 11px;
        border-radius: 50%;
        flex: 0 0 auto;
    }
    /* brand-fidelity: window traffic-light dots intentional, do not tokenize */
    .codepen-dot--r { background: #ff5f57; }
    .codepen-dot--y { background: #febc2e; }
    .codepen-dot--g { background: #28c840; }

    .codepen-filename {
        margin-left: 8px;
        font-family: var(--wp--preset--font-family--mono);
        font-size: 0.78rem;
        color: var(--le-muted);
        letter-spacing: 0.02em;
    }

    .codepen-editorWrap {
        position: relative;
        height: 460px;
        background: var(--le-bg);
        overflow: hidden;
    }

    /* Both layers share every metric that affects where a glyph lands. */
    .codepen-highlight,
    .codepen-input {
        position: absolute;
        inset: 0;
        margin: 0;
        padding: 16px 18px;
        border: 0;
        font-family: var(--wp--preset--font-family--mono);
        font-size: var(--le-fs);
        line-height: var(--le-lh);
        letter-spacing: normal;
        font-variant-ligatures: none;
        text-size-adjust: 100%;
        -webkit-text-size-adjust: 100%;
        white-space: pre;
        word-wrap: normal;
        overflow-wrap: normal;
        word-break: normal;
        tab-size: 2;
        -moz-tab-size: 2;
        box-sizing: border-box;
    }

    .codepen-highlight {
        color: var(--le-fg);
        pointer-events: none;
        overflow: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--le-subtle) transparent;
    }
    .codepen-highlight code { display: block; }

    .codepen-input {
        background: transparent;
        color: transparent;
        caret-color: var(--le-caret);
        outline: 0;
        resize: none;
        overflow: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--le-subtle) transparent;
        z-index: 2;
    }
    /* The pane rings on focus-within, so skip the clipped outline here. */
    .codepen-input:focus-visible { outline: none; }

    /* Token colors: one-dark-ish, all values verified at 4.5:1 or better on the editor background.
       The 404 page shares .t-comment, .t-string and .t-func from style.css. */
    /* brand-fidelity: one-dark syntax colors intentional, do not tokenize */
    .codepen .t-comment   { color: #8a93a6; font-style: italic; }
    .codepen .t-string    { color: #98c379; }
    .codepen .t-variable  { color: #c678dd; }
    .codepen .t-color     { color: #e5c07b; }
    .codepen .t-swatch {
        display: inline-block;
        width: 0.7em; height: 0.7em;
        margin-right: 4px;
        border-radius: 2px;
        border: 1px solid rgba(255,255,255,0.18);
        vertical-align: -1px;
    }
    .codepen .t-number    { color: #d19a66; }
    .codepen .t-property  { color: #61afef; }
    .codepen .t-func      { color: #56b6c2; }
    .codepen .t-brace     { color: #abb2bf; }

    /* The frame is the containing block for anything the visitor's CSS
       fixes or absolutely positions (layout + paint containment), and it
       clips whatever pokes out. isolation keeps z-index inside it too. */
    .codepen-frame {
        flex: 1;
        padding: 24px;
        background: var(--le-bg);
        min-height: 460px;
        position: relative;
        overflow: hidden;
        contain: layout paint;
        isolation: isolate;
    }
    .codepen-host {
        height: 100%;
        min-height: 100%;
    }

    .codepen-hint {
        margin-top: 18px;
        color: var(--wp--preset--color--muted);
        font-size: 0.85rem;
        text-align: center;
    }
    .codepen-hint code {
        background: var(--wp--preset--color--surface);
        padding: 2px 7px;
        border-radius: 4px;
        font-family: var(--wp--preset--font-family--mono);
        font-size: 0.8rem;
        color: var(--wp--preset--color--accent-soft);
        border: 1px solid var(--wp--preset--color--subtle);
    }
    </style>

    <script>
    /* Live CSS editor: a textarea over a highlighted <pre>, with the preview
       rendered in a Shadow DOM. Hand-rolled tokenizer, no libraries. */
    (function () {
        // Single regex with named capture groups picks the first matching token type.
        var TOKEN_RE = /(?<comment>\/\*[\s\S]*?\*\/)|(?<string>"(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*')|(?<variable>--[\w-]+)|(?<color>#[0-9a-fA-F]{3,8}\b)|(?<func>\b[a-zA-Z][\w-]*(?=\s*\())|(?<number>\b\d+(?:\.\d+)?(?:px|em|rem|%|vh|vw|s|ms|deg|fr)?\b)|(?<prop>\b[a-zA-Z-][\w-]*(?=\s*:(?!\/)))|(?<brace>[{}])/g;

        var PRESETS = {
            light: [
                '/* Light card */',
                '',
                ':root {',
                '  --bg:     #faf6ef;',
                '  --text:   #2a241c;',
                '  --accent: #e11d48;',
                '  --radius: 18px;',
                '  --pad:    28px;',
                '}',
                '',
                '.demo {',
                '  background: var(--bg);',
                '  border-radius: var(--radius);',
                '  padding: var(--pad);',
                '  color: var(--text);',
                '  font-family: ui-sans-serif, system-ui, sans-serif;',
                '  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);',
                '}',
                '',
                '.demo h4 {',
                '  color: var(--accent);',
                '  margin: 0 0 12px;',
                '  font-size: 1.4rem;',
                '}',
                '',
                '.demo .tag {',
                '  display: inline-block;',
                '  padding: 4px 10px;',
                '  border-radius: 999px;',
                '  background: var(--accent);',
                '  color: white;',
                '  font-size: 0.75rem;',
                '  font-weight: 700;',
                '  letter-spacing: 0.08em;',
                '  text-transform: uppercase;',
                '}',
                '',
                '.demo p {',
                '  color: #5b5246;',
                '  margin: 14px 0 0;',
                '  line-height: 1.55;',
                '}'
            ].join('\n'),
            gradient: [
                '/* Gradient */',
                '',
                ':root {',
                '  --from:   #f43f5e;',
                '  --to:     #facc15;',
                '  --accent: #12100c;',
                '  --radius: 24px;',
                '  --pad:    32px;',
                '}',
                '',
                '.demo {',
                '  background: linear-gradient(135deg, var(--from), var(--to));',
                '  border-radius: var(--radius);',
                '  padding: var(--pad);',
                '  color: #12100c;',
                '  font-family: ui-sans-serif, system-ui, sans-serif;',
                '}',
                '',
                '.demo h4 {',
                '  color: var(--accent);',
                '  margin: 0 0 12px;',
                '  font-size: 1.5rem;',
                '  letter-spacing: -0.01em;',
                '}',
                '',
                '.demo .tag {',
                '  display: inline-block;',
                '  padding: 4px 10px;',
                '  border-radius: 999px;',
                '  background: var(--accent);',
                '  color: #facc15;',
                '  font-size: 0.75rem;',
                '  font-weight: 700;',
                '  letter-spacing: 0.08em;',
                '  text-transform: uppercase;',
                '}',
                '',
                '.demo p {',
                '  margin: 14px 0 0;',
                '  line-height: 1.55;',
                '}'
            ].join('\n'),
            outline: [
                '/* Outline */',
                '',
                ':root {',
                '  --accent: #f43f5e;',
                '  --radius: 10px;',
                '  --pad:    28px;',
                '}',
                '',
                '.demo {',
                '  background: transparent;',
                '  border: 2px solid var(--accent);',
                '  border-radius: var(--radius);',
                '  padding: var(--pad);',
                '  color: #efe9df;',
                '  font-family: ui-sans-serif, system-ui, sans-serif;',
                '  transition: box-shadow 220ms ease;',
                '}',
                '',
                '.demo:hover {',
                '  box-shadow: 0 0 0 6px rgba(244, 63, 94, 0.25);',
                '}',
                '',
                '.demo h4 {',
                '  margin: 0 0 12px;',
                '  font-size: 1.4rem;',
                '}',
                '',
                '.demo .tag {',
                '  display: inline-block;',
                '  padding: 3px 10px;',
                '  border: 1px solid var(--accent);',
                '  border-radius: var(--radius);',
                '  color: #fda4af;',
                '  font-size: 0.75rem;',
                '  font-weight: 700;',
                '  letter-spacing: 0.08em;',
                '  text-transform: uppercase;',
                '}',
                '',
                '.demo p {',
                '  color: #a89f8f;',
                '  margin: 14px 0 0;',
                '  line-height: 1.55;',
                '}'
            ].join('\n'),
            retro: [
                '/* Retro */',
                '',
                ':root {',
                '  --paper:  #facc15;',
                '  --ink:    #12100c;',
                '  --accent: #f43f5e;',
                '  --radius: 4px;',
                '  --pad:    26px;',
                '}',
                '',
                '.demo {',
                '  background: var(--paper);',
                '  border: 3px solid var(--ink);',
                '  border-radius: var(--radius);',
                '  padding: var(--pad);',
                '  color: var(--ink);',
                '  font-family: ui-monospace, Menlo, Consolas, monospace;',
                '  box-shadow: 8px 8px 0 var(--ink);',
                '}',
                '',
                '.demo h4 {',
                '  margin: 0 0 12px;',
                '  font-size: 1.3rem;',
                '  text-transform: uppercase;',
                '}',
                '',
                '.demo .tag {',
                '  display: inline-block;',
                '  padding: 3px 8px;',
                '  border: 2px solid var(--ink);',
                '  background: var(--accent);',
                '  color: white;',
                '  font-size: 0.75rem;',
                '  font-weight: 700;',
                '  letter-spacing: 0.08em;',
                '  text-transform: uppercase;',
                '}',
                '',
                '.demo p {',
                '  margin: 14px 0 0;',
                '  line-height: 1.55;',
                '}'
            ].join('\n')
        };

        // The visitor's <style> goes last so their rules win ties against the base rules.
        var PREVIEW_HTML =
            '<style>' +
            ':host { display:block; height:100%; }' +
            '.demo-mount { margin:0; height:100%; display:flex; align-items:center; justify-content:center; }' +
            '</style>' +
            '<div class="demo-mount">' +
            '<div class="demo">' +
            '<span class="tag">live</span>' +
            '<h4>Hello from the preview pane.</h4>' +
            '<p>Every keystroke in the editor re-applies this CSS in real time. The panel runs in a Shadow DOM, so your selectors only style this preview.</p>' +
            '</div>' +
            '</div>' +
            '<style id="user-style"></style>';

        function escapeHtml(s) {
            return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function highlightCss(src) {
            return escapeHtml(src).replace(TOKEN_RE, function (match) {
                // the named-groups object is always the last argument
                var g = arguments[arguments.length - 1];
                if (g.comment)  return '<span class="t-comment">'  + match + '</span>';
                if (g.string)   return '<span class="t-string">'   + match + '</span>';
                if (g.variable) return '<span class="t-variable">' + match + '</span>';
                if (g.color) {
                    return '<span class="t-color"><span class="t-swatch" style="background:' + match + '"></span>' + match + '</span>';
                }
                if (g.number)   return '<span class="t-number">'   + match + '</span>';
                if (g.brace)    return '<span class="t-brace">'    + match + '</span>';
                if (g.func)     return '<span class="t-func">'     + match + '</span>';
                return '<span class="t-property">' + match + '</span>';
            });
        }

        // Replace a range through the browser's own edit path so Ctrl+Z keeps
        // working; fall back to setRangeText where execCommand is unavailable.
        function replaceRange(input, start, end, text) {
            input.focus();
            input.setSelectionRange(start, end);
            var done = false;
            try { done = document.execCommand('insertText', false, text); } catch (_) { done = false; }
            if (!done) {
                input.setRangeText(text, start, end, 'end');
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }

        // Indent or outdent every line the selection touches.
        function shiftLines(input, outdent) {
            var v  = input.value;
            var s  = input.selectionStart;
            var en = input.selectionEnd;
            var from = (s === 0) ? 0 : v.lastIndexOf('\n', s - 1) + 1;
            var to = en;
            // a selection that ends at the start of a line does not include that line
            if (en > s) {
                if (v.charAt(en - 1) === '\n') to = en - 1;
            }
            var lineEnd = v.indexOf('\n', to);
            if (lineEnd === -1) lineEnd = v.length;

            var firstDelta = 0;
            var total = 0;
            var out = v.slice(from, lineEnd).split('\n').map(function (line, i) {
                var next = line;
                var delta = 0;
                if (outdent) {
                    var m = /^( {1,2}|\t)/.exec(line);
                    if (m) {
                        next = line.slice(m[0].length);
                        delta = -m[0].length;
                    }
                } else if (line.length) {
                    next = '  ' + line;
                    delta = 2;
                }
                if (i === 0) firstDelta = delta;
                total += delta;
                return next;
            });
            if (total === 0) return;

            var ns = Math.max(from, s + firstDelta);
            var ne = Math.max(ns, en + total);
            replaceRange(input, from, lineEnd, out.join('\n'));
            input.setSelectionRange(ns, ne);
        }

        function initLiveEditor(root) {
            var input   = root.querySelector('[data-input]');
            var code    = root.querySelector('[data-highlight]');
            var pre     = root.querySelector('.codepen-highlight');
            var mount   = root.querySelector('[data-preview]');
            if (!input) return;
            if (!code) return;
            if (!pre) return;
            if (!mount) return;

            var shadow = mount.attachShadow ? mount.attachShadow({ mode: 'open' }) : null;
            if (!shadow) return; // very old browser; bail silently
            shadow.innerHTML = PREVIEW_HTML;
            var styleEl = shadow.getElementById('user-style');

            function update() {
                var value = input.value;
                code.innerHTML = highlightCss(value);
                // :root matches nothing inside a shadow tree, so point it at the host
                // (custom properties set there inherit down into the preview).
                if (styleEl) styleEl.textContent = value.replace(/:root\b/g, ':host');
            }

            input.addEventListener('input', update);

            // Keep the highlight scrolled in lockstep with the textarea.
            input.addEventListener('scroll', function () {
                pre.scrollTop  = input.scrollTop;
                pre.scrollLeft = input.scrollLeft;
            });

            // Tab indents instead of moving focus, EXCEPT immediately after
            // Esc, which lets keyboard users leave the editor (WCAG 2.1.2
            // No Keyboard Trap). Same pattern as CodePen / CodeMirror / Monaco.
            var escArmed = false;
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    escArmed = true;
                    return;
                }
                // modifier keys alone must not cancel the Esc arm (Esc, then Shift+Tab)
                if (e.key === 'Shift' || e.key === 'Control' || e.key === 'Alt' || e.key === 'Meta') return;
                if (e.key === 'Tab') {
                    if (escArmed) {
                        escArmed = false;
                        return; // allow the browser to move focus
                    }
                    // leave Ctrl/Alt/Cmd + Tab to the browser and OS
                    if (e.ctrlKey || e.altKey || e.metaKey) return;
                    e.preventDefault();
                    var s  = input.selectionStart;
                    var en = input.selectionEnd;
                    var multiLine = input.value.slice(s, en).indexOf('\n') !== -1;
                    if (e.shiftKey) {
                        shiftLines(input, true);
                    } else if (multiLine) {
                        shiftLines(input, false);
                    } else {
                        replaceRange(input, s, en, '  ');
                    }
                    return;
                }
                escArmed = false; // any other key cancels the escape arm
            });
            input.addEventListener('blur', function () { escArmed = false; });

            function load(text) {
                replaceRange(input, 0, input.value.length, text);
                input.setSelectionRange(0, 0);
                input.scrollTop  = 0;
                input.scrollLeft = 0;
            }

            root.querySelectorAll('[data-preset]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var css = PRESETS[btn.getAttribute('data-preset')];
                    if (css) load(css);
                });
            });
            var resetBtn = root.querySelector('[data-reset]');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () { load(input.defaultValue); });
            }

            update();
        }

        document.querySelectorAll('[data-cp]').forEach(initLiveEditor);
    })();
    </script>
    <!-- /wp:html -->

</div>
<!-- /wp:group -->
