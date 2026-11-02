<?php
/**
 * Title: Ant Buster Demo
 * Slug: dariobimbi/demo-ant-buster
 * Categories: dariobimbi
 * Inserter: no
 */
?>
<!-- wp:group {"align":"full","className":"content-page demo-ant-buster","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull content-page demo-ant-buster" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">

  <!-- wp:paragraph {"className":"eyebrow"} -->
  <p class="eyebrow">demo</p>
  <!-- /wp:paragraph -->

  <!-- wp:heading {"level":1,"className":"has-gradient-text"} -->
  <h1 class="wp-block-heading has-gradient-text">Ant Buster</h1>
  <!-- /wp:heading -->

  <!-- wp:paragraph -->
  <p>The ants are after the cake. Put a few cannons down on the picnic blanket and try to keep them from carrying all eight slices back to the anthill. A full run is 25 waves, and it saves after every wave, so you can pick it back up later with a mouse or a finger.</p>
  <!-- /wp:paragraph -->

  <!-- wp:html -->
  <div class="ab-stage">
    <canvas id="ab-canvas" data-role="canvas" tabindex="0" aria-label="Ant Buster game canvas. Press Space to pause, Enter to send the next wave, and 1, 2 or 3 to change the speed." width="1280" height="720"></canvas>
  </div>
  <!-- /wp:html -->

</div>
<!-- /wp:group -->

<style>
.demo-ant-buster {
  --ab-bg: #14110d; /* warm canvas backdrop */
  --ab-frame: #2a241c; /* = subtle, warm frame */
}

body.has-ant-buster-page {
  background-color: var(--wp--preset--color--background) !important;
}

.demo-ant-buster .ab-stage {
  display: flex;
  justify-content: center;
  align-items: center;
  margin: var(--wp--preset--spacing--40) auto 0;
  padding: var(--wp--preset--spacing--20);
  background: var(--ab-frame);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  max-width: 100%;
}

.demo-ant-buster #ab-canvas {
  width: 1280px;
  max-width: 100%;
  height: auto;
  aspect-ratio: 16 / 9;
  background: var(--ab-bg);
  image-rendering: pixelated;
  image-rendering: -moz-crisp-edges;
  image-rendering: crisp-edges;
  cursor: default;
  display: block;
  touch-action: none;
}

.demo-ant-buster #ab-canvas.ab-dragging {
  cursor: grabbing;
}

.demo-ant-buster #ab-canvas:focus-visible {
  outline: 3px solid var(--wp--preset--color--highlight);
  outline-offset: 3px;
}

@media (max-width: 1000px) {
  .demo-ant-buster #ab-canvas {
    width: 100%;
  }
}

@media (max-aspect-ratio: 1/1) {
  .demo-ant-buster #ab-canvas {
    aspect-ratio: 480 / 540;
    width: 480px;
  }
}
</style>

<script>
(function () {
  'use strict';

  // === BODY CLASS (full-bleed paint workaround) ===
  document.body.classList.add('has-ant-buster-page');

  // === CONSTANTS ===
  // Layout constants. Mutated at boot and on rotation by relayout(). The
  // landscape values (640x360, right sidebar) are the desktop default; the
  // portrait values (480x600, bottom strip) kick in when isPortrait() is
  // true. PLAYFIELD_H is new and equals HEIGHT - HUD_H in landscape and
  // HEIGHT - HUD_H - STRIP_H in portrait. STRIP_H is the height of the
  // bottom strip in portrait, 0 in landscape.
  var WIDTH = 640;
  var HEIGHT = 360;
  var PLAYFIELD_W = 480;
  var PLAYFIELD_H = 336;
  var HUD_H = 24;
  var SHOP_W = 160;
  var STRIP_H = 0;
  var TARGET_FPS = 60;
  var FIXED_DT = 1 / TARGET_FPS;

  // Mirrors the CSS @media (max-aspect-ratio: 1/1) query so JS and CSS agree
  // on orientation. Reading the .ab-stage rect doesn't work because the
  // stage flex-centers the canvas inside a wider column -- its rect stays
  // landscape-shaped even when the canvas inside is portrait.
  function isPortrait() {
    if (window.matchMedia) {
      return window.matchMedia('(max-aspect-ratio: 1/1)').matches;
    }
    // Fallback for environments without matchMedia (none of the targets,
    // but keeps the function safe to call during boot).
    if (window.innerHeight >= window.innerWidth) { return true; }
    return false;
  }

  // True when the visitor asked their OS for less motion. Gates screenshake,
  // the lose-sign jitter, the blinking disabled-cannon sparks and the oversized
  // god-tier muzzle flash.
  var reducedMotionQuery = null;
  if (window.matchMedia) {
    reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  }
  function prefersReducedMotion() {
    if (!reducedMotionQuery) { return false; }
    return reducedMotionQuery.matches;
  }

  // Mutates the layout constants and resizes the backing canvas. Called once
  // at boot and again on resize/orientationchange when isPortrait() flips.
  // Game state (cannons, gold, wave, paused, selectedCannon, armedKind, etc.)
  // is preserved -- only render-layer constants change.
  function relayout() {
    if (isPortrait()) {
      WIDTH = 480;
      HEIGHT = 540;
      PLAYFIELD_W = 480;
      PLAYFIELD_H = 336;
      SHOP_W = 0;
      STRIP_H = 180;
    } else {
      WIDTH = 640;
      HEIGHT = 360;
      PLAYFIELD_W = 480;
      PLAYFIELD_H = 336;
      SHOP_W = 160;
      STRIP_H = 0;
    }
    // 2x retina backing buffer.
    canvas.width = WIDTH * 2;
    canvas.height = HEIGHT * 2;
    ctx.setTransform(2, 0, 0, 2, 0, 0);
    // Resizing the canvas resets all 2D context state, so re-disable image
    // smoothing here so crisp pixel-art rendering survives rotation.
    ctx.imageSmoothingEnabled = false;
  }

  // Static layout for the 15 idle-splash ants. Frozen so they don't reshuffle
  // every frame. Each: { x, y, size, rot } in canvas coords. size: 0=small,
  // 1=medium, 2=big. rot in radians.
  var IDLE_ANT_LAYOUT = [
    { x:  40, y:  40, size: 2, rot:  0.6 },
    { x: 540, y:  40, size: 1, rot: -0.35 },
    { x: 110, y:  60, size: 0, rot:  1.92 },
    { x: 590, y:  70, size: 1, rot:  1.31 },
    { x:  20, y: 110, size: 1, rot: -0.87 },
    { x: 560, y: 140, size: 2, rot: -1.92 },
    { x:  50, y: 215, size: 0, rot:  0.35 },
    { x: 530, y: 250, size: 1, rot:  2.79 },
    { x:  90, y: 275, size: 1, rot: -1.22 },
    { x: 380, y: 310, size: 2, rot:  0.7 },
    { x:  40, y: 330, size: 0, rot: -0.44 },
    { x: 600, y: 300, size: 1, rot:  1.05 },
    { x: 410, y:  56, size: 0, rot: -1.48 },
    { x: 240, y: 100, size: 1, rot: -0.52 },
    { x: 320, y: 320, size: 1, rot:  2.09 }
  ];

  // Portrait variant: 15 ants distributed across a 480x540 canvas, all below
  // the HUD (an ant at y 10 used to sit right on the gold readout).
  var IDLE_ANT_LAYOUT_PORTRAIT = [
    { x:  30, y:  44, size: 2, rot:  0.6 },
    { x: 360, y:  40, size: 1, rot: -0.35 },
    { x: 110, y:  60, size: 0, rot:  1.92 },
    { x: 420, y:  90, size: 1, rot:  1.31 },
    { x:  50, y: 130, size: 1, rot: -0.87 },
    { x: 430, y: 180, size: 2, rot: -1.92 },
    { x:  40, y: 230, size: 0, rot:  0.35 },
    { x: 380, y: 260, size: 1, rot:  2.79 },
    { x: 100, y: 310, size: 1, rot: -1.22 },
    { x: 270, y: 340, size: 2, rot:  0.7 },
    { x:  60, y: 390, size: 0, rot: -0.5 },
    { x: 410, y: 410, size: 1, rot:  1.0 },
    { x: 200, y: 450, size: 0, rot:  2.1 },
    { x: 100, y: 490, size: 1, rot: -1.7 },
    { x: 380, y: 510, size: 2, rot:  0.3 }
  ];

  // Static layout for win-splash confetti. Each: { x, color }. y is animated.
  // x stays inside the 480-wide playfield; drawWinOverlay clips to it too.
  var WIN_CONFETTI_LAYOUT = [
    { x:  30, color: '#fee761' },
    { x:  90, color: '#ff77a8' },
    { x: 150, color: '#c8f5a8' },
    { x: 210, color: '#fee761' },
    { x: 270, color: '#ff77a8' },
    { x: 330, color: '#c8f5a8' },
    { x: 390, color: '#fee761' },
    { x: 450, color: '#ff77a8' }
  ];

  // Static layout for lose-splash ants. Six items, all medium or big sizes,
  // positioned around (not behind) the sign and inside the playfield, so
  // none of them land on the HUD or the shop. Works for both orientations
  // because the playfield is 480x336 in each.
  var LOSE_ANT_LAYOUT = [
    { x:  50, y:  60, size: 2, rot:  0.7 },
    { x: 430, y:  70, size: 1, rot: -0.5 },
    { x:  40, y: 290, size: 1, rot:  2.6 },
    { x: 440, y: 300, size: 2, rot: -1.8 },
    { x: 240, y:  48, size: 1, rot:  1.2 },
    { x: 250, y: 320, size: 2, rot: -0.3 }
  ];

  // Family colors: the shop card strip, the ring on every cannon base, and
  // the field-guide family line all read from here.
  var FAMILY_STRIP_COLOR = {
    heavy:  '#b4a890',
    quick:  '#b0d04a',
    status: '#5ab4d8',
    rubber: '#ff77a8'
  };

  // === GAME STATE ===
  // Two flag layers, intentionally kept separate (Plan 2 Task 5):
  //   game.state     — 'idle' | 'playing'   (top-level: has the player started?)
  //   game.waveState — 'prep' | 'wave' | 'won' | 'lost'   (campaign progress)
  // Rationale: 'idle' is a pre-game splash concern; win/lose belongs to the wave
  // state machine. The render and input layers read whichever flag is relevant.
  var game = {
    state: 'idle',            // 'idle' | 'playing'
    time: 0,                  // simulated seconds since game start; the speed button runs update() extra times per tick, so this advances with it
    // Wall-clock seconds the game has been unpaused. Every cosmetic timer
    // (muzzle flash, rings, splats, popups, banners) runs on this instead of
    // game.time, so effects last the same on screen at 1x, 2x and 3x and keep
    // playing out under the win and lose signs. Never reset.
    fxTime: 0,
    simTime: 0,               // kept in step with time; used for slice timeout
    accumulator: 0,           // for fixed-timestep
    cake: { x: 352, y: 80, slices: 8, lost: 0 },
    anthill: { x: 40, y: 300 },
    ants: [],
    cannons: [],
    projectiles: [],
    effects: [],              // transient visual effects (e.g. Bomber AoE flash); see updateEffects
    lightningBolts: [],       // Tesla arc-jump polylines; each { points: [{x,y}], expiresAt: number }
    spawnJobs: [],            // periodic spawn callbacks (King's worker drip — Plan 3 Task 31)
    slices: [],               // fallen cake slices on the ground (Task 23); 8s timeout returns to cake
    shatterParticles: [],     // Plan 6: ephemeral cyan particles; never persisted
    sprites: {},              // populated in Task 4
    gold: 150,
    waveIndex: 0,             // index into WAVES
    waveState: 'prep',        // 'prep' | 'wave' | 'won' | 'lost'
    prepRemaining: 30,        // seconds remaining in current prep window
    spawner: { groupIndex: 0, spawnedInGroup: 0, nextSpawnAt: 0, queueDone: false },
    banner: { active: false, text: '', sub: '', style: 'wood', startedAt: 0, duration: 2.5 },
    bannerQueue: [],          // banners waiting their turn; see showBanner
    popups: [],               // floating "+N" gold and short callouts; see addPopup
    seenKinds: {},            // ant kinds already introduced this run
    sliceLostFx: -10,         // fxTime of the last delivered slice (HUD tag pulse)
    cakeDipFx: -10,           // fxTime of the last slice grabbed off the cake (plate dip)
    endedFx: 0,               // fxTime the run ended; the result sign fades in from here
    kills: 0,                 // ants killed this run, for the win sign
    screenshake: { magnitude: 0, decay: 0 },
    paused: false,
    speed: 1,
    // Plan 4 — wave-15 god-tier unlock state. godTierUnlocked flips true
    // when wave 15 clears and rides along in the wave-clear save (saveRun,
    // localStorage, wrapped in try/catch). godTierUnlockPulseStart stamps
    // game.time so the selected-tower panel can render a 0.5s golden halo
    // flash on T2 finals' god-tier upgrade buttons. Both reset on game-over
    // → restart.
    godTierUnlocked: false,
    godTierUnlockPulseStart: 0
  };

  // Theme URI and cache-bust version, injected from PHP. Used by the PNG
  // cannon-sprite loader registered after CANNON_KINDS is declared.
  var THEME_URI = '<?php echo esc_url( get_template_directory_uri() ); ?>';
  var SPRITE_VER = '<?php echo esc_js( wp_get_theme()->get('Version') ); ?>';

  // Module-level rect helper used by HUD hit-tests. Avoids inline and-operator chains
  // (which the WP content filter occasionally HTML-encodes) and centralizes the
  // pattern that future tasks will reuse for upgrade buttons, selected-tower UI, etc.
  function inRect(p, r) {
    if (p.x < r.x) { return false; }
    if (p.x > r.x + r.w) { return false; }
    if (p.y < r.y) { return false; }
    if (p.y > r.y + r.h) { return false; }
    return true;
  }

  // Queue a banner. Wave intros, new-ant cards, wave-clear tags and the
  // god-tier unlock all go through here and wait their turn, so none of them
  // overwrites another. style: 'wood' | 'boss' | 'final' | 'gold'. sub is an
  // optional second, smaller line. Durations run on fxTime, so a 3x game
  // still leaves time to read them.
  function showBanner(text, style, sub) {
    var duration = 2.5;
    if (style === 'boss') { duration = 3; }
    if (style === 'final') { duration = 4; }
    if (style === 'gold') { duration = 4; }
    if (sub) { duration = duration + 0.5; }
    // ponytail: a long queue only builds if something fires banners in a loop; cap it.
    if (game.bannerQueue.length >= 4) { game.bannerQueue.shift(); }
    game.bannerQueue.push({ text: text, sub: sub || '', style: style || 'wood', duration: duration });
  }

  // Floating text over the board: "+N" gold on a kill, SHATTER, SLICE LOST.
  // Capped so a wave-20 swarm can't bury the blanket in numbers.
  function addPopup(x, y, text, color) {
    if (game.popups.length >= 12) { game.popups.shift(); }
    game.popups.push({ x: x, y: y, text: text, color: color, born: game.fxTime });
  }

  // === CANVAS SETUP ===
  var canvas = document.getElementById('ab-canvas');
  if (!canvas) { return; }
  var ctx = canvas.getContext('2d');
  relayout();

  // On rotate / window resize, re-pick the layout if portrait/landscape flips.
  // Cache the last result so we only run relayout() on actual changes -- not
  // every pixel-by-pixel resize event during a slow drag.
  var lastIsPortrait = isPortrait();
  window.addEventListener('resize', function () {
    var nowPortrait = isPortrait();
    if (nowPortrait !== lastIsPortrait) {
      lastIsPortrait = nowPortrait;
      relayout();
    }
  });
  window.addEventListener('orientationchange', function () {
    var nowPortrait = isPortrait();
    if (nowPortrait !== lastIsPortrait) {
      lastIsPortrait = nowPortrait;
      relayout();
    }
  });

  // Belt-and-suspenders: matchMedia change fires on the exact breakpoint
  // crossing, even in edge cases where resize/orientationchange don't (e.g.
  // some DevTools device-emulation transitions).
  if (window.matchMedia) {
    var mql = window.matchMedia('(max-aspect-ratio: 1/1)');
    var onMqlChange = function () {
      var nowPortrait = isPortrait();
      if (nowPortrait !== lastIsPortrait) {
        lastIsPortrait = nowPortrait;
        relayout();
      }
    };
    if (mql.addEventListener) {
      mql.addEventListener('change', onMqlChange);
    } else if (mql.addListener) {
      // Older Safari / iOS pre-14 fallback.
      mql.addListener(onMqlChange);
    }
  }

  // === SPRITES ===
  // All sprites are generated procedurally as offscreen canvases.
  // Plan 4 will swap these out for real PNGs loaded from disk.
  function makeCanvas(w, h) {
    var c = document.createElement('canvas');
    c.width = w;
    c.height = h;
    return c;
  }

  // Ant ink is the wooden chrome's warm near-black (#2a1810 family) rather
  // than the cool navy the board started with, so the ants, splats, bases
  // and cake plate belong to the same picnic as the signs around them.
  // Each threat class gets one readable mark at 16 px: Carrier a cream
  // bundle, Soldier a brass helmet, Royal Guard a wine cape, Tank a pale shell.
  function makeWorkerSprite() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    x.fillStyle = '#3b2a20';
    x.beginPath();
    x.ellipse(8, 8, 5, 4, 0, 0, Math.PI * 2);   // body
    x.fill();
    x.fillStyle = '#5a4030';
    x.fillRect(6, 6, 2, 1);                     // warm highlight
    x.fillStyle = '#2a1810';
    x.fillRect(7, 3, 2, 2);                     // head
    x.fillStyle = '#1f1712';
    x.fillRect(7, 1, 1, 2);                     // antenna L
    x.fillRect(8, 1, 1, 2);                     // antenna R
    return c;
  }

  function makeCarrierSprite() {
    var c = makeWorkerSprite();
    var x = c.getContext('2d');
    x.fillStyle = '#2a1810';
    x.fillRect(5, 8, 6, 5);                     // bundle outline
    x.fillStyle = '#e8d4a8';
    x.fillRect(6, 9, 4, 3);                     // cream bundle on its back
    x.fillStyle = '#c84244';
    x.fillRect(6, 10, 4, 1);                    // red strap
    return c;
  }

  function makeRoyalGuardSprite() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    x.fillStyle = '#3b2a20';
    x.beginPath();
    x.ellipse(8, 8, 5, 4, 0, 0, Math.PI * 2);   // body, same as worker
    x.fill();
    x.fillStyle = '#8a2a3a';
    x.fillRect(4, 7, 8, 5);                     // wine cape over the back
    x.fillRect(5, 12, 6, 1);                    // cape hem
    x.fillStyle = '#b84a5a';
    x.fillRect(4, 7, 8, 1);                     // cape collar
    x.fillStyle = '#2a1810';
    x.fillRect(7, 3, 2, 2);                     // head
    x.fillStyle = '#fee761';
    x.fillRect(6, 1, 4, 1);                     // crown base, forward of head
    x.fillRect(6, 0, 1, 1);                     // crown spike L
    x.fillRect(8, 0, 1, 1);                     // crown spike center
    x.fillRect(9, 0, 1, 1);                     // crown spike R (intentional 3-spike pattern)
    x.fillStyle = '#1f1712';
    x.fillRect(7, 1, 1, 1);                     // antenna L (shorter, crown takes the upper space)
    x.fillRect(8, 1, 1, 1);                     // antenna R
    return c;
  }

  function makeSoldierSprite() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    x.fillStyle = '#2e221c';
    x.beginPath();
    x.ellipse(8, 9, 6, 5, 0, 0, Math.PI * 2);   // chunkier body
    x.fill();
    x.fillStyle = '#4a3428';
    x.fillRect(5, 9, 2, 1);                     // highlight
    x.fillStyle = '#b08a3a';
    x.fillRect(5, 3, 6, 3);                     // brass helmet, wider than the head
    x.fillStyle = '#e0b860';
    x.fillRect(6, 3, 2, 1);                     // helmet glint
    x.fillStyle = '#1f1712';
    x.fillRect(6, 6, 4, 1);                     // face under the brim
    x.fillRect(5, 1, 1, 2);                     // antenna L
    x.fillRect(10, 1, 1, 2);                    // antenna R
    return c;
  }

  function makeScout() {
    var c = makeCanvas(12, 12);
    var x = c.getContext('2d');
    x.fillStyle = '#f4b41b';
    x.fillRect(3, 3, 6, 6);                     // body
    x.fillStyle = '#2a1810';
    x.fillRect(5, 1, 2, 2);                     // head / eyes
    x.fillRect(2, 4, 1, 2);                     // left leg
    x.fillRect(9, 4, 1, 2);                     // right leg
    x.fillRect(4, 10, 1, 1);                    // left rear leg
    x.fillRect(7, 10, 1, 1);                    // right rear leg
    return c;
  }

  function makeTank() {
    var c = makeCanvas(24, 24);
    var x = c.getContext('2d');
    // Heavy armored body in a pale warm grey, so it reads as a shell next to
    // the dark ants; blocky treads visual
    x.fillStyle = '#2a1810';
    x.beginPath();
    x.ellipse(12, 12, 9, 7, 0, 0, Math.PI * 2);   // outline
    x.fill();
    x.fillStyle = '#8a7e70';
    x.beginPath();
    x.ellipse(12, 12, 8, 6, 0, 0, Math.PI * 2);   // main body, broader than worker
    x.fill();
    x.fillStyle = '#5a5048';                       // darker accent
    x.fillRect(5, 10, 14, 2);                      // bottom plate / tread line
    x.fillStyle = '#aca090';                       // lighter accent for armor plating
    x.fillRect(4, 8, 3, 8);                        // left armor plate
    x.fillRect(16, 8, 3, 8);                       // right armor plate
    x.fillStyle = '#2a1810';                       // very dark head
    x.fillRect(10, 5, 4, 3);                       // head
    return c;
  }

  function makeSplitter() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    // Two halves of a splittable body, slightly offset, with a fissure line down the middle.
    x.fillStyle = '#5a9c7d';                    // green-blue base
    x.beginPath();
    x.ellipse(5, 8, 4, 4, 0, 0, Math.PI * 2);   // left half body
    x.fill();
    x.fillStyle = '#4a8c6d';                    // slightly darker right half
    x.beginPath();
    x.ellipse(11, 8, 4, 4, 0, 0, Math.PI * 2);  // right half body
    x.fill();
    x.fillStyle = '#2a1810';
    x.fillRect(7, 7, 2, 4);                     // fissure line down middle
    x.fillStyle = '#4a3428';
    x.fillRect(4, 3, 2, 2);                     // left head bump
    x.fillRect(10, 3, 2, 2);                    // right head bump
    x.fillStyle = '#1f1712';
    x.fillRect(4, 1, 1, 2);                     // antenna L
    x.fillRect(11, 1, 1, 2);                    // antenna R
    return c;
  }

  function makeMiniSplitter() {
    var c = makeCanvas(8, 8);
    var x = c.getContext('2d');
    // Crude small version: two pixel halves with a fissure.
    x.fillStyle = '#5a9c7d';
    x.fillRect(1, 2, 3, 4);                     // left half
    x.fillStyle = '#4a8c6d';
    x.fillRect(4, 2, 3, 4);                     // right half
    x.fillStyle = '#2a1810';
    x.fillRect(3, 2, 2, 4);                     // fissure
    x.fillStyle = '#1f1712';
    x.fillRect(1, 0, 1, 1);                     // antenna L
    x.fillRect(6, 0, 1, 1);                     // antenna R
    return c;
  }

  function makeBomber() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    // Red-orange ant body reading as a "carrier of explosives" with a small dark
    // fuse-spot on top. Same overall silhouette as worker so it still reads as ant.
    x.fillStyle = '#e43b44';
    x.beginPath();
    x.ellipse(8, 9, 5, 4, 0, 0, Math.PI * 2);   // body
    x.fill();
    x.fillStyle = '#a8302f';                    // darker red accent
    x.fillRect(5, 9, 6, 1);                     // belly stripe
    x.fillStyle = '#f57b3f';                    // orange head
    x.fillRect(7, 4, 3, 3);                     // head
    x.fillStyle = '#2a1810';
    x.fillRect(8, 2, 1, 2);                     // dark fuse-like spot on top
    x.fillStyle = '#fee761';
    x.fillRect(8, 1, 1, 1);                     // tiny spark on the fuse tip
    x.fillStyle = '#1f1712';
    x.fillRect(6, 3, 1, 1);                     // antenna L
    x.fillRect(10, 3, 1, 1);                    // antenna R
    return c;
  }

  function makeQueen() {
    var c = makeCanvas(24, 24);
    var x = c.getContext('2d');
    // Regal silhouette: deep purple/burgundy body with crown-like light yellow pixels.
    x.fillStyle = '#682837';
    x.beginPath();
    x.ellipse(12, 14, 7, 6, 0, 0, Math.PI * 2);   // body
    x.fill();
    x.fillStyle = '#a44444';                      // lighter burgundy for head
    x.fillRect(10, 8, 4, 4);                      // head
    x.fillStyle = '#fee761';                      // crown color: light yellow
    x.fillRect(9, 6, 2, 2);                       // crown left peak
    x.fillRect(13, 6, 2, 2);                      // crown right peak
    x.fillRect(11, 5, 2, 2);                      // crown center peak
    x.fillStyle = '#1f1712';
    x.fillRect(8, 4, 1, 1);                       // antenna L
    x.fillRect(15, 4, 1, 1);                      // antenna R
    return c;
  }

  function makePhase() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    // Ghostly translucent purple body with thin outline.
    x.fillStyle = '#7a4a6a';
    x.fillRect(4, 3, 8, 10);                    // body
    x.fillRect(3, 5, 2, 6);                     // left segment
    x.fillRect(11, 5, 2, 6);                    // right segment
    x.fillStyle = '#5a3a4a';
    x.strokeStyle = '#4a2a3a';
    x.lineWidth = 1;
    x.strokeRect(4, 3, 8, 10);                  // outline
    x.fillStyle = '#1f1712';
    x.fillRect(6, 1, 1, 1);                     // antenna L
    x.fillRect(9, 1, 1, 1);                     // antenna R
    return c;
  }

  function makeAntSplat() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    x.fillStyle = '#4a2a1c';
    x.fillRect(4, 7, 8, 3);
    x.fillRect(5, 6, 6, 1);
    x.fillRect(5, 10, 6, 1);
    return c;
  }

  function makeRoyalGuardSplat() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    x.fillStyle = '#6a2030';
    x.fillRect(4, 7, 8, 3);
    x.fillRect(5, 6, 6, 1);
    x.fillRect(5, 10, 6, 1);
    return c;
  }

  // Cannon base: a warm pewter plate whose rim shows the tier (walnut base,
  // brass mid, riveted iron final, gold god) and whose inner ring shows the
  // family color, so upgrades read on the board without clicking each one.
  // The gold rim replaces the old pulsing yellow halo disc under god tier.
  var TIER_RIM = { base: '#6a4e38', mid: '#b08a3a', final: '#55504a', god: '#e8b830' };
  function makeCannonBase(tier, family) {
    var c = makeCanvas(32, 32);
    var x = c.getContext('2d');
    function disc(r, color) {
      x.fillStyle = color;
      x.beginPath();
      x.arc(16, 16, r, 0, Math.PI * 2);
      x.fill();
    }
    disc(13, '#2a1810');                        // outline, so it reads on red and cream
    disc(12, TIER_RIM[tier] || TIER_RIM.base);
    disc(10.5, FAMILY_STRIP_COLOR[family] || '#b4a890');
    disc(9.5, '#8c8378');                       // pewter plate
    x.fillStyle = '#a89e90';
    x.fillRect(10, 9, 5, 2);                    // plate highlight
    if (tier === 'final') {
      x.fillStyle = '#c8c0b0';                  // four rivets
      x.fillRect(15, 4, 2, 2);
      x.fillRect(15, 26, 2, 2);
      x.fillRect(4, 15, 2, 2);
      x.fillRect(26, 15, 2, 2);
    }
    if (tier === 'god') {
      x.fillStyle = '#fee761';                  // gold glints on the rim
      x.fillRect(9, 6, 3, 1);
      x.fillRect(22, 24, 2, 1);
      x.fillRect(5, 18, 1, 2);
    }
    return c;
  }

  // One base per tier and family, made once.
  var baseCache = {};
  function cannonBaseFor(kind) {
    var def = CANNON_KINDS[kind];
    var key = def.tier + ':' + def.family;
    if (!baseCache[key]) { baseCache[key] = makeCannonBase(def.tier, def.family); }
    return baseCache[key];
  }

  function makeCannonTurret() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#4a4440';
    x.fillRect(8, 4, 4, 12);                    // iron barrel pointing up (rotated at draw time)
    x.fillStyle = '#6a625a';
    x.fillRect(8, 5, 1, 10);                    // barrel highlight
    x.fillStyle = '#2a1810';
    x.fillRect(7, 2, 6, 3);                     // barrel tip
    return c;
  }

  function makeCannonTurretRepeater() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Thinner, longer barrel; green-ish.
    x.fillStyle = '#5a7d4a';
    x.fillRect(9, 1, 2, 15);                    // long thin barrel
    x.fillStyle = '#3a5d2a';
    x.fillRect(8, 0, 4, 2);                     // barrel tip
    return c;
  }

  function makeCannonTurretAutocannon() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Chunkier than Repeater; same green family, slightly darker.
    x.fillStyle = '#4a7d6a';
    x.fillRect(8, 1, 4, 15);                    // wider barrel
    x.fillStyle = '#2a5d4a';
    x.fillRect(7, 0, 6, 3);                     // wide tip
    x.fillStyle = '#6a9d8a';
    x.fillRect(7, 11, 6, 4);                    // breech band
    return c;
  }

  function makeCannonTurretVulcan() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Multi-barrel rotary; teal-ish.
    x.fillStyle = '#3a8c8c';
    x.fillRect(5, 3, 2, 13);                    // left barrel
    x.fillRect(9, 3, 2, 13);                    // center barrel
    x.fillRect(13, 3, 2, 13);                   // right barrel
    x.fillStyle = '#1a5c5c';
    x.fillRect(4, 1, 4, 3);                     // left tip
    x.fillRect(8, 1, 4, 3);                     // center tip
    x.fillRect(12, 1, 4, 3);                    // right tip
    x.fillStyle = '#5aacac';
    x.fillRect(4, 12, 12, 4);                   // wide breech housing
    return c;
  }

  function makeCannonTurretTwinGun() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Two short side-by-side barrels; purple-ish.
    x.fillStyle = '#7d4a7d';
    x.fillRect(6, 5, 3, 11);                    // left barrel
    x.fillRect(11, 5, 3, 11);                   // right barrel
    x.fillStyle = '#5d2a5d';
    x.fillRect(5, 3, 4, 3);                     // left tip
    x.fillRect(11, 3, 4, 3);                    // right tip
    return c;
  }

  function makeCannonTurretSonicPulse() {
    // Double tier-3 (knockback + stun). Yellow horn-shaped emitter with a
    // dark inner cone — reads as a sonic horn / megaphone, distinct from
    // the chemical sprayers (Toxin / Plague) and the icy variants.
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#888822';
    x.fillRect(8, 10, 4, 8);                    // dark stem
    x.fillStyle = '#cccc3a';
    x.fillRect(6, 4, 8, 7);                     // wide horn body
    x.fillStyle = '#eedd44';
    x.fillRect(5, 0, 10, 5);                    // flared bell mouth
    x.fillStyle = '#3a3a14';
    x.fillRect(7, 1, 6, 3);                     // dark inner cone
    x.fillStyle = '#fff080';
    x.fillRect(7, 6, 1, 4);                     // horn highlight left
    x.fillRect(12, 6, 1, 4);                    // horn highlight right
    return c;
  }

  function makeCannonTurretPlague() {
    // Double tier-3 (poison + spread on death). Sickly bright-green barrel
    // with a bulbous reservoir at the breech — reads as the bigger sibling
    // of Toxin's basic sprayer, distinct via the wide canister.
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#2a5a2a';
    x.fillRect(5, 11, 10, 7);                   // wide reservoir base
    x.fillStyle = '#5acc5a';
    x.fillRect(8, 2, 4, 11);                    // bright green barrel
    x.fillStyle = '#1a4a1a';
    x.fillRect(7, 0, 6, 3);                     // dark muzzle tip
    x.fillStyle = '#88e088';
    x.fillRect(9, 4, 2, 1);                     // barrel highlight
    x.fillRect(7, 13, 6, 1);                    // reservoir top highlight
    x.fillStyle = '#aaff88';
    x.fillRect(6, 17, 1, 2);                    // venom drip left
    x.fillRect(13, 17, 1, 2);                   // venom drip right
    return c;
  }

  function makeCannonTurretCryo() {
    // Double tier-3 (freeze + damage amp). Pale ice-blue body with sharper
    // crystalline accents than Frost's softer fins; the brighter cyan tip
    // hints at the +50% damage amplification while frozen.
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#80c0ff';
    x.fillRect(7, 4, 6, 14);                    // ice-blue body
    x.fillStyle = '#3080d0';
    x.fillRect(6, 2, 8, 3);                     // darker tip
    x.fillStyle = '#c8e8ff';
    x.fillRect(5, 8, 2, 3);                     // crystal facet left
    x.fillRect(13, 8, 2, 3);                    // crystal facet right
    x.fillStyle = '#ffffff';
    x.fillRect(8, 0, 4, 1);                     // sparkle highlight
    x.fillRect(9, 6, 2, 4);                     // inner barrel highlight
    return c;
  }

  function makeCannonTurretToxin() {
    // Double tier-2 (poison DoT). Bright green barrel with darker venom drips
    // along the lower edge — reads as a chemical sprayer, distinct from
    // Frost's icy blue and Triple Threat's magenta.
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#2a5a2a';
    x.fillRect(7, 6, 6, 12);                    // dark green base
    x.fillStyle = '#3aaa3a';
    x.fillRect(8, 2, 4, 10);                    // bright green barrel
    x.fillStyle = '#1a4a1a';
    x.fillRect(7, 0, 6, 3);                     // dark muzzle tip
    x.fillStyle = '#88e088';
    x.fillRect(9, 3, 2, 1);                     // highlight
    x.fillRect(6, 14, 1, 2);                    // venom drip left
    x.fillRect(13, 16, 1, 2);                   // venom drip right
    return c;
  }

  function makeCannonTurretMinigun() {
    // Double tier-3 (sustained hose). Single sleek dark steel barrel with a
    // long muzzle and visible vent slits — reads as a high-RPM hose, distinct
    // from Vulcan's three rotating barrels and Buckshot's flared bell.
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#3a3a3a';
    x.fillRect(8, 1, 4, 17);                    // long single barrel
    x.fillStyle = '#1a1a1a';
    x.fillRect(7, 0, 6, 3);                     // dark muzzle tip
    x.fillStyle = '#5a5a5a';
    x.fillRect(6, 12, 8, 6);                    // wider breech housing
    x.fillStyle = '#2a2a2a';
    x.fillRect(9, 5, 1, 1);                     // vent slit
    x.fillRect(10, 5, 1, 1);
    x.fillRect(9, 8, 1, 1);
    x.fillRect(10, 8, 1, 1);
    return c;
  }

  function makeCannonTurretBuckshot() {
    // Double tier-3 (5-pellet shotgun cone). Wide flared muzzle reads as a
    // sawed-off blunderbuss; warm orange tones distinguish it from Triple
    // Threat's magenta and Minigun's dark steel.
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#5c2a14';
    x.fillRect(7, 6, 6, 12);                    // dark wooden stock body
    x.fillStyle = '#cc8a3a';
    x.fillRect(8, 4, 4, 6);                     // mid barrel
    x.fillStyle = '#ee9a4a';
    x.fillRect(6, 0, 8, 4);                     // wide flared muzzle
    x.fillStyle = '#3a1a0a';
    x.fillRect(7, 1, 2, 2);                     // muzzle bore left
    x.fillRect(11, 1, 2, 2);                    // muzzle bore right
    return c;
  }

  function makeCannonTurretTripleThreat() {
    // Double tier-2 (3-barrel volley). Brighter magenta than Twin Gun, with a
    // third center barrel reading clearly as "Twin Gun's bigger sibling".
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#aa3aaa';
    x.fillRect(4, 5, 3, 11);                    // left barrel
    x.fillRect(8, 4, 3, 12);                    // center barrel (slightly longer)
    x.fillRect(13, 5, 3, 11);                   // right barrel
    x.fillStyle = '#7d1a7d';
    x.fillRect(3, 3, 4, 3);                     // left tip
    x.fillRect(7, 2, 4, 3);                     // center tip
    x.fillRect(13, 3, 4, 3);                    // right tip
    return c;
  }

  function makeCannonTurretFrost() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Thin barrel with frost crystal/icicle accents; light blue.
    x.fillStyle = '#7da4f7';
    x.fillRect(9, 2, 2, 14);                    // thin barrel
    x.fillStyle = '#5a82d4';
    x.fillRect(8, 1, 4, 2);                     // tip
    x.fillStyle = '#bcd4fc';
    x.fillRect(7, 5, 1, 2);                     // left icicle accent
    x.fillRect(12, 8, 1, 2);                    // right icicle accent
    x.fillRect(7, 11, 1, 2);                    // left lower accent
    x.fillStyle = '#e0ecff';
    x.fillRect(9, 0, 2, 1);                     // crystal sparkle at tip
    return c;
  }

  function makeCannonTurretGlacier() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Pale icy blue with wider barrel and cooling fins.
    x.fillStyle = '#a4d4ff';
    x.fillRect(7, 3, 6, 13);                    // wide barrel
    x.fillStyle = '#6fa4d4';
    x.fillRect(6, 1, 8, 3);                     // wide tip
    x.fillStyle = '#7ab4e4';
    x.fillRect(5, 6, 2, 2);                     // left cooling fin upper
    x.fillRect(13, 6, 2, 2);                    // right cooling fin upper
    x.fillRect(5, 10, 2, 2);                    // left cooling fin lower
    x.fillRect(13, 10, 2, 2);                   // right cooling fin lower
    x.fillStyle = '#e4f4ff';
    x.fillRect(8, 0, 4, 1);                     // crystal sparkle
    x.fillRect(9, 7, 2, 5);                     // inner barrel highlight
    return c;
  }

  function makeCannonTurretSiege() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Chunky stubby barrel; dark red.
    x.fillStyle = '#5a3a44';
    x.fillRect(7, 4, 6, 12);                    // wide barrel
    x.fillStyle = '#3a1a24';
    x.fillRect(6, 2, 8, 3);                     // wide tip
    return c;
  }

  function makeCannonTurretRailgun() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Sleek long barrel; dark red-violet.
    x.fillStyle = '#6f3a44';
    x.fillRect(9, 0, 2, 17);                    // very long thin barrel
    x.fillStyle = '#4f1a24';
    x.fillRect(8, 0, 4, 3);                     // tip
    x.fillStyle = '#9a5a64';
    x.fillRect(7, 12, 6, 4);                    // breech block
    return c;
  }

  function makeCannonTurretMortar() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Stubby high-angle mortar tube; muddy brown body, darker breech base.
    x.fillStyle = '#5a3a1a';
    x.fillRect(7, 2, 6, 11);                    // short fat barrel pointing up
    x.fillStyle = '#3a2a0a';
    x.fillRect(5, 9, 10, 8);                    // wide breech base
    return c;
  }

  function makeCannonTurretDiscus() {
    var c = makeCanvas(24, 24);
    var x = c.getContext('2d');
    // Wide horizontal launcher with a square base; the projectile's silhouette
    // is round but the launcher just suggests a flat throw.
    x.fillStyle = '#9b5a2a';
    x.fillRect(4, 12, 16, 4);                   // wide horizontal launcher
    x.fillStyle = '#3a2a0a';
    x.fillRect(8, 8, 8, 8);                     // base
    return c;
  }

  function makeCannonTurretPatriot() {
    var c = makeCanvas(28, 28);
    var x = c.getContext('2d');
    // Vertical missile silhouette on a wide breech; yellow nose flicker reads
    // as a homing warhead at glance.
    x.fillStyle = '#3a4d8a';
    x.fillRect(10, 4, 8, 20);                   // missile body
    x.fillStyle = '#1a2d4a';
    x.fillRect(6, 18, 16, 8);                   // breech base
    x.fillStyle = '#fee761';
    x.fillRect(13, 6, 2, 4);                    // warhead tip
    return c;
  }

  function makeCannonTurretDemolisher() {
    var c = makeCanvas(28, 28);
    var x = c.getContext('2d');
    // Stout horizontal barrel on a heavy square breech; rust-brown body reads
    // as armor-piercing siege weight at glance.
    x.fillStyle = '#7a3a1a';
    x.fillRect(4, 11, 22, 6);                   // wide stubby barrel
    x.fillStyle = '#3a1a0a';
    x.fillRect(8, 8, 12, 12);                   // heavy breech block
    return c;
  }

  function makeCannonTurretHellfire() {
    var c = makeCanvas(28, 28);
    var x = c.getContext('2d');
    // Stubby high-angle launcher in fiery red over a charred dark base; reads
    // as a heavier kin of Mortar that lobs a wider blast.
    x.fillStyle = '#d04020';
    x.fillRect(7, 4, 14, 12);                   // upward launcher mouth
    x.fillStyle = '#5a1a0a';
    x.fillRect(6, 14, 16, 10);                  // charred breech base
    return c;
  }

  function makeCannonTurretRicochet() {
    var c = makeCanvas(28, 28);
    var x = c.getContext('2d');
    // Heavier kin of Discus with a wider horizontal launcher; the warmer
    // orange reads as the upgraded variant at glance.
    x.fillStyle = '#cc6a4a';
    x.fillRect(2, 12, 24, 4);                   // wide horizontal launcher
    x.fillStyle = '#9b3a1a';
    x.fillRect(10, 8, 8, 12);                   // taller breech block
    return c;
  }

  function makeCannonTurretShockwave() {
    var c = makeCanvas(28, 28);
    var x = c.getContext('2d');
    // Stout violet barrel on a wide squat base, with a yellow band on the
    // muzzle that hints at the splash/shake punch.
    x.fillStyle = '#a040c0';
    x.fillRect(10, 6, 8, 14);                   // central barrel
    x.fillStyle = '#5a1a5a';
    x.fillRect(4, 16, 20, 8);                   // wide squat base
    x.fillStyle = '#fee761';
    x.fillRect(11, 8, 6, 2);                    // muzzle band
    return c;
  }

  function makeCannonTurretMarksman() {
    var c = makeCanvas(28, 28);
    var x = c.getContext('2d');
    x.fillStyle = '#6a8a3a';
    x.fillRect(2, 12, 24, 3);
    x.fillStyle = '#3a5a1a';
    x.fillRect(10, 9, 8, 9);
    x.fillStyle = '#fee761';
    x.fillRect(20, 11, 2, 5);
    return c;
  }

  function makeCannonTurretSpotter() {
    var c = makeCanvas(28, 28);
    var x = c.getContext('2d');
    // Tall spotter scope on a low base, with a yellow lens dot up top hinting
    // at the reveal beam that flags carriers for every other cannon.
    x.fillStyle = '#aa8a3a';
    x.fillRect(10, 8, 8, 12);                   // scope body
    x.fillStyle = '#5a5a3a';
    x.fillRect(2, 12, 24, 4);                   // sighting bar
    x.fillStyle = '#fee761';
    x.fillRect(13, 4, 2, 4);                    // lens highlight
    return c;
  }

  function makeCannonTurretPlasmaTorch() {
    // Quick tier-3 (cone-DoT). Orange flame body over a dark base, with a
    // bright yellow inner core hinting at the plasma jet that emits the cone.
    var c = makeCanvas(28, 28);
    var x = c.getContext('2d');
    x.fillStyle = '#ff7a30';
    x.fillRect(8, 6, 12, 14);
    x.fillStyle = '#a4421a';
    x.fillRect(6, 18, 16, 6);
    x.fillStyle = '#fee761';
    x.fillRect(11, 9, 6, 4);
    return c;
  }

  function makeCannonTurretSharpshooter() {
    // Quick tier-3 (full-HP precision). Long teal barrel with a prominent
    // yellow scope - reads as Marksman's bigger sibling, max-range pick.
    var c = makeCanvas(28, 28);
    var x = c.getContext('2d');
    x.fillStyle = '#3a8a8a';
    x.fillRect(1, 12, 26, 3);                   // extra-long barrel (range 350)
    x.fillStyle = '#1a4a4a';
    x.fillRect(9, 9, 10, 9);                    // base
    x.fillStyle = '#fee761';
    x.fillRect(20, 10, 3, 6);                   // big scope
    x.fillStyle = '#ffffff';
    x.fillRect(21, 11, 1, 1);                   // scope glint
    return c;
  }

  function makeCannonTurretLance() {
    // Quick tier-3 (continuous beam, direct damage). Cyan emitter with a
    // crystalline lens at the muzzle - reads as an energy projector, distinct
    // from Sharpshooter's scoped rifle and Glacier's icy fins.
    var c = makeCanvas(28, 28);
    var x = c.getContext('2d');
    x.fillStyle = '#1a4a4a';
    x.fillRect(9, 11, 10, 9);                   // dark base
    x.fillStyle = '#3acccc';
    x.fillRect(2, 12, 23, 3);                   // long cyan barrel
    x.fillStyle = '#84e8e8';
    x.fillRect(0, 11, 4, 5);                    // wider lens emitter at muzzle
    x.fillStyle = '#e4ffff';
    x.fillRect(1, 13, 1, 1);                    // lens glint
    x.fillRect(10, 13, 12, 1);                  // inner barrel highlight
    return c;
  }

  function makeCannonTurretTesla() {
    // Quick tier-3 (chain-jump arc). Squat blue body with a stacked coil
    // emitter on top — reads as a Tesla coil at glance, distinct from the
    // long-barrel Sharpshooter / Phantom and the lens-emitter Lance.
    var c = makeCanvas(28, 28);
    var x = c.getContext('2d');
    x.fillStyle = '#1a3a5a';
    x.fillRect(8, 14, 12, 8);                   // dark blue base
    x.fillStyle = '#5a8acc';
    x.fillRect(10, 6, 8, 9);                    // mid-body coil housing
    x.fillStyle = '#b0d8ff';
    x.fillRect(11, 4, 6, 2);                    // top coil disc
    x.fillRect(12, 1, 4, 3);                    // upper coil disc
    x.fillStyle = '#e4f4ff';
    x.fillRect(13, 0, 2, 1);                    // top spark
    x.fillRect(11, 8, 6, 1);                    // coil banding
    x.fillRect(11, 11, 6, 1);                   // coil banding
    return c;
  }

  function makeCannonTurretPhantom() {
    // Quick tier-3 (anti-Queen specialist). Long purple barrel with a
    // ghostly white wisp running along it - reads as a long-range spectral
    // sniper, distinct from Sharpshooter's scoped rifle and Lance's emitter.
    var c = makeCanvas(28, 28);
    var x = c.getContext('2d');
    x.fillStyle = '#5a2a7a';
    x.fillRect(9, 10, 10, 10);                  // dark purple base
    x.fillStyle = '#9a6acc';
    x.fillRect(1, 12, 26, 3);                   // long purple barrel (range 450)
    x.fillStyle = '#c8a4f0';
    x.fillRect(1, 11, 4, 1);                    // muzzle highlight
    x.fillStyle = '#ffffff';
    x.fillRect(2, 13, 24, 1);                   // ghostly inner-barrel wisp
    x.fillRect(13, 8, 2, 3);                    // dorsal sight glint
    return c;
  }

  // === Pink Rubber Family (Plan 4 — Bucket B) ===
  function makeCannonTurretPinkPea() {
    // Base tier — small bright-pink turret with a short stubby barrel.
    // Reads as a casual / approachable family alongside Howitzer / Repeater.
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#d24a8a';
    x.fillRect(8, 4, 4, 12);                    // pink barrel (rotated at draw time)
    x.fillStyle = '#ff69b4';
    x.fillRect(7, 2, 6, 3);                     // bright pink barrel tip
    x.fillStyle = '#ff8fc4';
    x.fillRect(8, 4, 4, 1);                     // highlight strip
    return c;
  }

  function makeCannonTurretBouncyBubble() {
    // Mid tier — chunkier pink turret, slightly larger, with a bubble-tip
    // barrel that hints at the larger projectile. Reads as a step up from
    // Pink Pea without changing family identity.
    var c = makeCanvas(24, 24);
    var x = c.getContext('2d');
    x.fillStyle = '#c83a7a';
    x.fillRect(10, 6, 4, 12);                   // dark-pink base barrel
    x.fillStyle = '#ff5fa0';
    x.fillRect(9, 4, 6, 4);                     // mid-pink barrel section
    x.fillStyle = '#ff8fc4';
    x.fillRect(8, 1, 8, 4);                     // bubble-tip ring (lighter pink)
    x.fillStyle = '#ffd6ec';
    x.fillRect(10, 2, 4, 2);                    // bubble highlight
    return c;
  }

  function makeCannonTurretRubberWreckingBall() {
    // Final tier — big heavy pink turret with a wrecking-ball motif. Chains
    // hint at the weight of the projectile, dark pink reads as the family's
    // capstone. Largest sprite of the three.
    var c = makeCanvas(28, 28);
    var x = c.getContext('2d');
    x.fillStyle = '#a02864';
    x.fillRect(11, 8, 6, 14);                   // heavy dark-pink base
    x.fillStyle = '#c83a7a';
    x.fillRect(9, 6, 10, 6);                    // wide breech
    x.fillStyle = '#ff1493';
    x.fillRect(10, 0, 8, 8);                    // bright-pink wrecking-ball muzzle
    x.fillStyle = '#2a1810';
    x.fillRect(13, 8, 2, 1);                    // chain link 1 (dark)
    x.fillRect(13, 11, 2, 1);                   // chain link 2
    x.fillRect(13, 14, 2, 1);                   // chain link 3
    x.fillStyle = '#ff8fc4';
    x.fillRect(11, 1, 2, 2);                    // muzzle highlight
    return c;
  }

  // === God-tier turret factories (all 18) ===
  // Each one extends its parent's look (palette and silhouette) and adds a
  // brass or gold detail, so the capstone of a line reads as that line.

  function makeCannonTurretParticleCannon() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Breech base (slate, halo-safe)
    x.fillStyle = '#1a1a30';
    x.fillRect(4, 16, 12, 2);
    // Side rails
    x.fillStyle = '#5a5a6a';
    x.fillRect(4, 14, 1, 4);
    x.fillRect(15, 14, 1, 4);
    // Barrel (silver-white accelerator)
    x.fillStyle = '#d8e0f0';
    x.fillRect(9, 2, 2, 14);
    // Plasma rings (cyan core at y=5, 9, 13)
    x.fillStyle = '#3affff';
    x.fillRect(8, 5, 4, 1);
    x.fillRect(8, 9, 4, 1);
    x.fillRect(8, 13, 4, 1);
    // Ring highlights (lighter cyan, scattered)
    x.fillStyle = '#80ffff';
    x.fillRect(9, 5, 1, 1);
    x.fillRect(10, 9, 1, 1);
    x.fillRect(9, 13, 1, 1);
    // Purple energy core in breech
    x.fillStyle = '#a050ff';
    x.fillRect(9, 15, 2, 1);
    x.fillStyle = '#e0a0ff';
    x.fillRect(10, 15, 1, 1);
    return c;
  }
  function makeCannonTurretAnnihilator() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Cast-iron breech (extends full width — heaviest base)
    x.fillStyle = '#2a2530';
    x.fillRect(3, 11, 14, 7);
    // Bolt heads (4 corners)
    x.fillStyle = '#806040';
    x.fillRect(3, 11, 2, 2);
    x.fillRect(15, 11, 2, 2);
    x.fillRect(3, 16, 2, 2);
    x.fillRect(15, 16, 2, 2);
    // Stubby barrel (chunkiest)
    x.fillStyle = '#3a3540';
    x.fillRect(7, 3, 7, 8);
    // Brass reinforcement bands
    x.fillStyle = '#aa7822';
    x.fillRect(6, 5, 9, 1);
    x.fillRect(6, 9, 9, 1);
    // Brass band highlights
    x.fillStyle = '#d8a040';
    x.fillRect(7, 5, 1, 1);
    x.fillRect(13, 9, 1, 1);
    // Black bore with orange flicker
    x.fillStyle = '#000000';
    x.fillRect(8, 0, 5, 3);
    x.fillStyle = '#cc4020';
    x.fillRect(10, 1, 1, 1);
    return c;
  }
  function makeCannonTurretEarthshaker() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Cast-iron breech (widest base — anchored deep)
    x.fillStyle = '#1a1820';
    x.fillRect(2, 11, 16, 7);
    // Corner bolt-heads
    x.fillStyle = '#604035';
    x.fillRect(2, 11, 2, 2);
    x.fillRect(15, 11, 2, 2);
    x.fillRect(2, 16, 2, 2);
    x.fillRect(15, 16, 2, 2);
    // Industrial-steel piston (lighter than family anchor for pop)
    x.fillStyle = '#3a4055';
    x.fillRect(6, 4, 8, 7);
    // Hammer-face dark cap
    x.fillStyle = '#604035';
    x.fillRect(6, 2, 8, 2);
    // Stud dots (+ pattern on hammer-face)
    x.fillStyle = '#806040';
    x.fillRect(10, 1, 1, 1);
    x.fillRect(8, 3, 1, 1);
    x.fillRect(10, 3, 1, 1);
    x.fillRect(12, 3, 1, 1);
    // Orange seep at the very bottom (warm light leaking)
    x.fillStyle = '#cc4020';
    x.fillRect(5, 18, 10, 1);
    return c;
  }
  function makeCannonTurretPhoenix() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Breech (dark with ember glow)
    x.fillStyle = '#3a2520';
    x.fillRect(4, 14, 12, 4);
    x.fillStyle = '#cc4020';
    x.fillRect(6, 18, 8, 1);
    // Gold-bronze stem (narrow neck connecting cone to breech)
    x.fillStyle = '#aa7822';
    x.fillRect(8, 10, 4, 4);
    // Cone walls (slope inward — darker orange band)
    x.fillStyle = '#cc4020';
    x.fillRect(7, 6, 6, 2);
    x.fillRect(6, 8, 8, 1);
    x.fillRect(4, 5, 12, 1);
    // Mouth — concentric color bands (back to front)
    // Outer ring (deep orange)
    x.fillStyle = '#ff6020';
    x.fillRect(3, 0, 14, 4);
    // Mid ring (bright yellow-orange)
    x.fillStyle = '#ffcc20';
    x.fillRect(5, 0, 10, 4);
    // White-hot core
    x.fillStyle = '#fff0b0';
    x.fillRect(7, 0, 6, 4);
    x.fillStyle = '#ffffff';
    x.fillRect(9, 2, 2, 1);
    // Flame-feather curls (left and right outriggers — 3-px each, mirrored)
    x.fillStyle = '#ff9020';
    x.fillRect(2, 5, 1, 1);
    x.fillRect(1, 6, 1, 1);
    x.fillRect(2, 7, 1, 1);
    x.fillRect(17, 5, 1, 1);
    x.fillRect(18, 6, 1, 1);
    x.fillRect(17, 7, 1, 1);
    return c;
  }
  function makeCannonTurretReaper() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Breech base (dark slate — halo zone)
    x.fillStyle = '#0a0a14';
    x.fillRect(8, 14, 6, 4);
    // Purple aura rim at base edge
    x.fillStyle = '#602080';
    x.fillRect(8, 18, 6, 1);
    // Hood cowl (dark backdrop behind the hook tip)
    x.fillStyle = '#1a1a20';
    x.fillRect(5, 0, 5, 3);
    // Red eye (single pixel, inside hood)
    x.fillStyle = '#ff2020';
    x.fillRect(6, 1, 1, 1);
    // Eye highlight
    x.fillStyle = '#ff8080';
    x.fillRect(7, 1, 1, 1);
    // Vertical shaft (bone-white)
    x.fillStyle = '#e8e0d0';
    x.fillRect(10, 2, 1, 13);
    // Hook (bone-white, overlays hood at apex)
    x.fillRect(9, 2, 1, 1);  // hook base
    x.fillRect(8, 1, 1, 1);  // hook apex (overlays hood)
    x.fillRect(7, 2, 1, 1);  // hook tip
    // Cutting edge (rusted iron, inner side of hook)
    x.fillStyle = '#604035';
    x.fillRect(8, 2, 1, 1);
    x.fillRect(9, 3, 1, 1);
    return c;
  }
  function makeCannonTurretHawkeye() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Walnut stock (breech)
    x.fillStyle = '#604030';
    x.fillRect(6, 14, 8, 4);
    // Wood grain lines (broken pattern)
    x.fillStyle = '#805040';
    x.fillRect(7, 15, 2, 1);
    x.fillRect(11, 15, 2, 1);
    x.fillRect(8, 17, 2, 1);
    x.fillRect(11, 17, 1, 1);
    // Scope cylinder (perpendicular to barrel — cross-axis geometry)
    x.fillStyle = '#1a1820';
    x.fillRect(4, 11, 12, 3);
    // Brass edge rings (left and right end caps of scope)
    x.fillStyle = '#aa7822';
    x.fillRect(4, 11, 1, 3);
    x.fillRect(15, 11, 1, 3);
    // Barrel (gunmetal, very thin — only 2 px wide)
    x.fillStyle = '#4a5560';
    x.fillRect(9, 2, 2, 12);
    // Suppressor bulge at muzzle (slightly wider)
    x.fillStyle = '#2a3540';
    x.fillRect(8, 0, 4, 2);
    return c;
  }
  function makeCannonTurretBlizzard() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Frosted base (halo-friendly slate-blue)
    x.fillStyle = '#3a4055';
    x.fillRect(4, 14, 12, 4);
    // Snowflake glyph (5-pixel asterisk centered in base)
    x.fillStyle = '#ffffff';
    x.fillRect(9, 15, 1, 1);
    x.fillRect(8, 16, 1, 1);
    x.fillRect(9, 16, 1, 1);
    x.fillRect(10, 16, 1, 1);
    x.fillRect(9, 17, 1, 1);
    // Rime crawling up base edges
    x.fillStyle = '#a0d8ff';
    x.fillRect(5, 14, 1, 1);
    x.fillRect(15, 14, 1, 1);
    x.fillRect(4, 16, 1, 1);
    x.fillRect(15, 17, 1, 1);
    // Left ice fragment (3-px triangle)
    x.fillStyle = '#c8e8ff';
    x.fillRect(5, 10, 2, 1);
    x.fillRect(4, 11, 3, 1);
    x.fillRect(5, 12, 2, 1);
    // Right ice fragment (mirrored)
    x.fillRect(13, 10, 2, 1);
    x.fillRect(13, 11, 3, 1);
    x.fillRect(13, 12, 2, 1);
    // Central spire — hexagonal cross-section
    x.fillStyle = '#a0d8ff';
    // Top narrowing (2 px wide)
    x.fillRect(9, 2, 2, 2);
    // Middle widening (4 px)
    x.fillRect(8, 4, 4, 8);
    // Bottom narrowing back
    x.fillRect(9, 12, 2, 2);
    // Right-edge shadow on spire
    x.fillStyle = '#5090c0';
    x.fillRect(11, 4, 1, 8);
    // Sparkle highlights
    x.fillStyle = '#ffffff';
    x.fillRect(9, 4, 1, 1);
    x.fillRect(10, 10, 1, 1);
    return c;
  }
  function makeCannonTurretTempest() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Stormy purple-grey breech
    x.fillStyle = '#3a2a40';
    x.fillRect(6, 15, 8, 4);
    // Lightning glyph in breech (5-pixel zig)
    x.fillStyle = '#fff080';
    x.fillRect(9, 16, 1, 1);
    x.fillRect(10, 16, 1, 1);
    x.fillRect(8, 17, 1, 1);
    x.fillRect(9, 17, 1, 1);
    x.fillRect(10, 17, 1, 1);
    // Shock-rings on the ground (concentric, symmetric — anchors rotation)
    x.fillStyle = '#80c0ff';
    x.fillRect(4, 15, 12, 1);
    x.fillRect(2, 17, 16, 1);
    // Z-bolt outer-edge halo (drawn first as a backdrop glow)
    x.fillStyle = '#ffd040';
    // Top segment glow
    x.fillRect(8, 2, 3, 4);
    // Middle kink glow
    x.fillRect(9, 5, 5, 5);
    // Bottom segment glow
    x.fillRect(12, 9, 3, 6);
    // Z-bolt core (bright yellow-white, 1-px path)
    x.fillStyle = '#fff080';
    // Top segment (lean left from y=2 to y=5)
    x.fillRect(10, 2, 1, 1);
    x.fillRect(9, 3, 1, 1);
    x.fillRect(9, 4, 1, 1);
    x.fillRect(8, 5, 1, 1);
    // Middle segment (diagonal right from y=6 to y=9)
    x.fillRect(10, 6, 1, 1);
    x.fillRect(11, 7, 1, 1);
    x.fillRect(12, 8, 1, 1);
    x.fillRect(13, 9, 1, 1);
    // Bottom segment (vertical from y=10 to y=14)
    x.fillRect(13, 10, 1, 1);
    x.fillRect(13, 11, 1, 1);
    x.fillRect(14, 12, 1, 1);
    x.fillRect(14, 13, 1, 1);
    x.fillRect(14, 14, 1, 1);
    return c;
  }
  function makeCannonTurretBiohazard() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    // Rust-patina base
    x.fillStyle = '#3a2a20';
    x.fillRect(4, 14, 12, 4);
    // Patina speckles
    x.fillStyle = '#5a3a30';
    x.fillRect(6, 15, 1, 1);
    x.fillRect(13, 16, 1, 1);
    x.fillRect(8, 17, 1, 1);
    // Left reservoir (bile-green with shadow)
    x.fillStyle = '#7acc20';
    x.fillRect(2, 8, 6, 10);
    // Left reservoir shadow (left edge)
    x.fillStyle = '#4a8c10';
    x.fillRect(2, 8, 1, 10);
    // Left reservoir liquid line
    x.fillStyle = '#5a8c10';
    x.fillRect(3, 11, 5, 1);
    // Right reservoir (mirrored)
    x.fillStyle = '#7acc20';
    x.fillRect(12, 8, 6, 10);
    x.fillStyle = '#4a8c10';
    x.fillRect(17, 8, 1, 10);
    x.fillStyle = '#5a8c10';
    x.fillRect(12, 11, 5, 1);
    // Central nozzle (dull stainless)
    x.fillStyle = '#8a90a0';
    x.fillRect(9, 2, 2, 8);
    // Venom drip at the muzzle
    x.fillStyle = '#7acc20';
    x.fillRect(9, 0, 1, 1);
    // Biohazard trefoil glyph (3 black 2x2 lobes clustered around x=9-10, y=5-8)
    x.fillStyle = '#0a0a14';
    x.fillRect(7, 5, 2, 2);
    x.fillRect(11, 5, 2, 2);
    x.fillRect(9, 7, 2, 2);
    return c;
  }
  // Saturation (from Hellfire): three fat mortar tubes with ember mouths.
  function makeCannonTurretSaturation() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#5a1a0a';
    x.fillRect(2, 13, 16, 5);                   // charred breech
    x.fillStyle = '#d04020';
    x.fillRect(2, 5, 5, 9);                     // left tube
    x.fillRect(7, 2, 6, 12);                    // center tube
    x.fillRect(13, 5, 5, 9);                    // right tube
    x.fillStyle = '#8a2a10';
    x.fillRect(2, 5, 5, 1);                     // tube lips
    x.fillRect(7, 2, 6, 1);
    x.fillRect(13, 5, 5, 1);
    x.fillStyle = '#ffcc20';
    x.fillRect(4, 6, 1, 1);                     // embers
    x.fillRect(9, 3, 2, 1);
    x.fillRect(15, 6, 1, 1);
    x.fillStyle = '#aa7822';
    x.fillRect(2, 10, 16, 1);                   // brass band
    return c;
  }
  // Cluster Strike (from Patriot): a missile pod with three warheads.
  function makeCannonTurretClusterStrike() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#2a3048';
    x.fillRect(4, 14, 12, 4);                   // breech
    x.fillStyle = '#4a5a7a';
    x.fillRect(7, 4, 6, 11);                    // missile body
    x.fillStyle = '#c84244';
    x.fillRect(5, 11, 2, 4);                    // fins
    x.fillRect(13, 11, 2, 4);
    x.fillStyle = '#fee761';
    x.fillRect(6, 1, 2, 3);                     // three warheads
    x.fillRect(9, 0, 2, 4);
    x.fillRect(12, 1, 2, 3);
    x.fillStyle = '#aa7822';
    x.fillRect(7, 8, 6, 1);                     // brass band
    return c;
  }
  // Vortex (from Ricochet): a swirl launcher around a central hub.
  function makeCannonTurretVortex() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#cc6a4a';
    x.fillRect(3, 4, 6, 2);                     // swirl arm, upper left
    x.fillRect(3, 6, 2, 4);
    x.fillRect(11, 14, 6, 2);                   // swirl arm, lower right
    x.fillRect(15, 10, 2, 4);
    x.fillStyle = '#9b3a1a';
    x.fillRect(7, 7, 6, 6);                     // hub
    x.fillStyle = '#e8a070';
    x.fillRect(8, 1, 4, 3);                     // two disc slots up front
    x.fillRect(8, 16, 4, 2);
    x.fillStyle = '#aa7822';
    x.fillRect(9, 9, 2, 2);                     // brass axle
    return c;
  }
  // Hailstorm (from Vulcan): a wider four-barrel rotary with hail-white tips.
  function makeCannonTurretHailstorm() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#3a8c8c';
    x.fillRect(3, 3, 2, 12);                    // four barrels
    x.fillRect(7, 2, 2, 13);
    x.fillRect(11, 2, 2, 13);
    x.fillRect(15, 3, 2, 12);
    x.fillStyle = '#e8f8ff';
    x.fillRect(3, 1, 2, 2);                     // hail-white tips
    x.fillRect(7, 0, 2, 2);
    x.fillRect(11, 0, 2, 2);
    x.fillRect(15, 1, 2, 2);
    x.fillStyle = '#5aacac';
    x.fillRect(2, 12, 16, 5);                   // housing
    x.fillStyle = '#aa7822';
    x.fillRect(2, 12, 16, 1);                   // brass band
    return c;
  }
  // Solar Lance (from Lance): a gold barrel over a small sun.
  function makeCannonTurretSolarLance() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#ff9c44';
    x.fillRect(5, 13, 10, 1);                   // sun rays
    x.fillRect(9, 9, 2, 10);
    x.fillStyle = '#ffcc20';
    x.fillRect(7, 11, 6, 5);                    // sun disc
    x.fillStyle = '#c8a040';
    x.fillRect(9, 2, 2, 10);                    // gold barrel
    x.fillStyle = '#fff0b0';
    x.fillRect(7, 0, 6, 2);                     // lens emitter
    x.fillStyle = '#ffffff';
    x.fillRect(9, 0, 1, 1);                     // lens glint
    return c;
  }
  // Stormcaller (from Tesla): a taller coil with two spark prongs.
  function makeCannonTurretStormcaller() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#1a3a5a';
    x.fillRect(5, 14, 10, 5);                   // base
    x.fillStyle = '#5a8acc';
    x.fillRect(7, 5, 6, 10);                    // coil housing
    x.fillStyle = '#b0d8ff';
    x.fillRect(6, 3, 8, 2);                     // coil discs
    x.fillRect(7, 8, 6, 1);
    x.fillRect(7, 11, 6, 1);
    x.fillStyle = '#4a4440';
    x.fillRect(3, 6, 2, 6);                     // prongs
    x.fillRect(15, 6, 2, 6);
    x.fillStyle = '#fee761';
    x.fillRect(3, 4, 2, 2);                     // sparks
    x.fillRect(15, 4, 2, 2);
    x.fillRect(9, 0, 2, 3);
    x.fillStyle = '#aa7822';
    x.fillRect(5, 14, 10, 1);                   // brass band
    return c;
  }
  // Scattergun (from Buckshot): a bell-mouth blunderbuss.
  function makeCannonTurretScattergun() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#5c2a14';
    x.fillRect(7, 9, 6, 9);                     // wooden stock
    x.fillStyle = '#cc8a3a';
    x.fillRect(7, 4, 6, 6);                     // barrel
    x.fillStyle = '#ee9a4a';
    x.fillRect(3, 0, 14, 4);                    // wide bell mouth
    x.fillStyle = '#3a1a0a';
    x.fillRect(5, 1, 2, 2);                     // three bores
    x.fillRect(9, 1, 2, 2);
    x.fillRect(13, 1, 2, 2);
    x.fillStyle = '#e8b830';
    x.fillRect(7, 6, 6, 1);                     // gold bands
    x.fillRect(7, 12, 6, 1);
    return c;
  }
  // Phalanx (from Minigun): a domed housing with three short barrels.
  function makeCannonTurretPhalanx() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#5a5a5a';
    x.fillRect(4, 9, 12, 9);                    // dome housing
    x.fillRect(6, 7, 8, 2);
    x.fillStyle = '#7a7a7a';
    x.fillRect(6, 10, 3, 2);                    // dome highlight
    x.fillStyle = '#3a3a3a';
    x.fillRect(6, 1, 2, 7);                     // three barrels
    x.fillRect(9, 0, 2, 8);
    x.fillRect(12, 1, 2, 7);
    x.fillStyle = '#1a1a1a';
    x.fillRect(6, 1, 2, 1);                     // muzzles
    x.fillRect(9, 0, 2, 1);
    x.fillRect(12, 1, 2, 1);
    x.fillStyle = '#aa7822';
    x.fillRect(4, 15, 12, 1);                   // brass band
    return c;
  }
  // Absolute Zero (from Cryo): a crystal spire throwing off shards.
  function makeCannonTurretAbsoluteZero() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#3a4055';
    x.fillRect(4, 14, 12, 4);                   // frosted base
    x.fillStyle = '#80c0ff';
    x.fillRect(3, 6, 3, 6);                     // side shards
    x.fillRect(14, 6, 3, 6);
    x.fillStyle = '#c8e8ff';
    x.fillRect(7, 2, 6, 13);                    // crystal spire
    x.fillStyle = '#3080d0';
    x.fillRect(9, 4, 2, 9);                     // cold core
    x.fillStyle = '#ffffff';
    x.fillRect(9, 0, 2, 2);                     // tip and sparkles
    x.fillRect(4, 5, 1, 1);
    x.fillRect(15, 5, 1, 1);
    x.fillStyle = '#e8b830';
    x.fillRect(4, 14, 12, 1);                   // gold band
    return c;
  }

  function makeCake(slices) {
    var c = makeCanvas(48, 48);
    var x = c.getContext('2d');
    var maxSlices = 8;
    var fillRatio = slices / maxSlices;
    // Drop shadow under the plate so the whole element pops off the
    // red/cream blanket regardless of which checker square it lands on.
    x.fillStyle = 'rgba(0,0,0,0.35)';
    x.fillRect(6, 42, 36, 3);
    // Plate: warm dark wood (was white) for hard contrast against both blanket
    // colors. The white plate disappeared on the cream squares.
    x.fillStyle = '#2a1810';
    x.fillRect(6, 36, 36, 6);
    x.fillStyle = '#5a3a26';
    x.fillRect(6, 36, 36, 1);                 // plate top highlight
    if (fillRatio > 0) {
      // Dark outline ring under the cake body so it reads as a silhouette
      // even when sitting on a red square.
      x.fillStyle = '#2a1810';
      x.fillRect(11, 17 + (1 - fillRatio) * 18, 26, fillRatio * 19 + 2);
      // Cake body
      x.fillStyle = '#feae34';
      x.fillRect(12, 18 + (1 - fillRatio) * 18, 24, fillRatio * 18);
      // Icing
      x.fillStyle = '#fee761';
      x.fillRect(15, 12 + (1 - fillRatio) * 18, 18, fillRatio * 9);
      // Candle outline + body
      x.fillStyle = '#2a1810';
      x.fillRect(22, 4, 4, 11);
      x.fillStyle = '#e43b44';
      x.fillRect(23, 5, 2, 9);
      // Flame
      x.fillStyle = '#fee761';
      x.fillRect(21, 1, 6, 4);
    }
    return c;
  }

  function makeAnthill() {
    var c = makeCanvas(32, 32);
    var x = c.getContext('2d');
    x.fillStyle = '#5a432f';
    x.beginPath();
    x.ellipse(16, 24, 14, 6, 0, 0, Math.PI * 2);
    x.fill();
    x.fillStyle = '#7a5a3a';
    x.beginPath();
    x.ellipse(16, 22, 11, 5, 0, 0, Math.PI * 2);
    x.fill();
    x.fillStyle = '#1f1712';
    x.beginPath();
    x.ellipse(16, 22, 4, 2, 0, 0, Math.PI * 2);
    x.fill();
    return c;
  }

  // Projectile sprites, 4x4, one per look: heavy is an iron ball with a
  // highlight, quick a brass tracer, rubber a pink pea, and status shots a
  // dot in their status color. drawProjectiles picks one per shot.
  function makeShot(outline, fill, hi) {
    var c = makeCanvas(4, 4);
    var x = c.getContext('2d');
    x.fillStyle = outline;
    x.fillRect(1, 0, 2, 4);
    x.fillRect(0, 1, 4, 2);
    x.fillStyle = fill;
    x.fillRect(1, 1, 2, 2);
    x.fillStyle = hi;
    x.fillRect(1, 1, 1, 1);
    return c;
  }
  function makeProjectiles() {
    return {
      heavy:  makeShot('#2a1810', '#4a4440', '#a89e90'),
      quick:  makeShot('#6a4a10', '#e0b040', '#fff0b0'),
      rubber: makeShot('#a02864', '#ff77a8', '#ffd0e0'),
      slow:   makeShot('#2a5a8a', '#a4d4ff', '#ffffff'),
      freeze: makeShot('#2a5a8a', '#c8e8ff', '#ffffff'),
      poison: makeShot('#2a5a10', '#7acc20', '#c8f5a8'),
      stun:   makeShot('#6a5a10', '#fee761', '#ffffff'),
      knockback: makeShot('#6a5a10', '#fee761', '#ffffff')
    };
  }

  // King — final-wave boss (Plan 3 Task 29). 32x32 warm near-black body with brown
  // legs, a yellow three-spike crown over a dark band, and a red gem on the
  // chest so the ant reads as a regal threat at any zoom level.
  function makeKingSprite() {
    var c = makeCanvas(32, 32);
    var x = c.getContext('2d');
    x.fillStyle = '#2e1e18';
    x.fillRect(8, 8, 16, 22);                    // body
    x.fillStyle = '#7a3a1a';
    x.fillRect(6, 12, 4, 4); x.fillRect(22, 12, 4, 4);   // legs upper
    x.fillRect(6, 20, 4, 4); x.fillRect(22, 20, 4, 4);   // legs lower
    x.fillStyle = '#fee761';
    x.fillRect(11, 4, 2, 4);                     // crown spike L
    x.fillRect(15, 2, 2, 6);                     // crown spike center
    x.fillRect(19, 4, 2, 4);                     // crown spike R
    x.fillStyle = '#3a2a0a';
    x.fillRect(11, 8, 10, 2);                    // crown band
    x.fillStyle = '#e43b44';
    x.fillRect(13, 16, 6, 6);                    // chest gem
    return c;
  }

  // 12x12 gold coin used in the HUD coin tag and every cost readout.
  // 12x12 gold coin used in the HUD coin tag and every cost readout.
  function makeCoinSprite() {
    var c = document.createElement('canvas');
    c.width = 12; c.height = 12;
    var x = c.getContext('2d');
    var ink = '#2a1810';
    var gold = '#c8a000';
    var hi = '#fee761';
    // Outline ring (octagonal approximation of a circle)
    x.fillStyle = ink;
    x.fillRect(3, 0, 6, 1);     // top edge
    x.fillRect(2, 1, 1, 1);
    x.fillRect(9, 1, 1, 1);
    x.fillRect(1, 2, 1, 2);     // upper sides
    x.fillRect(10, 2, 1, 2);
    x.fillRect(0, 4, 1, 4);     // mid sides
    x.fillRect(11, 4, 1, 4);
    x.fillRect(1, 8, 1, 2);     // lower sides
    x.fillRect(10, 8, 1, 2);
    x.fillRect(2, 10, 1, 1);
    x.fillRect(9, 10, 1, 1);
    x.fillRect(3, 11, 6, 1);    // bottom edge
    // Gold body fill
    x.fillStyle = gold;
    x.fillRect(3, 1, 6, 10);    // tall band
    x.fillRect(1, 3, 10, 6);    // wide band
    x.fillRect(2, 2, 8, 8);     // square fill
    // Highlight on upper-left
    x.fillStyle = hi;
    x.fillRect(3, 3, 4, 4);
    x.fillRect(2, 4, 6, 2);
    return c;
  }

  // 12x12 cake-slice with candle. Used in the HUD cake tag.
  function makeCakeSliceSprite() {
    var c = document.createElement('canvas');
    c.width = 12; c.height = 12;
    var x = c.getContext('2d');
    // Frosting top (cream-yellow)
    x.fillStyle = '#fee761';
    x.fillRect(1, 4, 10, 2);
    // Cake body
    x.fillStyle = '#f5d000';
    x.fillRect(1, 6, 10, 2);
    // Bottom red layer
    x.fillStyle = '#c84244';
    x.fillRect(1, 8, 10, 3);
    // Outline
    x.fillStyle = '#2a1810';
    x.fillRect(0, 4, 1, 7);
    x.fillRect(11, 4, 1, 7);
    x.fillRect(1, 11, 10, 1);
    x.fillRect(1, 3, 10, 1);
    // Candle
    x.fillStyle = '#f5e6c8';
    x.fillRect(5, 1, 2, 3);
    // Flame
    x.fillStyle = '#ff9c44';
    x.fillRect(5, 0, 2, 1);
    return c;
  }

  // 96x72 hero cake for the idle/title splash. Three tiers, three lit candles.
  // Drawn at 2x output scale (96x72) so that, when canvas is rendered at the
  // typical 2x CSS scale on desktop, it stays crisp.
  function makeTitleCakeSprite() {
    var c = document.createElement('canvas');
    c.width = 96; c.height = 72;
    var x = c.getContext('2d');
    var ink = '#2a1810';
    // Tier sizes (centered)
    // base: full-width bottom tier
    var b = { x: 4,  y: 50, w: 88, h: 18 };
    var m = { x: 14, y: 32, w: 68, h: 18 };
    var t = { x: 24, y: 18, w: 48, h: 14 };
    function tier(rect, frostingColor) {
      // Outline
      x.fillStyle = ink;
      x.fillRect(rect.x, rect.y, rect.w, rect.h);
      // Frosting strip on top
      x.fillStyle = '#fee761';
      x.fillRect(rect.x + 2, rect.y + 2, rect.w - 4, 4);
      // Body band
      x.fillStyle = '#f5d000';
      x.fillRect(rect.x + 2, rect.y + 6, rect.w - 4, 4);
      // Bottom frosting/colour band
      x.fillStyle = frostingColor;
      x.fillRect(rect.x + 2, rect.y + 10, rect.w - 4, rect.h - 12);
      // Bottom darken
      x.fillStyle = 'rgba(0, 0, 0, 0.25)';
      x.fillRect(rect.x + 2, rect.y + rect.h - 4, rect.w - 4, 2);
    }
    tier(b, '#c84244');     // bottom: red frosting band
    tier(m, '#ff77a8');     // middle: pink
    tier(t, '#c8f5a8');     // top: green
    // Three candles on top tier
    var cy = 6;
    for (var i = 0; i < 3; i++) {
      var cx = 36 + i * 12;
      x.fillStyle = ink;
      x.fillRect(cx, cy, 4, 12);
      x.fillStyle = '#f5e6c8';
      x.fillRect(cx + 1, cy + 1, 2, 10);
      // Flame
      x.fillStyle = ink;
      x.fillRect(cx + 1, cy - 5, 2, 5);
      x.fillStyle = '#ff9c44';
      x.fillRect(cx + 1, cy - 4, 2, 4);
      x.fillStyle = '#fee761';
      x.fillRect(cx + 1, cy - 3, 2, 2);
    }
    return c;
  }

  game.sprites = {
    worker: makeWorkerSprite(),
    carrier: makeCarrierSprite(),
    royal_guard: makeRoyalGuardSprite(),
    soldier: makeSoldierSprite(),
    scout: makeScout(),
    tank: makeTank(),
    splitter: makeSplitter(),
    mini_splitter: makeMiniSplitter(),
    phase: makePhase(),
    bomber: makeBomber(),
    queen: makeQueen(),
    king: makeKingSprite(),
    splat: makeAntSplat(),
    royalGuardSplat: makeRoyalGuardSplat(),
    turrets: {
      howitzer: makeCannonTurret(),
      siege: makeCannonTurretSiege(),
      railgun: makeCannonTurretRailgun(),
      mortar: makeCannonTurretMortar(),
      discus: makeCannonTurretDiscus(),
      patriot: makeCannonTurretPatriot(),
      demolisher: makeCannonTurretDemolisher(),
      hellfire: makeCannonTurretHellfire(),
      ricochet: makeCannonTurretRicochet(),
      shockwave: makeCannonTurretShockwave(),
      repeater: makeCannonTurretRepeater(),
      autocannon: makeCannonTurretAutocannon(),
      vulcan: makeCannonTurretVulcan(),
      plasma_torch: makeCannonTurretPlasmaTorch(),
      sharpshooter: makeCannonTurretSharpshooter(),
      lance: makeCannonTurretLance(),
      phantom: makeCannonTurretPhantom(),
      tesla: makeCannonTurretTesla(),
      marksman: makeCannonTurretMarksman(),
      spotter: makeCannonTurretSpotter(),
      twingun: makeCannonTurretTwinGun(),
      triple_threat: makeCannonTurretTripleThreat(),
      buckshot: makeCannonTurretBuckshot(),
      minigun: makeCannonTurretMinigun(),
      toxin: makeCannonTurretToxin(),
      plague: makeCannonTurretPlague(),
      sonic_pulse: makeCannonTurretSonicPulse(),
      frost: makeCannonTurretFrost(),
      glacier: makeCannonTurretGlacier(),
      cryo: makeCannonTurretCryo(),
      // Plan 4 — Pink Rubber Family (Bucket B). Linear chain, no T3 god.
      pink_pea: makeCannonTurretPinkPea(),
      bouncy_bubble: makeCannonTurretBouncyBubble(),
      rubber_wrecking_ball: makeCannonTurretRubberWreckingBall(),
      // God tier, all 18.
      particle_cannon: makeCannonTurretParticleCannon(),
      annihilator: makeCannonTurretAnnihilator(),
      saturation: makeCannonTurretSaturation(),
      cluster_strike: makeCannonTurretClusterStrike(),
      vortex: makeCannonTurretVortex(),
      earthshaker: makeCannonTurretEarthshaker(),
      hailstorm: makeCannonTurretHailstorm(),
      phoenix: makeCannonTurretPhoenix(),
      reaper: makeCannonTurretReaper(),
      solar_lance: makeCannonTurretSolarLance(),
      hawkeye: makeCannonTurretHawkeye(),
      stormcaller: makeCannonTurretStormcaller(),
      scattergun: makeCannonTurretScattergun(),
      phalanx: makeCannonTurretPhalanx(),
      blizzard: makeCannonTurretBlizzard(),
      absolute_zero: makeCannonTurretAbsoluteZero(),
      tempest: makeCannonTurretTempest(),
      biohazard: makeCannonTurretBiohazard()
    },
    anthill: makeAnthill(),
    shots: makeProjectiles(),
    coin: makeCoinSprite(),
    cakeSlice: makeCakeSliceSprite(),
    titleCake: makeTitleCakeSprite()
  };
  // Note: cake sprite is regenerated in render() per frame because it depends on slice count.

  // A few turret sprites (Marksman, Sharpshooter, Lance, Phantom, Demolisher)
  // were drawn with the barrel lying sideways on a 28x28 canvas, while
  // drawCannonTurrets assumes "up" is the firing direction, so they aimed 90
  // degrees off. Stand them up once here, muzzle first (their muzzle sits at
  // the left edge, so a quarter turn clockwise points it up).
  ['marksman', 'sharpshooter', 'lance', 'phantom', 'demolisher'].forEach(function (k) {
    var src = game.sprites.turrets[k];
    var c = makeCanvas(src.height, src.width);
    var x = c.getContext('2d');
    x.translate(c.width, 0);
    x.rotate(Math.PI / 2);
    x.drawImage(src, 0, 0);
    game.sprites.turrets[k] = c;
  });

  // Tinted copies of the ant sprites: one per status effect, plus white for
  // the hit flash. Made on first use and cached, so a frozen or flashing ant
  // is still a single drawImage.
  var ANT_TINTS = {
    white:  ['#ffffff', 1],
    freeze: ['#cdeeff', 0.7],
    slow:   ['#8cc4ee', 0.45],
    stun:   ['#fee761', 0.5],
    burn:   ['#ff7a30', 0.5],
    poison: ['#8ad830', 0.5]
  };
  var tintCache = {};
  function tintedSprite(key, tint) {
    var id = key + ':' + tint;
    if (!tintCache[id]) {
      var src = game.sprites[key];
      var c = makeCanvas(src.width, src.height);
      var x = c.getContext('2d');
      x.drawImage(src, 0, 0);
      x.globalCompositeOperation = 'source-atop';
      x.globalAlpha = ANT_TINTS[tint][1];
      x.fillStyle = ANT_TINTS[tint][0];
      x.fillRect(0, 0, c.width, c.height);
      tintCache[id] = c;
    }
    return tintCache[id];
  }

  // === RENDERING ===
  // Wood-grain panel background filling the right shop column.
  // Vertical 4-px stripes alternating two wood tones plus a top-to-bottom
  // gradient overlay to read as planks.
  function drawShopBackground() {
    var sx; var sy; var sw; var sh;
    if (isPortrait()) {
      sx = 0;
      sy = HEIGHT - STRIP_H;
      sw = WIDTH;
      sh = STRIP_H;
    } else {
      sx = PLAYFIELD_W;
      sy = 0;
      sw = SHOP_W;
      sh = HEIGHT;
    }
    // Wood-plank stripes. Vertical planks for landscape (running top-to-bottom
    // along the right column); horizontal planks for portrait (running
    // left-to-right along the bottom strip) so the grain reads correctly.
    if (isPortrait()) {
      for (var j = 0; j < sh; j = j + 4) {
        if ((j / 4) % 2 === 0) {
          ctx.fillStyle = '#4a3220';
        } else {
          ctx.fillStyle = '#5a3a26';
        }
        ctx.fillRect(sx, sy + j, sw, 4);
      }
    } else {
      for (var i = 0; i < sw; i = i + 4) {
        if ((i / 4) % 2 === 0) {
          ctx.fillStyle = '#4a3220';
        } else {
          ctx.fillStyle = '#5a3a26';
        }
        ctx.fillRect(sx + i, sy, 4, sh);
      }
    }
    // Darken gradient (top-to-bottom in either orientation: the strip / column
    // gets slightly darker toward the bottom so the chrome reads as recessed).
    var grd = ctx.createLinearGradient(sx, sy, sx, sy + sh);
    grd.addColorStop(0, 'rgba(90, 58, 38, 0.0)');
    grd.addColorStop(1, 'rgba(58, 32, 16, 0.6)');
    ctx.fillStyle = grd;
    ctx.fillRect(sx, sy, sw, sh);
    // Dark inner-edge border on the side facing the playfield.
    ctx.fillStyle = '#2a1810';
    if (isPortrait()) {
      // Top edge dark border
      ctx.fillRect(sx, sy, sw, 2);
    } else {
      // Left edge dark border
      ctx.fillRect(sx, sy, 2, sh);
    }
  }

  function drawShopInfoPanel() {
    var k = ui.shopInfoKind;
    if (!k) { ui.shopMode = 'list'; return; }
    var sk = CANNON_KINDS[k];
    if (!sk) { ui.shopMode = 'list'; return; }

    if (isPortrait()) {
      drawShopInfoPanelPortrait(sk);
    } else {
      drawShopInfoPanelLandscape(sk);
    }
  }

  function drawShopInfoPanelLandscape(sk) {
    var px = PLAYFIELD_W + 6;
    var pw = SHOP_W - 12;
    var py = 26;

    // "FIELD GUIDE" eyebrow + back button. The back affordance is a wooden
    // button so it matches the sell / targeting / send-next chrome elsewhere
    // in the game. Label is contextual: when the panel was opened by tapping
    // "?" on an upgrade row of a placed cannon's detail panel, we return to
    // that cannon (selectedCannon is preserved across the info-panel push),
    // so the label says "BACK TO HOWITZER" rather than "BACK TO CANNONS".
    drawTextWithShadow('FIELD GUIDE', px + pw / 2, py, '#c8a868', 'bold 8px monospace', 'center');

    var bbX = px + 16;
    var bbY = py + 6;
    var bbW = pw - 32;
    var bbH = 16;
    drawWoodenButton(bbX, bbY, bbW, bbH, 'wood');

    // Compute the label after sizing the button so we can truncate the
    // cannon name against the actual interior width (button w minus 8 px
    // total horizontal padding) at the rendered font.
    ctx.font = 'bold 9px monospace';
    var backLabel;
    if (ui.shopInfoFrom === 'detail') {
      if (ui.selectedCannon) {
        var srcName = CANNON_KINDS[ui.selectedCannon.kind].name.toUpperCase();
        var prefixW = ctx.measureText('< BACK TO ').width;
        var nameMaxW = (bbW - 8) - prefixW;
        backLabel = '< BACK TO ' + truncateToWidth(srcName, nameMaxW);
      } else {
        backLabel = '< BACK TO CANNONS';
      }
    } else {
      backLabel = '< BACK TO CANNONS';
    }
    drawTextWithShadow(backLabel, bbX + bbW / 2, bbY + bbH / 2, '#fee761', 'bold 9px monospace', 'center');
    ui.shopInfoBackRect = { x: bbX, y: bbY, w: bbW, h: bbH };

    // Name — same shrink-then-truncate fallback as the detail panel so
    // a long kind name doesn't overflow the field-guide panel.
    var infoNameFit = fitFont(sk.name, pw - 16, 'bold __PX__px monospace', 14, 9);
    drawTextWithShadow(infoNameFit.text, px + 8, py + 38, '#fee761', infoNameFit.font, 'start');
    drawTextWithShadow(sk.family + ' family', px + 8, py + 54, '#c8a868', 'bold 8px monospace', 'start');

    // Stats row in a single recessed tag
    var sy = py + 64;
    var sh = 30;
    drawWoodenTag(px + 2, sy, pw - 4, sh);
    var col = (pw - 4) / 3;
    var col1 = px + 2 + col * 0 + col / 2;
    var col2 = px + 2 + col * 1 + col / 2;
    var col3 = px + 2 + col * 2 + col / 2;
    drawTextWithShadow('DMG', col1, sy + 9, '#c8a868', 'bold 7px monospace', 'center');
    drawTextWithShadow('' + sk.damage, col1, sy + 22, '#f5e6c8', 'bold 11px monospace', 'center');
    drawTextWithShadow('RATE', col2, sy + 9, '#c8a868', 'bold 7px monospace', 'center');
    drawTextWithShadow('' + sk.fireRate, col2, sy + 22, '#f5e6c8', 'bold 11px monospace', 'center');
    drawTextWithShadow('RNG', col3, sy + 9, '#c8a868', 'bold 7px monospace', 'center');
    drawTextWithShadow('' + sk.range, col3, sy + 22, '#f5e6c8', 'bold 11px monospace', 'center');

    // Description card — cream paper to match the field-guide aesthetic.
    var desc = sk.description;
    if (!desc) {
      var tierLabel = sk.tier;
      if (!tierLabel) { tierLabel = 'base'; }
      desc = sk.family + ' ' + tierLabel;
    }
    var dy = py + 102;
    var dh = 56;
    // Cream paper body
    var dgrd = ctx.createLinearGradient(px + 2, dy, px + 2, dy + dh);
    dgrd.addColorStop(0, '#fff8e8');
    dgrd.addColorStop(1, '#f0dfb8');
    ctx.fillStyle = dgrd;
    ctx.fillRect(px + 2, dy, pw - 4, dh);
    // Dark border
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(px + 2, dy, pw - 4, 2);
    ctx.fillRect(px + 2, dy, 2, dh);
    ctx.fillRect(px + pw - 4, dy, 2, dh);
    ctx.fillRect(px + 2, dy + dh - 2, pw - 4, 2);
    // Text in dark on cream — drawn directly (not via drawTextWithShadow,
    // which would put a dark stamp on dark text).
    drawWrappedTextDark(desc, px + 12, dy + 10, pw - 24, '#3a2210', 'bold 9px monospace', 14);

    // "EVOLVES TO" header (different word than detail panel's "UPGRADES TO"
    // so the screen reads as a different surface).
    drawTextWithShadow('EVOLVES TO', px + 8, py + 168, '#c8a868', 'bold 8px monospace', 'start');

    // Upgrade rows — cream paper cards, no cost, no affordability dim.
    drawShopInfoEvolveCards(sk, px, pw, py + 180, 46);
  }

  function drawShopInfoPanelPortrait(sk) {
    var stripY = HEIGHT - STRIP_H;

    // Back button (full strip-width wooden chrome) + FIELD GUIDE eyebrow.
    var bbX = 16;
    var bbY = stripY + 4;
    var bbW = WIDTH - 32;
    var bbH = 16;
    drawWoodenButton(bbX, bbY, bbW, bbH, 'wood');
    ctx.font = 'bold 10px monospace';
    var backLabel;
    if (ui.shopInfoFrom === 'detail') {
      if (ui.selectedCannon) {
        var srcName = CANNON_KINDS[ui.selectedCannon.kind].name.toUpperCase();
        var prefixW = ctx.measureText('< BACK TO ').width;
        var nameMaxW = (bbW - 8) - prefixW;
        backLabel = '< BACK TO ' + truncateToWidth(srcName, nameMaxW);
      } else {
        backLabel = '< BACK TO CANNONS';
      }
    } else {
      backLabel = '< BACK TO CANNONS';
    }
    drawTextWithShadow(backLabel, bbX + bbW / 2, bbY + bbH / 2, '#fee761', 'bold 10px monospace', 'center');
    ui.shopInfoBackRect = { x: bbX, y: stripY, w: bbW, h: 24 };   // tap area padded to the strip edge

    // FIELD GUIDE eyebrow below the back button.
    drawTextWithShadow('FIELD GUIDE', WIDTH / 2, stripY + 28, '#c8a868', 'bold 10px monospace', 'center');

    // Two columns
    var colTop = stripY + 36;
    var colH = STRIP_H - 40;     // 140 with STRIP_H=180
    var leftX = 4;
    var leftW = 276;
    var rightX = 284;
    var rightW = WIDTH - rightX - 4;

    // === LEFT: cream paper with name + family + stats + description ===
    var lgrd = ctx.createLinearGradient(leftX, colTop, leftX, colTop + colH);
    lgrd.addColorStop(0, '#fff8e8');
    lgrd.addColorStop(1, '#f0dfb8');
    ctx.fillStyle = lgrd;
    ctx.fillRect(leftX, colTop, leftW, colH);
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(leftX, colTop, leftW, 2);
    ctx.fillRect(leftX, colTop, 2, colH);
    ctx.fillRect(leftX + leftW - 2, colTop, 2, colH);
    ctx.fillRect(leftX, colTop + colH - 2, leftW, 2);

    // Name (dark on cream)
    ctx.font = 'bold 14px monospace';
    var fittedInfoName = truncateToWidth(sk.name, leftW - 16);
    ctx.fillStyle = '#3a2210';
    ctx.textAlign = 'start';
    ctx.textBaseline = 'middle';
    ctx.fillText(fittedInfoName, leftX + 8, colTop + 12);
    ctx.font = 'bold 10px monospace';
    ctx.fillStyle = '#8a5a30';
    ctx.fillText(sk.family + ' family', leftX + 8, colTop + 26);

    // 3-stat row inside the cream area
    var statsY = colTop + 38;
    var colW = (leftW - 16) / 3;
    var labels3 = ['DMG', 'RATE', 'RNG'];
    var values3 = ['' + sk.damage, '' + sk.fireRate, '' + sk.range];
    for (var sI = 0; sI < 3; sI++) {
      var sCx = leftX + 8 + colW * sI + colW / 2;
      ctx.font = 'bold 10px monospace';
      ctx.fillStyle = '#8a5a30';
      ctx.textAlign = 'center';
      ctx.fillText(labels3[sI], sCx, statsY);
      ctx.font = 'bold 11px monospace';
      ctx.fillStyle = '#3a2210';
      ctx.fillText(values3[sI], sCx, statsY + 14);
    }
    ctx.textAlign = 'start';
    ctx.textBaseline = 'alphabetic';

    // Description (wrapped, dark on cream)
    var descP = sk.description;
    if (!descP) {
      var tierLabelP = sk.tier;
      if (!tierLabelP) { tierLabelP = 'base'; }
      descP = sk.family + ' ' + tierLabelP;
    }
    drawWrappedTextDark(descP, leftX + 8, colTop + 62, leftW - 16, '#3a2210', 'bold 10px monospace', 13);

    // === RIGHT: EVOLVES TO header + cream evolution cards ===
    drawTextWithShadow('EVOLVES TO', rightX + rightW / 2, colTop + 6, '#fee761', 'bold 10px monospace', 'center');
    var evolveTop = colTop + 14;
    var evolveH = colH - 14;
    // Up to 3 cards, with 4px gaps. rh = (evolveH - 8) / 3 = ~39 with colH 140
    var evolveRowH = Math.floor((evolveH - 8) / 3);
    drawShopInfoEvolveCards(sk, rightX, rightW, evolveTop, evolveRowH);
  }

  // Shared evolution-cards renderer. Renders up to 3 cream-paper cards
  // starting at baseY, each rh tall with a 5 px gap.
  function drawShopInfoEvolveCards(sk, px, pw, baseY, rh) {
    var upgrades = sk.upgradesTo;
    if (!upgrades) { upgrades = []; }
    var rowY = baseY;
    for (var i = 0; i < upgrades.length; i++) {
      var uk = upgrades[i];
      var udef = CANNON_KINDS[uk];
      if (!udef) { continue; }

      // Cream paper card body
      var rgrd = ctx.createLinearGradient(px + 2, rowY, px + 2, rowY + rh);
      rgrd.addColorStop(0, '#fff8e8');
      rgrd.addColorStop(1, '#f0dfb8');
      ctx.fillStyle = rgrd;
      ctx.fillRect(px + 2, rowY, pw - 4, rh);
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(px + 2, rowY, pw - 4, 2);
      ctx.fillRect(px + 2, rowY, 2, rh);
      ctx.fillRect(px + pw - 4, rowY, 2, rh);
      ctx.fillRect(px + 2, rowY + rh - 2, pw - 4, 2);

      // Top line: name + tier badge. Phones get 10 px for everything here.
      var small = 7;
      var stat = 8;
      if (isPortrait()) { small = 10; stat = 10; }
      ctx.font = 'bold ' + small + 'px monospace';
      var tierLabel2 = udef.tier;
      if (!tierLabel2) { tierLabel2 = 'mid'; }
      var tierW = ctx.measureText(tierLabel2.toUpperCase()).width;
      ctx.font = 'bold 10px monospace';
      var fittedInfoUpgName = truncateToWidth(udef.name, pw - 28 - tierW);
      ctx.fillStyle = '#3a2210';
      ctx.textAlign = 'start';
      ctx.textBaseline = 'middle';
      ctx.fillText(fittedInfoUpgName, px + 12, rowY + 13);

      ctx.font = 'bold ' + small + 'px monospace';
      ctx.fillStyle = '#8a5a30';
      ctx.textAlign = 'end';
      ctx.fillText(tierLabel2.toUpperCase(), px + pw - 8, rowY + 13);

      // Stats line
      ctx.font = 'bold ' + stat + 'px monospace';
      ctx.fillStyle = '#5a3a20';
      ctx.textAlign = 'start';
      ctx.fillText('DMG ' + udef.damage + '  RATE ' + udef.fireRate + '  RNG ' + udef.range, px + 10, rowY + 27);

      // Role line — only render if the row is tall enough to fit a 7px text
      // baseline at y+38 plus descenders without clipping the card's bottom
      // border. Landscape cards (rh=46) qualify; portrait cards (rh~39) do
      // not, so it is dropped there.
      if (rh >= 46) {
        ctx.font = 'bold 7px monospace';
        ctx.fillStyle = '#7a5a40';
        ctx.fillText(truncateToWidth(cannonRole(udef), pw - 16), px + 10, rowY + 38);
      }

      ctx.textAlign = 'start';
      ctx.textBaseline = 'alphabetic';

      rowY = rowY + rh + 5;
    }
  }

  // Word-wrap helper for the description card. Naive but enough for short
  // sentences at small widths.
  function drawWrappedText(text, x, y, maxW, color, font, lineH) {
    ctx.font = font;
    var words = text.split(' ');
    var line = '';
    var ly = y + lineH / 2;
    for (var i = 0; i < words.length; i++) {
      var test = line;
      if (test.length > 0) { test = test + ' '; }
      test = test + words[i];
      if (ctx.measureText(test).width > maxW) {
        if (line.length > 0) {
          drawTextWithShadow(line, x, ly, color, font, 'start');
          ly = ly + lineH;
          line = words[i];
        } else {
          drawTextWithShadow(test, x, ly, color, font, 'start');
          ly = ly + lineH;
          line = '';
        }
      } else {
        line = test;
      }
    }
    if (line.length > 0) {
      drawTextWithShadow(line, x, ly, color, font, 'start');
    }
  }

  // Word-wrap helper for cream/paper backgrounds (no drop shadow).
  function drawWrappedTextDark(text, x, y, maxW, color, font, lineH) {
    ctx.font = font;
    ctx.fillStyle = color;
    ctx.textAlign = 'start';
    ctx.textBaseline = 'middle';
    var words = text.split(' ');
    var line = '';
    var ly = y + lineH / 2;
    for (var i = 0; i < words.length; i++) {
      var test = line;
      if (test.length > 0) { test = test + ' '; }
      test = test + words[i];
      if (ctx.measureText(test).width > maxW) {
        if (line.length > 0) {
          ctx.fillText(line, x, ly);
          ly = ly + lineH;
          line = words[i];
        } else {
          ctx.fillText(test, x, ly);
          ly = ly + lineH;
          line = '';
        }
      } else {
        line = test;
      }
    }
    if (line.length > 0) {
      ctx.fillText(line, x, ly);
    }
    ctx.textBaseline = 'alphabetic';
  }

  // A cannon's one-line role: the first sentence of its description, kept
  // to 32 characters or less so it fits the evolve cards and phone shop cards.
  function cannonRole(def) {
    var s = def.description.split('. ')[0];
    if (s.charAt(s.length - 1) !== '.') { s = s + '.'; }
    return s;
  }

  // Pixel-aware truncation. Caller MUST set ctx.font before calling so the
  // measurement reflects the font that will actually render the text.
  // Returns the input verbatim when it already fits maxW, else a binary-search
  // truncated prefix with a single-character ellipsis suffix that fits.
  function truncateToWidth(text, maxW) {
    if (ctx.measureText(text).width <= maxW) { return text; }
    var ell = '…';
    if (ctx.measureText(ell).width > maxW) { return ''; }
    var lo = 0;
    var hi = text.length;
    while (lo < hi) {
      var mid = (lo + hi + 1) >> 1;
      var probe = text.substring(0, mid) + ell;
      if (ctx.measureText(probe).width <= maxW) { lo = mid; }
      else { hi = mid - 1; }
    }
    if (lo === 0) { return ell; }
    return text.substring(0, lo) + ell;
  }

  // Shrinks the font size from startPx down to minPx (1px steps) until the
  // text fits maxW; if it never fits at minPx, returns a width-truncated copy.
  // Side effect: leaves ctx.font set to the chosen size so the caller can render
  // immediately. fontTpl uses '__PX__' as the size placeholder, e.g.
  // 'bold __PX__px monospace'. Returns { text, font } so the caller can pass
  // `font` straight to drawTextWithShadow.
  function fitFont(text, maxW, fontTpl, startPx, minPx) {
    var px = startPx;
    while (px >= minPx) {
      var f = fontTpl.replace('__PX__', '' + px);
      ctx.font = f;
      if (ctx.measureText(text).width <= maxW) {
        return { text: text, font: f };
      }
      px = px - 1;
    }
    var fmin = fontTpl.replace('__PX__', '' + minPx);
    ctx.font = fmin;
    return { text: truncateToWidth(text, maxW), font: fmin };
  }

  function drawShop() {
    drawShopBackground();

    // If shopMode is 'info', delegate to the info panel render and return.
    if (ui.shopMode === 'info') {
      drawShopInfoPanel();
      return;
    }

    var keys = ['howitzer', 'repeater', 'twingun', 'pink_pea'];
    ui.shopRects = [];
    ui.shopInfoRects = [];

    var portrait = isPortrait();
    var cardX; var cardW; var cardH; var startY; var startX; var dx; var dy;
    if (portrait) {
      // 4 cards in a row at the BOTTOM of the strip, with 4px horizontal
      // gaps. Card height is compact (160) and the cards sit at the strip's
      // bottom edge so the empty space lives above the cards (where the
      // wood plaque is visible) instead of as dead space inside each card.
      var stripY = HEIGHT - STRIP_H;
      var GAP = 4;
      cardW = (WIDTH - 5 * GAP) / 4;
      cardH = 160;
      startX = GAP;
      startY = stripY + STRIP_H - cardH - GAP;
      dx = cardW + GAP;
      dy = 0;
      cardX = startX;
    } else {
      cardX = PLAYFIELD_W + 6;
      cardW = SHOP_W - 12;
      cardH = 78;
      startX = cardX;
      startY = 26;
      dx = 0;
      dy = cardH + 4;
    }

    // "CANNONS" header — landscape only. Portrait uses the strip's own area
    // and skips the standalone header to save vertical space.
    if (!portrait) {
      drawTextWithShadow('CANNONS', PLAYFIELD_W + SHOP_W / 2, 14, '#c8a868', 'bold 9px monospace', 'center');
    }

    for (var i = 0; i < keys.length; i++) {
      var k = keys[i];
      var sk = CANNON_KINDS[k];
      var cx = startX + i * dx;
      var cy = startY + i * dy;
      var canAfford = (game.gold >= sk.cost);

      // Card body
      drawWoodenButton(cx, cy, cardW, cardH, 'wood');

      // Family color strip
      var stripColor = FAMILY_STRIP_COLOR[sk.family];
      if (!stripColor) { stripColor = '#888'; }
      ctx.fillStyle = stripColor;
      ctx.fillRect(cx + 2, cy + 2, 4, cardH - 4);
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(cx + 6, cy + 2, 1, cardH - 4);

      if (portrait) {
        // === PORTRAIT CARD ===
        // Sprite tile 40x40, centered horizontally near the top.
        var spX = cx + cardW / 2 - 20;
        var spY = cy + 10;
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(spX, spY, 40, 40);
        ctx.fillStyle = '#1a0a00';
        ctx.fillRect(spX, spY, 40, 1);
        ctx.fillRect(spX, spY, 1, 40);
        ctx.fillRect(spX + 39, spY, 1, 40);
        ctx.fillRect(spX, spY + 39, 40, 1);
        // Render the cannon (base + turret) in the centered tile. Sprites are
        // 32x32 so center inside the 40x40 inset with a 4 px margin.
        ctx.drawImage(cannonBaseFor(k), spX + 4, spY + 4);
        var turret = game.sprites.turrets[k];
        if (turret) { ctx.drawImage(turret, spX + 10, spY + 4); }

        // Name centered under sprite. Truncate to fit card width minus 8 px.
        ctx.font = 'bold 11px monospace';
        var fittedName = truncateToWidth(sk.name, cardW - 8);
        drawTextWithShadow(fittedName, cx + cardW / 2, cy + 64, '#fee761', 'bold 11px monospace', 'center');

        // Cost row: coin + value, centered.
        ctx.font = 'bold 11px monospace';
        var costStr = '' + sk.cost;
        var costTextW = ctx.measureText(costStr).width;
        var rowW = 12 + 4 + costTextW;
        var rowStartX = cx + (cardW - rowW) / 2;
        ctx.drawImage(game.sprites.coin, rowStartX, cy + 84 - 6);
        var costColor;
        if (canAfford) { costColor = '#fee761'; } else { costColor = '#888'; }
        drawTextWithShadow(costStr, rowStartX + 16, cy + 84, costColor, 'bold 11px monospace', 'start');

        // A short role line instead of the DMG/RATE/RNG columns, which came
        // out at 5 CSS px on a phone. The numbers live in the "?" field guide.
        drawWrappedText(cannonRole(sk), cx + 9, cy + 96, cardW - 14, '#f5e6c8', 'bold 10px monospace', 13);

        // ? hangtag at top-right. Larger than landscape (16 vs 18) for tappability.
        var tagW = 18;
        var tagH = 18;
        var tagX = cx + cardW - tagW - 4;
        var tagY = cy + 4;
        ctx.fillStyle = '#fff8e8';
        ctx.fillRect(tagX, tagY, tagW, tagH);
        ctx.fillStyle = '#f0dfb8';
        ctx.fillRect(tagX + 1, tagY + tagH - 3, tagW - 2, 2);
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(tagX, tagY, tagW, 1);
        ctx.fillRect(tagX, tagY, 1, tagH);
        ctx.fillRect(tagX + tagW - 1, tagY, 1, tagH);
        ctx.fillRect(tagX, tagY + tagH - 1, tagW, 1);
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(tagX + tagW / 2 - 1, tagY - 3, 2, 4);
        ctx.font = 'bold 13px monospace';
        ctx.fillStyle = '#2a1810';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('?', tagX + tagW / 2, tagY + tagH / 2 + 1);
        ctx.textAlign = 'start';
        ctx.textBaseline = 'alphabetic';
        // The tap area reaches past the tag toward the card's corner and a
        // little inward, so a near miss opens the guide instead of arming.
        ui.shopInfoRects.push({ x: tagX - 10, y: tagY - 6, w: tagW + 14, h: tagH + 14, kind: k });

        // Armed-card highlight + Tap a spot pill
        if (ui.armedKind === k) {
          ctx.strokeStyle = '#fee761';
          ctx.lineWidth = 3;
          ctx.strokeRect(cx + 1, cy + 1, cardW - 2, cardH - 2);
          ctx.lineWidth = 1;
          var pillW = cardW - 16;
          var pillH = 14;
          var pillX = cx + 8;
          var pillY = cy + cardH - 10;
          ctx.fillStyle = '#fee761';
          ctx.fillRect(pillX, pillY, pillW, pillH);
          ctx.fillStyle = '#2a1810';
          ctx.fillRect(pillX, pillY, pillW, 1);
          ctx.fillRect(pillX, pillY + pillH - 1, pillW, 1);
          ctx.fillRect(pillX, pillY, 1, pillH);
          ctx.fillRect(pillX + pillW - 1, pillY, 1, pillH);
          drawTextWithShadow('TAP A SPOT', pillX + pillW / 2, pillY + pillH / 2, '#2a1810', 'bold 10px monospace', 'center');
        }
      } else {
        // === LANDSCAPE CARD === (existing visual coherence layout, unchanged)
        // Sprite tile (32x32 dark inset)
        var spXL = cx + 10;
        var spYL = cy + 8;
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(spXL, spYL, 32, 32);
        ctx.fillStyle = '#1a0a00';
        ctx.fillRect(spXL, spYL, 32, 1);
        ctx.fillRect(spXL, spYL, 1, 32);
        ctx.fillRect(spXL + 31, spYL, 1, 32);
        ctx.fillRect(spXL, spYL + 31, 32, 1);
        // Render the full cannon (base + turret) so the shop card matches the
        // in-game silhouette. Match the in-game offsets: base centered in the
        // 32x32 tile, turret offset +6 px on x.
        ctx.drawImage(cannonBaseFor(k), spXL, spYL);
        var turretL = game.sprites.turrets[k];
        if (turretL) { ctx.drawImage(turretL, spXL + 6, spYL); }

        // Name — truncate so a long name can't reach the "?" hangtag.
        // Sprite tile ends at spXL+32 (we render the name at spXL+36); hangtag
        // starts at cx + cardW - 22 with a 4px gap.
        ctx.font = 'bold 11px monospace';
        var fittedShopName = truncateToWidth(sk.name, cardW - 72);
        drawTextWithShadow(fittedShopName, spXL + 36, cy + 14, '#fee761', 'bold 11px monospace', 'start');

        // Cost row: coin sprite + value, vertically centered with the coin.
        var costYL = cy + 30;
        ctx.drawImage(game.sprites.coin, spXL + 36, costYL - 5);
        var costColorL;
        if (canAfford) { costColorL = '#fee761'; } else { costColorL = '#888'; }
        drawTextWithShadow('' + sk.cost, spXL + 52, costYL + 1, costColorL, 'bold 11px monospace', 'start');

        // Stats row — inset from the family-color strip on the left so the
        // box doesn't visually butt against it. cx+12 leaves a 5px gap
        // past the strip border at cx+7; right edge stays at cx+cardW-8.
        var statsYL = cy + 56;
        ctx.fillStyle = 'rgba(0, 0, 0, 0.18)';
        ctx.fillRect(cx + 12, statsYL - 4, cardW - 20, 18);
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(cx + 12, statsYL - 4, cardW - 20, 1);

        // Three stats in equal-width columns, label-then-value with a fixed gap.
        var statColW = (cardW - 20) / 3;
        var col1X = cx + 12;
        var col2X = cx + 12 + statColW;
        var col3X = cx + 12 + 2 * statColW;
        drawTextWithShadow('DMG', col1X, statsYL + 4, '#c8a868', 'bold 8px monospace', 'start');
        drawTextWithShadow('' + sk.damage, col1X + 22, statsYL + 4, '#f5e6c8', 'bold 10px monospace', 'start');
        drawTextWithShadow('RATE', col2X, statsYL + 4, '#c8a868', 'bold 8px monospace', 'start');
        drawTextWithShadow('' + sk.fireRate, col2X + 22, statsYL + 4, '#f5e6c8', 'bold 10px monospace', 'start');
        drawTextWithShadow('RNG', col3X, statsYL + 4, '#c8a868', 'bold 8px monospace', 'start');
        drawTextWithShadow('' + sk.range, col3X + 22, statsYL + 4, '#f5e6c8', 'bold 10px monospace', 'start');

        // Cream paper "?" hangtag at the top-right — stands out against the wooden card
        var tagWL = 18;
        var tagHL = 18;
        var tagXL = cx + cardW - tagWL - 4;
        var tagYL = cy + 4;
        // Paper body (cream)
        ctx.fillStyle = '#fff8e8';
        ctx.fillRect(tagXL, tagYL, tagWL, tagHL);
        ctx.fillStyle = '#f0dfb8';
        ctx.fillRect(tagXL + 1, tagYL + tagHL - 3, tagWL - 2, 2);
        // Dark border
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(tagXL, tagYL, tagWL, 1);
        ctx.fillRect(tagXL, tagYL, 1, tagHL);
        ctx.fillRect(tagXL + tagWL - 1, tagYL, 1, tagHL);
        ctx.fillRect(tagXL, tagYL + tagHL - 1, tagWL, 1);
        // Dark string anchor at top
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(tagXL + tagWL / 2 - 1, tagYL - 3, 2, 4);
        // "?" glyph in dark on cream
        ctx.font = 'bold 13px monospace';
        ctx.fillStyle = '#2a1810';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('?', tagXL + tagWL / 2, tagYL + tagHL / 2 + 1);
        ctx.textAlign = 'start';
        ctx.textBaseline = 'alphabetic';
        ui.shopInfoRects.push({ x: tagXL, y: tagYL, w: tagWL, h: tagHL, kind: k });

        // Armed-card highlight + Tap a spot pill
        if (ui.armedKind === k) {
          ctx.strokeStyle = '#fee761';
          ctx.lineWidth = 3;
          ctx.strokeRect(cx + 1, cy + 1, cardW - 2, cardH - 2);
          ctx.lineWidth = 1;
          var pillWL = 60;
          var pillHL = 12;
          var pillXL = cx + cardW / 2 - pillWL / 2;
          var pillYL = cy + cardH - 6;
          ctx.fillStyle = '#fee761';
          ctx.fillRect(pillXL, pillYL, pillWL, pillHL);
          ctx.fillStyle = '#2a1810';
          ctx.fillRect(pillXL, pillYL, pillWL, 1);
          ctx.fillRect(pillXL, pillYL + pillHL - 1, pillWL, 1);
          ctx.fillRect(pillXL, pillYL, 1, pillHL);
          ctx.fillRect(pillXL + pillWL - 1, pillYL, 1, pillHL);
          drawTextWithShadow('TAP A SPOT', pillXL + pillWL / 2, pillYL + pillHL / 2, '#2a1810', 'bold 8px monospace', 'center');
        }
      }

      ui.shopRects.push({ x: cx, y: cy, w: cardW, h: cardH, kind: k });
    }
  }

  // Sell refund = 70% of the floored sum of every kind ever owned (kindHistory
  // includes the base from placeCannon and every kind pushed by upgradeCannon).
  // Spec SYS-2's literal text double-counts the base, so this implements the
  // intent: 70% of total spend across the upgrade chain.
  function sellRefund(c) {
    var total = 0;
    for (var i = 0; i < c.kindHistory.length; i++) {
      total = total + CANNON_KINDS[c.kindHistory[i]].cost;
    }
    return Math.floor(0.7 * total);
  }

  function upgradeCannon(c, toKind) {
    var udef = CANNON_KINDS[toKind];
    if (!udef) { return; }
    // Plan 4: block god-tier upgrades when locked. Defense-in-depth alongside
    // the visual greying in drawTowerPanel (Bucket F). Without this guard a
    // misclick on a 50%-alpha button would still commit the upgrade.
    if (udef.tier === 'god') {
      if (!game.godTierUnlocked) { return; }
    }
    if (game.gold < udef.cost) { return; }
    game.gold = game.gold - udef.cost;
    applyUpgrade(c, toKind);
  }

  // The state change of an upgrade, minus the gold. upgradeCannon pays for it;
  // restoreRun replays a saved kindHistory through it for free.
  function applyUpgrade(c, toKind) {
    c.kind = toKind;
    c.kindHistory.push(toKind);
  }

  function sellCannon(c) {
    var refund = sellRefund(c);
    game.gold = game.gold + refund;
    var idx = game.cannons.indexOf(c);
    if (idx >= 0) { game.cannons.splice(idx, 1); }
    ui.selectedCannon = null;
  }

  // Plan 7 — Targeting row: 5 buttons (C, F, cake slice, S, W) + label below.
  // Returns the y-coordinate immediately after the row, so drawTowerPanel
  // can push the upgrade buttons down by the row's vertical extent.
  // Sets ui.targetingRects to a 5-array of hit-test rects in mode order:
  //   [closest, first, carrying, strongest, weakest].
  // The cake-slice button ("Thieves") goes after ants running off with a
  // slice; it replaced "Last", which was ambiguous on an open blanket.
  // Phones get wider, taller buttons (the portrait column has the room).
  function drawTargetingRow(c, px, py) {
    var def = CANNON_KINDS[c.kind];
    if (def.aura) {
      ui.targetingRects = [];
      return py;
    }
    var modes = ['closest', 'first', 'carrying', 'strongest', 'weakest'];
    var glyphs = ['C', 'F', '', 'S', 'W'];
    var modeNames = ['Closest', 'First', 'Thieves', 'Strongest', 'Weakest'];
    var modeColors = ['#4a6acc', '#c8a040', '#feae34', '#c84444', '#c84090'];
    var btnW = 22;
    var btnH = 22;
    var gap = 4;
    var labelFont = 'bold 8px monospace';
    if (isPortrait()) {
      btnW = 44;
      btnH = 26;
      gap = 6;
      labelFont = 'bold 10px monospace';
    }

    var active = c.targetingMode;
    if (!active) { active = 'closest'; }

    ui.targetingRects = [];
    for (var i = 0; i < 5; i++) {
      var bx = px + i * (btnW + gap);
      var by = py;
      var isActive = (modes[i] === active);
      drawWoodenButton(bx, by, btnW, btnH, 'wood');
      if (isActive) {
        // Mode-color bar along the bottom edge
        ctx.fillStyle = modeColors[i];
        ctx.fillRect(bx + 2, by + btnH - 6, btnW - 4, 4);
      }
      var glyphColor;
      if (isActive) { glyphColor = '#fee761'; } else { glyphColor = '#f5e6c8'; }
      if (modes[i] === 'carrying') {
        ctx.drawImage(game.sprites.cakeSlice, Math.round(bx + btnW / 2 - 6), Math.round(by + btnH / 2 - 7));
      } else {
        drawTextWithShadow(glyphs[i], bx + btnW / 2, by + btnH / 2, glyphColor, 'bold 11px monospace', 'center');
      }
      ui.targetingRects.push({ x: bx, y: by, w: btnW, h: btnH });
    }

    var labelY = py + btnH + 12;
    var activeIdx = 0;
    for (var li = 0; li < modes.length; li++) {
      if (modes[li] === active) { activeIdx = li; break; }
    }
    drawTextWithShadow('TARGETING ' + modeNames[activeIdx].toUpperCase(), px, labelY, '#c8a868', labelFont, 'start');
    return labelY + 8;
  }

  function drawTowerPanel(c) {
    var sk = CANNON_KINDS[c.kind];
    drawShopBackground();

    if (isPortrait()) {
      drawTowerPanelPortrait(c, sk);
    } else {
      drawTowerPanelLandscape(c, sk);
    }
  }

  // Landscape: vertical stack in the right sidebar. Lifted verbatim from the
  // pre-portrait drawTowerPanel — no behavior change.
  function drawTowerPanelLandscape(c, sk) {
    var px = PLAYFIELD_W + 6;
    var pw = SHOP_W - 12;
    var py = 26;

    // Back button — wooden chrome to match the info panel's back button and
    // the rest of the game's button language (sell / targeting / send-next).
    var dbX = px + 16;
    var dbY = py + 2;
    var dbW = pw - 32;
    var dbH = 16;
    drawWoodenButton(dbX, dbY, dbW, dbH, 'wood');
    drawTextWithShadow('< BACK TO CANNONS', dbX + dbW / 2, dbY + dbH / 2, '#fee761', 'bold 9px monospace', 'center');
    ui.detailBackRect = { x: dbX, y: dbY, w: dbW, h: dbH };

    // Name — shrink to fit the panel width, then truncate at the smallest size
    // we'll allow. Long upgrade names (e.g. "Rubber Wrecking Ball") would
    // otherwise spill past the panel's right edge.
    var detailNameFit = fitFont(sk.name, pw - 16, 'bold __PX__px monospace', 14, 9);
    drawTextWithShadow(detailNameFit.text, px + 8, py + 32, '#fee761', detailNameFit.font, 'start');
    drawTextWithShadow(sk.family + ' family', px + 8, py + 48, '#c8a868', 'bold 8px monospace', 'start');

    // Stats row — 4 centered columns (DMG / RATE / RNG / KILLS).
    var sy = py + 58;
    var sh = 30;
    drawWoodenTag(px + 2, sy, pw - 4, sh);
    var col = (pw - 4) / 4;
    var killCount;
    if (c.kills) { killCount = c.kills; } else { killCount = 0; }
    var labels = ['DMG', 'RATE', 'RNG', 'KILLS'];
    var values = ['' + sk.damage, '' + sk.fireRate, '' + sk.range, '' + killCount];
    var valColors = ['#f5e6c8', '#f5e6c8', '#f5e6c8', '#fee761'];
    for (var i = 0; i < 4; i++) {
      var cx = px + 2 + col * i + col / 2;
      drawTextWithShadow(labels[i], cx, sy + 9, '#c8a868', 'bold 7px monospace', 'center');
      drawTextWithShadow(values[i], cx, sy + 22, valColors[i], 'bold 11px monospace', 'center');
    }

    // Targeting row + label (extra y-padding below the stats tag)
    var afterTargetingY = drawTargetingRow(c, px + 8, py + 110);

    // Upgrade rows + sell — shared helpers so portrait can render the same.
    // A cannon at the end of its line has no upgrade rows, so its
    // description fills that space instead of bare wood.
    if (sk.upgradesTo.length === 0) {
      drawPaperNote(sk.description, px + 2, afterTargetingY + 6, pw - 4, 'bold 9px monospace', 14);
    }
    drawTowerPanelUpgrades(c, sk, px, pw, afterTargetingY + 6, /*upgRowH=*/ 42);
    drawTowerPanelSell(c, px, pw, HEIGHT - 30, /*sellH=*/ 24, /*variant=*/ 'red');
  }

  // A cream paper card holding a wrapped line of text, sized to the text.
  function drawPaperNote(text, x, y, w, font, lineH) {
    ctx.font = font;
    var words = text.split(' ');
    var lines = 1;
    var line = '';
    for (var i = 0; i < words.length; i++) {
      var test = line ? line + ' ' + words[i] : words[i];
      if (ctx.measureText(test).width > w - 20) { lines = lines + 1; line = words[i]; } else { line = test; }
    }
    var h = lines * lineH + 14;
    var grd = ctx.createLinearGradient(x, y, x, y + h);
    grd.addColorStop(0, '#fff8e8');
    grd.addColorStop(1, '#f0dfb8');
    ctx.fillStyle = grd;
    ctx.fillRect(x, y, w, h);
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(x, y, w, 2);
    ctx.fillRect(x, y, 2, h);
    ctx.fillRect(x + w - 2, y, 2, h);
    ctx.fillRect(x, y + h - 2, w, 2);
    drawWrappedTextDark(text, x + 10, y + 7, w - 20, '#3a2210', font, lineH);
  }

  // Portrait: two-column horizontal layout in the bottom strip.
  // STRIP_H = 180. Back button at the top, then left column (name + stats +
  // targeting) and right column (3 upgrade rows + sell) side by side.
  function drawTowerPanelPortrait(c, sk) {
    var stripY = HEIGHT - STRIP_H;

    // Back button — full strip-width wooden chrome.
    var bbX = 16;
    var bbY = stripY + 4;
    var bbW = WIDTH - 32;
    var bbH = 16;
    drawWoodenButton(bbX, bbY, bbW, bbH, 'wood');
    drawTextWithShadow('< BACK TO CANNONS', bbX + bbW / 2, bbY + bbH / 2, '#fee761', 'bold 10px monospace', 'center');
    // Tap area padded from the strip's top edge down to the columns.
    ui.detailBackRect = { x: bbX, y: stripY, w: bbW, h: 22 };

    // Two columns
    var colTop = stripY + 22;
    var colH = STRIP_H - 26;     // 154 with STRIP_H = 180
    var leftX = 4;
    var leftW = 276;
    var rightX = 284;
    var rightW = WIDTH - rightX - 4;

    // === LEFT COLUMN: name, family, stats, targeting ===
    ctx.fillStyle = 'rgba(0, 0, 0, 0.18)';
    ctx.fillRect(leftX, colTop, leftW, colH);
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(leftX, colTop, leftW, 1);

    // Name
    var detailNameFitP = fitFont(sk.name, leftW - 16, 'bold __PX__px monospace', 14, 9);
    drawTextWithShadow(detailNameFitP.text, leftX + 8, colTop + 12, '#fee761', detailNameFitP.font, 'start');
    drawTextWithShadow(sk.family + ' family', leftX + 8, colTop + 26, '#c8a868', 'bold 10px monospace', 'start');

    // 4-stat row in 4 even columns (DMG / RATE / RNG / KILLS)
    var statsY = colTop + 38;
    var statsH = 30;
    drawWoodenTag(leftX + 4, statsY, leftW - 8, statsH);
    var colW = (leftW - 8) / 4;
    var killCountP;
    if (c.kills) { killCountP = c.kills; } else { killCountP = 0; }
    var labelsP = ['DMG', 'RATE', 'RNG', 'KILLS'];
    var valuesP = ['' + sk.damage, '' + sk.fireRate, '' + sk.range, '' + killCountP];
    var valColorsP = ['#f5e6c8', '#f5e6c8', '#f5e6c8', '#fee761'];
    for (var iP = 0; iP < 4; iP++) {
      var cxP = leftX + 4 + colW * iP + colW / 2;
      drawTextWithShadow(labelsP[iP], cxP, statsY + 9, '#c8a868', 'bold 10px monospace', 'center');
      drawTextWithShadow(valuesP[iP], cxP, statsY + 22, valColorsP[iP], 'bold 11px monospace', 'center');
    }

    // Targeting row — sits below the stats tag. drawTargetingRow renders
    // 5 buttons starting at (x, y) and adds a "TARGETING X" label below them.
    drawTargetingRow(c, leftX + 8, statsY + statsH + 8);

    // === RIGHT COLUMN: upgrade rows + sell ===
    var sellH = 20;
    var upgTop = colTop;
    var upgBottom = colTop + colH - sellH - 4;
    var upgArea = upgBottom - upgTop;     // ~130 with colH 154
    // 3 upgrade rows with 4 px gaps = 3*rh + 2*4 = upgArea; so rh = (upgArea - 8) / 3
    var upgRowH = Math.floor((upgArea - 8) / 3);
    if (sk.upgradesTo.length === 0) {
      drawPaperNote(sk.description, rightX + 2, upgTop, rightW - 4, 'bold 10px monospace', 13);
    }
    drawTowerPanelUpgrades(c, sk, rightX, rightW, upgTop, upgRowH);

    // Sell button at the bottom of the right column; its tap area runs to
    // the bottom edge of the canvas.
    drawTowerPanelSell(c, rightX, rightW, upgBottom + 4, sellH, 'red');
    ui.sellRect.h = HEIGHT - ui.sellRect.y;
  }

  // Shared upgrade-row renderer. Renders up to 3 upgrade buttons starting
  // at baseY, each upgRowH tall with a 5 px gap. Populates ui.upgradeRects
  // and ui.detailUpgradeInfoRects.
  function drawTowerPanelUpgrades(c, sk, px, pw, baseY, upgRowH) {
    var upgrades = sk.upgradesTo;
    if (!upgrades) { upgrades = []; }
    ui.upgradeRects = [null, null, null];
    ui.detailUpgradeInfoRects = [null, null, null];
    for (var i2 = 0; i2 < upgrades.length; i2++) {
      var uk = upgrades[i2];
      var udef = CANNON_KINDS[uk];
      if (!udef) { continue; }
      var rh = upgRowH;
      var by = baseY + i2 * (rh + 5);
      var canAfford = (game.gold >= udef.cost);
      var isGod = (udef.tier === 'god');
      var isLocked = false;
      if (isGod) {
        if (!game.godTierUnlocked) { isLocked = true; }
      }

      // Plan 4 unlock pulse, 1.5 s on the effects clock.
      if (isGod) {
        var pulseAge = game.fxTime - game.godTierUnlockPulseStart;
        if (pulseAge >= 0) {
          if (pulseAge < 1.5) {
            var pulseAlpha = 1 - (pulseAge / 1.5);
            ctx.save();
            ctx.globalAlpha = pulseAlpha;
            ctx.fillStyle = '#fee761';
            ctx.fillRect(px - 2, by - 4, pw + 4, rh + 8);
            ctx.restore();
          }
        }
      }

      ctx.save();
      // Affordability + locked dim
      if (isLocked) {
        ctx.globalAlpha = 0.5;
      } else if (!canAfford) {
        ctx.globalAlpha = 0.55;
      }

      // Wooden frame, gold tint for god-tier
      if (isGod) {
        var grd = ctx.createLinearGradient(px + 2, by, px + 2, by + rh);
        grd.addColorStop(0, '#5a4a10');
        grd.addColorStop(1, '#3a2c00');
        ctx.fillStyle = grd;
        ctx.fillRect(px + 2, by, pw - 4, rh);
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(px + 2, by, pw - 4, 2);
        ctx.fillRect(px + 2, by, 2, rh);
        ctx.fillRect(px + pw - 4, by, 2, rh);
        ctx.fillRect(px + 2, by + rh - 2, pw - 4, 2);
        ctx.fillStyle = '#c0a040';
        ctx.fillRect(px + 4, by + rh - 4, pw - 8, 2);
      } else {
        drawWoodenButton(px + 2, by, pw - 4, rh, 'wood');
      }

      // Top line — name on the left, cost in the middle (with coin), small ?
      // hangtag on the far right that opens the upgrade's info panel.
      // Truncate the name so it can't extend under the coin / cost / ? hangtag.
      // Coin x = px + pw - 56, so the name must end before it with a small gap.
      ctx.font = 'bold 10px monospace';
      var fittedUpgName = truncateToWidth(udef.name, pw - 70);
      drawTextWithShadow(fittedUpgName, px + 10, by + 11, '#fee761', 'bold 10px monospace', 'start');
      ctx.drawImage(game.sprites.coin, px + pw - 56, by + 6);
      var costColor;
      if (isLocked) { costColor = '#888'; }
      else if (canAfford) { costColor = '#fee761'; }
      else { costColor = '#ff7070'; }
      drawTextWithShadow('' + udef.cost, px + pw - 26, by + 11, costColor, 'bold 10px monospace', 'end');
      // Stats line (a locked god-tier row says when it opens instead, below
      // at full strength so it reads on touch too, where there is no hover).
      var statFont = 'bold 8px monospace';
      if (isPortrait()) { statFont = 'bold 10px monospace'; }
      if (!isLocked) {
        drawTextWithShadow('DMG ' + udef.damage + '  RATE ' + udef.fireRate + '  RNG ' + udef.range, px + 10, by + 28, '#f5e6c8', statFont, 'start');
      }

      // Cream paper "?" hangtag at top-right — opens info for this upgrade.
      var ihtW = 16;
      var ihtH = 16;
      var ihtX = px + pw - ihtW - 6;
      var ihtY = by + 4;
      ctx.fillStyle = '#fff8e8';
      ctx.fillRect(ihtX, ihtY, ihtW, ihtH);
      ctx.fillStyle = '#f0dfb8';
      ctx.fillRect(ihtX + 1, ihtY + ihtH - 3, ihtW - 2, 2);
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(ihtX, ihtY, ihtW, 1);
      ctx.fillRect(ihtX, ihtY, 1, ihtH);
      ctx.fillRect(ihtX + ihtW - 1, ihtY, 1, ihtH);
      ctx.fillRect(ihtX, ihtY + ihtH - 1, ihtW, 1);
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(ihtX + ihtW / 2 - 1, ihtY - 3, 2, 4);
      ctx.font = 'bold 11px monospace';
      ctx.fillStyle = '#2a1810';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText('?', ihtX + ihtW / 2, ihtY + ihtH / 2 + 1);
      ctx.textAlign = 'start';
      ctx.textBaseline = 'alphabetic';

      ctx.restore();

      if (isLocked) {
        drawTextWithShadow('UNLOCKS AFTER WAVE 15', px + 10, by + 28, '#fee761', statFont, 'start');
      }

      ui.upgradeRects[i2] = { x: px + 2, y: by, w: pw - 4, h: rh, toKind: uk, locked: isLocked };
      // The "?" tap area covers the row's full height at its right end, and
      // is tested before the row itself, so a near miss opens the guide
      // instead of buying the upgrade.
      ui.detailUpgradeInfoRects[i2] = { x: ihtX - 6, y: by, w: (px + pw - 2) - (ihtX - 6), h: rh, kind: uk };
    }
  }

  function drawTowerPanelSell(c, px, pw, sbY, sbH, variant) {
    var refund = sellRefund(c);
    var sbW = pw - 4;
    var sbX = px + 2;
    drawWoodenButton(sbX, sbY, sbW, sbH, variant);
    drawTextWithShadow('Sell · +' + refund, sbX + sbW / 2, sbY + sbH / 2, '#fee761', 'bold 10px monospace', 'center');
    ui.sellRect = { x: sbX, y: sbY, w: sbW, h: sbH };
  }

  // === Render helpers (z-order spec §3.5, layers 1–13) ===
  // render() is the single entry point and walks back-to-front through the
  // canonical layer list. Each helper below owns one layer (or one z-slot).

  // Layer 1: blanket + grass tiles + cake plate background.
  function drawBackground() {
    ctx.fillStyle = '#2e4a2a';                  // moss grass (was a cool teal)
    ctx.fillRect(0, 0, WIDTH, HEIGHT);

    // Picnic blanket — alternating red/white check on top of the grass field.
    var blanketX = 48;
    var blanketY = 48;
    var blanketW = 384;
    var blanketH = 272;
    var checkSize = 16;
    for (var by = 0; by < blanketH; by += checkSize) {
      for (var bx = 0; bx < blanketW; bx += checkSize) {
        var isRed = ((bx / checkSize) + (by / checkSize)) % 2 === 0;
        ctx.fillStyle = isRed ? '#e43b44' : '#feeed8';
        ctx.fillRect(blanketX + bx, blanketY + by, checkSize, checkSize);
      }
    }
  }

  // Layer 2: cake (regenerated each frame because it depends on slice count).
  // The plate dips a pixel for a moment when an ant grabs a slice.
  function drawCake() {
    var cakeSprite = makeCake(game.cake.slices);
    var dip = 0;
    if (game.fxTime - game.cakeDipFx < 0.12) {
      if (!prefersReducedMotion()) { dip = 1; }
    }
    ctx.drawImage(cakeSprite, game.cake.x - 24, game.cake.y - 24 + dip);
  }

  // Layer 3: anthill.
  function drawAnthill() {
    ctx.drawImage(game.sprites.anthill, game.anthill.x - 16, game.anthill.y - 16);
  }

  // Layer 4: fallen slices on the ground. Inline 6x4 frosted-orange block with a
  // tiny shadow strip and a yellow highlight pixel — they read as on the ground
  // because cannons and ants render over them.
  function drawSlices() {
    for (var si = 0; si < game.slices.length; si++) {
      var sl = game.slices[si];
      ctx.fillStyle = 'rgba(0,0,0,0.3)';
      ctx.fillRect(sl.x - 3, sl.y + 1, 6, 2);
      ctx.fillStyle = '#ff9c40';
      ctx.fillRect(sl.x - 3, sl.y - 2, 6, 4);
      ctx.fillStyle = '#fee761';
      ctx.fillRect(sl.x - 2, sl.y - 1, 2, 1);
    }
  }

  // Layer 5: range circle for the selected cannon. Drawn under ants and cannons
  // so it never obscures moving entities.
  // Draws a circle as a 1.5 px Sticky Yellow line over a darker 3 px line, so
  // it reads on both the red and the cream squares of the blanket.
  function strokeRing(x, y, r, color, dashed) {
    ctx.save();
    if (dashed) { ctx.setLineDash([4, 4]); }
    ctx.strokeStyle = 'rgba(42, 24, 16, 0.55)';
    ctx.lineWidth = 3;
    ctx.beginPath();
    ctx.arc(x, y, r, 0, Math.PI * 2);
    ctx.stroke();
    ctx.strokeStyle = color;
    ctx.lineWidth = 1.5;
    ctx.stroke();
    ctx.restore();
  }

  function drawRangeCircle(c) {
    var selKind = CANNON_KINDS[c.kind];
    // An aura cannon's ring already shows its reach.
    if (!selKind.aura) { strokeRing(c.x, c.y, selKind.range, '#fee761', false); }

    // Hover-preview: when the player hovers an upgrade button, show the upgrade
    // target's range as a dashed pink circle. Honest preview before spending gold.
    if (ui.hoveredUpgrade) {
      var udef = CANNON_KINDS[ui.hoveredUpgrade];
      if (udef) { strokeRing(c.x, c.y, udef.range, '#ff77a8', true); }
    }
  }

  // Layer 5c: persistent cannon auras (Blizzard, Plan 4 — MP-N). An icy ring
  // plus a very faint warm wash marks the aura's reach without painting half
  // the blanket blue. Drawn under bases/turrets/ants.
  function drawCannonAuras() {
    for (var i = 0; i < game.cannons.length; i++) {
      var c = game.cannons[i];
      var def = CANNON_KINDS[c.kind];
      if (!def.aura) { continue; }
      ctx.save();
      ctx.fillStyle = 'rgba(255, 248, 232, 0.06)';
      ctx.beginPath();
      ctx.arc(c.x, c.y, def.aura.radius, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
      strokeRing(c.x, c.y, def.aura.radius, 'rgba(200, 232, 255, 0.85)', true);
    }
  }

  // Layer 6: static cannon bases. Split from the turret pass so the round bases
  // sit under ants while the turrets read on top. The base carries the tier rim
  // and family ring (see makeCannonBase).
  function drawCannonBases() {
    for (var ci = 0; ci < game.cannons.length; ci++) {
      var c = game.cannons[ci];
      ctx.drawImage(cannonBaseFor(c.kind), Math.round(c.x - 16), Math.round(c.y - 16));
    }
  }

  // Layer 7: ants, sorted top-down by y so a lower ant draws over a higher one.
  // The slice() copy keeps game.ants stable for indices/refs used elsewhere.
  // Which status tints an ant when it has several: the one that matters most
  // to the player first (a frozen ant is a shatter target, a stunned one is
  // stuck), then the damage-over-time ones, then slow.
  var STATUS_TINT_ORDER = ['freeze', 'stun', 'burn', 'poison', 'slow'];

  function drawAnts() {
    var sorted = game.ants.slice().sort(function(a, b) { return a.y - b.y; });
    var calm = prefersReducedMotion();
    for (var i = 0; i < sorted.length; i++) {
      var a = sorted[i];
      var k = ANT_KINDS[a.kind];
      var sprite;
      var left = Math.round(a.x - k.spriteW / 2);
      var top = Math.round(a.y - k.spriteH / 2);
      if (a.dead) {
        // Delivered ants vanish (the slice-lost ring at the anthill marks
        // them). Killed ants splat: it lands 30 percent big, settles, and
        // fades over its last 0.2 s. Nested ifs avoid the AND operator.
        if (a.deliveredAt !== 0) { continue; }
        if (a.kind === 'royal_guard') {
          sprite = game.sprites.royalGuardSplat;
        } else {
          sprite = game.sprites.splat;
        }
        var age = game.fxTime - a.deathFx;
        var s = 1;
        if (age < 0.07) { s = 1.3; }
        var sw = sprite.width * s;
        var sh = sprite.height * s;
        ctx.save();
        if (age > 0.3) { ctx.globalAlpha = Math.max(0, 1 - (age - 0.3) / 0.2); }
        ctx.drawImage(sprite, Math.round(a.x - sw / 2), Math.round(a.y - sh / 2), sw, sh);
        ctx.restore();
        continue;
      }
      var tint = null;
      for (var ti = 0; ti < STATUS_TINT_ORDER.length; ti++) {
        if (a.effects[STATUS_TINT_ORDER[ti]]) { tint = STATUS_TINT_ORDER[ti]; break; }
      }
      if (tint) {
        sprite = tintedSprite(k.spriteKey, tint);
      } else {
        sprite = game.sprites[k.spriteKey];
      }
      ctx.save();
      if (a.intangible) { ctx.globalAlpha = 0.3; }
      ctx.drawImage(sprite, left, top);
      // Hit flash: a white silhouette for a few frames after a direct hit.
      if (game.fxTime - a.hitFx < 0.05) {
        ctx.globalAlpha = ctx.globalAlpha * 0.85;
        ctx.drawImage(tintedSprite(k.spriteKey, 'white'), left, top);
      }
      ctx.restore();
      // Small status marks on top of the tint: an ember over a burning ant,
      // a drip under a poisoned one, two stars over a stunned one.
      var cx = Math.round(a.x);
      if (a.effects.burn) {
        var emberX = cx;
        if (!calm) {
          if (Math.floor(game.fxTime * 8) % 2 === 0) { emberX = cx - 1; }
        }
        ctx.fillStyle = '#ff9c44';
        ctx.fillRect(emberX, top - 2, 2, 2);
        ctx.fillStyle = '#fee761';
        ctx.fillRect(emberX, top - 2, 1, 1);
      }
      if (a.effects.poison) {
        ctx.fillStyle = '#7acc20';
        ctx.fillRect(cx + 2, top + k.spriteH, 1, 2);
      }
      if (a.effects.stun) {
        ctx.fillStyle = '#fee761';
        ctx.fillRect(cx - 4, top - 2, 2, 2);
        ctx.fillRect(cx + 2, top - 3, 2, 2);
      }
      // Plan 4 — Biohazard infected ring around any ant whose poison
      // carries damageAmp >= 1.25: the +25 percent priority target. Bile
      // green, and it only pulses when motion is welcome.
      if (a.effects.poison) {
        if (a.effects.poison.damageAmp) {
          if (a.effects.poison.damageAmp >= 1.25) {
            var biAlpha = 0.8;
            if (!calm) { biAlpha = 0.65 + 0.25 * Math.sin(game.fxTime * 10); }
            ctx.save();
            ctx.globalAlpha = biAlpha;
            ctx.strokeStyle = '#9be03a';
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.arc(a.x, a.y, k.spriteW / 2 + 2, 0, Math.PI * 2);
            ctx.stroke();
            ctx.restore();
          }
        }
      }
      if (a.carrying) {
        // The stolen slice rides on the ant's back.
        ctx.fillStyle = '#2a1810';
        ctx.fillRect(cx - 4, Math.round(a.y) - 2, 8, 6);
        ctx.fillStyle = '#feae34';
        ctx.fillRect(cx - 3, Math.round(a.y) - 1, 6, 4);
        ctx.fillStyle = '#fee761';
        ctx.fillRect(cx - 2, Math.round(a.y) - 1, 4, 1);
      }
      drawAntHpBar(a);
    }
  }

  // HP bar dispatcher. Default: damage-triggered 16x2 red bar above the ant.
  // Per-kind override via ANT_KINDS[kind].barStyle = { width, height, color,
  // borderColor, persistent } — used by King (Task 29) and reserved for future
  // bosses so their bar reads as a different threat tier from spawn. Nested
  // ifs only — inline scripts in this theme HTML-encode the AND operator.
  function drawAntHpBar(ant) {
    var k = ANT_KINDS[ant.kind];
    var style = k.barStyle;
    var persistent = false;
    if (style) {
      if (style.persistent) { persistent = true; }
    }
    var damaged = ant.hp < ant.maxHp;
    if (!persistent) {
      if (!damaged) { return; }
    }

    var bw = 16;
    var bh = 2;
    var color = '#e43b44';
    var border = null;
    if (style) {
      if (style.width) { bw = style.width; }
      if (style.height) { bh = style.height; }
      if (style.color) { color = style.color; }
      if (style.borderColor) { border = style.borderColor; }
    }

    // Sits 4 px over the sprite's top edge (it used to float 14 px over the
    // center, which drifted off small ants in a crowd), with a dark border so
    // it stays tied to its ant.
    var bx = Math.round(ant.x - bw / 2);
    var by = Math.round(ant.y - k.spriteH / 2) - bh - 3;
    var pct = ant.hp / ant.maxHp;
    if (pct < 0) { pct = 0; }

    if (!border) { border = '#2a1810'; }
    ctx.fillStyle = border;
    ctx.fillRect(bx - 1, by - 1, bw + 2, bh + 2);
    ctx.fillStyle = '#4a3220';
    ctx.fillRect(bx, by, bw, bh);
    ctx.fillStyle = color;
    ctx.fillRect(bx, by, bw * pct, bh);
  }

  // Layer 8: cannon turrets + disabled-spark indicators (the muzzle-flash slot
  // for future tasks). Bases are drawn earlier; this pass covers the rotating
  // turret sprite.
  function drawCannonTurrets() {
    for (var ci = 0; ci < game.cannons.length; ci++) {
      var c = game.cannons[ci];
      var def = CANNON_KINDS[c.kind];
      ctx.save();
      ctx.translate(Math.round(c.x), Math.round(c.y));
      ctx.rotate(c.heading + Math.PI / 2);
      // Try the PNG sprite first; fall back to procedural if missing, broken,
      // or still loading. Nested ifs (no logical AND chains) per the CLAUDE.md
      // note about WordPress HTML-encoding ampersands inside inline scripts.
      var pngSprite = game.sprites.cannonTurretPNG[c.kind];
      var pngReady = false;
      if (pngSprite) {
        if (pngSprite.complete) {
          if (pngSprite.naturalWidth) { pngReady = true; }
        }
      }
      var turretSprite, drawX, drawY;
      if (pngReady) {
        turretSprite = pngSprite;
        drawX = -pngSprite.naturalWidth / 2;
        drawY = -pngSprite.naturalHeight / 2;
      } else {
        turretSprite = game.sprites.turrets[c.kind];
        if (!turretSprite) { turretSprite = game.sprites.turrets.howitzer; }
        // 20x20 turrets pivot on their breech; the larger ones are centered.
        if (turretSprite.width === 20) {
          drawX = -10; drawY = -16;
        } else {
          drawX = -turretSprite.width / 2; drawY = -turretSprite.height / 2;
        }
      }
      ctx.drawImage(turretSprite, drawX, drawY);
      ctx.restore();
      // Plan 4 — muzzle flash. Renders for FLASH_DUR seconds after the cannon
      // fires; alpha fades linearly. flashScale on def scales the flash
      // radius for god-tier rows (default 1.0, god-tier 1.7). Timed on fxTime
      // so it still shows at 3x.
      if (c.flashFx) {
        var FLASH_DUR = 0.08;
        var dtSinceFire = game.fxTime - c.flashFx;
        if (dtSinceFire >= 0) {
          if (dtSinceFire < FLASH_DUR) {
            var fAlpha = 1 - dtSinceFire / FLASH_DUR;
            var fScale = def.flashScale ? def.flashScale : 1.0;
            if (prefersReducedMotion()) { fScale = 1.0; }
            var fRadius = 6 * fScale;
            // Barrel tip is ~16px in front of cannon center along heading.
            var tipDist = 14;
            var fx = c.x + Math.cos(c.heading) * tipDist;
            var fy = c.y + Math.sin(c.heading) * tipDist;
            ctx.save();
            ctx.globalAlpha = fAlpha;
            var grad = ctx.createRadialGradient(fx, fy, 0, fx, fy, fRadius);
            grad.addColorStop(0, '#ffffff');
            grad.addColorStop(0.4, '#ffd75e');
            grad.addColorStop(1, 'rgba(255, 160, 30, 0)');
            ctx.fillStyle = grad;
            ctx.beginPath();
            ctx.arc(fx, fy, fRadius, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
          }
        }
      }
      if (c.disabled) {
        // Steady (no 3 Hz blink) when the visitor prefers reduced motion.
        var sparkOn = Math.floor(game.fxTime * 6) % 2 === 0;
        if (prefersReducedMotion()) { sparkOn = true; }
        if (sparkOn) {
          ctx.fillStyle = '#ff5040';
          ctx.fillRect(c.x - 1, c.y - 16, 2, 2);
          ctx.fillRect(c.x + 3, c.y - 14, 2, 2);
        }
      }
    }
  }

  // Layer 9a: projectiles. Arcing projectiles (Mortar etc.) get a cosmetic
  // y-offset so the shell visibly rises and falls between barrel and impact.
  // Collision resolves on linear (p.x, p.y) — arcOffset is render-only.
  function drawProjectiles() {
    for (var pi = 0; pi < game.projectiles.length; pi++) {
      var p = game.projectiles[pi];
      // Plan 4 — barrelStagger. Dormant shells sit invisible at spawn until
      // their activation time. Skips render entirely; the visual cost is one
      // ctx.drawImage avoided.
      if (p.dormantUntil) {
        if (game.time < p.dormantUntil) { continue; }
      }
      var arcOffset = 0;
      if (p.arcing) {
        var t;
        if (p.arcDuration > 0) { t = (game.time - p.arcStartedAt) / p.arcDuration; } else { t = 1; }
        if (t > 1) { t = 1; }
        if (t < 0) { t = 0; }
        arcOffset = -Math.sin(t * Math.PI) * p.arcHeight;
      }
      // Plan 4 — visualScale scales the 4×4 sprite around the projectile's
      // center (offset = -size/2). Pink Pea 1.0, Bouncy Bubble 1.5,
      // Wrecking Ball 2.5. Default 1.0 keeps existing cannons unchanged.
      var pScale = p.visualScale ? p.visualScale : 1.0;
      var pSize = 4 * pScale;
      var pOff = pSize / 2;
      ctx.drawImage(shotSpriteFor(p), Math.round(p.x - pOff), Math.round(p.y - pOff + arcOffset), pSize, pSize);
    }
  }

  // Which 4x4 shot sprite a projectile uses: rubber peas are pink, a shot
  // that carries a status wears its status color, quick shots are brass
  // tracers and everything else is an iron ball.
  function shotSpriteFor(p) {
    var shots = game.sprites.shots;
    if (!p.sourceCannon) { return shots.heavy; }
    var def = CANNON_KINDS[p.sourceCannon.kind];
    if (def.family === 'rubber') { return shots.rubber; }
    var st = p.statusOnHit;
    if (st) {
      if (Array.isArray(st)) { st = st[st.length - 1]; }
      if (shots[st.kind]) { return shots[st.kind]; }
    }
    if (def.family === 'quick') { return shots.quick; }
    return shots.heavy;
  }

  // Layer 9b: beam lines (Glacier). Thin icy line from a beam cannon to its
  // current live target. Only drawn while the target is alive.
  // Cone overlay for cone-DoT cannons (Plan 3 — Plasma Torch). Drawn before
  // the bases/turrets/ants so the orange wedge sits under sprites, not over
  // them. firingCone is set in updateCannons whenever the cannon has a target.
  function drawConeDots() {
    for (var ci = 0; ci < game.cannons.length; ci++) {
      var cc = game.cannons[ci];
      var cdef = CANNON_KINDS[cc.kind];
      if (cdef.coneDot) {
        if (cc.firingCone) {
          var halfA = (cdef.coneDot.angleDeg * Math.PI / 180) / 2;
          ctx.fillStyle = 'rgba(255, 120, 30, 0.18)';
          ctx.beginPath();
          ctx.moveTo(cc.x, cc.y);
          ctx.arc(cc.x, cc.y, cdef.coneDot.range, cc.heading - halfA, cc.heading + halfA);
          ctx.closePath();
          ctx.fill();
        }
      }
    }
  }

  function drawBeams() {
    for (var bi = 0; bi < game.cannons.length; bi++) {
      var bc = game.cannons[bi];
      var bdef = CANNON_KINDS[bc.kind];
      if (bdef.beam) {
        if (bc.beamTarget) {
          if (!bc.beamTarget.dead) {
            var bcolor = bdef.beam.color;
            if (!bcolor) { bcolor = '#a4d4ff'; }
            var blw = bdef.beam.lineWidth;
            if (!blw) { blw = 2; }
            ctx.strokeStyle = bcolor;
            ctx.lineWidth = blw;
            ctx.beginPath();
            ctx.moveTo(bc.x, bc.y);
            ctx.lineTo(bc.beamTarget.x, bc.beamTarget.y);
            ctx.stroke();
          }
        }
      }
    }
  }

  // Layer 9c: Tesla arc-jump polyline. Drawn over ants so the chain lines
  // remain visible across hit targets. Each bolt fades from full to 0 alpha
  // over its 0.15s lifetime; small per-segment jitter sells the lightning feel.
  function drawLightningBolts() {
    for (var bi = 0; bi < game.lightningBolts.length; bi++) {
      var b = game.lightningBolts[bi];
      var lifeRemaining = b.expiresAt - game.fxTime;
      var alpha = lifeRemaining / 0.15;
      if (alpha < 0) { alpha = 0; }
      if (alpha > 1) { alpha = 1; }
      ctx.strokeStyle = 'rgba(255, 240, 176, ' + alpha + ')';
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.moveTo(b.points[0].x, b.points[0].y);
      // The jitter is the lightning feel; it holds still for reduced motion.
      var jit = 6;
      if (prefersReducedMotion()) { jit = 0; }
      for (var pi = 1; pi < b.points.length; pi++) {
        var jx = b.points[pi].x + (Math.random() * jit - jit / 2);
        var jy = b.points[pi].y + (Math.random() * jit - jit / 2);
        ctx.lineTo(jx, jy);
      }
      ctx.stroke();
      ctx.lineWidth = 1;
    }
  }

  // Layer 10: transient effects — AoE rings today, hit sparks/splat fades/
  // phase shimmer/status indicators slot in here as later tasks add them.
  // Each entry advances its own `t` in updateEffects; render uses p = t/life
  // as an animation parameter (0 to 1) for ring expansion + fade.
  function drawEffects() {
    for (var ei = 0; ei < game.effects.length; ei++) {
      var ef = game.effects[ei];
      if (ef.kind === 'bomber_aoe') {
        var p = ef.t / ef.life;
        ctx.strokeStyle = 'rgba(255,80,40,' + (1 - p) + ')';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(ef.x, ef.y, 60 * p, 0, Math.PI * 2);
        ctx.stroke();
      }
      // Plan 4 — splitFlash (Cluster Strike split point). Filled circle that
      // shrinks from maxRadius * 0.8 to 0 with alpha fading. ~0.1s lifetime.
      if (ef.kind === 'splitFlash') {
        var sp = ef.t / ef.life;
        var sAlpha = 1 - sp;
        var sRad = ef.maxRadius * (1 - sp) * 0.8;
        ctx.save();
        ctx.globalAlpha = sAlpha;
        ctx.fillStyle = ef.color;
        ctx.beginPath();
        ctx.arc(ef.x, ef.y, sRad, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
      }
      // Plan 4 — spreadRing (Phoenix burn-spread). Stroked circle that grows
      // from 0 to maxRadius with alpha fading. ~0.2s lifetime, orange.
      if (ef.kind === 'spreadRing') {
        var rp = ef.t / ef.life;
        var rAlpha = 1 - rp;
        var rRad = ef.maxRadius * rp;
        ctx.save();
        ctx.globalAlpha = rAlpha;
        ctx.strokeStyle = ef.color;
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(ef.x, ef.y, rRad, 0, Math.PI * 2);
        ctx.stroke();
        ctx.restore();
      }
      // Plan 4 — knockbackRing (Tempest splash impact). Stroked circle that
      // grows from 0 to maxRadius with alpha fading. ~0.15s lifetime, white.
      if (ef.kind === 'knockbackRing') {
        var kp = ef.t / ef.life;
        var kAlpha = 1 - kp;
        var kRad = ef.maxRadius * kp;
        ctx.save();
        ctx.globalAlpha = kAlpha;
        ctx.strokeStyle = ef.color;
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(ef.x, ef.y, kRad, 0, Math.PI * 2);
        ctx.stroke();
        ctx.restore();
      }
      // Touch Input Task 7: invalid placement flash. Brief red pulse at
      // the rejected tap location. Stays Armed; this is the visible
      // "I missed" signal.
      if (ef.kind === 'invalidPlacement') {
        var ip = ef.t / ef.life;
        ctx.save();
        ctx.globalAlpha = 1 - ip;
        ctx.strokeStyle = '#e43b44';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(ef.x, ef.y, 8 + ip * 12, 0, Math.PI * 2);
        ctx.stroke();
        // Small "X" through the center for unambiguous rejection.
        ctx.beginPath();
        ctx.moveTo(ef.x - 5, ef.y - 5);
        ctx.lineTo(ef.x + 5, ef.y + 5);
        ctx.moveTo(ef.x + 5, ef.y - 5);
        ctx.lineTo(ef.x - 5, ef.y + 5);
        ctx.stroke();
        ctx.restore();
      }
      // A red ring that grows from a point: a slice grabbed off the cake
      // (small) or carried into the anthill (big). ef.r is the final radius.
      if (ef.kind === 'redRing') {
        var gp = ef.t / ef.life;
        ctx.save();
        ctx.globalAlpha = 1 - gp;
        ctx.strokeStyle = '#e43b44';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(ef.x, ef.y, 6 + gp * ef.r, 0, Math.PI * 2);
        ctx.stroke();
        ctx.restore();
      }
    }
    drawPopups();
  }

  // Popups rise 10 px over 0.8 s and fade in their last 0.3 s. With reduced
  // motion they stay put and just fade. Expired ones are dropped here.
  function drawPopups() {
    var LIFE = 0.8;
    var kept = [];
    var rise = !prefersReducedMotion();
    for (var i = 0; i < game.popups.length; i++) {
      var pp = game.popups[i];
      var age = game.fxTime - pp.born;
      if (age >= LIFE) { continue; }
      kept.push(pp);
      var py = pp.y;
      if (rise) { py = py - (age / LIFE) * 10; }
      ctx.save();
      if (age > LIFE - 0.3) { ctx.globalAlpha = (LIFE - age) / 0.3; }
      drawTextWithShadow(pp.text, Math.round(pp.x), Math.round(py), pp.color, 'bold 9px monospace', 'center');
      ctx.restore();
    }
    game.popups = kept;
  }

  // Visual coherence pass — shared wooden chrome primitives.
  // drawWoodenPlaque: filled wood gradient with edge highlights and a deep border.
  // Used as the HUD strip background and as the body of larger signs.
  function drawWoodenPlaque(x, y, w, h) {
    var grd = ctx.createLinearGradient(x, y, x, y + h);
    grd.addColorStop(0, '#6a4a2e');
    grd.addColorStop(1, '#4a3220');
    ctx.fillStyle = grd;
    ctx.fillRect(x, y, w, h);
    // Top edge highlight (wood-edge)
    ctx.fillStyle = '#5a3a20';
    ctx.fillRect(x, y, w, 1);
    // Bottom edge highlight (wood-light)
    ctx.fillStyle = '#8a6a4e';
    ctx.fillRect(x, y + h - 2, w, 2);
    // Bottom border (wood-deep)
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(x, y + h - 1, w, 1);
  }

  // drawWoodenTag: a small recessed wooden box for HUD readouts and stat cells.
  // Body is a darker wood gradient than the plaque so tags sit IN the plaque.
  function drawWoodenTag(x, y, w, h) {
    var grd = ctx.createLinearGradient(x, y, x, y + h);
    grd.addColorStop(0, '#5a3a20');
    grd.addColorStop(1, '#3a2210');
    ctx.fillStyle = grd;
    ctx.fillRect(x, y, w, h);
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(x, y, w, 1);                    // top dark inset
    ctx.fillRect(x, y, 1, h);
    ctx.fillRect(x + w - 1, y, 1, h);
    ctx.fillRect(x, y + h - 1, w, 1);
    ctx.fillStyle = '#8a6a4e';
    ctx.fillRect(x + 1, y + h - 2, w - 2, 1);    // light bottom inset
  }

  // drawWoodenSign: full wooden sign with two metal nails at the top corners
  // and a bottom drop shadow. Used by all four full-screen overlays plus the
  // wave intro banner (at smaller scale).
  function drawWoodenSign(x, y, w, h) {
    // Drop shadow first so it sits behind the body
    ctx.fillStyle = 'rgba(0, 0, 0, 0.4)';
    ctx.fillRect(x + 2, y + h, w, 4);
    // Body (lighter than tags so the sign reads as foreground)
    var grd = ctx.createLinearGradient(x, y, x, y + h);
    grd.addColorStop(0, '#6a4a2e');
    grd.addColorStop(1, '#4a3220');
    ctx.fillStyle = grd;
    ctx.fillRect(x, y, w, h);
    // 3 px deep border
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(x, y, w, 3);
    ctx.fillRect(x, y, 3, h);
    ctx.fillRect(x + w - 3, y, 3, h);
    ctx.fillRect(x, y + h - 3, w, 3);
    // Inset edge highlights
    ctx.fillStyle = '#5a3a20';
    ctx.fillRect(x + 3, y + 3, w - 6, 1);
    ctx.fillStyle = '#8a6a4e';
    ctx.fillRect(x + 3, y + h - 4, w - 6, 1);
    // Two metal nails at top corners
    ctx.fillStyle = '#444';
    ctx.fillRect(x + 6, y + 5, 4, 4);
    ctx.fillRect(x + w - 10, y + 5, 4, 4);
    ctx.fillStyle = '#888';
    ctx.fillRect(x + 7, y + 6, 2, 2);
    ctx.fillRect(x + w - 9, y + 6, 2, 2);
  }

  // drawWoodenButton: a chunky button with a top light inset and a bottom
  // shadow inset. Three variants:
  //   'green'  PLAY / Send Next / affordable upgrade row
  //   'red'    Sell / Restart Run
  //   'wood'   Targeting buttons and back link (neutral)
  function drawWoodenButton(x, y, w, h, variant) {
    var topColor; var bottomColor; var borderColor; var lightColor; var shadowColor;
    if (variant === 'red') {
      topColor = '#a04040';
      bottomColor = '#6a2020';
      borderColor = '#2a0a0a';
      lightColor = '#c06060';
      shadowColor = '#6a2020';
    } else if (variant === 'wood') {
      topColor = '#5a3a20';
      bottomColor = '#3a2210';
      borderColor = '#2a1810';
      lightColor = '#8a6a4e';
      shadowColor = '#3a2210';
    } else {
      // green default
      topColor = '#9ac870';
      bottomColor = '#6a9840';
      borderColor = '#2a1810';
      lightColor = '#b8e890';
      shadowColor = '#88b860';
    }
    var grd = ctx.createLinearGradient(x, y, x, y + h);
    grd.addColorStop(0, topColor);
    grd.addColorStop(1, bottomColor);
    ctx.fillStyle = grd;
    ctx.fillRect(x, y, w, h);
    // 2 px border
    ctx.fillStyle = borderColor;
    ctx.fillRect(x, y, w, 2);
    ctx.fillRect(x, y, 2, h);
    ctx.fillRect(x + w - 2, y, 2, h);
    ctx.fillRect(x, y + h - 2, w, 2);
    // Top light inset
    ctx.fillStyle = lightColor;
    ctx.fillRect(x + 2, y + 2, w - 4, 2);
    // Bottom shadow inset
    ctx.fillStyle = shadowColor;
    ctx.fillRect(x + 2, y + h - 4, w - 4, 2);
  }

  // drawTextWithShadow: drops a 1-px wood-deep shadow behind the text so
  // numbers and labels stand off the wood. Used by every readout, label,
  // and title in the visual pass.
  function drawTextWithShadow(text, x, y, color, font, align) {
    ctx.font = font;
    if (align) { ctx.textAlign = align; } else { ctx.textAlign = 'start'; }
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#2a1810';
    ctx.fillText(text, x + 1, y + 1);
    ctx.fillStyle = color;
    ctx.fillText(text, x, y);
    ctx.textAlign = 'start';
    ctx.textBaseline = 'alphabetic';
  }

  // Layer 11: HUD strip across the top of the playfield. Gold, cake, lost,
  // wave index, prep timer, send-next button, pause + speed widgets.
  function drawHud() {
    if (isPortrait()) {
      drawWoodenPlaque(0, 0, WIDTH, HUD_H);
    } else {
      drawWoodenPlaque(0, 0, PLAYFIELD_W, HUD_H);
    }

    // Right-edge anchor for the pause / speed / Send-Next widgets. In
    // landscape the HUD plaque only spans 0..PLAYFIELD_W (the shop sits to
    // the right at PLAYFIELD_W..WIDTH), so the widgets must anchor to
    // PLAYFIELD_W. In portrait the plaque spans the full canvas, so they
    // anchor to WIDTH. Both reduce to "the right edge of the HUD plaque".
    var hudRightEdge;
    if (isPortrait()) { hudRightEdge = WIDTH; } else { hudRightEdge = PLAYFIELD_W; }

    // Four readout tags, left-aligned.
    // Tag layout: 22 px tall, 1 px from top of HUD, content vertically centered.
    var tagY = 1;
    var tagH = HUD_H - 2;
    var tagX = 4;
    // Phones show the canvas at about 0.7 CSS px per game px, so the small
    // caps labels go up to 10 px there (7 CSS px) instead of 8.
    var labelFont = 'bold 8px monospace';
    if (isPortrait()) { labelFont = 'bold 10px monospace'; }
    ctx.font = labelFont;
    var labelGap = ctx.measureText('WAVE').width + 8;

    // 1. Coin tag — 12x12 sprite + gold value. Set font BEFORE measuring so
    // the tag width tracks the actual rendered glyphs (otherwise we'd be
    // measuring with whatever font the previous draw left on ctx).
    ctx.font = 'bold 11px monospace';
    var goldStr = '' + game.gold;
    var coinTagW = 14 + ctx.measureText(goldStr).width + 14;
    drawWoodenTag(tagX, tagY, coinTagW, tagH);
    ctx.drawImage(game.sprites.coin, tagX + 4, tagY + 5);
    drawTextWithShadow(goldStr, tagX + 20, tagY + tagH / 2, '#fee761', 'bold 11px monospace', 'start');
    tagX = tagX + coinTagW + 4;

    // 2. Cake tag — slices still yours (8 minus the ones carried home), so it
    // only drops when a slice is really gone; one an ant is still carrying,
    // or one lying on the blanket, can be won back. Flashes red when one
    // reaches the anthill.
    ctx.font = 'bold 11px monospace';
    var cakeStr = (8 - game.cake.lost) + '/8';
    var cakeTagW = 14 + ctx.measureText(cakeStr).width + 14;
    drawWoodenTag(tagX, tagY, cakeTagW, tagH);
    var lostAge = game.fxTime - game.sliceLostFx;
    if (lostAge < 0.8) {
      ctx.save();
      ctx.globalAlpha = 0.6 * (1 - lostAge / 0.8);
      ctx.fillStyle = '#e43b44';
      ctx.fillRect(tagX + 1, tagY + 1, cakeTagW - 2, tagH - 2);
      ctx.restore();
    }
    ctx.drawImage(game.sprites.cakeSlice, tagX + 4, tagY + 5);
    drawTextWithShadow(cakeStr, tagX + 20, tagY + tagH / 2, '#ffd86b', 'bold 11px monospace', 'start');
    tagX = tagX + cakeTagW + 4;

    // 3. Wave tag — small caps "WAVE" + white value.
    ctx.font = 'bold 11px monospace';
    var waveStr = (game.waveIndex + 1) + '/' + WAVES.length;
    var waveTagW = 5 + labelGap + ctx.measureText(waveStr).width + 8;
    drawWoodenTag(tagX, tagY, waveTagW, tagH);
    drawTextWithShadow('WAVE', tagX + 5, tagY + tagH / 2, '#f5e6c8', labelFont, 'start');
    drawTextWithShadow(waveStr, tagX + 5 + labelGap, tagY + tagH / 2, '#ffffff', 'bold 11px monospace', 'start');
    tagX = tagX + waveTagW + 4;

    // Send Next is a fixed 90 px so the widgets beside it don't shift as the
    // bonus counts down.
    var btnW = 90;

    // 4. Prep tag — small caps "PREP" + light-green value, only during prep.
    if (game.waveState === 'prep') {
      ctx.font = 'bold 11px monospace';
      var prepStr = Math.ceil(game.prepRemaining) + 's';
      var prepTagW = 5 + labelGap + ctx.measureText(prepStr).width + 8;
      drawWoodenTag(tagX, tagY, prepTagW, tagH);
      drawTextWithShadow('PREP', tagX + 5, tagY + tagH / 2, '#f5e6c8', labelFont, 'start');
      drawTextWithShadow(prepStr, tagX + 5 + labelGap, tagY + tagH / 2, '#c8f5a8', 'bold 11px monospace', 'start');

      // Send Next button — green wooden, right-edge. The label shows the
      // gold you get for skipping the rest of the wait.
      var btnH = HUD_H - 4;
      var btnX = hudRightEdge - btnW - 4;
      var btnY = 2;
      ui.sendNextRect = { x: btnX, y: btnY, w: btnW, h: btnH };
      drawWoodenButton(btnX, btnY, btnW, btnH, 'green');
      var sendLabel = 'Send Next';
      var sendBonus = sendNextBonus();
      if (sendBonus > 0) { sendLabel = 'Send Next +' + sendBonus; }
      ctx.font = 'bold 10px monospace';
      ctx.fillStyle = '#1a1a0a';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(sendLabel, btnX + btnW / 2, btnY + btnH / 2);
      ctx.textAlign = 'start';
      ctx.textBaseline = 'alphabetic';
    } else {
      ui.sendNextRect = null;
    }

    // Pause and speed widgets — repositioned inline with the Send Next button.
    // Wooden tag underneath each so they read as part of the HUD chrome on
    // the right edge. Pause and speed sit at fixed positions; Send Next
    // appears to their right during prep.
    ui.pauseRect.x = hudRightEdge - btnW - 58;
    ui.pauseRect.y = 2;
    ui.pauseRect.w = 22;
    ui.pauseRect.h = HUD_H - 4;
    ui.speedRect.x = hudRightEdge - btnW - 32;
    ui.speedRect.y = 2;
    ui.speedRect.w = 22;
    ui.speedRect.h = HUD_H - 4;

    drawWoodenTag(ui.pauseRect.x, ui.pauseRect.y, ui.pauseRect.w, ui.pauseRect.h);
    // Pause icon centered in the 22x20 tag (icon is 7x10).
    var pIconX = ui.pauseRect.x + (ui.pauseRect.w - 7) / 2;
    var pIconY = ui.pauseRect.y + (ui.pauseRect.h - 10) / 2;
    ctx.fillStyle = '#2a1810';
    if (game.paused) {
      ctx.beginPath();
      ctx.moveTo(pIconX + 1, pIconY + 1);
      ctx.lineTo(pIconX + 8, pIconY + 6);
      ctx.lineTo(pIconX + 1, pIconY + 11);
      ctx.closePath();
      ctx.fill();
    } else {
      ctx.fillRect(pIconX + 1, pIconY + 1, 2, 10);
      ctx.fillRect(pIconX + 5, pIconY + 1, 2, 10);
    }
    ctx.fillStyle = '#fee761';
    if (game.paused) {
      ctx.beginPath();
      ctx.moveTo(pIconX, pIconY);
      ctx.lineTo(pIconX + 7, pIconY + 5);
      ctx.lineTo(pIconX, pIconY + 10);
      ctx.closePath();
      ctx.fill();
    } else {
      ctx.fillRect(pIconX, pIconY, 2, 10);
      ctx.fillRect(pIconX + 4, pIconY, 2, 10);
    }

    drawWoodenTag(ui.speedRect.x, ui.speedRect.y, ui.speedRect.w, ui.speedRect.h);
    var speedLabel = game.speed + 'x';
    drawTextWithShadow(speedLabel, ui.speedRect.x + ui.speedRect.w / 2, ui.speedRect.y + ui.speedRect.h / 2, '#fee761', 'bold 11px monospace', 'center');
  }

  // Layer 13a: drag preview (ghost cannon under the cursor while dragging from
  // the shop). Sits inside the overlays bucket because it is UI feedback drawn
  // above gameplay, but below pause/win/lose modals.
  function drawDragPreview() {
    if (ui.armedKind === null) { return; }
    if (ui.activePointerId === null) { return; }   // hide ghost when no pointer is down (Task 3 keeps this)
    var ok = canPlaceCannon(ui.mouseX, ui.mouseY);
    var dragDef = CANNON_KINDS[ui.armedKind];
    var dragTurret = game.sprites.turrets[ui.armedKind];
    if (!dragTurret) { dragTurret = game.sprites.turrets.howitzer; }
    // Ghost sprite Y offset: lift 30 game-px on touch/pen so the player can
    // see the placement tile under their finger. Range ring stays centered
    // on the real (ui.mouseX, ui.mouseY) so validity readout is accurate.
    var ghostDy = 0;
    if (ui.pointerType === 'touch') { ghostDy = -30; }
    if (ui.pointerType === 'pen') { ghostDy = -30; }
    ctx.globalAlpha = 0.6;
    ctx.drawImage(cannonBaseFor(ui.armedKind), Math.round(ui.mouseX - 16), Math.round(ui.mouseY - 16 + ghostDy));
    ctx.drawImage(dragTurret, Math.round(ui.mouseX - 10), Math.round(ui.mouseY - 16 + ghostDy));
    // Invalid-spot red tint over the ghost (Task 7). Drawn on top with a
    // multiply-ish blend by stacking a translucent red rect; cheap and
    // visible on small screens. Only renders when canPlaceCannon = false.
    if (!ok) {
      ctx.fillStyle = 'rgba(228, 59, 68, 0.45)';
      ctx.fillRect(Math.round(ui.mouseX - 16), Math.round(ui.mouseY - 16 + ghostDy), 32, 24);
    }
    ctx.globalAlpha = 1;
    ctx.strokeStyle = ok ? 'rgba(255, 255, 255, 0.5)' : 'rgba(228, 59, 68, 0.7)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.arc(ui.mouseX, ui.mouseY, dragDef.range, 0, Math.PI * 2);
    ctx.stroke();
  }

  // Banner — fades in, dwells, fades out over the last 0.5s, then the next
  // queued one takes its place. Lives in the overlay layer so it can ride
  // above gameplay. Sized to its text; a sub line adds a smaller second row.
  function drawBanner() {
    var b = game.banner;
    if (b.active) {
      if (game.fxTime - b.startedAt > b.duration) { b.active = false; }
    }
    if (!b.active) {
      if (game.bannerQueue.length === 0) { return; }
      var next = game.bannerQueue.shift();
      b.active = true;
      b.text = next.text;
      b.sub = next.sub;
      b.style = next.style;
      b.duration = next.duration;
      b.startedAt = game.fxTime;
    }
    var bt = game.fxTime - b.startedAt;
    var alpha = 1;
    if (bt < 0.4) { alpha = bt / 0.4; }
    if (bt > b.duration - 0.5) { alpha = (b.duration - bt) / 0.5; }
    var subFont = 'bold 10px monospace';
    ctx.font = 'bold 12px monospace';
    var w = ctx.measureText(b.text).width + 32;
    if (b.sub) {
      ctx.font = subFont;
      var subW = ctx.measureText(b.sub).width + 32;
      if (subW > w) { w = subW; }
    }
    if (w < 140) { w = 140; }
    var h = 26;
    if (b.sub) { h = 42; }
    var x = Math.round(PLAYFIELD_W / 2 - w / 2);
    var y = HUD_H + 8;
    var color = '#fee761';
    ctx.save();
    ctx.globalAlpha = alpha;
    if (b.style === 'boss') {
      drawWoodenSignWine(x, y, w, h);
      color = '#ffaaaa';
    } else if (b.style === 'final') {
      drawWoodenSignWine(x, y, w, h);
    } else {
      drawWoodenSign(x, y, w, h);
      if (b.style === 'gold') {
        ctx.strokeStyle = '#fee761';
        ctx.lineWidth = 2;
        ctx.strokeRect(x + 4, y + 4, w - 8, h - 8);
      }
    }
    if (b.sub) {
      drawTextWithShadow(b.text, x + w / 2, y + 15, color, 'bold 12px monospace', 'center');
      drawTextWithShadow(b.sub, x + w / 2, y + 29, '#f5e6c8', subFont, 'center');
    } else {
      drawTextWithShadow(b.text, x + w / 2, y + h / 2, color, 'bold 12px monospace', 'center');
    }
    ctx.restore();
  }

  // During prep, the banner slot shows what the next wave brings: one small
  // sprite and a count per ant kind, on a wine sign for boss waves. Drawn
  // only while no banner is up, so it never covers one.
  function drawWavePreview() {
    if (game.state !== 'playing') { return; }
    if (game.waveState !== 'prep') { return; }
    if (game.banner.active) { return; }
    if (game.bannerQueue.length > 0) { return; }
    var wv = WAVES[game.waveIndex];
    var kinds = [];
    var counts = {};
    for (var i = 0; i < wv.spawns.length; i++) {
      var t = wv.spawns[i].type;
      if (!counts[t]) { counts[t] = 0; kinds.push(t); }
      counts[t] = counts[t] + wv.spawns[i].count;
    }
    var labelFont = 'bold 9px monospace';
    if (isPortrait()) { labelFont = 'bold 10px monospace'; }
    ctx.font = labelFont;
    var label = 'NEXT';
    if (wv.isBoss) { label = 'BOSS NEXT'; }
    var labelW = ctx.measureText(label).width;
    ctx.font = 'bold 10px monospace';
    var w = 12 + labelW + 6;
    for (var j = 0; j < kinds.length; j++) {
      w = w + 21 + ctx.measureText('x' + counts[kinds[j]]).width + 8;
    }
    w = w + 4;
    var h = 22;
    var x = Math.round(PLAYFIELD_W / 2 - w / 2);
    var y = HUD_H + 6;
    if (wv.isBoss) { drawWoodenSignWine(x, y, w, h); } else { drawWoodenTag(x, y, w, h); }
    drawTextWithShadow(label, x + 10, y + h / 2, '#c8a868', labelFont, 'start');
    var cx = x + 12 + labelW + 6;
    for (var k = 0; k < kinds.length; k++) {
      var spr = game.sprites[ANT_KINDS[kinds[k]].spriteKey];
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(cx - 1, y + h / 2 - 9, 18, 18);
      ctx.fillStyle = '#e8d4a8';
      ctx.fillRect(cx, y + h / 2 - 8, 16, 16);              // cream tile so dark ants read on the wood
      ctx.drawImage(spr, cx, y + h / 2 - 8, 16, 16);
      drawTextWithShadow('x' + counts[kinds[k]], cx + 21, y + h / 2, '#f5e6c8', 'bold 10px monospace', 'start');
      ctx.font = 'bold 10px monospace';
      cx = cx + 21 + ctx.measureText('x' + counts[kinds[k]]).width + 8;
    }
  }

  // drawSplashAnt: renders the existing worker-ant sprite at one of three
  // scaled sizes with rotation. Used by idle and lose splashes.
  function drawSplashAnt(x, y, size, rot) {
    var s = game.sprites.worker;
    var scale;
    if (size === 0) { scale = 0.5; }
    else if (size === 1) { scale = 0.75; }
    else { scale = 1.0; }
    var w = s.width * scale;
    var h = s.height * scale;
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(rot);
    ctx.drawImage(s, -w / 2, -h / 2, w, h);
    ctx.restore();
  }

  function drawIdleOverlay() {
    var portrait = isPortrait();

    // Dim layer
    ctx.fillStyle = 'rgba(20, 10, 4, 0.55)';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);

    // 15 scattered ants, behind the cake and sign.
    var layoutAnts;
    if (portrait) { layoutAnts = IDLE_ANT_LAYOUT_PORTRAIT; }
    else { layoutAnts = IDLE_ANT_LAYOUT; }
    for (var i = 0; i < layoutAnts.length; i++) {
      var a = layoutAnts[i];
      drawSplashAnt(a.x, a.y, a.size, a.rot);
    }

    // Hero cake -- landscape: 280x210 at y=14, centered. Portrait: smaller
    // 192x144 sit near the top of the playfield (HUD_H + 24) so there's
    // room below for the sign and PLAY button within the 336-tall playfield.
    var cakeW; var cakeH; var cakeX; var cakeY;
    if (portrait) {
      cakeW = 192;
      cakeH = 144;
      cakeX = (WIDTH - cakeW) / 2;
      cakeY = HUD_H + 24;
    } else {
      cakeW = 280;
      cakeH = 210;
      cakeX = (WIDTH - cakeW) / 2;
      cakeY = 14;
    }
    ctx.imageSmoothingEnabled = false;
    ctx.drawImage(game.sprites.titleCake, cakeX, cakeY, cakeW, cakeH);

    // Title sign -- 380x140. Landscape: bottom edge y=HEIGHT-40. Portrait:
    // positioned so it overlaps the cake's lower portion (same visual idea
    // as landscape), with PLAY button sitting just below cake area.
    var signW = 380;
    var signH = 140;
    var signX = (WIDTH - signW) / 2;
    var signY;
    if (portrait) { signY = HUD_H + 144; }
    else { signY = HEIGHT - 40 - signH; }
    drawWoodenSign(signX, signY, signW, signH);

    // Eyebrow
    drawTextWithShadow('DEFEND THE PICNIC', WIDTH / 2, signY + 22, '#c8a868', 'bold 10px monospace', 'center');

    // Title — per-letter rotation for hand-painted feel, no animation.
    var letters = ['A', 'N', 'T', ' ', 'B', 'U', 'S', 'T', 'E', 'R'];
    var rots = [-0.05, 0.035, -0.018, 0, 0.052, -0.035, 0, 0.035, -0.035, -0.018];
    ctx.font = 'bold 32px monospace';
    var totalW = 0;
    for (var l = 0; l < letters.length; l++) {
      totalW = totalW + ctx.measureText(letters[l]).width;
    }
    var lx = WIDTH / 2 - totalW / 2;
    var ly = signY + 70;
    for (var k = 0; k < letters.length; k++) {
      var ch = letters[k];
      var lw = ctx.measureText(ch).width;
      ctx.save();
      ctx.translate(lx + lw / 2, ly);
      ctx.rotate(rots[k]);
      ctx.fillStyle = '#2a1810';
      ctx.fillText(ch, -lw / 2 + 2, 2);
      ctx.fillStyle = '#fee761';
      ctx.fillText(ch, -lw / 2, 0);
      ctx.restore();
      lx = lx + lw;
    }

    // PLAY button — green wooden. With a saved run it becomes NEW GAME and
    // slides left to make room for a Continue button beside it.
    var btnW = 110;
    var btnH = 30;
    var contW = 160;
    var btnGap = 8;
    var btnX = WIDTH / 2 - btnW / 2;
    var btnY = signY + signH - 50;
    var hasSave = !!ui.savedRun;
    if (hasSave) { btnX = WIDTH / 2 - (btnW + btnGap + contW) / 2; }
    drawWoodenButton(btnX, btnY, btnW, btnH, 'green');
    if (hasSave) {
      drawTextWithShadow('NEW GAME', btnX + btnW / 2, btnY + btnH / 2, '#1a1a0a', 'bold 12px monospace', 'center');
    } else {
      drawTextWithShadow('PLAY', btnX + btnW / 2, btnY + btnH / 2, '#1a1a0a', 'bold 16px monospace', 'center');
    }

    // Track the click targets for tap-to-start and tap-to-continue.
    ui.idlePlayRect = { x: btnX, y: btnY, w: btnW, h: btnH };
    if (hasSave) {
      var contX = btnX + btnW + btnGap;
      drawWoodenButton(contX, btnY, contW, btnH, 'green');
      drawTextWithShadow('CONTINUE WAVE ' + (ui.savedRun.waveIndex + 1), contX + contW / 2, btnY + btnH / 2, '#1a1a0a', 'bold 12px monospace', 'center');
      ui.idleContinueRect = { x: contX, y: btnY, w: contW, h: btnH };
    } else {
      ui.idleContinueRect = null;
    }

    // Best run so far, under the sign, so a second run has something to beat.
    var best = readBestWave();
    if (best > 0) {
      drawTextWithShadow('YOUR BEST: ' + bestWaveLabel(best).toUpperCase(), WIDTH / 2, signY + signH + 16, '#fee761', 'bold 10px monospace', 'center');
    }
  }

  function drawTutorialHint() {
    if (game.state !== 'playing') { return; }
    if (ui.hintDone) { return; }
    if (game.paused) { return; }
    // Only during play; the win and lose signs sit where the card would.
    if (game.waveState === 'won') { return; }
    if (game.waveState === 'lost') { return; }
    // The hint stays until the first cannon lands, then retires for the run.
    if (game.cannons.length !== 0) { ui.hintDone = true; return; }
    // Step aside while a cannon is being dragged or armed so it never covers the drop spot.
    if (ui.armedKind !== null) { return; }
    var hintLine1 = 'Drag a cannon from the shop';
    var hintLine2 = 'onto the blanket. The ants are coming.';
    if (usesTouch()) {
      hintLine1 = 'Tap a cannon in the shop,';
      hintLine2 = 'then tap the blanket to place it.';
    }
    var w = 252;
    var h = 64;
    var x = 60;
    var y = 110;
    ctx.save();
    ctx.translate(x + w / 2, y + h / 2);
    ctx.rotate(-0.026); // -1.5 deg
    // Paper card body
    var grd = ctx.createLinearGradient(0, -h / 2, 0, h / 2);
    grd.addColorStop(0, '#fff8e8');
    grd.addColorStop(1, '#f0dfb8');
    ctx.fillStyle = grd;
    ctx.fillRect(-w / 2, -h / 2, w, h);
    // Drop shadow under the rotated card
    ctx.fillStyle = 'rgba(0, 0, 0, 0.35)';
    ctx.fillRect(-w / 2 + 3, h / 2, w, 3);
    // Border
    ctx.fillStyle = '#2a1810';
    ctx.fillRect(-w / 2, -h / 2, w, 2);
    ctx.fillRect(-w / 2, -h / 2, 2, h);
    ctx.fillRect(w / 2 - 2, -h / 2, 2, h);
    ctx.fillRect(-w / 2, h / 2 - 2, w, 2);
    // Text
    ctx.font = 'bold 10px monospace';
    ctx.fillStyle = '#4a3220';
    ctx.textAlign = 'center';
    ctx.fillText(hintLine1, 0, -6);
    ctx.fillText(hintLine2, 0, 10);
    ctx.restore();
    ctx.textAlign = 'start';
  }

  function drawPauseOverlay() {
    ctx.fillStyle = 'rgba(0, 0, 0, 0.55)';
    ctx.fillRect(0, HUD_H, PLAYFIELD_W, PLAYFIELD_H);

    var signW = 220;
    var signH = 120;
    var signX = (PLAYFIELD_W - signW) / 2;
    var signY = HUD_H + (PLAYFIELD_H - signH) / 2 - 10;
    drawWoodenSign(signX, signY, signW, signH);

    drawTextWithShadow('HOLD UP', PLAYFIELD_W / 2, signY + 22, '#c8a868', 'bold 10px monospace', 'center');
    drawTextWithShadow('PAUSED', PLAYFIELD_W / 2, signY + 52, '#fee761', 'bold 24px monospace', 'center');
    var resumeHint = 'Space or click to resume';
    if (usesTouch()) { resumeHint = 'Tap to resume'; }
    drawTextWithShadow(resumeHint, PLAYFIELD_W / 2, signY + 78, '#f5e6c8', 'bold 10px monospace', 'center');

    var rrW = 100;
    var rrH = 22;
    var rrX = PLAYFIELD_W / 2 - rrW / 2;
    var rrY = signY + signH - 36;
    drawWoodenButton(rrX, rrY, rrW, rrH, 'red');
    drawTextWithShadow('Restart Run', rrX + rrW / 2, rrY + rrH / 2, '#fee761', 'bold 10px monospace', 'center');
    ui.restartRunRect = { x: rrX, y: rrY, w: rrW, h: rrH };
  }

  // Wine-dark variant of drawWoodenSign for the lose splash.
  function drawWoodenSignWine(x, y, w, h) {
    ctx.fillStyle = 'rgba(0, 0, 0, 0.4)';
    ctx.fillRect(x + 2, y + h, w, 4);
    var grd = ctx.createLinearGradient(x, y, x, y + h);
    grd.addColorStop(0, '#5a2a20');
    grd.addColorStop(1, '#3a1a10');
    ctx.fillStyle = grd;
    ctx.fillRect(x, y, w, h);
    ctx.fillStyle = '#5a1a1a';
    ctx.fillRect(x, y, w, 3);
    ctx.fillRect(x, y, 3, h);
    ctx.fillRect(x + w - 3, y, 3, h);
    ctx.fillRect(x, y + h - 3, w, 3);
    ctx.fillStyle = '#3a1010';
    ctx.fillRect(x + 3, y + 3, w - 6, 1);
    ctx.fillStyle = '#7a3a30';
    ctx.fillRect(x + 3, y + h - 4, w - 6, 1);
    ctx.fillStyle = '#444';
    ctx.fillRect(x + 6, y + 5, 4, 4);
    ctx.fillRect(x + w - 10, y + 5, 4, 4);
    ctx.fillStyle = '#888';
    ctx.fillRect(x + 7, y + 6, 2, 2);
    ctx.fillRect(x + w - 9, y + 6, 2, 2);
  }

  // How far into the result screen we are, as a 0..1 fade. The win sign
  // waits a second first so the King's burst plays out on the open board.
  function resultFade(delay) {
    var t = (game.fxTime - game.endedFx - delay) / 0.5;
    if (t < 0) { return 0; }
    if (t > 1) { return 1; }
    return t;
  }

  function drawLoseOverlay() {
    ctx.save();
    ctx.globalAlpha = resultFade(0);
    // Saturated red dim
    ctx.fillStyle = 'rgba(80, 10, 10, 0.55)';
    ctx.fillRect(0, HUD_H, PLAYFIELD_W, PLAYFIELD_H);

    // Scattered ants
    for (var i = 0; i < LOSE_ANT_LAYOUT.length; i++) {
      var a = LOSE_ANT_LAYOUT[i];
      drawSplashAnt(a.x, a.y, a.size, a.rot);
    }

    var signW = 280;
    var signH = 130;
    var signX = (PLAYFIELD_W - signW) / 2;
    var signY = HUD_H + (PLAYFIELD_H - signH) / 2 - 6;
    drawWoodenSignWine(signX, signY, signW, signH);

    drawTextWithShadow('8 SLICES LOST', PLAYFIELD_W / 2, signY + 24, '#ff8a8a', 'bold 10px monospace', 'center');

    // Title with subtle horizontal jitter (1 px @ ~3 Hz), skipped for reduced motion.
    var jx = 0;
    if (!prefersReducedMotion()) {
      var p = (game.fxTime * 6) % 2;
      if (p < 1) { jx = 1; } else { jx = -1; }
    }
    ctx.save();
    ctx.shadowBlur = 14;
    ctx.shadowColor = 'rgba(255, 80, 80, 0.5)';
    drawTextWithShadow('THE ANTS WIN', PLAYFIELD_W / 2 + jx, signY + 60, '#ff5050', 'bold 32px monospace', 'center');
    ctx.restore();

    // How far this run got, and the best so far.
    var reached = game.waveIndex + 1;
    var lostLine = 'You made it to wave ' + reached;
    var best = readBestWave();
    if (best > reached) { lostLine = lostLine + '. Best: ' + bestWaveLabel(best); }
    drawTextWithShadow(lostLine, PLAYFIELD_W / 2, signY + 90, '#f5e6c8', 'bold 10px monospace', 'center');

    if (restartReady()) {
      var againHint = 'Click to try again';
      if (usesTouch()) { againHint = 'Tap to try again'; }
      drawTextWithShadow(againHint, PLAYFIELD_W / 2, signY + signH - 18, '#ffaaaa', 'bold 10px monospace', 'center');
    }
    ctx.restore();
  }

  function drawWinOverlay() {
    var calm = prefersReducedMotion();
    ctx.save();
    ctx.globalAlpha = resultFade(1);
    // Warm radial dim — golden glow at the center, deeper dim at the edges.
    var cxR = PLAYFIELD_W / 2;
    var cyR = HUD_H + PLAYFIELD_H / 2;
    var grd = ctx.createRadialGradient(cxR, cyR, 40, cxR, cyR, 320);
    grd.addColorStop(0, 'rgba(254, 231, 97, 0.35)');
    grd.addColorStop(1, 'rgba(20, 10, 4, 0.55)');
    ctx.fillStyle = grd;
    ctx.fillRect(0, HUD_H, PLAYFIELD_W, PLAYFIELD_H);

    // Confetti — eight pieces drifting down the playfield, looping every 4 s,
    // clipped so none of it falls over the HUD or the shop. Held still for
    // reduced motion.
    var t = (game.fxTime % 4) / 4;
    if (calm) { t = 0.3; }
    ctx.save();
    ctx.beginPath();
    ctx.rect(0, HUD_H, PLAYFIELD_W, PLAYFIELD_H);
    ctx.clip();
    for (var i = 0; i < WIN_CONFETTI_LAYOUT.length; i++) {
      var p = WIN_CONFETTI_LAYOUT[i];
      var phase = (t + i / 8) % 1;
      var py = HUD_H + phase * (PLAYFIELD_H + 20) - 10;
      ctx.save();
      ctx.translate(p.x, py);
      ctx.rotate(phase * Math.PI * 4);
      ctx.fillStyle = p.color;
      ctx.fillRect(-3, -4, 6, 8);
      ctx.restore();
    }
    ctx.restore();

    // Sign
    var signW = 280;
    var signH = 130;
    var signX = (PLAYFIELD_W - signW) / 2;
    var signY = HUD_H + (PLAYFIELD_H - signH) / 2 - 6;
    drawWoodenSign(signX, signY, signW, signH);

    // Three candles on top of the sign.
    var candleY = signY - 14;
    for (var c = 0; c < 3; c++) {
      var cx = signX + signW / 2 - 18 + c * 18;
      ctx.fillStyle = '#2a1810';
      ctx.fillRect(cx, candleY, 4, 14);
      ctx.fillStyle = '#f5e6c8';
      ctx.fillRect(cx + 1, candleY + 1, 2, 12);
      // Flame flicker — uses time so all three flicker together. Cheap.
      var flick = (game.fxTime * 4 + c) % 1;
      var flameH;
      if (flick < 0.5) { flameH = 5; } else { flameH = 6; }
      if (calm) { flameH = 6; }
      ctx.fillStyle = '#ff9c44';
      ctx.fillRect(cx + 1, candleY - flameH, 2, flameH);
      ctx.fillStyle = '#fee761';
      ctx.fillRect(cx + 1, candleY - flameH + 2, 2, flameH - 2);
    }

    // Title — VICTORY at 40 px with a gold glow.
    drawTextWithShadow('ALL 25 WAVES CLEARED', PLAYFIELD_W / 2, signY + 24, '#fee761', 'bold 10px monospace', 'center');
    ctx.save();
    ctx.shadowBlur = 18;
    ctx.shadowColor = 'rgba(254, 231, 97, 0.6)';
    drawTextWithShadow('VICTORY', PLAYFIELD_W / 2, signY + 62, '#fff8a0', 'bold 40px monospace', 'center');
    ctx.restore();
    var kept = 8 - game.cake.lost;
    var keptLine = kept + ' of 8 slices kept, ' + game.kills + ' ants squashed';
    drawTextWithShadow(keptLine, PLAYFIELD_W / 2, signY + 92, '#f5e6c8', 'bold 10px monospace', 'center');
    if (restartReady()) {
      var restartHint = 'Click to play again';
      if (usesTouch()) { restartHint = 'Tap to play again'; }
      drawTextWithShadow(restartHint, PLAYFIELD_W / 2, signY + signH - 18, '#c8a868', 'bold 10px monospace', 'center');
    }
    ctx.restore();
  }

  // Layer 13: overlays — drag preview, wave banner, then full-screen modals.
  // Modal order: pause first, then win/lose so terminal-state screens still
  // win the z-order if both are somehow active in the same frame.
  function drawOverlays() {
    drawDragPreview();
    drawBanner();
    drawWavePreview();
    drawTutorialHint();
    if (game.paused) {
      drawPauseOverlay();
    } else {
      ui.restartRunRect = null;
    }
    if (game.state === 'idle') { drawIdleOverlay(); }
    if (game.waveState === 'won') { drawWinOverlay(); }
    if (game.waveState === 'lost') { drawLoseOverlay(); }
  }

  // Single render() entry point. Walks the canonical 13-layer z-order from
  // the spec §3.5, back to front. Each step is its own helper so additions
  // land in a clearly-named slot rather than accreting inline.
  function render() {
    // Screenshake (Plan 3 — Shockwave). Translate the canvas by a small
    // random offset whose magnitude decays in frame() on the wall clock. The
    // drawBackground() call below repaints the whole view each frame, so the
    // shake's 1-3 px translate doesn't leave ghosted pixels at the edges.
    // Skipped entirely for visitors who prefer reduced motion.
    var shaking = false;
    var shakeOn = game.screenshake.magnitude > 0;
    if (prefersReducedMotion()) { shakeOn = false; }
    if (shakeOn) {
      var sx = (Math.random() * 2 - 1) * game.screenshake.magnitude;
      var sy = (Math.random() * 2 - 1) * game.screenshake.magnitude;
      ctx.save();
      ctx.translate(sx, sy);
      shaking = true;
    }
    drawBackground();          // 1. blanket + grass + shop bg
    drawCake();                // 2. cake (current damage state)
    drawAnthill();             // 3. anthill
    drawSlices();              // 4. fallen slices on the ground
    if (ui.selectedCannon) { drawRangeCircle(ui.selectedCannon); } // 5. range circle
    drawConeDots();            // 5b. cone-DoT overlay (under bases/ants)
    drawCannonAuras();         // 5c. persistent radial auras (Blizzard MP-N)
    drawCannonBases();         // 6. static cannon bases (tier rim + family ring)
    drawAnts();                // 7. ants, sorted top-down by y
    drawShatterParticles();    // 7b. cyan shatter particles (Plan 6 Brittle)
    drawTargetingOverlay();    // 7c. selected-cannon target line + reticle (Plan 7)
    drawCannonTurrets();       // 8. cannon turrets + muzzle/disable indicators
    drawProjectiles();         // 9a. projectiles
    drawBeams();               // 9b. beam lines (Glacier, Lance)
    drawLightningBolts();      // 9c. Tesla arc-jump polylines (~0.15s flash)
    drawEffects();             // 10. AoE flashes + future hit/splat fx
    drawHud();                 // 11. HUD strip
    if (ui.shopMode === 'info') {   // 12. info panel takes precedence (recursive drill-down)
      drawShopBackground();
      // The sidebar header only exists in landscape; in portrait this x is
      // off the right edge and clipped to "CAN".
      if (!isPortrait()) {
        drawTextWithShadow('CANNONS', PLAYFIELD_W + SHOP_W / 2, 14, '#c8a868', 'bold 9px monospace', 'center');
      }
      drawShopInfoPanel();
    } else if (ui.selectedCannon) {   // shop / tower-detail panel
      drawTowerPanel(ui.selectedCannon);
    } else {
      drawShop();
      ui.upgradeRects[0] = null;
      ui.upgradeRects[1] = null;
      ui.upgradeRects[2] = null;
      ui.hoveredUpgrade = null;
      ui.sellRect = null;
    }
    drawOverlays();            // 13. drag preview, banner, modals (paused/win/lose/idle)
    if (shaking) {
      ctx.restore();
    }
  }

  // === ANTS ===
  // Per-kind ant stats. Plan 2 introduces this table; Plan 1's literals are migrated here.
  // Worker and Carrier share most numbers in Plan 2; the table is structured for the
  // 8 remaining kinds (Brood, Soldier, Scout, etc.) added in later tasks. Effects slot
  // (per-ant `effects` map) is wired in Task 4 — for now it just exists, empty.
  // barStyle (optional): { width, height, color, borderColor, persistent }.
  // Default = damage-triggered 16x2 red bar. Override per-kind for boss escalation.

  // Plan 3 Task 30 — factory for King's threshold-summon hook. Returns a
  // closure that spawns N Soldiers around the King at HP-threshold crossings.
  function spawnSoldiersAtKing(count) {
    return function(king) {
      for (var i = 0; i < count; i++) {
        var ang = Math.random() * Math.PI * 2;
        var dx = Math.cos(ang) * 10;
        var dy = Math.sin(ang) * 10;
        spawnAntAt('soldier', king.x + dx, king.y + dy, 'threshold');
      }
    };
  }

  var ANT_KINDS = {
    worker: {
      name: 'Worker',
      blurb: 'Grabs a slice and runs it home.',   // first-sighting banner line
      hp: 10,
      speed: 30,
      carryingSpeedMult: 1.5,    // Plan 3 Task 33 — flee-sprint at 1.5x; spec acceptance item 21 covers worker-turned-carrier too
      gold: 2,
      alignment: 0.2,
      jitterRate: 4.0,
      wanderFreqMin: 0.3, wanderFreqMax: 0.7,
      wanderAmpMin: 0.9, wanderAmpMax: 1.5,
      spriteKey: 'worker',
      spriteW: 16, spriteH: 16,
      slowResist: 0,
      damageMult: 1,
      pierceMult: 1,
      canPickup: true,
      onDeath: null,
      armor: 0
    },
    royal_guard: {
      name: 'Royal Guard',
      blurb: 'Escorts the King. Reapers cannot one-shot it.',
      hp: 20,
      speed: 35,
      carryingSpeedMult: 1.5,
      gold: 4,
      alignment: 0.3,
      jitterRate: 3.0,
      wanderFreqMin: 0.3, wanderFreqMax: 0.7,
      wanderAmpMin: 0.9, wanderAmpMax: 1.5,
      spriteKey: 'royal_guard',
      spriteW: 16, spriteH: 16,
      slowResist: 0.3,
      damageMult: 1,
      pierceMult: 1,
      canPickup: true,
      armor: 0.2
    },
    carrier: {
      name: 'Carrier',
      blurb: 'Tougher than a worker, same taste for cake.',
      hp: 15,
      speed: 30,
      carryingSpeedMult: 1.5,    // Plan 3 Task 33 — flees at 45 px/s once a slice is grabbed (panic sprint)
      gold: 4,
      alignment: 0.2,
      jitterRate: 4.0,
      wanderFreqMin: 0.3, wanderFreqMax: 0.7,
      wanderAmpMin: 0.9, wanderAmpMax: 1.5,
      spriteKey: 'carrier',
      spriteW: 16, spriteH: 16,
      slowResist: 0,
      damageMult: 1,
      pierceMult: 1,
      canPickup: true,
      onDeath: null,
      armor: 0
    },
    scout: {
      name: 'Scout',
      blurb: 'Fast and flimsy. Easy to miss.',
      hp: 6,
      speed: 60,
      carryingSpeedMult: 1.5,    // Plan 3 Task 33 — flee-sprint at 1.5x; spec acceptance item 21 covers worker-turned-carrier too
      gold: 3,
      alignment: 0.4,
      jitterRate: 5.0,
      wanderFreqMin: 0.4, wanderFreqMax: 0.9,
      wanderAmpMin: 1.0, wanderAmpMax: 1.7,
      spriteKey: 'scout',
      spriteW: 12, spriteH: 12,
      slowResist: 0,
      damageMult: 1,
      pierceMult: 1,
      canPickup: true,
      onDeath: null,
      armor: 0
    },
    soldier: {
      name: 'Soldier',
      blurb: 'Armored. Small hits do half damage.',
      hp: 40,
      speed: 20,
      gold: 5,
      alignment: 0.2,
      jitterRate: 3.0,
      wanderFreqMin: 0.2, wanderFreqMax: 0.5,
      wanderAmpMin: 0.6, wanderAmpMax: 1.0,
      spriteKey: 'soldier',
      spriteW: 16, spriteH: 16,
      carryingSpeedMult: 1.5,    // Plan 3 Task 33 — flee-sprint at 1.5x; spec acceptance item 21 covers worker-turned-carrier too
      slowResist: 0.5,
      damageMult: 1,
      pierceMult: 1,
      canPickup: true,
      onDeath: null,
      armor: 0.5
    },
    tank: {
      name: 'Tank',
      blurb: 'Armored, and too heavy to carry cake.',
      hp: 150,
      speed: 12,
      gold: 10,
      alignment: 0.15,
      jitterRate: 2.0,
      wanderFreqMin: 0.15, wanderFreqMax: 0.35,
      wanderAmpMin: 0.4, wanderAmpMax: 0.7,
      spriteKey: 'tank',
      spriteW: 24, spriteH: 24,
      carryingSpeedMult: 1,        // unused (canPickup=false) but keeps the schema uniform
      slowResist: 0,
      damageMult: 1,
      pierceMult: 1,
      canPickup: false,
      onDeath: null,
      armor: 0.5
    },
    splitter: {
      name: 'Splitter',
      blurb: 'Breaks into two little ants when it dies.',
      hp: 25,
      speed: 25,
      gold: 4,
      alignment: 0.25,
      jitterRate: 4.0,
      wanderFreqMin: 0.3, wanderFreqMax: 0.6,
      wanderAmpMin: 0.8, wanderAmpMax: 1.3,
      spriteKey: 'splitter',
      spriteW: 16, spriteH: 16,
      carryingSpeedMult: 1.5,    // Plan 3 Task 33 — flee-sprint at 1.5x; spec acceptance item 21 covers worker-turned-carrier too
      slowResist: 0,
      damageMult: 1,
      pierceMult: 1,
      canPickup: true,
      onDeath: function(ant) {
        for (var i = 0; i < 2; i++) {
          var ang = Math.random() * Math.PI * 2;
          spawnAntAt('mini_splitter', ant.x + Math.cos(ang) * 6, ant.y + Math.sin(ang) * 6, 'split');
        }
      },
      armor: 0
    },
    mini_splitter: {
      name: 'Mini-Splitter',
      hp: 8,
      speed: 35,
      gold: 1,
      alignment: 0.35,
      jitterRate: 5.0,
      wanderFreqMin: 0.4, wanderFreqMax: 0.8,
      wanderAmpMin: 1.0, wanderAmpMax: 1.5,
      spriteKey: 'mini_splitter',
      spriteW: 8, spriteH: 8,
      carryingSpeedMult: 1.5,    // Plan 3 Task 33 — flee-sprint at 1.5x; spec acceptance item 21 covers worker-turned-carrier too
      slowResist: 0,
      damageMult: 1,
      pierceMult: 1,
      canPickup: true,
      onDeath: null,
      armor: 0
    },
    phase: {
      name: 'Phase',
      blurb: 'Fades out now and then. Shots pass through.',
      hp: 20,
      speed: 35,
      gold: 6,
      alignment: 0.3,
      jitterRate: 4.0,
      wanderFreqMin: 0.3, wanderFreqMax: 0.7,
      wanderAmpMin: 0.9, wanderAmpMax: 1.4,
      spriteKey: 'phase',
      spriteW: 16, spriteH: 16,
      carryingSpeedMult: 1.5,    // Plan 3 Task 33 — flee-sprint at 1.5x; spec acceptance item 21 covers worker-turned-carrier too
      slowResist: 0,
      damageMult: 1,
      pierceMult: 1,
      canPickup: true,
      onDeath: null,
      armor: 0,
      phaseInterval: 3,
      phaseDuration: 1
    },
    bomber: {
      name: 'Bomber',
      blurb: 'When it pops, nearby cannons stall for 5 seconds.',
      hp: 30,
      speed: 40,
      gold: 7,
      alignment: 0.3,
      jitterRate: 4.5,
      wanderFreqMin: 0.4, wanderFreqMax: 0.8,
      wanderAmpMin: 1.0, wanderAmpMax: 1.5,
      spriteKey: 'bomber',
      spriteW: 16, spriteH: 16,
      carryingSpeedMult: 1.5,    // Plan 3 Task 33 — flee-sprint at 1.5x; spec acceptance item 21 covers worker-turned-carrier too
      slowResist: 0,
      damageMult: 1,
      pierceMult: 1,
      canPickup: true,
      armor: 0,
      // On death, disable every cannon within 60px for 5 seconds and push a flash
      // effect onto game.effects. updateCannons skips disabled cannons (Task 12 gate).
      onDeath: function(ant) {
        var radius = 60;
        for (var i = 0; i < game.cannons.length; i++) {
          var c = game.cannons[i];
          var dx = c.x - ant.x;
          var dy = c.y - ant.y;
          if (dx * dx + dy * dy <= radius * radius) {
            c.disabled = true;
            c.disabledUntil = game.time + 5;
          }
        }
        game.effects.push({ kind: 'bomber_aoe', x: ant.x, y: ant.y, t: 0, life: 0.6 });
      }
    },
    queen: {
      name: 'Queen',
      blurb: 'Big and tough. Six workers pop out when she falls.',
      hp: 400,
      speed: 15,
      gold: 50,
      alignment: 0.15,
      jitterRate: 1.5,
      wanderFreqMin: 0.1, wanderFreqMax: 0.3,
      wanderAmpMin: 0.3, wanderAmpMax: 0.5,
      spriteKey: 'queen',
      spriteW: 24, spriteH: 24,
      carryingSpeedMult: 1.5,    // Plan 3 Task 33 — flee-sprint at 1.5x; spec acceptance item 21 covers worker-turned-carrier too
      slowResist: 0.25,
      damageMult: 1,
      pierceMult: 1,
      canPickup: true,
      armor: 0.3,
      onDeath: function(ant) {
        for (var i = 0; i < 6; i++) {
          var ang = (Math.PI * 2 / 6) * i;
          spawnAntAt('worker', ant.x + Math.cos(ang) * 12, ant.y + Math.sin(ang) * 12, 'queen');
        }
      }
    },
    // King — final-wave boss (Plan 3 Tasks 29-31). Slow regal sprite with a
    // persistent dark-red HP bar so the threat reads from spawn. onSpawn
    // registers a periodic worker drip from (10, 320); onHpThresholds
    // summons 4 Soldiers at 75/50/25% HP. canPickup=false so the King ignores
    // the cake — only the drip-workers can finish a run.
    king: {
      name: 'King',
      blurb: 'Armored. His guards keep coming until he falls.',
      hp: 3000,
      speed: 10,
      gold: 200,
      alignment: 0.4,
      jitterRate: 1.0,
      wanderFreqMin: 0.1, wanderFreqMax: 0.2,
      wanderAmpMin: 0.2, wanderAmpMax: 0.4,
      spriteKey: 'king',
      spriteW: 32, spriteH: 32,
      carryingSpeedMult: 1,        // unused (canPickup=false) but keeps the schema uniform
      slowResist: 0.5,
      damageMult: 1,
      pierceMult: 1,
      canPickup: false,
      armor: 0.5,
      barStyle: { width: 60, height: 4, color: '#d04020', borderColor: '#fff', persistent: true },
      onHpThresholds: [
        { at: 0.75, action: spawnSoldiersAtKing(4) },
        { at: 0.50, action: spawnSoldiersAtKing(4) },
        { at: 0.25, action: spawnSoldiersAtKing(4) }
      ],
      onSpawn: function(king) {
        game.spawnJobs.push({
          interval: 2.5,
          lastFiredAt: game.time,
          owner: king,
          fn: function() {
            spawnAntAt('royal_guard', 10, 320, 'drip', { x: 10, y: 320 });
          }
        });
      },
      onDeath: null
    }
  };

  // === STATUS EFFECTS ===
  // Six-kind framework. Slow/freeze/stun scale movement; burn/poison tick HP damage; knockback
  // is a one-shot positional impulse on apply. Cannons emit these starting in Task 13 (Frost
  // emits 'slow'). Worker/Carrier carry slowResist=0 today; Soldier (Task 16) will use it.
  var STATUS_KINDS = {
    slow:      { name: 'Slow',      onApply: null, perTick: null, speedMult: function(m) { return 1 - m; } },
    freeze:    { name: 'Freeze',    onApply: null, perTick: null, speedMult: function(m) { return 0; } },
    burn:      { name: 'Burn',      onApply: null, perTick: function(ant, eff, dt) { ant.hp = ant.hp - eff.magnitude * dt; }, speedMult: null,
                 // Plan 4 — Phoenix burn-spread on burn-end. Mirror of the
                 // poison.onTargetDeath hook: when an ant's burn duration
                 // runs out (not on death), if the burn was tagged as
                 // spreading, propagate to ants within 30 px. Toxin and
                 // Plasma Torch leave the spreads tag unset so their burns
                 // expire silently. Pushes a transient orange ring effect
                 // for the visual cue (rendered by drawEffects, Task 13).
                 onExpire: function(ant) {
                   if (!ant.effects) { return; }
                   if (!ant.effects.burn) { return; }
                   if (!ant.effects.burn.spreads) { return; }
                   var radius = 30;
                   for (var i = 0; i < game.ants.length; i++) {
                     var a = game.ants[i];
                     if (a === ant) { continue; }
                     if (a.dead) { continue; }
                     var dx = a.x - ant.x;
                     var dy = a.y - ant.y;
                     if (dx * dx + dy * dy > radius * radius) { continue; }
                     applyStatus(a, 'burn', 1, 2, 'burn-spread');
                     // Spread the spread tag too so chained spreads continue
                     // (capped naturally by burn duration decay).
                     if (a.effects.burn) { a.effects.burn.spreads = true; }
                   }
                   game.effects.push({
                     kind: 'spreadRing',
                     x: ant.x, y: ant.y,
                     t: 0,
                     life: 0.2,
                     color: '#ff7733',
                     maxRadius: 30
                   });
                 } },
    poison:    { name: 'Poison',    onApply: null, perTick: function(ant, eff, dt) { ant.hp = ant.hp - eff.magnitude * dt; }, speedMult: null,
                 // Plan 3 — Plague spread-on-death hook. When an ant dies with
                 // a `spreads: true`-tagged poison effect (stamped after
                 // applyStatus by the projectile-collision pathway), every
                 // live, non-dying ant within 30 px catches the same poison
                 // (matched magnitude + remaining duration). Toxin's poison
                 // omits the tag so its kills don't propagate.
                 onTargetDeath: function(deadAnt) {
                   var orig = deadAnt.effects.poison;
                   if (!orig) { return; }
                   // Plan 4 — spreadRadius (Biohazard) overrides the 30 px
                   // default. Plague leaves spreadRadius unset, so existing
                   // behavior is unchanged.
                   var radius = 30;
                   if (orig.spreadRadius) { radius = orig.spreadRadius; }
                   var remainingDur = orig.expiresAt - game.time;
                   if (remainingDur <= 0) { return; }
                   for (var i = 0; i < game.ants.length; i++) {
                     var a = game.ants[i];
                     if (a.dead) { continue; }
                     if (a === deadAnt) { continue; }
                     var dx = a.x - deadAnt.x;
                     var dy = a.y - deadAnt.y;
                     if (dx * dx + dy * dy > radius * radius) { continue; }
                     applyStatus(a, 'poison', orig.magnitude, remainingDur, 'plague-spread');
                     // Spread the spread tag, the radius, and the damageAmp
                     // tag too so chain-deaths keep the same propagation
                     // shape and Biohazard's biohazard outline keeps showing.
                     if (a.effects.poison) {
                       a.effects.poison.spreads = true;
                       if (orig.spreadRadius) { a.effects.poison.spreadRadius = orig.spreadRadius; }
                       if (orig.damageAmp) { a.effects.poison.damageAmp = orig.damageAmp; }
                     }
                   }
                 } },
    stun:      { name: 'Stun',      onApply: null, perTick: null, speedMult: function(m) { return 0; } },
    knockback: { name: 'Knockback', onApply: function(ant, m) { ant.x = ant.x + Math.cos(ant.heading + Math.PI) * m; ant.y = ant.y + Math.sin(ant.heading + Math.PI) * m; }, perTick: null, speedMult: null }
  };

  // Apply a status effect to an ant. Refresh rule: if the ant already has this kind active,
  // the resulting entry takes the LONGER expiry and the HIGHER magnitude (nested ifs only).
  // sourceCannon (optional) is stamped on the effect so a burn or poison tick
  // that finishes an ant can credit the cannon that lit it.
  function applyStatus(ant, kindKey, magnitude, duration, source, sourceCannon) {
    var k = ANT_KINDS[ant.kind];
    if (kindKey === 'slow') {
      var slowResist = k.slowResist;
      if (!slowResist) { slowResist = 0; }
      magnitude = magnitude * (1 - slowResist);
    }
    var existing = ant.effects[kindKey];
    var nowTime = game.time;
    if (!nowTime) { nowTime = 0; }
    var newExpiry = nowTime + duration;
    if (existing) {
      if (existing.magnitude > magnitude) { magnitude = existing.magnitude; }
      if (existing.expiresAt > newExpiry) { newExpiry = existing.expiresAt; }
    }
    var credit = sourceCannon;
    if (!credit) {
      if (existing) { credit = existing.sourceCannon; }
    }
    ant.effects[kindKey] = { magnitude: magnitude, expiresAt: newExpiry, source: source, sourceCannon: credit };
    var def = STATUS_KINDS[kindKey];
    if (def.onApply) { def.onApply(ant, magnitude); }
  }

  // Particle burst factory. Plan 6's Brittle shatter (cyan), plus the King's
  // death burst (gold and red). Pushes `count` particles onto
  // game.shatterParticles with random radial velocity. The particles tick on
  // real-time dt (see updateShatterParticles below) so the visual stays
  // readable at any game speed.
  function spawnBurst(x, y, color, count, speed, lifetime, size) {
    for (var i = 0; i < count; i++) {
      var angle = Math.random() * Math.PI * 2;
      var v = speed * (0.6 + Math.random() * 0.4);
      game.shatterParticles.push({
        x: x,
        y: y,
        vx: Math.cos(angle) * v,
        vy: Math.sin(angle) * v,
        age: 0,
        lifetime: lifetime,
        color: color,
        size: size
      });
    }
  }

  // Plan 6: Particle tick. frame() passes real elapsed time so the burst
  // always lasts 0.18s wall-time regardless of game speed. Reverse iteration
  // so splice(i, 1) doesn't break the index — same convention as
  // updateProjectiles and the slices loop elsewhere in this file.
  function updateShatterParticles(dt) {
    for (var i = game.shatterParticles.length - 1; i >= 0; i--) {
      var p = game.shatterParticles[i];
      p.x = p.x + p.vx * dt;
      p.y = p.y + p.vy * dt;
      p.vx = p.vx * 0.92;
      p.vy = p.vy * 0.92;
      p.age = p.age + dt;
      if (p.age >= p.lifetime) {
        game.shatterParticles.splice(i, 1);
      }
    }
  }

  // Plan 6: Particle draw. Linear alpha fade over lifetime. Math.round on the
  // position to dodge subpixel blur on the 320x180 canvas. fillStyle hex
  // literal — no operator-encoding concerns.
  function drawShatterParticles() {
    if (game.shatterParticles.length === 0) { return; }
    ctx.save();
    for (var i = 0; i < game.shatterParticles.length; i++) {
      var p = game.shatterParticles[i];
      var alpha = 1 - (p.age / p.lifetime);
      ctx.globalAlpha = alpha;
      ctx.fillStyle = p.color;
      ctx.fillRect(Math.round(p.x) - 1, Math.round(p.y) - 1, p.size, p.size);
    }
    ctx.restore();
  }

  // Plan 7 — Targeting overlay. When a cannon is selected, draws a faint
  // dashed white line from the cannon to its current target and a 4 px
  // reticle on the target. Gated on selectedCannon non-null and the cannon
  // having a live target.
  function drawTargetingOverlay() {
    if (!ui.selectedCannon) { return; }
    var c = ui.selectedCannon;
    if (!c.target) { return; }
    if (c.target.dead) { return; }

    ctx.save();
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.4)';
    ctx.lineWidth = 1;
    ctx.setLineDash([3, 3]);
    ctx.beginPath();
    ctx.moveTo(c.x, c.y);
    ctx.lineTo(c.target.x, c.target.y);
    ctx.stroke();
    ctx.setLineDash([]);

    ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
    ctx.fillRect(Math.round(c.target.x) - 2, Math.round(c.target.y) - 2, 4, 4);
    ctx.restore();
  }

  // Spawn an ant of `kindKey` at an explicit (x, y). Used by spawnAnt for the
  // normal anthill spawn, and by onDeath hooks (Splitter, Bomber, Queen) that
  // need to drop new ants at a specific position.
  function spawnAntAt(kindKey, x, y, source, origin) {
    if (!source) { source = 'wave'; }
    var ant = spawnAnt(kindKey, source);
    ant.x = x;
    ant.y = y;
    if (origin) { ant.spawnOrigin = { x: origin.x, y: origin.y }; }
    return ant;
  }

  function spawnAnt(kindKey, source) {
    if (!source) { source = 'wave'; }
    var k = ANT_KINDS[kindKey];
    var ant = {
      kind: kindKey,
      x: game.anthill.x,
      y: game.anthill.y + (Math.random() * 60 - 30),
      hp: k.hp,
      maxHp: k.hp,
      speed: k.speed,
      heading: (Math.random() * 2 - 1) * 0.5,   // radians; small random offset from east
      dead: false,
      deathFx: 0,                                // fxTime of death; the splat plays out from here
      hitFx: -10,                                // fxTime of the last direct hit (white flash)
      deliveredAt: 0,                            // non-zero once carried home; drawAnts skips the splat
      carrying: false,
      wanderPhase: Math.random() * Math.PI * 2,
      wanderFreq: k.wanderFreqMin + Math.random() * (k.wanderFreqMax - k.wanderFreqMin),
      wanderAmp: k.wanderAmpMin + Math.random() * (k.wanderAmpMax - k.wanderAmpMin),
      effects: {},                                   // Task 4 fills this with status-effect entries
      intangible: false,
      phaseT: 0,
      spawnSource: source,
      spawnOrigin: { x: game.anthill.x, y: game.anthill.y },
      firedThresholds: {},
      isRevealed: false
    };
    game.ants.push(ant);
    if (k.onSpawn) { k.onSpawn(ant); }
    // First time this run a kind shows up, introduce it with a banner.
    if (k.blurb) {
      if (!game.seenKinds[kindKey]) {
        game.seenKinds[kindKey] = true;
        showBanner('NEW ANT: ' + k.name.toUpperCase(), 'wood', k.blurb);
      }
    }
    return ant;
  }

  function angleTowards(fromX, fromY, toX, toY) {
    return Math.atan2(toY - fromY, toX - fromX);
  }

  function angleDelta(from, to) {
    var d = to - from;
    while (d > Math.PI) { d -= Math.PI * 2; }
    while (d < -Math.PI) { d += Math.PI * 2; }
    return d;
  }

  // Bias the desired heading sideways around any cannon roughly on the path ahead.
  // Ants treat cannons as soft obstacles; a clamped soft repulsion runs after movement.
  function steerAroundCannons(ant, desiredHeading) {
    var lookAhead = 28;
    var combined = 18;          // ant radius + cannon radius (rough)
    var clearance = combined + 6;
    var bias = 0;
    var cosD = Math.cos(desiredHeading);
    var sinD = Math.sin(desiredHeading);
    for (var i = 0; i < game.cannons.length; i++) {
      var c = game.cannons[i];
      var dx = c.x - ant.x;
      var dy = c.y - ant.y;
      var distSq = dx * dx + dy * dy;
      if (distSq > lookAhead * lookAhead) { continue; }
      if (distSq < 0.0001) { continue; }
      // Forward/lateral split relative to where the ant wants to go.
      var forward = dx * cosD + dy * sinD;
      if (forward <= 0) { continue; }       // cannon is behind or abreast
      var lateral = -dx * sinD + dy * cosD;
      var lateralAbs = lateral < 0 ? -lateral : lateral;
      if (lateralAbs > clearance) { continue; }
      var dist = Math.sqrt(distSq);
      var urgency = 1 - dist / lookAhead;
      var sideways;
      if (lateral > 0) {
        sideways = -1;     // cannon is to the left of desired heading -> veer right
      } else {
        if (lateral < 0) {
          sideways = 1;
        } else {
          sideways = (ant.wanderPhase > Math.PI) ? 1 : -1;   // dead ahead, pick a side
        }
      }
      bias += sideways * urgency * 1.2;
    }
    return desiredHeading + bias;
  }

  function updateAnt(ant, dt) {
    if (ant.dead) { return; }
    var targetX;
    var targetY;
    if (ant.carrying) {
      targetX = ant.spawnOrigin.x;
      targetY = ant.spawnOrigin.y;
    } else {
      targetX = game.cake.x;
      targetY = game.cake.y;
    }
    var k = ANT_KINDS[ant.kind];
    if (k.phaseInterval) {
      var phaseT = ant.phaseT;
      if (!phaseT) { phaseT = 0; }
      ant.phaseT = phaseT + dt;
      var cycle = k.phaseInterval + k.phaseDuration;
      var t = ant.phaseT % cycle;
      ant.intangible = (t >= k.phaseInterval);
    }
    var alignment = k.alignment;
    var jitterRate = k.jitterRate;
    var wanderScale = 1.0;
    var speed = ant.speed;
    if (ant.carrying) {
      alignment = 0.6;       // more determined return trip
      jitterRate = 1.5;      // less wander when going home
      wanderScale = 0.3;     // tighten the sway when loaded
      speed = ant.speed * k.carryingSpeedMult;
    } else {
      // Final approach: within 2 squares (32px) of the cake, lock in for the grab.
      var dxc = ant.x - game.cake.x;
      var dyc = ant.y - game.cake.y;
      var distCakeSq = dxc * dxc + dyc * dyc;
      if (distCakeSq < 32 * 32) {
        alignment = 0.9;
        jitterRate = 0.5;
        wanderScale = 0.0;
      }
      // Hard lock zone: very close to the cake, snap heading dead at the
      // center and zero out jitter. Without this, jitter + wall-bounce can
      // keep an ant orbiting just outside the pickup radius indefinitely.
      if (distCakeSq < 26 * 26) {
        ant.heading = angleTowards(ant.x, ant.y, game.cake.x, game.cake.y);
        jitterRate = 0;
        wanderScale = 0;
      }
    }
    // Status-effect speed scaling: walk every active effect and apply its multiplier (slow,
    // freeze, stun). Burn/poison/knockback have null speedMult and are skipped here.
    var ek;
    for (ek in ant.effects) {
      var sdef = STATUS_KINDS[ek];
      if (sdef.speedMult) {
        var mag = ant.effects[ek].magnitude;
        speed = speed * sdef.speedMult(mag);
      }
    }
    // Status-effect tick damage: burn and poison drain HP each tick.
    for (ek in ant.effects) {
      var defT = STATUS_KINDS[ek];
      if (defT.perTick) { defT.perTick(ant, ant.effects[ek], dt); }
    }
    // Burn and poison only drain hp, so finish the kill here. Credit goes to
    // whichever cannon lit the effect; killAnt takes null when none did.
    if (ant.hp <= 0) {
      var dotKiller = null;
      for (ek in ant.effects) {
        if (STATUS_KINDS[ek].perTick) {
          if (ant.effects[ek].sourceCannon) { dotKiller = ant.effects[ek].sourceCannon; }
        }
      }
      killAnt(ant, dotKiller);
      return;
    }
    // Expire effects whose timer has run out. Collect keys first to avoid mutating the
    // object during iteration.
    var keys = [];
    for (ek in ant.effects) { keys.push(ek); }
    for (var ki = 0; ki < keys.length; ki++) {
      if (ant.effects[keys[ki]].expiresAt <= game.time) {
        // Plan 4: fire onExpire hook (currently used by burn for Phoenix
        // spread) before deleting the effect. Other status kinds leave
        // onExpire unset, so this is a no-op for them.
        var expiringDef = STATUS_KINDS[keys[ki]];
        if (expiringDef.onExpire) { expiringDef.onExpire(ant); }
        delete ant.effects[keys[ki]];
      }
    }
    ant.wanderPhase += ant.wanderFreq * dt * Math.PI * 2;
    var wanderOffset = Math.sin(ant.wanderPhase) * ant.wanderAmp * wanderScale;
    var desiredHeading = angleTowards(ant.x, ant.y, targetX, targetY) + wanderOffset;
    desiredHeading = steerAroundCannons(ant, desiredHeading);
    ant.heading += angleDelta(ant.heading, desiredHeading) * alignment * dt;
    ant.heading += (Math.random() * 2 - 1) * jitterRate * dt;
    ant.x += Math.cos(ant.heading) * speed * dt;
    ant.y += Math.sin(ant.heading) * speed * dt;
    // Bounce off playfield bounds so ants stay on the picnic blanket area.
    var margin = 8;
    if (ant.x < margin) {
      ant.x = margin;
      ant.heading = Math.PI - ant.heading;
    }
    if (ant.x > PLAYFIELD_W - margin) {
      ant.x = PLAYFIELD_W - margin;
      ant.heading = Math.PI - ant.heading;
    }
    if (ant.y < HUD_H + margin) {
      ant.y = HUD_H + margin;
      ant.heading = -ant.heading;
    }
    if (ant.y > HEIGHT - margin) {
      ant.y = HEIGHT - margin;
      ant.heading = -ant.heading;
    }
    // Soft repulsion: accumulate unit-vector contributions from each nearby cannon, scaled by
    // (influence - dN) / influence so contact-close towers push hardest. Magnitude is then
    // clamped to 1.5 * speed * dt per spec SYS-5 so two adjacent towers cannot catapult an ant
    // through a wall in one frame. The steerAroundCannons heading bias above does the wider
    // "flow around" behavior; this displacement just keeps ants from settling inside a tower.
    var rx = 0;
    var ry = 0;
    var towerRadius = 12;
    var influence = towerRadius + 4;
    for (var ci = 0; ci < game.cannons.length; ci++) {
      var cn = game.cannons[ci];
      var dxN = ant.x - cn.x;
      var dyN = ant.y - cn.y;
      var dN = Math.sqrt(dxN * dxN + dyN * dyN);
      if (dN < influence) {
        if (dN < 0.001) { dN = 0.001; }
        var force = (influence - dN) / influence;
        rx = rx + (dxN / dN) * force;
        ry = ry + (dyN / dN) * force;
      }
    }
    var rmag = Math.sqrt(rx * rx + ry * ry);
    var rclamp = 1.5 * speed * dt;
    if (rmag > rclamp) {
      if (rmag > 0) {
        rx = rx * (rclamp / rmag);
        ry = ry * (rclamp / rmag);
      }
    }
    ant.x = ant.x + rx;
    ant.y = ant.y + ry;
    // Final non-reflective clamp in case soft repulsion pushed us past a wall.
    if (ant.x < margin) { ant.x = margin; }
    if (ant.x > PLAYFIELD_W - margin) { ant.x = PLAYFIELD_W - margin; }
    if (ant.y < HUD_H + margin) { ant.y = HUD_H + margin; }
    if (ant.y > HEIGHT - margin) { ant.y = HEIGHT - margin; }
  }

  function updateAnts(dt) {
    for (var i = 0; i < game.ants.length; i++) {
      updateAnt(game.ants[i], dt);
    }
  }

  function checkPickupsAndDeliveries() {
    var pickupR = 18;
    var deliverR = 14;
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      if (a.dead) { continue; }
      if (a.carrying) {
        var dxHill = a.x - a.spawnOrigin.x;
        var dyHill = a.y - a.spawnOrigin.y;
        if (dxHill * dxHill + dyHill * dyHill < deliverR * deliverR) {
          game.cake.lost += 1;
          a.dead = true;
          a.deliveredAt = game.time;   // mark delivery so drawAnts skips the splat sprite
          a.deathFx = game.fxTime;
          // Make the loss visible: a red ring at the anthill, a callout, and
          // the HUD cake tag flashes (drawHud reads sliceLostFx).
          game.sliceLostFx = game.fxTime;
          game.effects.push({ kind: 'redRing', x: a.x, y: a.y, t: 0, life: 0.6, r: 30 });
          addPopup(a.x + 20, a.y - 18, 'SLICE LOST', '#ff8a8a');
          if (game.cake.lost >= 8) {
            endRun('lost');
          }
        }
      } else {
        var k = ANT_KINDS[a.kind];
        if (!k.canPickup) {
          // Tanks and similar do not pick up cake — just walk past
          continue;
        }
        // Task 23: ground-slice pickup. Before checking the cake itself, see if the
        // ant is standing on a fallen slice from a recent kill. 10 px radius (squared
        // = 100) keeps pickup tight enough that ants do not snag a slice they are
        // walking past. claimedBy is set defensively even though we splice on the
        // same frame — leaves a hook for any future cross-frame claim animation.
        var pickedFromGround = false;
        for (var si = 0; si < game.slices.length; si++) {
          var sl = game.slices[si];
          if (sl.claimedBy) { continue; }
          var dxs = a.x - sl.x;
          var dys = a.y - sl.y;
          if (dxs * dxs + dys * dys <= 100) {
            a.carrying = true;
            sl.claimedBy = a;
            a.heading = angleTowards(a.x, a.y, a.spawnOrigin.x, a.spawnOrigin.y);
            game.slices.splice(si, 1);
            pickedFromGround = true;
            break;
          }
        }
        if (pickedFromGround) { continue; }
        if (game.cake.slices > 0) {
          var dxCake = a.x - game.cake.x;
          var dyCake = a.y - game.cake.y;
          if (dxCake * dxCake + dyCake * dyCake < pickupR * pickupR) {
            game.cake.slices -= 1;
            a.carrying = true;
            a.heading = angleTowards(a.x, a.y, a.spawnOrigin.x, a.spawnOrigin.y);
            // A slice coming off the cake: small red ring and a plate dip.
            game.cakeDipFx = game.fxTime;
            game.effects.push({ kind: 'redRing', x: game.cake.x, y: game.cake.y, t: 0, life: 0.35, r: 18 });
          }
        }
      }
    }
  }

  function pruneAnts() {
    var kept = [];
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      // Dead ants stay for 0.5 s on the effects clock so the splat can play.
      if (!a.dead) {
        kept.push(a);
      } else if (game.fxTime - a.deathFx < 0.5) {
        kept.push(a);
      }
    }
    game.ants = kept;
  }

  // === CANNONS ===
  // CANNON_KINDS schema. Required fields per cannon entry:
  //   name, cost, damage, fireRate, range, projectileSpeed (omitted on beam),
  //   family, tier, upgradesTo.
  // Optional fields (Plan 3):
  //   damageModifier: function(target, sourceCannon) -> multiplier
  //     Per-cannon damage formula hook. Returns 1 by default (treated as no
  //     modifier when absent). Marksman returns 1.5 vs full-HP targets;
  //     Phantom returns 2 vs Queens; etc. Evaluated by applyDamage when a
  //     sourceCannon is threaded through.
  //   linePierce: number — projectile passes through up to N ants (Plan 3).
  //   armorPierce: bool — bypass armored mitigation (Plan 3).
  //   splash: { radius, falloff: number 0..1 } — AoE damage on impact.
  //   homing: { turnRate } — projectile steers each tick toward p.target (Patriot).
  //   arc: { height, travelTime } — ballistic arcing visual + fixed land time.
  //   returning: { phases } — projectile reverses path after first hit.
  //   pellets: number — multi-pellet cone (Buckshot).
  //   coneDeg: degrees — spread cone for pellets or aim jitter (Minigun).
  //   beam: { tickDamage, tickInterval, statusOnTick } — continuous beam (Lance).
  //   coneDot: { range, angleDeg, tickDamage, statusOnTick } — Plasma Torch.
  //   teslaArc: { jumps, range, falloff } — chain-jump targeting (Tesla).
  //   reveal: bool — sets target.isRevealed = true each tick (Spotter).
  //   screenshake: { magnitude, decay } — splash-triggered camera shake (Shockwave).
  var CANNON_KINDS = {
    howitzer: {
      name: 'Howitzer',
      description: 'Slow, heavy single shot. Shatters slowed or frozen ants.',
      cost: 50,
      damage: 8,
      fireRate: 0.6,         // shots per second
      range: 110,
      projectileSpeed: 200,
      family: 'heavy',
      tier: 'base',
      upgradesTo: ['siege', 'mortar', 'discus']
    },
    siege: {
      name: 'Siege',
      description: 'Much harder hits. Same slow rhythm as the Howitzer.',
      cost: 50,
      damage: 12,
      fireRate: 0.6,
      range: 110,
      projectileSpeed: 240,
      family: 'heavy',
      tier: 'mid',
      upgradesTo: ['railgun', 'demolisher']
    },
    railgun: {
      name: 'Railgun',
      description: 'Pierces a whole line of ants. Ignores armor, too.',
      cost: 90,
      damage: 25,
      fireRate: 0.6,
      range: 120,
      projectileSpeed: 360,
      family: 'heavy',
      tier: 'final',
      linePierce: 999,    // unlimited — pre-Plan-3 Railgun semantics
      armorPierce: true,
      upgradesTo: ['particle_cannon']
    },
    mortar: {
      name: 'Mortar',
      description: 'Lobs a shell that splashes. Good for clusters.',
      cost: 60,
      damage: 8,
      fireRate: 0.5,
      range: 130,
      projectileSpeed: 200,
      family: 'heavy',
      tier: 'mid',
      splash: { radius: 40, falloff: 0.5, splashMult: 0.5 },
      arc: { height: 24, travelTime: 0.6 },
      upgradesTo: ['patriot', 'hellfire']
    },
    patriot: {
      name: 'Patriot',
      description: 'Homing missile, small blast. Long range, never misses.',
      cost: 110,
      damage: 20,
      fireRate: 0.5,
      range: 200,
      projectileSpeed: 260,
      family: 'heavy',
      tier: 'final',
      homing: { turnRate: 4.0 },
      splash: { radius: 30, falloff: 0.4, splashMult: 0.4 },
      upgradesTo: ['cluster_strike']
    },
    demolisher: {
      name: 'Demolisher',
      description: 'One huge, slow shot. Punches through armor.',
      cost: 110,
      damage: 60,
      fireRate: 0.25,
      range: 110,
      projectileSpeed: 260,
      family: 'heavy',
      tier: 'final',
      armorPierce: true,
      upgradesTo: ['annihilator']
    },
    hellfire: {
      name: 'Hellfire',
      description: 'Big lobbed shell, wide splash. Fires from far away.',
      cost: 90,
      damage: 15,
      fireRate: 0.4,
      range: 180,
      projectileSpeed: 200,
      family: 'heavy',
      tier: 'final',
      splash: { radius: 60, falloff: 0.4, splashMult: 0.67 },
      arc: { height: 32, travelTime: 0.7 },
      upgradesTo: ['saturation']
    },
    discus: {
      name: 'Discus',
      description: 'Disc flies out and comes back. Hits on both trips.',
      cost: 60,
      damage: 4,
      fireRate: 0.4,
      range: 150,
      projectileSpeed: 220,
      family: 'heavy',
      tier: 'mid',
      returning: true,
      upgradesTo: ['ricochet', 'shockwave']
    },
    ricochet: {
      name: 'Ricochet',
      description: 'Longer-range disc. Pierces an extra ant on the way back.',
      cost: 90,
      damage: 8,
      fireRate: 0.4,
      range: 180,
      projectileSpeed: 240,
      family: 'heavy',
      tier: 'final',
      returning: true,
      returnPierce: 1,
      upgradesTo: ['vortex']
    },
    shockwave: {
      name: 'Shockwave',
      description: 'Slow blast with a huge splash. Shakes the whole blanket.',
      cost: 110,
      damage: 50,
      fireRate: 0.15,
      range: 130,
      projectileSpeed: 180,
      family: 'heavy',
      tier: 'final',
      splash: { radius: 80, falloff: 0.3, splashMult: 0.6 },
      screenshake: { magnitude: 3, decay: 12 },
      upgradesTo: ['earthshaker']
    },
    repeater: {
      name: 'Repeater',
      description: 'Light, fast pellets. Good early, weak on armor.',
      cost: 50,
      damage: 1,
      fireRate: 4,
      range: 130,
      projectileSpeed: 280,
      family: 'quick',
      tier: 'base',
      upgradesTo: ['autocannon', 'marksman', 'spotter']
    },
    autocannon: {
      name: 'Auto-Cannon',
      description: 'Faster, harder pellets. Keeps up steady pressure.',
      cost: 50,
      damage: 2,
      fireRate: 6,
      range: 130,
      projectileSpeed: 320,
      family: 'quick',
      tier: 'mid',
      upgradesTo: ['vulcan', 'plasma_torch']
    },
    marksman: {
      name: 'Marksman',
      description: 'Long-range shot. Extra damage on unhurt ants.',
      cost: 70,
      damage: 8,
      fireRate: 0.6,
      range: 250,
      projectileSpeed: 320,
      family: 'quick',
      tier: 'mid',
      damageModifier: function (target, sourceCannon) {
        if (target.hp >= target.maxHp - 0.001) { return 1.5; }
        return 1;
      },
      upgradesTo: ['sharpshooter', 'lance']
    },
    spotter: {
      name: 'Spotter',
      description: 'Marks its target. Every cannon nearby focuses on it.',
      cost: 60,
      damage: 3,
      fireRate: 2,
      range: 200,
      projectileSpeed: 280,
      family: 'quick',
      tier: 'mid',
      reveal: true,
      upgradesTo: ['phantom', 'tesla']
    },
    vulcan: {
      name: 'Vulcan',
      description: 'Rotary gun. Spins up to a silly fire rate.',
      cost: 80,
      damage: 3,
      fireRate: 9,
      range: 140,
      projectileSpeed: 360,
      family: 'quick',
      tier: 'final',
      spoolUp: 0.5,
      upgradesTo: ['hailstorm']
    },
    plasma_torch: {
      name: 'Plasma Torch',
      description: 'Short flame cone. Burns everything in front of it.',
      cost: 90,
      damage: 0,                 // cone-DoT cannons don't fire projectiles
      fireRate: 0,
      range: 70,                 // mirrors coneDot.range so the panel circle preview matches
      projectileSpeed: 0,
      family: 'quick',
      tier: 'final',
      coneDot: {
        range: 70,
        angleDeg: 60,
        tickDamage: 10,
        statusOnTick: { kind: 'burn', magnitude: 1, duration: 2 }
      },
      upgradesTo: ['phoenix']
    },
    sharpshooter: {
      name: 'Sharpshooter',
      description: 'Very long range. Extra damage on unhurt ants.',
      cost: 100,
      damage: 25,
      fireRate: 0.5,
      range: 350,
      projectileSpeed: 360,
      family: 'quick',
      tier: 'final',
      damageModifier: function (target, sourceCannon) {
        // +50% vs full-HP, same shape as Marksman. The "ignores 50% slow penalty"
        // line in the spec describes a damage debuff that doesn't exist in Plan 2's
        // slow status (which is movement-only), so this clause is a no-op today.
        // Left as a documented hook for any future slow-debuffs-damage mechanic.
        if (target.hp >= target.maxHp - 0.001) { return 1.5; }
        return 1;
      },
      upgradesTo: ['reaper']
    },
    lance: {
      name: 'Lance',
      description: 'Steady beam that never misses. Long reach.',
      cost: 110,
      damage: 0,                 // beam cannons deal damage via beam.tickDamage
      fireRate: 0,
      range: 250,
      projectileSpeed: 0,
      family: 'quick',
      tier: 'final',
      beam: {
        tickDamage: 4,           // 4 dps continuous; integrated as tickDamage * dt
        statusOnTick: null,      // direct damage only, no status
        color: '#3acccc',        // saturated cyan, distinct from Glacier's pale ice
        lineWidth: 1.5           // tighter than Glacier's 2 — reads as a precision lance
      },
      upgradesTo: ['solar_lance']
    },
    phantom: {
      name: 'Phantom',
      description: 'Huge range, slow shots. Double damage on Queens.',
      cost: 110,
      damage: 40,
      fireRate: 0.3,
      range: 450,
      projectileSpeed: 380,
      family: 'quick',
      tier: 'final',
      damageModifier: function (target, sourceCannon) {
        // +100% vs Queen — anti-boss specialist. King is a separate kind and
        // intentionally does not benefit from this bonus.
        if (target.kind === 'queen') { return 2; }
        return 1;
      },
      upgradesTo: ['hawkeye']
    },
    tesla: {
      name: 'Tesla',
      description: 'Chain lightning. Jumps to three more ants.',
      cost: 100,
      damage: 10,
      fireRate: 1,
      range: 200,
      projectileSpeed: 0,        // instant-resolve; no projectile travel
      family: 'quick',
      tier: 'final',
      teslaArc: { jumps: 3, range: 60, falloff: 0.5 },
      upgradesTo: ['stormcaller']
    },
    twingun: {
      name: 'Twin Gun',
      description: 'Two barrels side by side. Cheap, steady chip damage.',
      cost: 75,
      damage: 2,
      fireRate: 1,
      range: 100,
      projectileSpeed: 240,
      family: 'status',
      tier: 'base',
      barrels: 2,            // multi-barrel volley logic landed in Task 13
      upgradesTo: ['triple_threat', 'frost', 'toxin']
    },
    triple_threat: {
      name: 'Triple Threat',
      description: 'Three barrels in a fan. Good for crowds.',
      cost: 70,
      damage: 2,
      fireRate: 1,
      range: 110,
      projectileSpeed: 240,
      family: 'status',
      tier: 'mid',
      barrels: 3,
      spread: 0.10,          // ~5.7 deg per step — wider than Twin Gun's default
      upgradesTo: ['buckshot', 'minigun']
    },
    buckshot: {
      name: 'Buckshot',
      description: 'Five-pellet spread. Brutal up close.',
      cost: 90,
      damage: 2,
      fireRate: 0.8,
      range: 90,
      projectileSpeed: 220,
      family: 'status',
      tier: 'final',
      pellets: 5,
      coneDeg: 50,           // 50 deg fan, ~12.5 deg between pellets
      upgradesTo: ['scattergun']
    },
    minigun: {
      name: 'Minigun',
      description: 'Sprays tiny pellets very fast. Loves a crowd.',
      cost: 110,
      damage: 1,
      fireRate: 14,
      range: 120,
      projectileSpeed: 320,
      family: 'status',
      tier: 'final',
      aimJitter: 0.05,       // ~2.9 deg max per-shot angular wobble
      upgradesTo: ['phalanx']
    },
    toxin: {
      name: 'Toxin',
      description: 'Poisons ants. Damage keeps ticking after the hit.',
      cost: 75,
      damage: 1,
      fireRate: 1,
      range: 100,
      projectileSpeed: 200,
      family: 'status',
      tier: 'mid',
      statusOnHit: { kind: 'poison', magnitude: 1, duration: 5 },   // 1 dps over 5s = 5 dmg DoT
      upgradesTo: ['plague', 'sonic_pulse']
    },
    cryo: {
      name: 'Cryo',
      description: 'Freezes ants for a moment. Frozen ants take extra damage.',
      cost: 110,
      damage: 3,
      fireRate: 1,
      range: 110,
      projectileSpeed: 220,
      family: 'status',
      tier: 'final',
      // damageAmp tag on the freeze effect: subsequent damage from any cannon
      // lands +50% while the 0.8s freeze is active. See Step 2 stamp pattern
      // and applyDamage's freeze.damageAmp read.
      statusOnHit: { kind: 'freeze', magnitude: 1, duration: 0.8, damageAmp: 1.5 },
      upgradesTo: ['absolute_zero']
    },
    plague: {
      name: 'Plague',
      description: 'Stronger poison. Spreads to nearby ants when one dies.',
      cost: 90,
      damage: 2,
      fireRate: 1,
      range: 110,
      projectileSpeed: 200,
      family: 'status',
      tier: 'final',
      // spreads tag on the poison effect: when the carrier dies, the same
      // poison (matched magnitude + remaining duration) propagates to live
      // ants within 30 px via STATUS_KINDS.poison.onTargetDeath. 2.5 dps over
      // 6s = 15 dmg per stack.
      statusOnHit: { kind: 'poison', magnitude: 2.5, duration: 6, spreads: true },
      upgradesTo: ['biohazard']
    },
    sonic_pulse: {
      name: 'Sonic Pulse',
      description: 'Sonic blast. Knocks ants back and stuns them.',
      cost: 90,
      damage: 5,
      fireRate: 0.8,
      range: 100,
      projectileSpeed: 240,
      family: 'status',
      tier: 'final',
      // statusOnHit array: knockback (one-shot 30 px backward shove) +
      // stun (0.4s zero-movement). Array form is handled by the
      // projectile-collision pathway's wrap-and-iterate.
      statusOnHit: [
        { kind: 'knockback', magnitude: 30, duration: 0 },
        { kind: 'stun', magnitude: 1, duration: 0.4 }
      ],
      upgradesTo: ['tempest']
    },
    frost: {
      name: 'Frost',
      description: 'Slows ants. Heavy cannons shatter slowed ants.',
      cost: 75,
      damage: 1,
      fireRate: 1.5,
      range: 100,
      projectileSpeed: 220,
      family: 'status',
      tier: 'mid',
      statusOnHit: { kind: 'slow', magnitude: 0.3, duration: 2 },
      upgradesTo: ['glacier', 'cryo']
    },
    glacier: {
      name: 'Glacier',
      description: 'Icy beam. Keeps one ant slowed to a crawl.',
      cost: 90,
      damage: 1,
      fireRate: 0,
      range: 130,
      family: 'status',
      tier: 'final',
      beam: {
        tickDamage: 1,
        statusOnTick: { kind: 'slow', magnitude: 0.5, duration: 1 },
        color: '#a4d4ff',
        lineWidth: 2
      },
      upgradesTo: ['blizzard']
    },

    // === Pink Rubber Family (Plan 4 — Bucket G carryover from Plan 3 §9) ===
    // Linear chain (no fork at mid). MP-L bouncing-retarget primitive (Task 6).
    // visualScale + statusOnFirstHit fields (Task 7). No T3 god-tier — the
    // family stays "casual / approachable" by design.
    pink_pea: {
      name: 'Pink Pea',
      description: 'Bouncy rubber pea. Hops to a second ant after a hit.',
      cost: 50,
      damage: 5,
      fireRate: 0.8,
      range: 130,
      projectileSpeed: 220,
      family: 'rubber',
      tier: 'base',
      bouncesRemaining: 1,
      bounceRadius: 50,
      damageDecay: 0.8,
      visualScale: 1.0,
      upgradesTo: ['bouncy_bubble']
    },
    bouncy_bubble: {
      name: 'Bouncy Bubble',
      description: 'Bigger bounce. Hops between three ants.',
      cost: 80,
      damage: 12,
      fireRate: 0.6,
      range: 160,
      projectileSpeed: 240,
      family: 'rubber',
      tier: 'mid',
      bouncesRemaining: 2,
      bounceRadius: 60,
      damageDecay: 0.8,
      visualScale: 1.5,
      upgradesTo: ['rubber_wrecking_ball']
    },
    rubber_wrecking_ball: {
      name: 'Rubber Wrecking Ball',
      description: 'Huge rubber ball. Knocks the first ant back, then bounces.',
      cost: 120,
      damage: 30,
      fireRate: 0.4,
      range: 180,
      projectileSpeed: 200,
      family: 'rubber',
      tier: 'final',
      bouncesRemaining: 4,
      bounceRadius: 70,
      damageDecay: 0.8,
      visualScale: 2.5,
      // First-hit-only knockback. Bounce targets take damage but no status
      // (prevents pinball chaos). Magnitude 30 px, duration 0 (instant
      // displacement, no follow-on tick).
      statusOnFirstHit: { kind: 'knockback', magnitude: 30, duration: 0 },
      upgradesTo: []
    },

    // === HEAVY FAMILY GOD-TIER (Plan 4 — Bucket E, Task 19) ===
    // Six T3 rows, each upgraded from one Heavy T2 final. All gated behind
    // wave-15 unlock (game.godTierUnlocked). flashScale: 1.7 produces the
    // larger muzzle flash; the gold halo is rendered automatically by
    // drawGodTierHalos when tier === 'god'.
    particle_cannon: {
      name: 'Particle Cannon',
      description: 'Railgun\'s big brother. Pierces everything, ignores armor.',
      cost: 250,
      damage: 60,
      fireRate: 0.6,
      range: 140,
      projectileSpeed: 360,
      family: 'heavy',
      tier: 'god',
      linePierce: 999,
      armorPierce: true,
      flashScale: 1.7,
      upgradesTo: []
    },
    annihilator: {
      name: 'Annihilator',
      description: 'Enormous shot plus a splash. Armor doesn\'t matter.',
      cost: 280,
      damage: 120,
      fireRate: 0.3,
      range: 130,
      projectileSpeed: 260,
      family: 'heavy',
      tier: 'god',
      armorPierce: true,
      // Base 120 dmg; splashMult 0.25 yields 30 splash damage in the 60 px ring.
      splash: { radius: 60, falloff: 0.4, splashMult: 0.25 },
      flashScale: 1.7,
      screenShakeMag: 5,
      upgradesTo: []
    },
    saturation: {
      name: 'Saturation',
      description: 'Three shells per volley. Rains splash over a wide area.',
      cost: 260,
      damage: 25,
      fireRate: 0.5,
      range: 200,
      projectileSpeed: 220,
      family: 'heavy',
      tier: 'god',
      barrels: 3,
      // 0.05s stagger between the three shells so the volley reads as a
      // rolling rain instead of a simultaneous triple-fire. fireProjectile
      // delays each barrel's spawn-active time and arc start by i*stagger.
      barrelStagger: 0.05,
      arc: { height: 32, travelTime: 0.7 },
      splash: { radius: 80, falloff: 0.4, splashMult: 0.8 },
      flashScale: 1.7,
      upgradesTo: []
    },
    cluster_strike: {
      name: 'Cluster Strike',
      description: 'Missile that splits into three. Each one homes in.',
      cost: 280,
      damage: 30,
      fireRate: 0.5,
      range: 220,
      projectileSpeed: 240,
      family: 'heavy',
      tier: 'god',
      homing: { turnRate: 5.0 },
      // MP-M mid-flight split. Parent missile splits at 50% of distance to
      // its initial target; three homing children spread across a 60 deg arc
      // and re-acquire targets on spawn.
      splitFraction: 0.5,
      splitCount: 3,
      splash: { radius: 30, falloff: 0.4, splashMult: 0.4 },
      flashScale: 1.7,
      upgradesTo: []
    },
    vortex: {
      name: 'Vortex',
      description: 'Two discs at once. Each pierces on the way back.',
      cost: 250,
      damage: 12,
      fireRate: 0.4,
      range: 200,
      projectileSpeed: 240,
      family: 'heavy',
      tier: 'god',
      barrels: 2,
      returning: true,
      returnPierce: 2,
      flashScale: 1.7,
      upgradesTo: []
    },
    earthshaker: {
      name: 'Earthshaker',
      description: 'Giant slow slam. Stuns every ant in a wide ring.',
      cost: 300,
      damage: 80,
      fireRate: 0.12,
      range: 140,
      projectileSpeed: 180,
      family: 'heavy',
      tier: 'god',
      // 120 px splash at 62.5% mult yields 50 splash damage; stun radiates to
      // every ant in the ring (splashStatus: true gates the broadcast).
      splash: { radius: 120, falloff: 0.4, splashMult: 0.625 },
      splashStatus: true,
      statusOnHit: { kind: 'stun', magnitude: 1, duration: 0.6 },
      flashScale: 1.7,
      screenShakeMag: 8,
      upgradesTo: []
    },

    // === QUICK FAMILY GOD-TIER (Plan 4 — Bucket E, Task 20) ===
    // Six T3 rows for the Quick lineage. Phoenix uses the burn.onExpire spread
    // hook from Task 4. Reaper and Hawkeye carry damageModifier functions that
    // ride on top of any sourceCannon-keyed multiplier in applyDamage.
    hailstorm: {
      name: 'Hailstorm',
      description: 'Vulcan\'s big brother. Spins up to a storm of shots.',
      cost: 250,
      damage: 4,
      fireRate: 14,
      range: 160,
      projectileSpeed: 360,
      family: 'quick',
      tier: 'god',
      spoolUp: 0.5,
      aimJitter: 0,
      flashScale: 1.7,
      upgradesTo: []
    },
    phoenix: {
      name: 'Phoenix',
      description: 'Wide flame cone. Burning ants spread fire as it fades.',
      cost: 260,
      damage: 0,
      fireRate: 0,
      range: 80,
      projectileSpeed: 0,
      family: 'quick',
      tier: 'god',
      coneDot: {
        range: 80,
        angleDeg: 70,
        tickDamage: 12,
        // spreads on burn — Task 4's burn.onExpire hook fires the Phoenix
        // chain when the burn duration runs out, not on death.
        statusOnTick: { kind: 'burn', magnitude: 3, duration: 3, spreads: true }
      },
      flashScale: 1.7,
      upgradesTo: []
    },
    reaper: {
      name: 'Reaper',
      description: 'One-shots Workers and Scouts. Huge range.',
      cost: 280,
      damage: 60,
      fireRate: 0.5,
      range: 380,
      projectileSpeed: 360,
      family: 'quick',
      tier: 'god',
      damageModifier: function (target, sourceCannon) {
        // Instakill Worker and Scout. +100% vs full-HP everything else.
        // Nested ifs avoid the inline-script and-operator (CLAUDE.md note).
        if (target.kind === 'worker') { return 999; }
        if (target.kind === 'scout') { return 999; }
        if (target.hp >= target.maxHp - 0.001) { return 2; }
        return 1;
      },
      flashScale: 1.7,
      upgradesTo: []
    },
    solar_lance: {
      name: 'Solar Lance',
      description: 'Golden beam, pierces two ants. Very long reach.',
      cost: 290,
      damage: 0,
      fireRate: 0,
      range: 280,
      projectileSpeed: 0,
      family: 'quick',
      tier: 'god',
      // beam: object form so Plan 3's existing beam pathway picks it up.
      // beamPierce reads from the def root (Task 12 — MP-O cylinder math).
      beam: {
        tickDamage: 8,
        statusOnTick: null,
        color: '#ffd75e',
        lineWidth: 3
      },
      beamPierce: 2,
      flashScale: 1.7,
      upgradesTo: []
    },
    hawkeye: {
      name: 'Hawkeye',
      description: 'Shoots across the whole blanket. Triple damage on Queens.',
      cost: 300,
      damage: 80,
      fireRate: 0.3,
      range: 600,
      projectileSpeed: 400,
      family: 'quick',
      tier: 'god',
      // minRange documented for future "ranger does not fire on adjacent ants"
      // hooks — currently a no-op since no targeting code reads it.
      minRange: 0,
      damageModifier: function (target, sourceCannon) {
        if (target.kind === 'queen') { return 3; }
        return 1;
      },
      flashScale: 1.7,
      upgradesTo: []
    },
    stormcaller: {
      name: 'Stormcaller',
      description: 'Bigger chain lightning. Six jumps at full damage.',
      cost: 280,
      damage: 18,
      fireRate: 1,
      range: 240,
      projectileSpeed: 360,
      family: 'quick',
      tier: 'god',
      // Plan 4 plan literally said falloff: 1, but the Tesla math is
      // dmg * (1 - falloff), so falloff: 1 would deal zero on every hop.
      // Spec intent is "no falloff, full damage every jump" = falloff: 0.
      teslaArc: { jumps: 6, range: 80, falloff: 0 },
      flashScale: 1.7,
      upgradesTo: []
    },

    // === STATUS FAMILY GOD-TIER (Plan 4 — Bucket E, Task 21) ===
    // Six T3 rows for the status lineage. Blizzard uses MP-N (Task
    // 11). Absolute Zero and Tempest carry splashStatus: true with
    // splashMult: 0 — the splash exists only to broadcast the status, not to
    // damage. Biohazard's spreadRadius reads in poison.onTargetDeath (below).
    scattergun: {
      name: 'Scattergun',
      description: 'Eight-pellet blast. Each pellet pierces one ant.',
      cost: 260,
      damage: 3,
      fireRate: 0.7,
      range: 100,
      projectileSpeed: 320,
      family: 'status',
      tier: 'god',
      pellets: 8,
      // coneDeg is the existing pellet-fan field; spreadDeg is the spec name.
      coneDeg: 70,
      linePierce: 1,
      flashScale: 1.7,
      upgradesTo: []
    },
    phalanx: {
      name: 'Phalanx',
      description: 'Three barrels, absurd fire rate. Shreds swarms.',
      cost: 280,
      damage: 2,
      fireRate: 18,
      range: 140,
      projectileSpeed: 380,
      family: 'status',
      tier: 'god',
      barrels: 3,
      aimJitter: 0,
      flashScale: 1.7,
      upgradesTo: []
    },
    blizzard: {
      name: 'Blizzard',
      description: 'Snowstorm aura. Slows and hurts every ant nearby.',
      cost: 260,
      damage: 0,
      fireRate: 0,
      range: 150,
      projectileSpeed: 0,
      family: 'status',
      tier: 'god',
      // MP-N persistent radial aura (Task 11). damagePerTick is per render
      // tick at 60fps; updateCannons scales by dt. slow expires after
      // slowExpiry seconds without re-application; freezeRandomEvery picks
      // one ant in radius every N seconds and freezes for freezeDuration.
      aura: {
        radius: 150,
        damagePerTick: 60,
        slow: 0.5,
        slowExpiry: 0.15,
        freezeRandomEvery: 5,
        freezeDuration: 2
      },
      flashScale: 1.7,
      upgradesTo: []
    },
    absolute_zero: {
      name: 'Absolute Zero',
      description: 'Freezing blast. Freezes every ant in a small ring.',
      cost: 280,
      damage: 6,
      fireRate: 1,
      range: 130,
      projectileSpeed: 280,
      family: 'status',
      tier: 'god',
      // 1.5x damageAmp on the freeze tag — Plan 3's freeze.damageAmp pathway
      // multiplies subsequent damage during the freeze window.
      statusOnHit: { kind: 'freeze', magnitude: 1, duration: 1.5, damageAmp: 1.5 },
      // splash for splashStatus broadcast only — splashMult 0 yields zero
      // damage to bystanders, just propagates the freeze.
      splash: { radius: 40, falloff: 0, splashMult: 0 },
      splashStatus: true,
      flashScale: 1.7,
      upgradesTo: []
    },
    biohazard: {
      name: 'Biohazard',
      description: 'Toxic shot. Poisoned ants take extra damage and spread it.',
      cost: 260,
      damage: 4,
      fireRate: 1,
      range: 130,
      projectileSpeed: 260,
      family: 'status',
      tier: 'god',
      // 30 dmg over 8s = 3.75 dps. spreadRadius 50 propagates on death;
      // damageAmp 1.25 makes the infected ant take 25% extra from any damage
      // source (read in applyDamage's poison.damageAmp branch — Task 3).
      statusOnHit: { kind: 'poison', magnitude: 30 / 8, duration: 8, spreads: true, spreadRadius: 50, damageAmp: 1.25 },
      flashScale: 1.7,
      upgradesTo: []
    },
    tempest: {
      name: 'Tempest',
      description: 'Storm shot. Knocks back and stuns a whole cluster.',
      cost: 260,
      damage: 10,
      fireRate: 0.7,
      range: 110,
      projectileSpeed: 280,
      family: 'status',
      tier: 'god',
      // splash for splashStatus broadcast only — splashMult 0, no damage.
      splash: { radius: 50, falloff: 0, splashMult: 0 },
      splashStatus: true,
      // statusOnHit array — Plan 3's projectile-collision pathway wraps and
      // iterates. Knockback 50 px backward + 0.8s stun.
      statusOnHit: [
        { kind: 'knockback', magnitude: 50, duration: 0 },
        { kind: 'stun', magnitude: 1, duration: 0.8 }
      ],
      flashScale: 1.7,
      upgradesTo: []
    }
  };

  // PNG cannon-turret preloader. Walks CANNON_KINDS and creates an Image
  // object for every cannon kind that opts in via `spriteFile:`. Loads run
  // asynchronously and never block the game; cannons without a spriteFile
  // entry are skipped, and any cannon whose image is still loading or
  // failed (404) falls through to the procedural sprite in drawCannonTurrets.
  game.sprites.cannonTurretPNG = {};
  for (var __spritePreloadKey in CANNON_KINDS) {
    var __spritePreloadFile = CANNON_KINDS[__spritePreloadKey].spriteFile;
    if (__spritePreloadFile) {
      var __spritePreloadImg = new Image();
      __spritePreloadImg.src = THEME_URI + '/assets/sprites/cannons/' + __spritePreloadFile + '?v=' + SPRITE_VER;
      game.sprites.cannonTurretPNG[__spritePreloadKey] = __spritePreloadImg;
    }
  }

  function cannonAt(x, y) {
    for (var i = 0; i < game.cannons.length; i++) {
      var c = game.cannons[i];
      var dx = c.x - x;
      var dy = c.y - y;
      if (dx * dx + dy * dy < 16 * 16) {
        return c;
      }
    }
    return null;
  }

  function canPlaceCannon(x, y) {
    if (x < 16) { return false; }
    if (x > PLAYFIELD_W - 16) { return false; }
    if (y < HUD_H + 16) { return false; }
    if (y > HEIGHT - 16) { return false; }
    var dxCake = x - game.cake.x;
    var dyCake = y - game.cake.y;
    if (dxCake * dxCake + dyCake * dyCake < 32 * 32) { return false; }
    var dxAnt = x - game.anthill.x;
    var dyAnt = y - game.anthill.y;
    if (dxAnt * dxAnt + dyAnt * dyAnt < 24 * 24) { return false; }
    if (cannonAt(x, y)) { return false; }
    return true;
  }

  function placeCannon(kindKey, x, y) {
    var def = CANNON_KINDS[kindKey];
    if (!def) { return false; }
    if (game.gold < def.cost) { return false; }
    if (!canPlaceCannon(x, y)) { return false; }
    game.gold -= def.cost;
    game.cannons.push(buildCannon(kindKey, x, y));
    return true;
  }

  // Fresh cannon record. Shared by placeCannon and restoreRun so a restored
  // cannon starts with exactly the same derived fields as a placed one.
  function buildCannon(kindKey, x, y) {
    return {
      kind: kindKey,
      kindHistory: [kindKey],   // base kind included per spec SYS-2
      x: x,
      y: y,
      heading: 0,
      cooldown: 0,
      target: null,
      kills: 0,
      disabled: false,
      disabledUntil: 0,
      targetingMode: 'closest'  // Plan 7 — per-cannon targeting dial
    };
  }

  function findTarget(cannon) {
    var def = CANNON_KINDS[cannon.kind];
    var rangeSq = def.range * def.range;
    var mode = cannon.targetingMode;
    if (!mode) { mode = 'closest'; }

    // Filter pass: gather candidates with cached distSq, flag if any revealed.
    // Avoids and-operator chains by using nested ifs (CLAUDE.md inline-script gotcha).
    var candidates = [];
    var anyRevealed = false;
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      if (a.dead) { continue; }
      if (a.intangible) { continue; }
      var dx = a.x - cannon.x;
      var dy = a.y - cannon.y;
      var distSq = dx * dx + dy * dy;
      if (distSq > rangeSq) { continue; }
      candidates.push({ ant: a, distSq: distSq });
      if (a.isRevealed) { anyRevealed = true; }
    }
    if (candidates.length === 0) { return null; }

    // Stealth filter: if any revealed, keep only revealed ants. Preserves the
    // existing Hawkeye apex-priority while letting the mode pick within that pool.
    var pool = candidates;
    if (anyRevealed) {
      pool = [];
      for (var j = 0; j < candidates.length; j++) {
        if (candidates[j].ant.isRevealed) { pool.push(candidates[j]); }
      }
    }

    // Pick pass: apply the targeting mode to the active pool.
    var best = pool[0];
    for (var k = 1; k < pool.length; k++) {
      var cur = pool[k];
      if (mode === 'closest') {
        if (cur.distSq < best.distSq) { best = cur; }
      } else if (mode === 'strongest') {
        if (cur.ant.hp > best.ant.hp) { best = cur; }
      } else if (mode === 'weakest') {
        if (cur.ant.hp < best.ant.hp) { best = cur; }
      } else if (mode === 'carrying') {
        // Ants running off with a slice first, then the closest of those.
        if (cur.ant.carrying !== best.ant.carrying) {
          if (cur.ant.carrying) { best = cur; }
        } else if (cur.distSq < best.distSq) {
          best = cur;
        }
      } else {
        // first: the ant closest to the cake.
        var ccdx = cur.ant.x - game.cake.x;
        var ccdy = cur.ant.y - game.cake.y;
        var bcdx = best.ant.x - game.cake.x;
        var bcdy = best.ant.y - game.cake.y;
        if (ccdx * ccdx + ccdy * ccdy < bcdx * bcdx + bcdy * bcdy) { best = cur; }
      }
    }
    return best.ant;
  }

  // Cone-DoT primitive (Plan 3 — Plasma Torch and future cone-DoT cannons).
  // Plasma Torch doesn't fire projectiles; instead it emits a continuous flame
  // cone in the direction of cannon.heading. Every ant inside the cone takes
  // tickDamage * dt every frame and (optionally) refreshes a status. The angle
  // delta is normalized into [-PI, PI] before the half-angle compare so the
  // wrap-around at the +/- PI boundary doesn't drop targets.
  function applyConeDoT(cannon, def, dt) {
    var range = def.coneDot.range;
    var halfAngle = (def.coneDot.angleDeg * Math.PI / 180) / 2;
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      if (a.dead) { continue; }
      if (a.intangible) { continue; }
      var dx = a.x - cannon.x;
      var dy = a.y - cannon.y;
      var dist = Math.sqrt(dx * dx + dy * dy);
      if (dist > range) { continue; }
      var angleToAnt = Math.atan2(dy, dx);
      var delta = angleToAnt - cannon.heading;
      while (delta > Math.PI) { delta = delta - Math.PI * 2; }
      while (delta < -Math.PI) { delta = delta + Math.PI * 2; }
      if (Math.abs(delta) > halfAngle) { continue; }
      applyDamage(a, def.coneDot.tickDamage * dt, !!def.armorPierce, cannon);
      if (def.coneDot.statusOnTick) {
        var s = def.coneDot.statusOnTick;
        applyStatus(a, s.kind, s.magnitude, s.duration, 'cone', cannon);
      }
      if (a.hp <= 0) { killAnt(a, cannon); }
    }
  }

  function updateCannons(dt) {
    // Reveal pre-pass: clear stale isRevealed flags so each tick recomputes
    // them from the current Spotter positions. Carrier ants in any Spotter's
    // range get isRevealed = true below; findTarget then prefers them.
    for (var ai = 0; ai < game.ants.length; ai++) {
      game.ants[ai].isRevealed = false;
    }
    for (var i = 0; i < game.cannons.length; i++) {
      var c = game.cannons[i];
      // Cannon-disabled gate (Task 20 Bomber AoE will set disabled/disabledUntil).
      if (c.disabled) {
        if (game.time >= c.disabledUntil) { c.disabled = false; }
      }
      if (c.disabled) {
        c.beamTarget = null;
        continue;
      }
      var def = CANNON_KINDS[c.kind];

      // Spotter reveal sweep: any Carrier in range gets isRevealed = true,
      // which biases every cannon's findTarget toward it for this tick.
      if (def.reveal) {
        for (var ri = 0; ri < game.ants.length; ri++) {
          var ra = game.ants[ri];
          if (ra.dead) { continue; }
          if (!ra.carrying) { continue; }
          var rdx = ra.x - c.x;
          var rdy = ra.y - c.y;
          if (rdx * rdx + rdy * rdy <= def.range * def.range) {
            ra.isRevealed = true;
          }
        }
      }

      // Beam cannons (Glacier, Lance, and any future beam emitter): per-tick
      // damage + status refresh, no projectile fired. Status refresh sets
      // duration to ~1s each tick so the status naturally lingers ~1s after
      // disconnect. Glacier emits status with light damage; Lance deals direct
      // damage with no status; the same code path supports both via def.beam.
      if (def.beam) {
        var btarget = findTarget(c);
        c.target = btarget;
        if (!btarget) {
          c.beamTarget = null;
          continue;
        }
        c.heading = angleTowards(c.x, c.y, btarget.x, btarget.y);
        if (def.beam.tickDamage > 0) {
          applyDamage(btarget, def.beam.tickDamage * dt, !!def.armorPierce, c);
          if (btarget.hp <= 0) {
            killAnt(btarget, c);
          }
        }
        if (def.beam.statusOnTick) {
          var sb = def.beam.statusOnTick;
          applyStatus(btarget, sb.kind, sb.magnitude, sb.duration, 'beam', c);
        }
        // Plan 4 — MP-O beam line-pierce (Solar Lance). Extends the standard
        // beam tick: after the primary damage lands, damage up to beamPierce
        // additional ants whose centers fall within a thin cylinder along
        // the cannon→target line. Cylinder half-width 6 px; ants are projected
        // onto the line and accepted if 0 ≤ proj ≤ |cannon→target| and
        // |perpendicular| ≤ 6. statusOnTick is applied to the primary only —
        // bystanders take damage but no status (mirrors statusOnFirstHit
        // intent for projectile cannons).
        if (def.beamPierce) {
          var pierceCount = def.beamPierce;
          var pierceHalfWidth = 6;
          var bdx = btarget.x - c.x;
          var bdy = btarget.y - c.y;
          var bdLen = Math.sqrt(bdx * bdx + bdy * bdy);
          if (bdLen > 0) {
            var bnx = bdx / bdLen;
            var bny = bdy / bdLen;
            var hits = 0;
            for (var bpi = 0; bpi < game.ants.length; bpi++) {
              var bpa = game.ants[bpi];
              if (bpa === btarget) { continue; }
              if (bpa.dead) { continue; }
              if (bpa.intangible) { continue; }
              var bpax = bpa.x - c.x;
              var bpay = bpa.y - c.y;
              var proj = bpax * bnx + bpay * bny;
              if (proj < 0) { continue; }
              if (proj > bdLen) { continue; }
              var perp = bpax * (-bny) + bpay * bnx;
              if (perp < 0) { perp = -perp; }
              if (perp > pierceHalfWidth) { continue; }
              applyDamage(bpa, def.beam.tickDamage * dt, !!def.armorPierce, c);
              if (bpa.hp <= 0) { killAnt(bpa, c); }
              hits = hits + 1;
              if (hits >= pierceCount) { break; }
            }
          }
        }
        c.beamTarget = btarget;
        continue;
      }

      // Plan 4 — MP-N persistent radial aura (Blizzard). The cannon IS the
      // AoE: no projectile, no fire-rate cooldown, no target acquisition.
      // Three independent timers run in parallel:
      //   1. Per-tick AoE damage to every ant within auraRadius.
      //   2. Per-tick slow re-application with a 0.15s expiry — re-applied
      //      every tick to maintain the slow while ants stay in range. The
      //      0.15s buffer (9 frames at 60Hz, 4-5 frames at 30Hz) prevents
      //      stuttering during heavy waves.
      //   3. A 5-second freeze cycle picks one uniform-random ant in radius
      //      and freezes it for 2s.
      if (def.aura) {
        var au = def.aura;
        for (var aai = 0; aai < game.ants.length; aai++) {
          var aaa = game.ants[aai];
          if (aaa.dead) { continue; }
          if (aaa.intangible) { continue; }
          var adx = aaa.x - c.x;
          var ady = aaa.y - c.y;
          if (adx * adx + ady * ady > au.radius * au.radius) { continue; }
          applyDamage(aaa, au.damagePerTick * dt, false, c);
          applyStatus(aaa, 'slow', au.slow, au.slowExpiry, 'aura');
          if (aaa.hp <= 0) { killAnt(aaa, c); }
        }
        if (!c.lastFreezeAt) { c.lastFreezeAt = 0; }
        if (game.time - c.lastFreezeAt >= au.freezeRandomEvery) {
          var inRadius = [];
          for (var fai = 0; fai < game.ants.length; fai++) {
            var fa = game.ants[fai];
            if (fa.dead) { continue; }
            if (fa.intangible) { continue; }
            var fdx = fa.x - c.x;
            var fdy = fa.y - c.y;
            if (fdx * fdx + fdy * fdy > au.radius * au.radius) { continue; }
            inRadius.push(fa);
          }
          if (inRadius.length > 0) {
            var pick = inRadius[Math.floor(Math.random() * inRadius.length)];
            applyStatus(pick, 'freeze', 1, au.freezeDuration, 'aura-freeze');
          }
          c.lastFreezeAt = game.time;
        }
        continue;
      }

      // Cone-DoT cannons (Plan 3 — Plasma Torch). Continuous flame cone in
      // the direction of the current target; no projectile, no cooldown.
      // firingCone gates the orange overlay in drawConeDots().
      if (def.coneDot) {
        var ct = findTarget(c);
        c.target = ct;
        if (ct) {
          c.heading = Math.atan2(ct.y - c.y, ct.x - c.x);
          applyConeDoT(c, def, dt);
          c.firingCone = true;
        } else {
          c.firingCone = false;
        }
        continue;
      }

      var target = findTarget(c);
      c.target = target;
      if (!target) {
        // Spool-down for cannons with spoolUp (Vulcan).
        if (def.spoolUp) {
          var st = c.spoolT;
          if (!st) { st = 0; }
          st = st - dt / def.spoolUp;
          if (st < 0) { st = 0; }
          c.spoolT = st;
        }
        continue;
      }
      c.heading = angleTowards(c.x, c.y, target.x, target.y);
      var effRate = def.fireRate;
      if (def.spoolUp) {
        var st2 = c.spoolT;
        if (!st2) { st2 = 0; }
        st2 = st2 + dt / def.spoolUp;
        if (st2 > 1) { st2 = 1; }
        c.spoolT = st2;
        effRate = def.fireRate * (0.3 + 0.7 * st2);
      }
      c.cooldown = c.cooldown - dt;
      if (c.cooldown <= 0) {
        fireProjectile(c, target);
        c.cooldown = c.cooldown + 1 / effRate;
      }
    }
  }

  // === PROJECTILES ===
  function fireProjectile(cannon, target) {
    var def = CANNON_KINDS[cannon.kind];

    // Plan 4 — stamp the fire moment so drawCannonTurrets can render a brief
    // muzzle flash for ~0.08s after firing. flashScale on def scales the
    // flash radius (god-tier rows set 1.7 to read as a heavier discharge).
    cannon.flashFx = game.fxTime;

    // Tesla arc-jump primitive (Plan 3 — MP-J). Resolves the primary hit and
    // up to def.teslaArc.jumps chain hits in one tick: each jump finds the
    // nearest non-hit live ant within def.teslaArc.range of the previous hit
    // and applies dmg * (1 - falloff) per hop. The chain "feel" is sold by
    // the lightning polyline (rendered for ~0.15s by drawLightningBolts), not
    // by a moving particle. Skips the projectile-spawn loop on return.
    if (def.teslaArc) {
      var primary = target;
      var hits = [primary];
      var current = primary;
      var jumps = def.teslaArc.jumps;
      var jumpRange = def.teslaArc.range;
      var falloff = def.teslaArc.falloff;
      var dmg = def.damage;

      applyDamage(primary, dmg, !!def.armorPierce, cannon);
      if (primary.hp <= 0) { killAnt(primary, cannon); }

      for (var j = 0; j < jumps; j++) {
        var nearest = null;
        var bestDist = Infinity;
        for (var ai = 0; ai < game.ants.length; ai++) {
          var aa = game.ants[ai];
          if (aa.dead) { continue; }
          if (aa.intangible) { continue; }
          if (hits.indexOf(aa) !== -1) { continue; }
          var jdx = aa.x - current.x;
          var jdy = aa.y - current.y;
          var jd2 = jdx * jdx + jdy * jdy;
          if (jd2 > jumpRange * jumpRange) { continue; }
          if (jd2 < bestDist) { bestDist = jd2; nearest = aa; }
        }
        if (!nearest) { break; }
        dmg = dmg * (1 - falloff);
        applyDamage(nearest, dmg, !!def.armorPierce, cannon);
        if (nearest.hp <= 0) { killAnt(nearest, cannon); }
        hits.push(nearest);
        current = nearest;
      }

      var bolt = { points: [{ x: cannon.x, y: cannon.y }], expiresAt: game.fxTime + 0.15 };
      for (var hi = 0; hi < hits.length; hi++) {
        bolt.points.push({ x: hits[hi].x, y: hits[hi].y });
      }
      game.lightningBolts.push(bolt);
      return;
    }

    var heading = angleTowards(cannon.x, cannon.y, target.x, target.y);
    var n = def.barrels;
    if (!n) { n = 1; }
    var statusOnHit = def.statusOnHit;
    if (!statusOnHit) { statusOnHit = null; }

    // Multi-pellet cone primitive (Plan 3 — MP-E, Buckshot). N independent
    // pellets fan across def.coneDeg degrees centered on the heading; each
    // pellet is its own projectile with the cannon's per-shot damage. Wider
    // and more independent than Triple Threat's per-step volley spread.
    if (def.pellets) {
      var pellets = def.pellets;
      var coneRad = def.coneDeg * Math.PI / 180;
      for (var pi = 0; pi < pellets; pi++) {
        var fanT;
        if (pellets === 1) {
          fanT = 0;
        } else {
          fanT = pi / (pellets - 1) - 0.5;        // -0.5..0.5 inclusive
        }
        var pAngle = heading + fanT * coneRad;
        game.projectiles.push({
          x: cannon.x + Math.cos(pAngle) * 12,
          y: cannon.y + Math.sin(pAngle) * 12,
          vx: Math.cos(pAngle) * def.projectileSpeed,
          vy: Math.sin(pAngle) * def.projectileSpeed,
          damage: def.damage,
          linePierce: def.linePierce ? def.linePierce : 0,
          armorPierce: !!def.armorPierce,
          pierceLeft: def.linePierce ? def.linePierce : 0,
          statusOnHit: statusOnHit,
          sourceCannon: cannon,
          _sawAnts: [],
          life: 1.0,
          arcing: false,
          arcStartedAt: 0,
          arcDuration: 0,
          arcHeight: 0,
          returning: false,
          // Plan 4 — MP-L bouncing retarget. Pellets currently never bounce
          // (no pellet cannon also bounces), but the fields must exist on the
          // projectile literal so updateProjectiles' single collision body
          // can read them uniformly.
          bouncesRemaining: 0,
          bounceRadius: 0,
          damageDecay: 1,
          initialHit: true,
          // Plan 4 — visualScale and statusOnFirstHit (Bucket G fields).
          // Pellets keep defaults (1.0 scale, no first-hit-only status).
          visualScale: def.visualScale ? def.visualScale : 1.0,
          statusOnFirstHit: def.statusOnFirstHit ? def.statusOnFirstHit : null,
          // Plan 4 — MP-M mid-flight split. Pellets don't split; fields exist
          // for uniform read in the per-tick split branch.
          splitFraction: 0,
          splitCount: 0,
          splitOriginX: 0,
          splitOriginY: 0,
          splitTotalDist: 0,
          _splitFired: false,
          dormantUntil: 0
        });
      }
      return;
    }

    var spread = def.spread ? def.spread : 0.06;   // per-step radian offset; ~3.4 deg default (Twin Gun)
    var stagger = def.barrelStagger ? def.barrelStagger : 0;
    for (var i = 0; i < n; i++) {
      var off;
      if (n === 1) {
        off = 0;
      } else {
        off = (i - (n - 1) / 2) * spread;
      }
      var h = heading + off;
      var dormantUntil = stagger > 0 ? game.time + i * stagger : 0;
      // Aim jitter (Plan 3 — MP-F, Minigun). Random per-shot angular offset
      // turns a high-fire-rate single-barrel into a spray cone — no formal
      // spread param, just constant inaccuracy. Multiplied by 2 to span the
      // full ±aimJitter range from a single random sample.
      if (def.aimJitter) {
        h = h + (Math.random() * 2 - 1) * def.aimJitter;
      }
      var p = {
        x: cannon.x + Math.cos(h) * 12,
        y: cannon.y + Math.sin(h) * 12,
        vx: Math.cos(h) * def.projectileSpeed,
        vy: Math.sin(h) * def.projectileSpeed,
        damage: def.damage,
        linePierce: def.linePierce ? def.linePierce : 0,
        armorPierce: !!def.armorPierce,
        pierceLeft: def.linePierce ? def.linePierce : 0,
        statusOnHit: statusOnHit,
        sourceCannon: cannon,
        _sawAnts: [],
        life: 1.5,
        // Arc visual primitive (Plan 3 — Mortar, future Howitzer-line). Render
        // applies `arcOffset = -sin(t * PI) * arcHeight` to draw position only;
        // collision still resolves on the linear (p.x, p.y).
        arcing: !!def.arc,
        arcStartedAt: 0,
        arcDuration: 0,
        arcHeight: 0,
        // Returning projectile primitive (Plan 3 — Discus, future Boomerang).
        // After the first hit the projectile flips velocity toward originX/originY
        // and can hit additional ants on the return leg before despawning at origin.
        returning: !!def.returning,
        phase: 'out',
        originX: cannon.x,
        originY: cannon.y,
        // Homing primitive (Plan 3 — Patriot). Each tick updateProjectiles
        // steers the velocity vector toward p.target by up to homingTurnRate*dt
        // radians. Re-acquires nearest ant if the original target is dead/null.
        homing: !!def.homing,
        homingTurnRate: 0,
        target: target,
        // Plan 4 — MP-L bouncing retarget (Pink Rubber Family). On hit, search
        // for nearest non-_sawAnts live ant within bounceRadius; if found,
        // redirect velocity, decrement bouncesRemaining, multiply damage by
        // damageDecay, do not despawn. initialHit flips to false after first
        // impact so statusOnFirstHit applies only to the original target
        // (Wrecking Ball's 30 px knockback).
        bouncesRemaining: def.bouncesRemaining ? def.bouncesRemaining : 0,
        bounceRadius: def.bounceRadius ? def.bounceRadius : 0,
        damageDecay: def.damageDecay ? def.damageDecay : 1,
        initialHit: true,
        // Plan 4 — visualScale scales the projectile sprite at render time
        // (Pink Pea 1.0, Bouncy Bubble 1.5, Wrecking Ball 2.5). Default 1.0
        // for all existing cannons, so no migration needed.
        visualScale: def.visualScale ? def.visualScale : 1.0,
        // Plan 4 — statusOnFirstHit applies a status to the first impact
        // only, not bounce targets (Wrecking Ball's knockback). The general
        // statusOnHit array continues to fire on every bounce.
        statusOnFirstHit: def.statusOnFirstHit ? def.statusOnFirstHit : null,
        // Plan 4 — MP-M mid-flight split (Cluster Strike). When the parent
        // missile travels splitTotalDist from origin, despawn and spawn
        // splitCount homing children. splitTotalDist is computed below from
        // the original target distance × splitFraction.
        splitFraction: def.splitFraction ? def.splitFraction : 0,
        splitCount: def.splitCount ? def.splitCount : 0,
        splitOriginX: cannon.x,
        splitOriginY: cannon.y,
        splitTotalDist: 0,
        _splitFired: false,
        // Plan 4 — barrelStagger (Saturation). dormantUntil > game.time
        // freezes the projectile at its spawn position until activation;
        // updateProjectiles and drawProjectiles both skip dormant shells.
        dormantUntil: dormantUntil
      };
      if (def.arc) {
        // Shift the arc start with the dormancy so a shell that activates
        // 0.05s late also lands 0.05s late — the rolling-rain feel needs the
        // arc clock to track dormancy, not real time.
        p.arcStartedAt = dormantUntil > 0 ? dormantUntil : game.time;
        p.arcDuration = def.arc.travelTime;
        p.arcHeight = def.arc.height;
      }
      if (def.homing) {
        p.homingTurnRate = def.homing.turnRate;
      }
      // Plan 4 — MP-M splitTotalDist computed from current cannon position
      // to original target. Children spawn when the parent has traveled
      // this distance from origin.
      if (def.splitFraction) {
        var sdx = target.x - cannon.x;
        var sdy = target.y - cannon.y;
        var sd = Math.sqrt(sdx * sdx + sdy * sdy);
        p.splitTotalDist = sd * def.splitFraction;
      }
      game.projectiles.push(p);
    }
  }

  // Centralized damage application. Soldier armor (Task 16) hooks here:
  // armored ants will reduce damage unless `armorPierce` is set. For now this is
  // a thin wrapper so the hook point exists.
  // Plan 3: optional `sourceCannon` lets the per-cannon `damageModifier` hook
  // multiply damage (Marksman, Sharpshooter, Phantom). Falls through cleanly
  // when sourceCannon is omitted (existing call sites stay 3-arg compatible).
  // Plan 6: optional `isDirectHit` (boolean) marks projectile-direct collisions.
  // Only direct hits trigger the heavy-family Brittle predicate (3x on frozen).
  function applyDamage(ant, amount, armorPierce, sourceCannon, isDirectHit) {
    var k = ANT_KINDS[ant.kind];
    var dmg = amount;
    if (sourceCannon) {
      var sk = CANNON_KINDS[sourceCannon.kind];
      if (sk.damageModifier) {
        dmg = dmg * sk.damageModifier(ant, sourceCannon);
      }
    }
    // Freeze damage amplification (Plan 3 — Cryo). When a freeze effect was
    // applied with a damageAmp tag (stamped on after applyStatus by the
    // projectile-collision pathway), every subsequent damage tick lands +N%
    // until the freeze expires. Independent of which cannon does the damage —
    // any follow-up hit benefits. Nested ifs to avoid the inline-script and-operator.
    if (ant.effects) {
      if (ant.effects.freeze) {
        var amp = ant.effects.freeze.damageAmp;
        if (amp) { dmg = dmg * amp; }
      }
    }
    // Plan 4: Biohazard +25% damage amp via poison.damageAmp. Mirror of the
    // freeze.damageAmp pattern above. Stamped onto the live poison effect by
    // the tag-after-apply path in updateProjectiles. Toxin and Plague leave
    // damageAmp unset (default 1, no amp). Source-independent — any cannon
    // firing on a Biohazard-infected target deals +25%.
    if (ant.effects) {
      if (ant.effects.poison) {
        var pamp = ant.effects.poison.damageAmp;
        if (pamp) { dmg = dmg * pamp; }
      }
    }
    // Plan 6: Brittle synergy. A heavy-family direct hit on a frozen ant deals
    // 2x damage on top of any existing freeze.damageAmp (composes
    // multiplicatively) and consumes the freeze. On a slowed ant (Frost,
    // Glacier, Blizzard) it deals 1.5x and consumes the slow, so the Frost
    // plus heavy combo the field guide describes works from the early game
    // instead of needing a Cryo. Either way a shatter burst and a callout
    // show it happened. Direct hit only — splash, beam, aura, and chain-link
    // calls leave isDirectHit unset. Nested ifs to dodge the inline-script
    // and-operator encoding gotcha.
    if (isDirectHit) {
      if (game.fxTime - ant.hitFx > 0.1) { ant.hitFx = game.fxTime; }
      if (sourceCannon) {
        var sk2 = CANNON_KINDS[sourceCannon.kind];
        if (sk2.family === 'heavy') {
          if (ant.effects.freeze) {
            dmg = dmg * 2;
            delete ant.effects.freeze;
            spawnBurst(ant.x, ant.y, '#c8f0ff', 12, 140, 0.25, 2);
            addPopup(ant.x, ant.y - 12, 'SHATTER x2', '#c8f0ff');
          } else if (ant.effects.slow) {
            dmg = dmg * 1.5;
            delete ant.effects.slow;
            spawnBurst(ant.x, ant.y, '#a4d4ff', 8, 110, 0.2, 2);
            addPopup(ant.x, ant.y - 12, 'SHATTER', '#c8f0ff');
          }
        }
      }
    }
    if (!armorPierce) {
      var armor = k.armor;
      if (!armor) { armor = 0; }
      dmg = dmg * (1 - armor);
    }
    ant.hp = ant.hp - dmg;
    // Threshold-summon hook (Plan 3 Task 30 — King). Each entry fires once
    // when HP drops past `at` (a fraction of maxHp). firedThresholds is
    // initialized in spawnAnt so the keyed write here is safe even on the
    // first crossing. Nested ifs to avoid the inline-script and-operator.
    if (k.onHpThresholds) {
      var pct = ant.hp / ant.maxHp;
      for (var ti = 0; ti < k.onHpThresholds.length; ti++) {
        var thr = k.onHpThresholds[ti];
        var key = String(thr.at);
        if (!ant.firedThresholds[key]) {
          if (pct < thr.at) {
            ant.firedThresholds[key] = true;
            thr.action(ant);
          }
        }
      }
    }
  }

  // Centralized kill handling. Used by both projectile collision and beam tick
  // so the gold drop and carried-slice return stay in sync. `killer` is the
  // cannon for kill-attribution; pass null when not applicable.
  function killAnt(ant, killer) {
    if (ant.dead) { return; }
    ant.dead = true;
    ant.deathFx = game.fxTime;
    var k = ANT_KINDS[ant.kind];
    game.gold = game.gold + k.gold;
    game.kills = game.kills + 1;
    addPopup(ant.x, ant.y - 10, '+' + k.gold, '#fee761');
    // The King gets a real send-off: a gold and red burst, a shake, and a
    // banner, before his last guards are mopped up and the Victory sign fades in.
    if (ant.kind === 'king') {
      spawnBurst(ant.x, ant.y, '#fee761', 40, 160, 0.9, 3);
      spawnBurst(ant.x, ant.y, '#e43b44', 20, 110, 0.7, 3);
      spawnBurst(ant.x, ant.y, '#fff8e8', 12, 70, 0.5, 2);
      game.screenshake.magnitude = 6;
      game.screenshake.decay = 6;
      game.bannerQueue = [];
      game.banner.active = false;
      var guardsLeft = 0;
      for (var gi = 0; gi < game.ants.length; gi++) {
        if (!game.ants[gi].dead) { guardsLeft = guardsLeft + 1; }
      }
      var kingSub = '';
      if (guardsLeft > 0) { kingSub = 'Clear out the last of his guards.'; }
      showBanner('THE KING IS DOWN', 'gold', kingSub);
    }
    // Task 23: dropping a slice on the ground (replaces Plan 1's instant cake.slices++ snap-back).
    // The slice sits where the ant fell. Another canPickup ant can claim it (ground pickup branch
    // in checkPickupsAndDeliveries); after 8s of nobody claiming, it returns to the cake without
    // a life deduction (updateSlices). Cake accounting: kill->drop is neutral on cake.slices,
    // timeout refunds it, ground pickup just transfers ownership.
    if (ant.carrying) {
      game.slices.push({ x: ant.x, y: ant.y, droppedAt: game.simTime, claimedBy: null });
      ant.carrying = false;
    }
    if (killer) {
      killer.kills = killer.kills + 1;
    }
    // Fire the kind's onDeath hook (Splitter spawns Mini-Splitters; Bomber/Queen
    // will use this in later tasks). spawnAntAt only pushes onto game.ants, so
    // it is safe even if killAnt is called from inside an iteration over game.ants.
    if (k.onDeath) { k.onDeath(ant); }
    // Plague spread-on-death (Plan 3). Tagged poison effects propagate to
    // ants within 30 px when their carrier dies. Toxin's poison omits the
    // spreads tag so this is a no-op for Toxin kills. Nested ifs to avoid
    // the inline-script and-operator (CLAUDE.md HTML-encoding gotcha).
    if (ant.effects) {
      if (ant.effects.poison) {
        if (ant.effects.poison.spreads) {
          STATUS_KINDS.poison.onTargetDeath(ant);
        }
      }
    }
  }

  // Splash AoE primitive (Plan 3). Iterates live ants once; applies damage
  // scaled by `falloff` (0 = flat-radius, 1 = linear-to-zero at edge). Skips
  // dead and intangible (Phantom phase-out, future tasks). Reads game.ants
  // by index against the snapshot at call time, so a direct-hit kill before
  // splash is fine — the loop sees positions, not the kill flag of the
  // carrier specifically. `damage` is the already-scaled splash damage
  // (caller multiplies by splashMult).
  function applySplash(x, y, radius, damage, falloff, sourceCannon) {
    // Plan 4: when sourceCannon has splashStatus: true, apply each entry of
    // its statusOnHit array to every ant caught in the splash, not just the
    // direct-hit. Backward compatible — splash cannons without splashStatus
    // (Mortar, Hellfire, Shockwave) keep their pre-Plan-4 damage-only behavior.
    var def = null;
    if (sourceCannon) { def = CANNON_KINDS[sourceCannon.kind]; }
    var applyStatusToAll = false;
    if (def) {
      if (def.splashStatus) { applyStatusToAll = true; }
    }
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      if (a.dead) { continue; }
      if (a.intangible) { continue; }
      var dx = a.x - x;
      var dy = a.y - y;
      var dist = Math.sqrt(dx * dx + dy * dy);
      if (dist > radius) { continue; }
      var pct;
      if (falloff > 0) { pct = 1 - (dist / radius) * falloff; } else { pct = 1; }
      if (pct < 0) { pct = 0; }
      applyDamage(a, damage * pct, false, sourceCannon);
      if (applyStatusToAll) {
        if (def.statusOnHit) {
          var slist = def.statusOnHit;
          if (!Array.isArray(slist)) { slist = [slist]; }
          for (var si = 0; si < slist.length; si++) {
            var s = slist[si];
            applyStatus(a, s.kind, s.magnitude, s.duration, 'splash', sourceCannon);
            // Tag-after-apply for damageAmp / spreads (mirrors the
            // projectile-collision pathway in updateProjectiles).
            if (s.damageAmp) {
              if (a.effects[s.kind]) { a.effects[s.kind].damageAmp = s.damageAmp; }
            }
            if (s.spreads) {
              if (a.effects[s.kind]) { a.effects[s.kind].spreads = true; }
            }
          }
        }
      }
      if (a.hp <= 0) { killAnt(a, sourceCannon); }
    }
    // Plan 4: when splashStatus carries a knockback effect (Tempest), push a
    // transient white ring at the impact point. Same shape as Phoenix's
    // burn-spread ring; rendered by drawTransientEffects (Task 13).
    if (applyStatusToAll) {
      if (def) {
        if (def.statusOnHit) {
          var hasKnockback = false;
          var slist2 = def.statusOnHit;
          if (!Array.isArray(slist2)) { slist2 = [slist2]; }
          for (var sj = 0; sj < slist2.length; sj++) {
            if (slist2[sj].kind === 'knockback') { hasKnockback = true; }
          }
          if (hasKnockback) {
            // Reuses Plan 3's existing game.effects framework
            // (drawEffects + updateEffects). Task 13 extends drawEffects
            // with the 'knockbackRing' kind branch.
            game.effects.push({
              kind: 'knockbackRing',
              x: x, y: y,
              t: 0,
              life: 0.15,
              color: '#ffffff',
              maxRadius: radius
            });
          }
        }
      }
    }
  }

  function updateProjectiles(dt) {
    var kept = [];
    for (var i = 0; i < game.projectiles.length; i++) {
      var p = game.projectiles[i];
      // Plan 4 — barrelStagger (Saturation). A staggered shell sits dormant
      // until game.time catches up to its dormantUntil; no movement, no
      // collision, no render. Lets a 3-shell volley feel like rolling rain
      // instead of a triple-fire instant. drawProjectiles also skips dormant
      // shells, so the ramp-up is purely time-gated.
      if (p.dormantUntil) {
        if (game.time < p.dormantUntil) { kept.push(p); continue; }
      }
      // Homing steer (Plan 3 — Patriot). Bend the velocity vector up to
      // homingTurnRate*dt radians toward p.target each tick. If the target is
      // null or already dead, scan game.ants for the nearest live, non-intangible
      // ant and re-acquire. CLAUDE.md: no `||` in inline <script>, so the
      // need-reacquire check is two if/else branches instead of `!t || t.dead`.
      if (p.homing) {
        var t = p.target;
        var needReacquire = false;
        if (!t) {
          needReacquire = true;
        } else if (t.dead) {
          needReacquire = true;
        }
        if (needReacquire) {
          var nearest = null;
          var bestDist = Infinity;
          for (var k = 0; k < game.ants.length; k++) {
            var ka = game.ants[k];
            if (ka.dead) { continue; }
            if (ka.intangible) { continue; }
            var kdx = ka.x - p.x;
            var kdy = ka.y - p.y;
            var kd = kdx * kdx + kdy * kdy;
            if (kd < bestDist) { bestDist = kd; nearest = ka; }
          }
          p.target = nearest;
          t = nearest;
        }
        if (t) {
          var desiredAngle = Math.atan2(t.y - p.y, t.x - p.x);
          var currentAngle = Math.atan2(p.vy, p.vx);
          var delta = desiredAngle - currentAngle;
          while (delta > Math.PI) { delta = delta - Math.PI * 2; }
          while (delta < -Math.PI) { delta = delta + Math.PI * 2; }
          var maxTurn = p.homingTurnRate * dt;
          if (delta > maxTurn) { delta = maxTurn; }
          if (delta < -maxTurn) { delta = -maxTurn; }
          var newAngle = currentAngle + delta;
          var speed = Math.sqrt(p.vx * p.vx + p.vy * p.vy);
          p.vx = Math.cos(newAngle) * speed;
          p.vy = Math.sin(newAngle) * speed;
        }
      }
      p.x += p.vx * dt;
      p.y += p.vy * dt;
      p.life -= dt;
      // Plan 4 — MP-M mid-flight projectile split (Cluster Strike). When the
      // parent missile has traveled splitTotalDist from its launch origin,
      // despawn and spawn splitCount homing children at the parent's current
      // position. Each child re-acquires the nearest live ant via the standard
      // MP-C homing pathway. Children inherit splash, statusOnHit, sourceCannon
      // so on-impact effects fire normally. Visual: white-flash transient
      // pushed onto game.effects (rendered by Task 13). One-shot per parent.
      if (p.splitFraction > 0) {
        if (!p._splitFired) {
          var ddx = p.x - p.splitOriginX;
          var ddy = p.y - p.splitOriginY;
          var travDist = Math.sqrt(ddx * ddx + ddy * ddy);
          if (travDist >= p.splitTotalDist) {
            p._splitFired = true;
            var pSpeed = Math.sqrt(p.vx * p.vx + p.vy * p.vy);
            for (var sc = 0; sc < p.splitCount; sc++) {
              // Distribute children around a 60-degree arc centered on the
              // parent's heading. With splitCount = 3, that's -30°, 0°, +30°.
              var spread;
              if (p.splitCount === 1) {
                spread = 0;
              } else {
                spread = (sc / (p.splitCount - 1) - 0.5) * (Math.PI / 3);
              }
              var parentHeading = Math.atan2(p.vy, p.vx);
              var childHeading = parentHeading + spread;
              game.projectiles.push({
                x: p.x, y: p.y,
                vx: Math.cos(childHeading) * pSpeed,
                vy: Math.sin(childHeading) * pSpeed,
                damage: p.damage,
                linePierce: 0,
                armorPierce: !!p.armorPierce,
                pierceLeft: 0,
                statusOnHit: p.statusOnHit,
                sourceCannon: p.sourceCannon,
                _sawAnts: [],
                life: 1.5,
                arcing: false,
                arcStartedAt: 0,
                arcDuration: 0,
                arcHeight: 0,
                returning: false,
                phase: 'out',
                originX: p.splitOriginX,
                originY: p.splitOriginY,
                homing: true,
                homingTurnRate: 5.0,
                target: null,
                bouncesRemaining: 0,
                bounceRadius: 0,
                damageDecay: 1,
                initialHit: true,
                visualScale: p.visualScale ? p.visualScale * 0.7 : 0.7,
                statusOnFirstHit: null,
                splitFraction: 0,
                splitCount: 0,
                splitOriginX: 0,
                splitOriginY: 0,
                splitTotalDist: 0,
                _splitFired: true,
                dormantUntil: 0
              });
            }
            // Push white-flash transient at the split point. Drawn by the
            // 'splitFlash' branch in drawEffects (Task 13).
            game.effects.push({
              kind: 'splitFlash',
              x: p.x, y: p.y,
              t: 0,
              life: 0.1,
              color: '#ffffff',
              maxRadius: 12
            });
            // Despawn the parent — life=-1 fails the `life > 0` check below.
            p.life = -1;
            continue;
          }
        }
      }
      var hit = false;
      for (var j = 0; j < game.ants.length; j++) {
        var a = game.ants[j];
        if (a.dead) { continue; }
        if (a.intangible) { continue; }
        if (p._sawAnts) {
          if (p._sawAnts.indexOf(a) !== -1) { continue; }
        }
        var dx = a.x - p.x;
        var dy = a.y - p.y;
        if (dx * dx + dy * dy < 6 * 6) {
          applyDamage(a, p.damage, p.armorPierce, p.sourceCannon, true);
          if (p.statusOnHit) {
            // statusOnHit is one effect descriptor or an array of them. Sonic
            // Pulse uses an array (knockback + stun in one volley); single-
            // effect cannons stay backwards compatible via the wrap-in-array.
            var slist = p.statusOnHit;
            if (!Array.isArray(slist)) { slist = [slist]; }
            for (var si = 0; si < slist.length; si++) {
              var s = slist[si];
              applyStatus(a, s.kind, s.magnitude, s.duration, 'projectile', p.sourceCannon);
              // Tag-after-apply pattern: extra fields (Cryo's damageAmp,
              // Plague's spreads) live on the cannon's statusOnHit config and
              // get stamped onto the just-applied effect here, so applyStatus
              // itself stays lean. Future emitters carry their own tags
              // without any framework change.
              if (s.damageAmp) {
                if (a.effects[s.kind]) {
                  a.effects[s.kind].damageAmp = s.damageAmp;
                }
              }
              if (s.spreads) {
                if (a.effects[s.kind]) {
                  a.effects[s.kind].spreads = true;
                }
              }
            }
          }
          // Plan 4 — statusOnFirstHit applies only to the projectile's
          // original target, not bounce targets (Wrecking Ball's knockback).
          // The MP-L bounce branch flips initialHit = false after the first
          // impact, so subsequent bounces skip this block.
          if (p.statusOnFirstHit) {
            if (p.initialHit) {
              var sflist = p.statusOnFirstHit;
              if (!Array.isArray(sflist)) { sflist = [sflist]; }
              for (var sfi = 0; sfi < sflist.length; sfi++) {
                var sfe = sflist[sfi];
                applyStatus(a, sfe.kind, sfe.magnitude, sfe.duration, 'first-hit', p.sourceCannon);
              }
            }
          }
          // Splash trigger (Plan 3 — Mortar, Hellfire, Demolisher, Sonic Pulse).
          // Runs before the carrier kill check so the radius scan sees
          // game.ants positions live; carrier is excluded later by the dead flag
          // anyway since applySplash skips dead ants on subsequent calls.
          var sk;
          if (p.sourceCannon) {
            sk = CANNON_KINDS[p.sourceCannon.kind];
          } else {
            sk = null;
          }
          if (sk) {
            if (sk.splash) {
              applySplash(p.x, p.y, sk.splash.radius, p.damage * sk.splash.splashMult, sk.splash.falloff, p.sourceCannon);
              // Screenshake on splash impact (Plan 3 — Shockwave; Plan 4 —
              // Earthshaker, Annihilator). Direct hits without splash never
              // trigger shake. Plan 4 prefers the flat screenShakeMag number
              // (default decay 12); legacy screenshake object kept for
              // Shockwave's tuned 3-magnitude / 12-decay snap. Nested ifs
              // avoid the and-operator.
              if (sk.screenShakeMag) {
                game.screenshake.magnitude = sk.screenShakeMag;
                game.screenshake.decay = 12;
              } else {
                if (sk.screenshake) {
                  game.screenshake.magnitude = sk.screenshake.magnitude;
                  game.screenshake.decay = sk.screenshake.decay;
                }
              }
            }
          }
          if (a.hp <= 0) {
            killAnt(a, p.sourceCannon ? p.sourceCannon : null);
          }
          if (p.pierceLeft > 0) {
            p.pierceLeft = p.pierceLeft - 1;
            if (!p._sawAnts) { p._sawAnts = []; }
            p._sawAnts.push(a);
            // projectile keeps flying — don't set hit, don't break
          } else if (p.bouncesRemaining > 0) {
            // Plan 4 — MP-L bouncing retarget (Pink Rubber Family). After the
            // hit lands, search nearest non-_sawAnts live ant within
            // bounceRadius. Found: redirect velocity, push current ant onto
            // _sawAnts, decrement counter, multiply damage by damageDecay, do
            // not despawn. Not found: fall through to normal despawn.
            if (!p._sawAnts) { p._sawAnts = []; }
            p._sawAnts.push(a);
            var bestBounce = null;
            var bestBounceDist = Infinity;
            for (var bki = 0; bki < game.ants.length; bki++) {
              var bka = game.ants[bki];
              if (bka.dead) { continue; }
              if (bka.intangible) { continue; }
              if (p._sawAnts.indexOf(bka) !== -1) { continue; }
              var bdx = bka.x - p.x;
              var bdy = bka.y - p.y;
              var bd2 = bdx * bdx + bdy * bdy;
              if (bd2 > p.bounceRadius * p.bounceRadius) { continue; }
              if (bd2 < bestBounceDist) { bestBounceDist = bd2; bestBounce = bka; }
            }
            if (bestBounce) {
              var bAngle = Math.atan2(bestBounce.y - p.y, bestBounce.x - p.x);
              var bSpeed = Math.sqrt(p.vx * p.vx + p.vy * p.vy);
              p.vx = Math.cos(bAngle) * bSpeed;
              p.vy = Math.sin(bAngle) * bSpeed;
              p.bouncesRemaining = p.bouncesRemaining - 1;
              p.damage = p.damage * p.damageDecay;
              p.initialHit = false;
              // Refresh life so the projectile has time to reach the bounce
              // target — without this it might expire mid-flight on long
              // bounces against the back of the playfield.
              p.life = 1.5;
              // projectile keeps flying — don't set hit, don't break
            } else {
              // No eligible bounce target → despawn normally.
              hit = true;
              break;
            }
          } else {
            // Returning projectile primitive (Plan 3 — Discus). On the first
            // outbound hit, flip velocity toward origin so the projectile can
            // hit more ants on the return leg. CLAUDE.md: no and-operator in
            // inline script, so two nested ifs instead of a compound condition.
            var canReturn = false;
            if (p.returning) {
              if (p.phase === 'out') {
                canReturn = true;
              }
            }
            if (canReturn) {
              p.phase = 'return';
              var rdx = p.originX - p.x;
              var rdy = p.originY - p.y;
              var rmag = Math.sqrt(rdx * rdx + rdy * rdy);
              var speed = Math.sqrt(p.vx * p.vx + p.vy * p.vy);
              if (rmag > 0) {
                p.vx = (rdx / rmag) * speed;
                p.vy = (rdy / rmag) * speed;
              }
              if (!p._sawAnts) { p._sawAnts = []; }
              p._sawAnts.push(a);
              p.life = 1.5;
              // Return-leg pierce re-arm (Plan 3 — Ricochet). Cannons without
              // returnPierce keep their default behavior (return-leg single hit,
              // despawn at origin). Nested ifs avoid the inline-script and-operator.
              if (sk) {
                if (sk.returnPierce) {
                  p.pierceLeft = sk.returnPierce;
                }
              }
              // projectile keeps flying back — don't set hit, don't break
            } else {
              hit = true;
              break;
            }
          }
        }
      }
      if (!hit) {
        // Return-leg despawn: drop the projectile when it arrives back at origin
        // so it doesn't fly off-map for an extra frame. Checked before the
        // bounds/life filter so an arrived returner exits cleanly via `continue`.
        if (p.phase === 'return') {
          var ddx = p.originX - p.x;
          var ddy = p.originY - p.y;
          if (ddx * ddx + ddy * ddy < 4 * 4) {
            continue;
          }
        }
        if (p.life > 0) {
          if (p.x >= 0) {
            if (p.x <= PLAYFIELD_W) {
              if (p.y >= 0) {
                if (p.y <= HEIGHT) {
                  kept.push(p);
                }
              }
            }
          }
        }
      }
    }
    game.projectiles = kept;
  }

  // === WAVES ===
  // Data-driven wave definitions. Plan 2 Task 5 ships wave 1 only; waves 2-5
  // arrive in Task 22. Each wave has a `prep` window (seconds before spawning
  // begins), a `bonus` (gold awarded on clear), and a list of spawn `groups`.
  // Each group spawns `count` ants of `type`, one every `interval` seconds,
  // starting `delay` seconds after the previous group finished.
  // Prep-time defaults per Plan 3 spec §2.3: 20s for regular waves, 30s for
  // boss waves (5/10/15/20/25). Waves can override via an explicit prepTime
  // field (wave 1 does this to keep the spec's first-run 30s breather).
  // Bonus formula is now `20 + wave.number` (replaces Plan 2's hand-tuned
  // values); deleted bonus fields here are intentional, not lost data.
  var DEFAULT_PREP_REGULAR = 20;
  var DEFAULT_PREP_BOSS = 30;

  // Wave entries (Plan 3 Task 28). Each wave has:
  //   number       — display index, also feeds the bonus formula (20 + number).
  //   prepTime     — optional override of DEFAULT_PREP_REGULAR/BOSS.
  //   isBoss       — boss flag (drives banner styling and default prep).
  //   bannerText   — exact wave-start banner string. Hardcodes /25 because
  //                  the campaign is 25 waves long even before all are loaded.
  //   spawns       — list of groups; each group has type, count, interval, delay,
  //                  and an optional `hp` override (Queens at 600/800/1200).
  var WAVES = [
    { number: 1, prepTime: 30, isBoss: false, bannerText: 'WAVE 1/25', spawns: [
      { type: 'worker', count: 8, interval: 1.0, delay: 0 }
    ]},
    { number: 2, prepTime: 20, isBoss: false, bannerText: 'WAVE 2/25', spawns: [
      { type: 'worker', count: 12, interval: 0.8, delay: 0 }
    ]},
    { number: 3, prepTime: 20, isBoss: false, bannerText: 'WAVE 3/25', spawns: [
      { type: 'worker', count: 6, interval: 0.7, delay: 0 },
      { type: 'worker', count: 6, interval: 0.7, delay: 4 }
    ]},
    { number: 4, prepTime: 20, isBoss: false, bannerText: 'WAVE 4/25', spawns: [
      { type: 'worker', count: 10, interval: 0.7, delay: 0 },
      { type: 'scout',  count: 4,  interval: 1.0, delay: 1.5 }
    ]},
    { number: 5, prepTime: 30, isBoss: true, bannerText: 'WAVE 5: QUEEN', spawns: [
      { type: 'queen',  count: 1, interval: 1,   delay: 0 },
      { type: 'worker', count: 8, interval: 0.8, delay: 2 }
    ]},
    { number: 6, prepTime: 20, isBoss: false, bannerText: 'WAVE 6/25', spawns: [
      { type: 'worker', count: 12, interval: 0.7, delay: 0 },
      { type: 'scout',  count: 6,  interval: 0.5, delay: 1 }
    ]},
    { number: 7, prepTime: 20, isBoss: false, bannerText: 'WAVE 7/25', spawns: [
      { type: 'scout',  count: 8, interval: 0.4, delay: 0 },
      { type: 'worker', count: 6, interval: 0.7, delay: 1.5 },
      { type: 'scout',  count: 4, interval: 0.4, delay: 1 }
    ]},
    { number: 8, prepTime: 20, isBoss: false, bannerText: 'WAVE 8/25', spawns: [
      { type: 'worker',  count: 12, interval: 0.7, delay: 0 },
      { type: 'soldier', count: 2,  interval: 1.5, delay: 2 }
    ]},
    { number: 9, prepTime: 20, isBoss: false, bannerText: 'WAVE 9/25', spawns: [
      { type: 'soldier', count: 6, interval: 1.5, delay: 0 },
      { type: 'worker',  count: 8, interval: 0.7, delay: 1 }
    ]},
    { number: 10, prepTime: 30, isBoss: true, bannerText: 'WAVE 10: QUEEN', spawns: [
      { type: 'queen',   count: 1, interval: 0,   delay: 0, hp: 600 },
      { type: 'soldier', count: 6, interval: 1.5, delay: 1 },
      { type: 'worker',  count: 6, interval: 0.7, delay: 1 }
    ]},
    { number: 11, prepTime: 20, isBoss: false, bannerText: 'WAVE 11/25', spawns: [
      { type: 'worker',  count: 8, interval: 0.7, delay: 0 },
      { type: 'carrier', count: 4, interval: 1.0, delay: 1 },
      { type: 'worker',  count: 6, interval: 0.7, delay: 1 }
    ]},
    { number: 12, prepTime: 20, isBoss: false, bannerText: 'WAVE 12/25', spawns: [
      { type: 'soldier', count: 4, interval: 1.5, delay: 0 },
      { type: 'carrier', count: 6, interval: 0.8, delay: 1.5 },
      { type: 'scout',   count: 4, interval: 0.4, delay: 1 }
    ]},
    { number: 13, prepTime: 20, isBoss: false, bannerText: 'WAVE 13/25', spawns: [
      { type: 'splitter', count: 6, interval: 1.2, delay: 0 },
      { type: 'worker',   count: 8, interval: 0.7, delay: 1.5 }
    ]},
    { number: 14, prepTime: 20, isBoss: false, bannerText: 'WAVE 14/25', spawns: [
      { type: 'splitter', count: 4, interval: 1.2, delay: 0 },
      { type: 'soldier',  count: 4, interval: 1.5, delay: 1 },
      { type: 'carrier',  count: 6, interval: 0.8, delay: 1 },
      { type: 'scout',    count: 8, interval: 0.4, delay: 1 }
    ]},
    { number: 15, prepTime: 30, isBoss: true, bannerText: 'WAVE 15: QUEEN', spawns: [
      { type: 'queen',    count: 1, interval: 0,   delay: 0, hp: 800 },
      { type: 'splitter', count: 8, interval: 1.0, delay: 1 },
      { type: 'carrier',  count: 6, interval: 0.8, delay: 1 }
    ]},
    { number: 16, prepTime: 20, isBoss: false, bannerText: 'WAVE 16/25', spawns: [
      { type: 'tank',   count: 2, interval: 2.5, delay: 0 },
      { type: 'worker', count: 8, interval: 0.7, delay: 2 }
    ]},
    { number: 17, prepTime: 20, isBoss: false, bannerText: 'WAVE 17/25', spawns: [
      { type: 'phase',   count: 6, interval: 1.0, delay: 0 },
      { type: 'worker',  count: 8, interval: 0.7, delay: 1 },
      { type: 'soldier', count: 4, interval: 1.5, delay: 1 }
    ]},
    { number: 18, prepTime: 20, isBoss: false, bannerText: 'WAVE 18/25', spawns: [
      { type: 'tank',     count: 3, interval: 2.5, delay: 0 },
      { type: 'phase',    count: 4, interval: 1.0, delay: 1 },
      { type: 'splitter', count: 6, interval: 1.2, delay: 1 }
    ]},
    { number: 19, prepTime: 20, isBoss: false, bannerText: 'WAVE 19/25', spawns: [
      { type: 'bomber',  count: 4, interval: 1.5, delay: 0 },
      { type: 'worker',  count: 8, interval: 0.7, delay: 1 },
      { type: 'carrier', count: 4, interval: 1.0, delay: 1 }
    ]},
    { number: 20, prepTime: 30, isBoss: true, bannerText: 'WAVE 20: QUEEN', spawns: [
      { type: 'queen',  count: 1, interval: 0,   delay: 0, hp: 1200 },
      { type: 'bomber', count: 4, interval: 1.5, delay: 1 },
      { type: 'tank',   count: 6, interval: 2.0, delay: 1 }
    ]},
    { number: 21, prepTime: 20, isBoss: false, bannerText: 'WAVE 21/25', spawns: [
      { type: 'tank',    count: 6, interval: 2.0, delay: 0 },
      { type: 'phase',   count: 6, interval: 1.0, delay: 1 },
      { type: 'carrier', count: 8, interval: 0.8, delay: 1 }
    ]},
    { number: 22, prepTime: 20, isBoss: false, bannerText: 'WAVE 22/25', spawns: [
      { type: 'bomber',   count: 8, interval: 1.2, delay: 0 },
      { type: 'splitter', count: 8, interval: 1.0, delay: 1 },
      { type: 'soldier',  count: 4, interval: 1.5, delay: 1 }
    ]},
    { number: 23, prepTime: 20, isBoss: false, bannerText: 'WAVE 23/25', spawns: [
      { type: 'tank',     count: 4,  interval: 2.0, delay: 0 },
      { type: 'phase',    count: 6,  interval: 1.0, delay: 1 },
      { type: 'bomber',   count: 6,  interval: 1.2, delay: 1 },
      { type: 'splitter', count: 8,  interval: 1.0, delay: 1 },
      { type: 'carrier',  count: 8,  interval: 0.8, delay: 1 },
      { type: 'worker',   count: 12, interval: 0.5, delay: 1 }
    ]},
    { number: 24, prepTime: 20, isBoss: false, bannerText: 'WAVE 24/25', spawns: [
      { type: 'tank',    count: 8,  interval: 1.8, delay: 0 },
      { type: 'carrier', count: 12, interval: 0.7, delay: 1 },
      { type: 'bomber',  count: 8,  interval: 1.2, delay: 1 }
    ]},
    { number: 25, prepTime: 30, isBoss: true, bannerText: 'FINAL WAVE: THE KING', spawns: [
      { type: 'king', count: 1, interval: 0, delay: 0 }
    ]}
  ];

  // Resolve a wave's prep duration: explicit prepTime wins, else default by
  // isBoss flag. Used both by updateWaveState's prep transition and resetGame.
  function wavePrepDuration(wv) {
    if (wv.prepTime) { return wv.prepTime; }
    if (wv.isBoss) { return DEFAULT_PREP_BOSS; }
    return DEFAULT_PREP_REGULAR;
  }

  // Gold for skipping the rest of the prep wait: half the seconds left. The
  // Send Next label shows it so people know skipping pays.
  function sendNextBonus() {
    return Math.floor(game.prepRemaining / 2);
  }

  function sendNext() {
    var bonus = sendNextBonus();
    game.gold = game.gold + bonus;
    game.prepRemaining = 0;
    if (bonus > 0) { addPopup(34, HUD_H + 10, '+' + bonus, '#fee761'); }
  }

  // Spawner state machine: prep -> wave -> (prep | won) ; lost is set by the
  // cake-loss path. Reads game.time for spawn scheduling.
  function updateWaveState(dt) {
    if (game.waveState === 'prep') {
      game.prepRemaining = game.prepRemaining - dt;
      if (game.prepRemaining <= 0) {
        game.waveState = 'wave';
        game.spawner.groupIndex = 0;
        game.spawner.spawnedInGroup = 0;
        game.spawner.nextSpawnAt = game.time + WAVES[game.waveIndex].spawns[0].delay;
        game.spawner.queueDone = false;
        var wv = WAVES[game.waveIndex];
        var waveStyle = 'wood';
        if (wv.isBoss) { waveStyle = 'boss'; }
        if (wv.number === 25) { waveStyle = 'final'; }
        showBanner(wv.bannerText, waveStyle);
      }
      return;
    }
    if (game.waveState === 'wave') {
      var wv = WAVES[game.waveIndex];
      if (!game.spawner.queueDone) {
        var grp = wv.spawns[game.spawner.groupIndex];
        if (game.time >= game.spawner.nextSpawnAt) {
          var spawned = spawnAnt(grp.type);
          // Per-spawn HP override: lets boss waves scale Queen HP (600/800/1200)
          // without needing three separate ANT_KINDS rows for the same sprite.
          if (grp.hp) {
            spawned.hp = grp.hp;
            spawned.maxHp = grp.hp;
          }
          game.spawner.spawnedInGroup = game.spawner.spawnedInGroup + 1;
          if (game.spawner.spawnedInGroup >= grp.count) {
            game.spawner.groupIndex = game.spawner.groupIndex + 1;
            game.spawner.spawnedInGroup = 0;
            if (game.spawner.groupIndex >= wv.spawns.length) {
              game.spawner.queueDone = true;
            } else {
              game.spawner.nextSpawnAt = game.time + wv.spawns[game.spawner.groupIndex].delay;
            }
          } else {
            game.spawner.nextSpawnAt = game.time + grp.interval;
          }
        }
      }
      if (game.spawner.queueDone) {
        var alive = 0;
        for (var i = 0; i < game.ants.length; i++) {
          if (!game.ants[i].dead) { alive = alive + 1; }
        }
        if (alive === 0) {
          // Plan 3 spec §2.3: wave-completion bonus = 20 + wave.number.
          // Replaces Plan 2's hand-tuned bonus field (wave 5 was 75g, now 25g —
          // intentional rebalance to make the 21-cannon roster's choices feel
          // meaningful in the late-game economy).
          game.gold = game.gold + (20 + wv.number);
          if (game.waveIndex + 1 < WAVES.length) {
            showBanner('WAVE ' + wv.number + ' CLEARED', 'wood', '+' + (20 + wv.number) + ' gold bonus');
          }
          // Plan 4: wave-15 unlocks god-tier upgrades. Fires once per run,
          // with its own gold banner. Stamps godTierUnlockPulseStart so the
          // selected-tower panel also flashes its god-tier rows.
          if (wv.number === 15) {
            if (!game.godTierUnlocked) {
              game.godTierUnlocked = true;
              game.godTierUnlockPulseStart = game.fxTime;
              showBanner('GOD-TIER CANNONS UNLOCKED', 'gold', 'Every final cannon can upgrade once more.');
            }
          }
          if (game.waveIndex + 1 >= WAVES.length) {
            endRun('won');
          } else {
            game.waveIndex = game.waveIndex + 1;
            game.waveState = 'prep';
            game.prepRemaining = wavePrepDuration(WAVES[game.waveIndex]);
            saveRun();
          }
        }
      }
    }
  }

  function resetGame() {
    game.state = 'playing';
    game.time = 0;
    game.simTime = 0;
    game.cake.slices = 8;
    game.cake.lost = 0;
    game.ants = [];
    game.cannons = [];
    game.projectiles = [];
    game.effects = [];
    game.lightningBolts = [];
    game.popups = [];
    game.bannerQueue = [];
    game.seenKinds = {};
    game.kills = 0;
    game.sliceLostFx = -10;
    game.cakeDipFx = -10;
    game.accumulator = 0;
    game.spawnJobs = [];
    game.slices = [];
    // game.shatterParticles is ephemeral and is not part of the saved run.
    game.shatterParticles = [];
    game.gold = 150;
    game.waveIndex = 0;
    game.waveState = 'prep';
    game.prepRemaining = wavePrepDuration(WAVES[0]);
    game.spawner.groupIndex = 0;
    game.spawner.spawnedInGroup = 0;
    game.spawner.nextSpawnAt = 0;
    game.spawner.queueDone = false;
    game.banner.active = false;
    game.screenshake.magnitude = 0;
    game.screenshake.decay = 0;
    game.paused = false;
    game.speed = 1;
    game.godTierUnlocked = false;
    game.godTierUnlockPulseStart = -10;
    ui.sendNextRect = null;
    ui.restartRunRect = null;
    ui.activePointerId = null;
    ui.pointerType = null;
    ui.armedKind = null;
    ui.pendingKind = null;
    ui.pendingX = 0;
    ui.pendingY = 0;
    ui.selectedCannon = null;
    ui.upgradeRects[0] = null;
    ui.upgradeRects[1] = null;
    ui.upgradeRects[2] = null;
    ui.hoveredUpgrade = null;
    ui.sellRect = null;
    ui.targetingRects = [];   // Plan 7 — clear targeting hit-test rects on restart
    ui.shopRects = [];
    ui.shopMode = 'list';
    ui.shopInfoKind = null;
    ui.shopInfoFrom = null;
    ui.hintDone = false;
    ui.endedAt = 0;
    clearSave();   // any restart is a new game, so the old save goes
  }

  // Wave end (won or lost). Stamps the time so the result screen can ignore
  // taps for a moment, and drops the save since the run is over.
  function endRun(result) {
    game.waveState = result;
    ui.endedAt = performance.now();
    game.endedFx = game.fxTime;
    if (result === 'won') { recordBestWave(WAVES.length + 1); } else { recordBestWave(game.waveIndex + 1); }
    clearSave();
  }

  // True once the win/lose screen has been up long enough that a tap on it was
  // meant, not a panicked one from the last second of play. The win sign
  // fades in a second later (resultFade), so it waits a second longer.
  function restartReady() {
    var wait = 1000;
    if (game.waveState === 'won') { wait = 2000; }
    return performance.now() - ui.endedAt >= wait;
  }

  // Best run, kept in its own key so it outlives the run save. Stored as the
  // highest wave reached; WAVES.length + 1 means all 25 were cleared.
  var BEST_KEY = 'dariobimbi-ant-buster-best';
  function readBestWave() {
    var n = 0;
    try { n = parseInt(window.localStorage.getItem(BEST_KEY), 10); } catch (err) { n = 0; }
    if (!isCount(n, WAVES.length + 1)) { return 0; }
    return n;
  }
  function recordBestWave(n) {
    if (n <= readBestWave()) { return; }
    try { window.localStorage.setItem(BEST_KEY, String(n)); } catch (err) { }
  }
  function bestWaveLabel(n) {
    if (n > WAVES.length) { return 'all ' + WAVES.length + ' waves'; }
    return 'wave ' + n;
  }

  // === SAVE AND RESUME ===
  // One run is about 20 minutes, so the state at each wave clear is written to
  // localStorage. Every storage access is wrapped in try/catch: private
  // windows and blocked site data throw, and the game just plays without it.
  // Saved: waveIndex (the wave about to start), gold, cake, godTierUnlocked and
  // each cannon's kind chain, spot, targeting mode and kills. Everything else
  // is derived and rebuilt on restore.
  var SAVE_KEY = 'dariobimbi-ant-buster-run';
  var SAVE_VERSION = 1;
  // 'last' was replaced by 'carrying'; older saves that still say 'last'
  // validate and are switched over in continueRun.
  var SAVE_TARGETING = ['closest', 'first', 'carrying', 'strongest', 'weakest', 'last'];

  function clearSave() {
    ui.savedRun = null;
    try { window.localStorage.removeItem(SAVE_KEY); } catch (err) { }
  }

  function saveRun() {
    recordBestWave(game.waveIndex + 1);
    var cannons = [];
    for (var i = 0; i < game.cannons.length; i++) {
      var c = game.cannons[i];
      cannons.push({
        kind: c.kind,
        kindHistory: c.kindHistory.slice(),
        x: c.x,
        y: c.y,
        targetingMode: c.targetingMode,
        kills: c.kills
      });
    }
    // Slices lying on the ground at wave clear walk back to the cake on their
    // own, so they count as slices here.
    var slices = game.cake.slices + game.slices.length;
    if (slices > 8) { slices = 8; }
    var data = {
      v: SAVE_VERSION,
      waveIndex: game.waveIndex,
      gold: game.gold,
      cakeSlices: slices,
      cakeLost: game.cake.lost,
      godTierUnlocked: game.godTierUnlocked,
      cannons: cannons
    };
    try { window.localStorage.setItem(SAVE_KEY, JSON.stringify(data)); } catch (err) { }
  }

  function isCount(n, max) {
    if (typeof n !== 'number') { return false; }
    if (n !== Math.floor(n)) { return false; }
    if (n < 0) { return false; }
    if (n > max) { return false; }
    return true;
  }

  // Returns a validated save object, or null for a missing, corrupt or
  // old-version save (which is also removed).
  function readSave() {
    var raw = null;
    try { raw = window.localStorage.getItem(SAVE_KEY); } catch (err) { return null; }
    if (!raw) { return null; }
    var d = null;
    try { d = JSON.parse(raw); } catch (err2) { d = null; }
    var ok = validateSave(d);
    if (!ok) {
      clearSave();
      return null;
    }
    return d;
  }

  function validateSave(d) {
    if (!d) { return false; }
    if (typeof d !== 'object') { return false; }
    if (d.v !== SAVE_VERSION) { return false; }
    if (!isCount(d.waveIndex, WAVES.length - 1)) { return false; }
    if (!isCount(d.gold, 1000000)) { return false; }
    if (!isCount(d.cakeSlices, 8)) { return false; }
    if (!isCount(d.cakeLost, 7)) { return false; }
    if (typeof d.godTierUnlocked !== 'boolean') { return false; }
    if (!Array.isArray(d.cannons)) { return false; }
    if (d.cannons.length > 80) { return false; }
    for (var i = 0; i < d.cannons.length; i++) {
      var c = d.cannons[i];
      if (!c) { return false; }
      if (typeof c !== 'object') { return false; }
      if (!Array.isArray(c.kindHistory)) { return false; }
      if (c.kindHistory.length === 0) { return false; }
      if (c.kindHistory.length > 6) { return false; }
      for (var h = 0; h < c.kindHistory.length; h++) {
        if (typeof c.kindHistory[h] !== 'string') { return false; }
        if (!CANNON_KINDS.hasOwnProperty(c.kindHistory[h])) { return false; }
      }
      if (c.kind !== c.kindHistory[c.kindHistory.length - 1]) { return false; }
      if (typeof c.x !== 'number') { return false; }
      if (typeof c.y !== 'number') { return false; }
      if (!isFinite(c.x)) { return false; }
      if (!isFinite(c.y)) { return false; }
      if (SAVE_TARGETING.indexOf(c.targetingMode) === -1) { return false; }
      if (!isCount(c.kills, 1000000)) { return false; }
    }
    return true;
  }

  function startNewRun() {
    clearSave();
    game.state = 'playing';
  }

  // Rebuilds a run from a validated save. Cannons go through buildCannon and
  // applyUpgrade, the same two steps placement and upgrades use. A cannon that
  // no longer fits the current layout (portrait vs landscape) is skipped and
  // its full cost goes back to the player.
  function continueRun() {
    var s = ui.savedRun;
    if (!s) { startNewRun(); return; }
    resetGame();
    game.state = 'playing';
    game.waveIndex = s.waveIndex;
    game.prepRemaining = wavePrepDuration(WAVES[s.waveIndex]);
    game.gold = s.gold;
    game.cake.slices = s.cakeSlices;
    game.cake.lost = s.cakeLost;
    game.godTierUnlocked = s.godTierUnlocked;
    // Ants from waves already beaten were introduced in the earlier session.
    for (var w = 0; w < s.waveIndex; w++) {
      for (var g = 0; g < WAVES[w].spawns.length; g++) {
        game.seenKinds[WAVES[w].spawns[g].type] = true;
      }
    }
    for (var i = 0; i < s.cannons.length; i++) {
      var sc = s.cannons[i];
      if (!canPlaceCannon(sc.x, sc.y)) {
        for (var r = 0; r < sc.kindHistory.length; r++) {
          game.gold = game.gold + CANNON_KINDS[sc.kindHistory[r]].cost;
        }
        continue;
      }
      var c = buildCannon(sc.kindHistory[0], sc.x, sc.y);
      for (var u = 1; u < sc.kindHistory.length; u++) {
        applyUpgrade(c, sc.kindHistory[u]);
      }
      c.targetingMode = sc.targetingMode;
      if (c.targetingMode === 'last') { c.targetingMode = 'carrying'; }
      c.kills = sc.kills;
      game.cannons.push(c);
    }
    saveRun();
  }

  // === INPUT ===
  var ui = {
    activePointerId: null,  // single-pointer gesture lock; subsequent pointers ignored
    pointerType: null,      // 'mouse' | 'touch' | 'pen' for last pointerdown; drives ghost offset
    armedKind: null,        // cannon kind promoted from Pending; non-null while dragging (Tasks 2-3)
    pendingKind: null,      // shop-card kind currently in Pending; null when not Pending
    pendingX: 0,            // pointerdown x in game-coords (motion-threshold reference)
    pendingY: 0,            // pointerdown y in game-coords
    mouseX: 0,
    mouseY: 0,
    sendNextRect: null,     // bounds of the Send-Next button (set when rendered in prep)
    pauseRect: { x: 4, y: 4, w: 16, h: 16 },
    speedRect: { x: 24, y: 4, w: 24, h: 16 },
    restartRunRect: null,   // populated when the pause overlay is visible
    selectedCannon: null,   // currently inspected cannon (click on a placed cannon to select)
    upgradeRects: [null, null, null], // up-to-3 upgrade buttons (3 for base, 2 for mid, 0 for final)
    hoveredUpgrade: null,   // when hovering an upgrade button, the target kind key (for range preview)
    sellRect: null,         // sell button in the tower panel
    targetingRects: [],     // Plan 7 — five mode-button rects (or empty for aura cannons)
    shopRects: [],          // populated by drawShop; one rect per shop card
    shopMode: 'list',       // 'list' | 'info' — Task 9 visual coherence shop sidebar
    shopInfoKind: null,     // selected kind when shopMode === 'info' (Task 10)
    shopInfoRects: [],      // populated by drawShop; one rect per "?" hangtag
    idlePlayRect: null,
    idleContinueRect: null, // populated on the idle splash when a saved run exists
    savedRun: null,         // validated save from localStorage (idle splash only)
    lastPointerType: null,  // sticks after pointerup; picks touch vs mouse wording
    endedAt: 0,             // performance.now() when the run ended; guards instant restart
    hintDone: false,        // tutorial hint retires once the first cannon lands
    detailBackRect: null,   // populated by drawTowerPanel; tap to clear selectedCannon
    shopInfoFrom: null,     // 'detail' | 'list' | null — entry point for the info panel
    detailUpgradeInfoRects: [null, null, null] // populated by drawTowerPanel; tap to drill into upgrade info
  };
  ui.savedRun = readSave();

  // True when the last pointer was a finger, or before any pointer has been
  // seen when the device's primary pointer is coarse.
  function usesTouch() {
    if (ui.lastPointerType === 'touch') { return true; }
    if (ui.lastPointerType === null) {
      if (window.matchMedia) { return window.matchMedia('(pointer: coarse)').matches; }
    }
    return false;
  }

  // Hit areas for the HUD pause and speed buttons. The art stays 22 px wide
  // (about 16 CSS px on a phone), so the touch target is padded out toward
  // 44 CSS px. Padding is capped by the neighbours: pause and speed split the
  // gap between them, speed stops short of Send Next, and the bottom edge
  // reaches only 6 px into the playfield (a cannon's own hit circle starts
  // right at the HUD line).
  function hudHitRects() {
    var scale = 1;
    var box = canvas.getBoundingClientRect();
    if (box.width > 0) { scale = box.width / WIDTH; }
    var need = 44 / scale;
    var pr = ui.pauseRect;
    var sr = ui.speedRect;
    var mid = (pr.x + pr.w + sr.x) / 2;
    var rightLimit = PLAYFIELD_W;
    if (isPortrait()) { rightLimit = WIDTH; }
    if (ui.sendNextRect) { rightLimit = (sr.x + sr.w + ui.sendNextRect.x) / 2; }
    var bottom = HUD_H + 6;
    var p0 = pr.x - (need - pr.w) / 2;
    if (p0 > mid - need) { p0 = mid - need; }
    if (p0 < 0) { p0 = 0; }
    var s1 = sr.x + sr.w + (need - sr.w) / 2;
    if (s1 < mid + need) { s1 = mid + need; }
    if (s1 > rightLimit) { s1 = rightLimit; }
    return {
      pause: { x: p0, y: 0, w: mid - p0, h: bottom },
      speed: { x: mid, y: 0, w: s1 - mid, h: bottom }
    };
  }

  function canvasToGame(evt) {
    var rect = canvas.getBoundingClientRect();
    var sx = WIDTH / rect.width;
    var sy = HEIGHT / rect.height;
    return {
      x: (evt.clientX - rect.left) * sx,
      y: (evt.clientY - rect.top) * sy
    };
  }

  // Returns true when the point is inside the playfield rect (between the
  // HUD and the bottom of the playable area, and within the playfield
  // width). Centralizes the orientation-aware check so the pointer handler
  // doesn't need to special-case landscape vs portrait. Replaces the old
  // `p.x < PLAYFIELD_W` shorthand, which incorrectly returned true for the
  // bottom strip in portrait (strip x range is 0..480, same as playfield).
  function inPlayfield(p) {
    if (p.x < 0) { return false; }
    if (p.x >= PLAYFIELD_W) { return false; }
    if (p.y < HUD_H) { return false; }
    if (p.y >= HUD_H + PLAYFIELD_H) { return false; }
    return true;
  }

  // Returns true when the point is inside the shop sidebar (landscape) or
  // the bottom strip (portrait). The complement of inPlayfield for the
  // non-HUD area. HUD-area taps (y < HUD_H) return false so they don't
  // fire side-panel hit-tests.
  function inSidePanel(p) {
    if (p.y < HUD_H) { return false; }
    if (isPortrait()) {
      // Portrait: strip lives BELOW the playfield.
      if (p.y < HUD_H + PLAYFIELD_H) { return false; }
      if (p.y >= HEIGHT) { return false; }
      return true;
    }
    // Landscape: sidebar lives to the RIGHT of the playfield.
    if (p.x < PLAYFIELD_W) { return false; }
    if (p.x >= WIDTH) { return false; }
    return true;
  }

  canvas.addEventListener('pointerdown', function (evt) {
    if (ui.activePointerId !== null) { return; }   // single-pointer lock
    // Mouse and touch players get the keyboard shortcuts too once they have
    // clicked the game; the keydown handler only listens while the canvas has focus.
    canvas.focus({ preventScroll: true });
    canvas.setPointerCapture(evt.pointerId);
    ui.activePointerId = evt.pointerId;
    ui.pointerType = evt.pointerType;
    ui.lastPointerType = evt.pointerType;
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
    // === Armed-state interactions (tap-to-arm world) ===
    // Runs before the normal hit-test cascade so an Armed gesture wins over
    // cannon-select and HUD pass-through. HUD pause/speed/sell/upgrade are
    // re-tested below: they do not clear armedKind.
    if (ui.armedKind !== null) {
      // activePointerId is guaranteed non-null here (set above after the
      // single-pointer-lock early return). Shop hit-tests first so a same-card
      // re-tap toggles off and a different-card tap swaps kind.
      if (ui.shopRects) {
        for (var sa = 0; sa < ui.shopRects.length; sa++) {
          var ra = ui.shopRects[sa];
          if (inRect(p, ra)) {
            if (ra.kind === ui.armedKind) {
              // Same card: toggle off.
              ui.armedKind = null;
              canvas.classList.remove('ab-dragging');
              return;
            }
            // Different card: swap if affordable, else ignore.
            var skSwap = CANNON_KINDS[ra.kind];
            if (game.gold >= skSwap.cost) {
              ui.armedKind = null;
              ui.pendingKind = ra.kind;
              ui.pendingX = p.x;
              ui.pendingY = p.y;
              canvas.classList.remove('ab-dragging');
            }
            return;
          }
        }
      }
      // Playfield: enter Placing (ghost will render via drawDragPreview).
      // Skipped while paused so the pause-overlay's Restart Run check downstream
      // can still receive the gesture. Armed survives the paused tap.
      if (inPlayfield(p)) {
        if (!game.paused) {
          // Note: do not call placeCannon here. Placement commits on
          // pointerup (Step 2 below) so the player can slide-to-adjust.
          canvas.classList.add('ab-dragging');
          return;
        }
      }
      // Anything else (HUD, side panel, etc.): fall through to normal
      // hit-tests below. armedKind is preserved.
    }
    // Pause overlay's Restart Run button — only live while paused. Tested first so
    // it wins over the HUD pause toggle behind it (which is in the same x range).
    if (game.paused) {
      if (ui.restartRunRect) {
        if (inRect(p, ui.restartRunRect)) { resetGame(); return; }
      }
      // Any other tap on the paused playfield resumes, so phone players
      // aren't left looking for a Space key. The gesture ends here, so its
      // pointerup can't also place an armed cannon on the spot.
      if (inPlayfield(p)) {
        game.paused = false;
        canvas.releasePointerCapture(evt.pointerId);
        ui.activePointerId = null;
        ui.pointerType = null;
        return;
      }
    }
    // HUD pause + speed widgets — always live during 'playing' state. Tested before
    // any other hit-test in case the playfield ever extends under them.
    if (game.state === 'playing') {
      var hudHit = hudHitRects();
      if (inRect(p, hudHit.pause)) { game.paused = !game.paused; return; }
      if (inRect(p, hudHit.speed)) {
        game.speed = game.speed + 1;
        if (game.speed > 3) { game.speed = 1; }
        return;
      }
    }
    // The first second of a result screen ignores taps so a panicked tap at
    // the end of a run does not skip it.
    if (game.waveState === 'won') {
      if (restartReady()) { resetGame(); }
      return;
    }
    if (game.waveState === 'lost') {
      if (restartReady()) { resetGame(); }
      return;
    }
    if (game.state === 'idle') {
      // With a save on file only the two buttons act, so a stray tap near
      // Continue cannot wipe the run by starting a new game.
      if (ui.savedRun) {
        if (ui.idleContinueRect) {
          if (inRect(p, ui.idleContinueRect)) { continueRun(); return; }
        }
        if (ui.idlePlayRect) {
          if (inRect(p, ui.idlePlayRect)) { startNewRun(); }
        }
        return;
      }
      startNewRun();
      return;
    }
    if (game.waveState === 'prep') {
      if (ui.sendNextRect) {
        if (inRect(p, ui.sendNextRect)) { sendNext(); return; }
      }
    }
    if (game.state === 'playing') {
      if (inPlayfield(p)) {
        // If info panel is open, close it on any playfield tap.
        if (ui.shopMode === 'info') {
          ui.shopMode = 'list';
          ui.shopInfoKind = null;
          ui.shopInfoFrom = null;
        }
        var hit = cannonAt(p.x, p.y);
        if (hit) {
          ui.selectedCannon = hit;
          return;
        }
        // click in playfield but not on a cannon: deselect
        if (ui.selectedCannon) {
          ui.selectedCannon = null;
          return;
        }
      }
      // Click inside the right-column panel while a cannon is selected — test
      // the targeting buttons first (Plan 7), then upgrade buttons (up to 3),
      // then the sell button. Only run this hit-test cascade when the detail
      // panel is the panel actually being rendered (shopMode === 'list'). When
      // the info panel is showing on top of a selected cannon (shopMode ===
      // 'info'), the detail-panel rects (detailBackRect, upgradeRects, etc.)
      // are stale from the previous frame and overlap the info panel's own
      // rects — including its back link — which would cause an info-panel
      // back tap to be eaten by the stale detail-panel back rect, clearing
      // selectedCannon silently.
      if (ui.selectedCannon) {
       if (ui.shopMode === 'list') {
        if (inSidePanel(p)) {
          // Detail-panel back link: clear selection.
          if (ui.detailBackRect) {
            var dbr = ui.detailBackRect;
            if (p.x >= dbr.x) {
              if (p.x <= dbr.x + dbr.w) {
                if (p.y >= dbr.y) {
                  if (p.y <= dbr.y + dbr.h) {
                    ui.selectedCannon = null;
                    canvas.releasePointerCapture(evt.pointerId);
                    ui.activePointerId = null;
                    ui.pointerType = null;
                    return;
                  }
                }
              }
            }
          }
          // Plan 7 — Targeting row hit-test. Mode strings are positional and
          // must match the order in drawTargetingRow's `modes` array.
          if (ui.targetingRects) {
            var tModes = ['closest', 'first', 'carrying', 'strongest', 'weakest'];
            for (var ti = 0; ti < ui.targetingRects.length; ti++) {
              var trec = ui.targetingRects[ti];
              if (trec) {
                if (inRect(p, trec)) {
                  ui.selectedCannon.targetingMode = tModes[ti];
                  return;
                }
              }
            }
          }
          // ? hangtag on a detail-panel upgrade row: open info panel for that upgrade.
          if (ui.detailUpgradeInfoRects) {
            for (var diuI = 0; diuI < ui.detailUpgradeInfoRects.length; diuI++) {
              var diur = ui.detailUpgradeInfoRects[diuI];
              if (!diur) { continue; }
              if (p.x >= diur.x) {
                if (p.x <= diur.x + diur.w) {
                  if (p.y >= diur.y) {
                    if (p.y <= diur.y + diur.h) {
                      ui.shopMode = 'info';
                      ui.shopInfoKind = diur.kind;
                      ui.shopInfoFrom = 'detail';
                      canvas.releasePointerCapture(evt.pointerId);
                      ui.activePointerId = null;
                      ui.pointerType = null;
                      return;
                    }
                  }
                }
              }
            }
          }
          for (var ri = 0; ri < ui.upgradeRects.length; ri++) {
            var ur = ui.upgradeRects[ri];
            if (ur) {
              if (inRect(p, ur)) {
                upgradeCannon(ui.selectedCannon, ur.toKind);
                return;
              }
            }
          }
          if (ui.sellRect) {
            if (inRect(p, ui.sellRect)) {
              sellCannon(ui.selectedCannon);
              return;
            }
          }
        }
       }
      }
    }
    // Visual coherence: hangtag click flips to info panel.
    if (ui.shopMode === 'info') {
      // Back link?
      if (ui.shopInfoBackRect) {
        var br = ui.shopInfoBackRect;
        if (p.x >= br.x) {
          if (p.x <= br.x + br.w) {
            if (p.y >= br.y) {
              if (p.y <= br.y + br.h) {
                ui.shopMode = 'list';
                ui.shopInfoKind = null;
                ui.shopInfoFrom = null;
                canvas.releasePointerCapture(evt.pointerId);
                ui.activePointerId = null;
                ui.pointerType = null;
                return;
              }
            }
          }
        }
      }
      // Any other tap inside the info panel: close.
      if (inSidePanel(p)) {
        ui.shopMode = 'list';
        ui.shopInfoKind = null;
        ui.shopInfoFrom = null;
        canvas.releasePointerCapture(evt.pointerId);
        ui.activePointerId = null;
        ui.pointerType = null;
        return;
      }
    }
    // Hangtag rects always checked first when in list mode.
    if (ui.shopMode === 'list') {
      for (var hi = 0; hi < ui.shopInfoRects.length; hi++) {
        var hr = ui.shopInfoRects[hi];
        if (p.x >= hr.x) {
          if (p.x <= hr.x + hr.w) {
            if (p.y >= hr.y) {
              if (p.y <= hr.y + hr.h) {
                ui.shopMode = 'info';
                ui.shopInfoKind = hr.kind;
                canvas.releasePointerCapture(evt.pointerId);
                ui.activePointerId = null;
                ui.pointerType = null;
                return;
              }
            }
          }
        }
      }
    }
    if (ui.shopRects) {
      for (var si = 0; si < ui.shopRects.length; si++) {
        var rs = ui.shopRects[si];
        if (inRect(p, rs)) {
          // Pending state: motion in pointermove will promote to Dragging.
          var sk = CANNON_KINDS[rs.kind];
          if (game.gold >= sk.cost) {
            ui.pendingKind = rs.kind;
            ui.pendingX = p.x;
            ui.pendingY = p.y;
          }
          return;
        }
      }
    }
  });

  canvas.addEventListener('pointermove', function (evt) {
    // Two-level guard: allow hover events when no gesture is active
    // (activePointerId === null), and filter spurious second pointers when one is.
    if (ui.activePointerId !== null) {
      if (evt.pointerId !== ui.activePointerId) { return; }
    }
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
    // Pending -> Dragging promotion: 6 game-px squared distance from down point.
    if (ui.pendingKind !== null) {
      var ddx = p.x - ui.pendingX;
      var ddy = p.y - ui.pendingY;
      if (ddx * ddx + ddy * ddy > 36) {
        ui.armedKind = ui.pendingKind;
        ui.pendingKind = null;
        canvas.classList.add('ab-dragging');
      }
    }
    // Track upgrade-button hover so drawRangeCircle can preview the target's range
    // as a dashed secondary circle. Avoids and-operator for the inline-script gotcha.
    var hovered = null;
    for (var ri = 0; ri < ui.upgradeRects.length; ri++) {
      var ur = ui.upgradeRects[ri];
      if (!ur) { continue; }
      if (p.x >= ur.x) {
        if (p.x <= ur.x + ur.w) {
          if (p.y >= ur.y) {
            if (p.y <= ur.y + ur.h) {
              hovered = ur.toKind;
              break;
            }
          }
        }
      }
    }
    ui.hoveredUpgrade = hovered;
  });

  canvas.addEventListener('pointerup', function (evt) {
    if (evt.pointerId !== ui.activePointerId) { return; }
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
    // Case A: Pending without motion -> Armed (tap-to-arm).
    if (ui.pendingKind !== null) {
      ui.armedKind = ui.pendingKind;
      ui.pendingKind = null;
      // ab-dragging class stays off: Armed without pointer down has no grab cursor.
    } else if (ui.armedKind !== null) {
      // Case B: Dragging or Placing -> commit if valid.
      // Skipped while paused so a stray paused tap cannot place. Armed survives.
      if (game.paused) {
        canvas.classList.remove('ab-dragging');
      } else if (canPlaceCannon(p.x, p.y)) {
        placeCannon(ui.armedKind, p.x, p.y);
        ui.armedKind = null;
        canvas.classList.remove('ab-dragging');
      } else {
        // Case C: invalid spot. Drag-from-shop releases (Idle); tap-armed
        // placement attempts stay Armed. We distinguish by pointerType:
        // touch/pen came from tap-to-arm and stay armed for retry; mouse
        // came from drag-from-shop and drops to Idle ("abandon" semantics).
        if (ui.pointerType === 'touch') {
          // Stay Armed; push the red-pulse flash at the rejected tap location.
          game.effects.push({ kind: 'invalidPlacement', x: p.x, y: p.y, t: 0, life: 0.15 });
          canvas.classList.remove('ab-dragging');
        } else if (ui.pointerType === 'pen') {
          // Same as touch: stay Armed. Separate branch avoids || (inline-script gotcha).
          game.effects.push({ kind: 'invalidPlacement', x: p.x, y: p.y, t: 0, life: 0.15 });
          canvas.classList.remove('ab-dragging');
        } else {
          ui.armedKind = null;
          canvas.classList.remove('ab-dragging');
        }
      }
    }
    canvas.releasePointerCapture(evt.pointerId);
    ui.activePointerId = null;
    ui.pointerType = null;
  });

  canvas.addEventListener('pointercancel', function (evt) {
    if (evt.pointerId !== ui.activePointerId) { return; }
    // OS-interrupt rule: drop Pending and the in-progress Dragging/Placing
    // gesture, but leave Armed intact so the user can finish the placement
    // after the interruption ends.
    ui.pendingKind = null;
    // armedKind is preserved iff this gesture started from Armed (tap-to-place).
    // In Dragging-from-shop, Pending->Dragging set armedKind, so cancel here
    // means dropping armedKind too. We distinguish by pointerType again:
    // touch/pen pointers came from tap-to-place, mouse pointers came from
    // drag-from-shop.
    if (ui.armedKind !== null) {
      if (ui.pointerType === 'mouse') {
        ui.armedKind = null;
      }
      // touch and pen: leave armedKind set (Armed survives).
    }
    canvas.classList.remove('ab-dragging');
    canvas.releasePointerCapture(evt.pointerId);
    ui.activePointerId = null;
    ui.pointerType = null;
  });

  // === UPDATE ===
  // Speed (1x/2x/3x) runs update() that many times per fixed step, always at
  // FIXED_DT, so movement, cooldowns, status timers, the spawner and game.time
  // all advance together and a faster game plays the same game, only sooner.
  // The wave banner and prep countdown ride game.time, so they speed up too.
  // Tick transient visual effects (e.g. Bomber AoE flash). Each entry has its own
  // `t` (elapsed) and `life` (total seconds); when `t` reaches `life` the entry is
  // dropped. New entries are pushed by gameplay hooks like ANT_KINDS.bomber.onDeath.
  function updateEffects(dt) {
    var kept = [];
    for (var i = 0; i < game.effects.length; i++) {
      game.effects[i].t = game.effects[i].t + dt;
      if (game.effects[i].t < game.effects[i].life) { kept.push(game.effects[i]); }
    }
    game.effects = kept;
  }

  // Task 23: tick fallen-slice timeouts. After 8s of nobody picking up a dropped
  // slice, it snaps back to the cake without a life deduction (the player is not
  // penalized for an enemy that died mid-carry). dt is unused — we compare against
  // game.simTime which advances in update() — but kept in the signature for parity
  // with the other update* functions.
  function updateSlices(dt) {
    var kept = [];
    for (var i = 0; i < game.slices.length; i++) {
      var s = game.slices[i];
      // Plan 4 Task 26 — read simTime so 8s "feels" like 8 simulation seconds
      // at any speed multiplier. Slices dropped from killAnt also stamp
      // droppedAt: game.simTime, so the diff stays consistent.
      var age = game.simTime - s.droppedAt;
      if (age >= 8) {
        game.cake.slices = game.cake.slices + 1;
        continue;
      }
      kept.push(s);
    }
    game.slices = kept;
  }

  function update(dt) {
    if (game.paused) { return; }
    if (game.state !== 'playing') { return; }
    if (game.waveState === 'won') { return; }
    if (game.waveState === 'lost') { return; }
    game.time += dt;
    // Plan 4 Task 26 — slice timeout reads game.simTime. Speed now runs whole
    // fixed steps, so it matches game.time.
    game.simTime = game.simTime + dt;
    updateWaveState(dt);
    // Periodic spawn jobs (Plan 3 Task 31 — King's worker drip). Each job
    // fires on its own interval and is owned by an ant; jobs whose owners
    // have died are pruned out. Nested ifs to avoid the inline-script and-operator.
    for (var ji = 0; ji < game.spawnJobs.length; ji++) {
      var job = game.spawnJobs[ji];
      if (job.owner) {
        if (job.owner.dead) { continue; }
      }
      if (game.time - job.lastFiredAt >= job.interval) {
        job.lastFiredAt = game.time;
        job.fn();
      }
    }
    var keptJobs = [];
    for (var jj = 0; jj < game.spawnJobs.length; jj++) {
      var keep = true;
      if (game.spawnJobs[jj].owner) {
        if (game.spawnJobs[jj].owner.dead) { keep = false; }
      }
      if (keep) { keptJobs.push(game.spawnJobs[jj]); }
    }
    game.spawnJobs = keptJobs;
    updateAnts(dt);
    updateCannons(dt);
    updateProjectiles(dt);
    // Tesla lightning bolts decay against fxTime, like the other effects.
    // Each bolt lives ~0.15s; expired bolts are filtered out before render.
    var keptBolts = [];
    for (var lbi = 0; lbi < game.lightningBolts.length; lbi++) {
      if (game.lightningBolts[lbi].expiresAt > game.fxTime) {
        keptBolts.push(game.lightningBolts[lbi]);
      }
    }
    game.lightningBolts = keptBolts;
    updateSlices(dt);
    checkPickupsAndDeliveries();
    pruneAnts();
  }

  // === MAIN LOOP ===
  var lastTime = performance.now();
  function frame(now) {
    var elapsed = (now - lastTime) / 1000;
    lastTime = now;
    if (elapsed > 0.25) { elapsed = 0.25; }   // clamp to avoid spiral-of-death
    // Screenshake and shatter particles run on the wall clock, outside
    // update(), so they settle on the pause, win and lose screens and last the
    // same time at any speed. fxTime and the transient effects do the same,
    // but hold still while paused.
    if (!game.paused) {
      game.fxTime = game.fxTime + elapsed;
      updateEffects(elapsed);
    }
    if (game.screenshake.magnitude > 0) {
      game.screenshake.magnitude = game.screenshake.magnitude - game.screenshake.decay * elapsed;
      if (game.screenshake.magnitude < 0) { game.screenshake.magnitude = 0; }
    }
    updateShatterParticles(elapsed);
    game.accumulator += elapsed;
    while (game.accumulator >= FIXED_DT) {
      for (var st = 0; st < game.speed; st++) { update(FIXED_DT); }
      game.accumulator -= FIXED_DT;
    }
    render();
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);

  // Keybinds. Listens on the canvas, so they only work while the game has
  // focus (click it, or Tab to it), and page keys like Space still scroll the
  // page everywhere else. preventDefault only fires for keys the game uses.
  //   Enter  — skip the prep window (same as Send Next); starts or continues
  //            from the splash; restarts from a result screen
  //   Space  — toggle pause while playing
  //   N      — new game from the splash when a save exists
  //   1/2/3  — set speed
  //   Escape — close the info panel, drop an armed cannon, deselect
  canvas.addEventListener('keydown', function (e) {
    if (e.ctrlKey) { return; }
    if (e.metaKey) { return; }
    if (e.altKey) { return; }
    if (game.state === 'idle') {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (ui.savedRun) { continueRun(); } else { startNewRun(); }
        return;
      }
      if (e.key === 'n') {
        e.preventDefault();
        startNewRun();
        return;
      }
      if (e.key === 'N') {
        e.preventDefault();
        startNewRun();
        return;
      }
      return;
    }
    if (game.waveState === 'won') {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (restartReady()) { resetGame(); }
      }
      return;
    }
    if (game.waveState === 'lost') {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (restartReady()) { resetGame(); }
      }
      return;
    }
    if (e.key === 'Enter') {
      e.preventDefault();
      if (e.repeat) { return; }
      if (game.waveState === 'prep') { sendNext(); }
      return;
    }
    if (e.key === ' ') {
      e.preventDefault();
      if (e.repeat) { return; }
      game.paused = !game.paused;
      return;
    }
    if (e.key === '1') { game.speed = 1; return; }
    if (e.key === '2') { game.speed = 2; return; }
    if (e.key === '3') { game.speed = 3; return; }
    if (e.key === 'Escape') {
      if (ui.shopMode === 'info') {
        ui.shopMode = 'list';
        ui.shopInfoKind = null;
        ui.shopInfoFrom = null;
        e.preventDefault();
        return;
      }
      // ESC unarms on desktop. Touch users have the same-card-retap to unarm.
      if (ui.armedKind !== null) {
        ui.armedKind = null;
        canvas.classList.remove('ab-dragging');
      }
      ui.selectedCannon = null;
      return;
    }
  });
})();
</script>
