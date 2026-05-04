/**
 * Dario Bimbi theme — front-end script.
 *
 * Modules:
 *   - scrollReveal:  fade + slide cards in as they enter the viewport.
 *   - cardTilt:      cursor-following 3D tilt on .dario-card hover.
 *   - liveEditor:    syntax-highlighted CSS playground with a Shadow DOM
 *                    preview; hand-rolled tokenizer (no Prism/CodeMirror).
 *
 * Everything is opt-in via DOM presence, motion-aware, and touch-aware.
 */
(function () {
    'use strict';

    if (typeof window === 'undefined') return;

    /* ====================================================
       Easter eggs
       ====================================================
       Two of them, both opt-in:
         1. A styled greeting if you opened DevTools.
         2. The Konami code toggles .party-mode on <body>.
            (CSS does the actual partying — see style.css.)
       Neither runs under prefers-reduced-motion. Mostly. */
    try {
        // The %c trick lets us style console output. Most people will
        // never see this. The ones who do are exactly the ones I want
        // to talk to.
        console.log(
            '%c </> ',
            'background: linear-gradient(135deg,#3b82f6,#a855f7);' +
            'color:#fff;padding:6px 12px;border-radius:6px;' +
            'font:700 16px ui-monospace,Menlo,monospace;'
        );
        console.log(
            '%cHey, you opened DevTools.',
            'font:600 14px system-ui;color:#e8ecf6;'
        );
        console.log(
            '%cEverything on this page is hand-built — block patterns, ' +
            'CSS playground, scroll-spy, the lot. Poke around. If something ' +
            'looks broken, ping dariobimbi@gmail.com.',
            'color:#94a3b8;font:13px system-ui;line-height:1.55;'
        );
        console.log(
            '%c↑ ↑ ↓ ↓ ← → ← → B A   ',
            'color:#facc15;font:italic 13px ui-monospace,Menlo,monospace;'
        );
    } catch (_) { /* IE-era consoles, no harm done */ }

    // Konami. The most important feature.
    (function konami() {
        var seq = ['ArrowUp','ArrowUp','ArrowDown','ArrowDown',
                   'ArrowLeft','ArrowRight','ArrowLeft','ArrowRight','b','a'];
        var pos = 0;
        document.addEventListener('keydown', function (e) {
            var key = e.key.length === 1 ? e.key.toLowerCase() : e.key;
            pos = (key === seq[pos]) ? pos + 1 : (key === seq[0] ? 1 : 0);
            if (pos === seq.length) {
                pos = 0;
                document.body.classList.toggle('party-mode');
                console.log('%c party mode ' +
                    (document.body.classList.contains('party-mode') ? 'on' : 'off'),
                    'color:#f43f5e;font:700 14px system-ui;');
            }
        });
    })();

    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var hasHover       = window.matchMedia && window.matchMedia('(hover: hover)').matches;

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    /* ====================================================
       Scroll reveal
       ==================================================== */
    function initScrollReveal() {
        if (!('IntersectionObserver' in window)) return;
        var cards = document.querySelectorAll('.dario-card');
        if (!cards.length) return;

        var threshold = window.innerHeight * 0.85;
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    e.target.classList.add('is-in');
                    observer.unobserve(e.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        cards.forEach(function (card) {
            if (card.getBoundingClientRect().top > threshold) {
                card.classList.add('reveal');
                observer.observe(card);
            }
        });
    }

    /* ====================================================
       3D tilt on hover  (project + testimonial + about cards)
       Skip timeline cards (they're list items, not standalone)
       ==================================================== */
    function initCardTilt() {
        if (prefersReduced || !hasHover) return;

        document.querySelectorAll('.dario-card:not(.is-timeline)').forEach(function (card) {
            var raf = null;

            card.addEventListener('mousemove', function (e) {
                if (raf) cancelAnimationFrame(raf);
                raf = requestAnimationFrame(function () {
                    var rect = card.getBoundingClientRect();
                    var nx = (e.clientX - rect.left) / rect.width  - 0.5; // -0.5 .. 0.5
                    var ny = (e.clientY - rect.top)  / rect.height - 0.5;
                    var rotY =  nx * 8;
                    var rotX = -ny * 8;
                    card.style.transition = 'transform 80ms ease-out';
                    card.style.transform  =
                        'perspective(900px) rotateX(' + rotX.toFixed(2) + 'deg) ' +
                        'rotateY(' + rotY.toFixed(2) + 'deg) translateY(-4px)';
                });
            });

            card.addEventListener('mouseleave', function () {
                if (raf) cancelAnimationFrame(raf);
                card.style.transition = 'transform 350ms cubic-bezier(.2,.7,.2,1)';
                card.style.transform  = '';
            });
        });
    }

    /* ====================================================
       Live editor — CSS playground with Shadow DOM preview
       ==================================================== */

    // Single regex with named capture groups picks the first matching token type.
    var TOKEN_RE = /(?<comment>\/\*[\s\S]*?\*\/)|(?<string>"(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*')|(?<variable>--[\w-]+)|(?<color>#[0-9a-fA-F]{3,8}\b)|(?<func>\b[a-zA-Z][\w-]*(?=\s*\())|(?<number>\b\d+(?:\.\d+)?(?:px|em|rem|%|vh|vw|s|ms|deg|fr)?\b)|(?<prop>\b[a-zA-Z-][\w-]*(?=\s*:(?!\/)))|(?<brace>[{}])/g;

    function escapeHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function highlightCss(src) {
        return escapeHtml(src).replace(TOKEN_RE, function (match, _c, _s, _v, _col, _f, _n, _p, _b, _offset, _full, groups) {
            // older browsers may not pass groups; fall back to checking match shape
            var g = groups || {};
            if (g.comment  || match.indexOf('/*') === 0) return '<span class="t-comment">'  + match + '</span>';
            if (g.string   || match.charAt(0) === '"' || match.charAt(0) === "'") return '<span class="t-string">' + match + '</span>';
            if (g.variable || match.indexOf('--') === 0) return '<span class="t-variable">' + match + '</span>';
            if (g.color    || match.charAt(0) === '#') {
                return '<span class="t-color"><span class="t-swatch" style="background:' + match + '"></span>' + match + '</span>';
            }
            if (g.number   || /^\d/.test(match))      return '<span class="t-number">'   + match + '</span>';
            if (g.brace    || match === '{' || match === '}') return '<span class="t-brace">' + match + '</span>';
            // Funcs and properties both look like bare identifiers; without
            // groups we can't tell. Default to property — close enough for the demo.
            if (g.func)  return '<span class="t-func">'     + match + '</span>';
            return '<span class="t-property">' + match + '</span>';
        });
    }

    var PREVIEW_HTML =
        '<style id="user-style"></style>' +
        '<style>' +
        ':host { display:block; height:100%; }' +
        'body, .demo-mount { margin:0; height:100%; display:flex; align-items:center; justify-content:center; }' +
        '</style>' +
        '<div class="demo-mount">' +
        '<div class="demo">' +
        '<span class="tag">live</span>' +
        '<h4>Hello from the preview pane.</h4>' +
        '<p>Every keystroke in the editor re-applies this CSS in real time. The panel runs in a Shadow DOM, so styles stay sandboxed.</p>' +
        '</div>' +
        '</div>';

    function initLiveEditor() {
        document.querySelectorAll('[data-cp]').forEach(function (root) {
            var input   = root.querySelector('[data-input]');
            var code    = root.querySelector('[data-highlight]');
            var preWrap = input && input.closest('.codepen-editorWrap');
            var mount   = root.querySelector('[data-preview]');
            if (!input || !code || !mount || !preWrap) return;

            var shadow = mount.attachShadow ? mount.attachShadow({ mode: 'open' }) : null;
            if (!shadow) return; // very old browser; bail silently
            shadow.innerHTML = PREVIEW_HTML;
            var styleEl = shadow.getElementById('user-style');

            function update() {
                var value = input.value;
                code.innerHTML = highlightCss(value);
                if (styleEl) styleEl.textContent = value;
            }

            input.addEventListener('input', update);

            // Keep the highlight scrolled in lockstep with the textarea.
            input.addEventListener('scroll', function () {
                var pre = preWrap.querySelector('.codepen-highlight');
                if (pre) {
                    pre.scrollTop  = input.scrollTop;
                    pre.scrollLeft = input.scrollLeft;
                }
            });

            // Tab inserts two spaces instead of moving focus, EXCEPT
            // immediately after Esc — which lets keyboard users escape
            // the editor (WCAG 2.1.2 No Keyboard Trap). Same pattern as
            // CodePen / CodeMirror / Monaco.
            var escArmed = false;
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    escArmed = true;
                    return;
                }
                if (e.key === 'Tab') {
                    if (escArmed) {
                        escArmed = false;
                        return; // allow the browser to move focus
                    }
                    e.preventDefault();
                    var s  = input.selectionStart;
                    var en = input.selectionEnd;
                    input.value = input.value.slice(0, s) + '  ' + input.value.slice(en);
                    input.selectionStart = input.selectionEnd = s + 2;
                    update();
                    return;
                }
                escArmed = false; // any other key cancels the escape arm
            });
            input.addEventListener('blur', function () { escArmed = false; });

            update();
        });
    }

    /* ====================================================
       Side-rail section nav — scroll-spy
       ====================================================
       For each rail link, find its target section heading. On scroll
       (rAF-throttled), pick the section whose top is at or just above
       the "activation line" (35% from the viewport top), and mark
       that link as .is-active + aria-current="true". */
    function initSideRail() {
        var rail = document.querySelector('.side-rail');
        if (!rail) return;

        var links = Array.prototype.slice.call(rail.querySelectorAll('a[href^="#"]'));
        var entries = links
            .map(function (link) {
                var id = link.getAttribute('href').slice(1);
                return { link: link, target: document.getElementById(id) };
            })
            .filter(function (e) { return e.target; });

        if (!entries.length) return;

        var ticking = false;
        function update() {
            ticking = false;
            var activationY = window.innerHeight * 0.35;
            var active = null;
            var bestDist = Infinity;

            entries.forEach(function (e) {
                var top = e.target.getBoundingClientRect().top;
                var dist = activationY - top; // positive when target is above the line
                if (dist >= 0 && dist < bestDist) {
                    active = e;
                    bestDist = dist;
                }
            });

            entries.forEach(function (e) {
                var on = (e === active);
                e.link.classList.toggle('is-active', on);
                if (on) e.link.setAttribute('aria-current', 'true');
                else    e.link.removeAttribute('aria-current');
            });
        }

        function onScroll() {
            if (!ticking) {
                requestAnimationFrame(update);
                ticking = true;
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        update();
    }

    /* ====================================================
       Boot
       ==================================================== */
    ready(function () {
        initScrollReveal();
        initCardTilt();
        initLiveEditor();
        initSideRail();
    });
})();
