<?php
/**
 * Title: Demo — Live Editor
 * Slug: dariobimbi/demo-live-editor
 * Categories: dariobimbi
 * Description: A two-pane mini code editor with live CSS preview rendered inside a Shadow DOM. The textarea, custom syntax highlighter, and isolated preview are all hand-built (no CodeMirror, no Prism).
 *
 * Note: the textarea below contains the initial CSS shown to visitors. Inside
 * <textarea>, content is RCDATA — `<` is verbatim, only `&` needs escaping, and
 * the block ends at `</textarea>`. The current snippet contains neither, so it
 * sits here as plain text without any encoding.
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
    <p class="has-muted-color has-text-color" style="line-height:1.55;margin-bottom:var(--wp--preset--spacing--40)">Edit the CSS on the left. The panel on the right re-renders on every keystroke. The preview lives inside a Shadow DOM, so whatever you type stays sandboxed and can't reach the rest of the page. The syntax highlighter is hand-rolled (no library), the tokenizer fits in about 30 lines, and the whole thing runs without a build step.</p>
    <!-- /wp:paragraph -->

    <!-- wp:html -->
    <div class="codepen" data-cp>
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
                    <label for="codepen-input-css" class="screen-reader-text">CSS source. Tab inserts two spaces. Press Escape and then Tab to leave the editor.</label>
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
                    <span class="codepen-filename">preview · sandboxed shadow dom</span>
                </header>
                <div class="codepen-frame" data-preview aria-live="polite" aria-atomic="false"></div>
            </div>

        </div>

        <p id="codepen-input-help" class="codepen-hint">Try changing <code>--accent</code> to <code>#facc15</code>, <code>--radius</code> to <code>32px</code>, or the <code>linear-gradient</code> direction. <span class="screen-reader-text">Press Escape and then Tab to leave the editor.</span></p>
    </div>
    <!-- /wp:html -->

</div>
<!-- /wp:group -->
