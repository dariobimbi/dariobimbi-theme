/**
 * Dario Bimbi theme — front-end script.
 *
 * Modules:
 *   - scrollReveal:  fade + slide cards in as they enter the viewport.
 *   - cardTilt:      cursor-following 3D tilt on .dario-card hover.
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
            'looks broken, drop a note in the contact form on the homepage.',
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
        initSideRail();
    });
})();
