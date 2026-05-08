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
  <p>Defend the cake from the ants. Drag a cannon onto the picnic blanket and try to keep the swarm from reaching the cake.</p>
  <!-- /wp:paragraph -->

  <!-- wp:html -->
  <div class="ab-stage">
    <canvas id="ab-canvas" data-role="canvas" aria-label="Ant Buster game canvas" width="1280" height="720"></canvas>
  </div>
  <!-- /wp:html -->

</div>
<!-- /wp:group -->

<style>
.demo-ant-buster {
  --ab-bg: #1a1c2c;
  --ab-frame: #2a2f3e;
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
}

.demo-ant-buster #ab-canvas.ab-dragging {
  cursor: grabbing;
}

@media (max-width: 1000px) {
  .demo-ant-buster #ab-canvas {
    width: 100%;
  }
}
</style>

<script>
(function () {
  'use strict';

  // === BODY CLASS (full-bleed paint workaround) ===
  document.body.classList.add('has-ant-buster-page');

  // === CONSTANTS ===
  var WIDTH = 640;
  var HEIGHT = 360;
  var PLAYFIELD_W = 480;
  var HUD_H = 24;
  var SHOP_W = 160;
  var TARGET_FPS = 60;
  var FIXED_DT = 1 / TARGET_FPS;

  // === DEBUG MODE ===
  // Captured once at parse time from the URL. ?debug=1 enables the T hotkey
  // (cycles spawning each ant kind at the anthill). The check uses a plain
  // regex — splitting case avoids the `||` operator, which gets HTML-encoded
  // inside inline scripts in this theme.
  var DEBUG = /[?&]debug=1/.test(location.search);
  var DEBUG_KINDS = ['worker', 'carrier', 'scout', 'soldier', 'tank', 'splitter', 'phase', 'bomber', 'queen'];
  var debugCycleIdx = 0;

  // === GAME STATE ===
  // Two flag layers, intentionally kept separate (Plan 2 Task 5):
  //   game.state     — 'idle' | 'playing'   (top-level: has the player started?)
  //   game.waveState — 'prep' | 'wave' | 'won' | 'lost'   (campaign progress)
  // Rationale: 'idle' is a pre-game splash concern; win/lose belongs to the wave
  // state machine. The render and input layers read whichever flag is relevant.
  var game = {
    state: 'idle',            // 'idle' | 'playing'
    time: 0,                  // seconds since game start
    accumulator: 0,           // for fixed-timestep
    cake: { x: 352, y: 76, slices: 8, lost: 0 },
    anthill: { x: 22, y: 184 },
    ants: [],
    cannons: [],
    projectiles: [],
    effects: [],              // transient visual effects (e.g. Bomber AoE flash); see updateEffects
    lightningBolts: [],       // Tesla arc-jump polylines; each { points: [{x,y}], expiresAt: number }
    slices: [],               // fallen cake slices on the ground (Task 23); 8s timeout returns to cake
    sprites: {},              // populated in Task 4
    gold: 150,
    waveIndex: 0,             // index into WAVES
    waveState: 'prep',        // 'prep' | 'wave' | 'won' | 'lost'
    prepRemaining: 30,        // seconds remaining in current prep window
    spawner: { groupIndex: 0, spawnedInGroup: 0, nextSpawnAt: 0, queueDone: false },
    banner: { active: false, text: '', startedAt: 0, duration: 2.5, isBoss: false },
    screenshake: { magnitude: 0, decay: 0 },
    paused: false,
    speed: 1
  };

  // Module-level rect helper used by HUD hit-tests. Avoids inline `&&` chains
  // (which the WP content filter occasionally HTML-encodes) and centralizes the
  // pattern that future tasks will reuse for upgrade buttons, selected-tower UI, etc.
  function inRect(p, r) {
    if (p.x < r.x) { return false; }
    if (p.x > r.x + r.w) { return false; }
    if (p.y < r.y) { return false; }
    if (p.y > r.y + r.h) { return false; }
    return true;
  }

  // Show a wave-intro banner. Duration is longer for boss waves.
  function showBanner(text, isBoss) {
    game.banner.active = true;
    game.banner.text = text;
    game.banner.startedAt = game.time;
    game.banner.duration = isBoss ? 3 : 2.5;
    game.banner.isBoss = isBoss;
  }

  // === CANVAS SETUP ===
  var canvas = document.getElementById('ab-canvas');
  if (!canvas) { return; }
  var ctx = canvas.getContext('2d');
  ctx.scale(2, 2);
  ctx.imageSmoothingEnabled = false;

  // === SPRITES ===
  // All sprites are generated procedurally as offscreen canvases.
  // Plan 4 will swap these out for real PNGs loaded from disk.
  function makeCanvas(w, h) {
    var c = document.createElement('canvas');
    c.width = w;
    c.height = h;
    return c;
  }

  function makeWorkerSprite() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    x.fillStyle = '#3a4466';
    x.beginPath();
    x.ellipse(8, 8, 5, 4, 0, 0, Math.PI * 2);   // body
    x.fill();
    x.fillStyle = '#262b44';
    x.fillRect(7, 3, 2, 2);                     // head
    x.fillStyle = '#181425';
    x.fillRect(7, 1, 1, 2);                     // antenna L
    x.fillRect(8, 1, 1, 2);                     // antenna R
    return c;
  }

  function makeSoldierSprite() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    x.fillStyle = '#2a2d42';                    // darker base than worker
    x.beginPath();
    x.ellipse(8, 9, 6, 5, 0, 0, Math.PI * 2);   // chunkier body
    x.fill();
    x.fillStyle = '#1a1d32';                    // very dark head
    x.fillRect(6, 4, 4, 3);                     // blocky head / helmet
    x.fillStyle = '#3a4466';                    // helmet highlight
    x.fillRect(7, 3, 2, 1);                     // small helmet peak on top
    x.fillStyle = '#181425';
    x.fillRect(5, 2, 1, 1);                     // antenna L
    x.fillRect(10, 2, 1, 1);                    // antenna R
    return c;
  }

  function makeScout() {
    var c = makeCanvas(12, 12);
    var x = c.getContext('2d');
    x.fillStyle = '#f4b41b';
    x.fillRect(3, 3, 6, 6);                     // body
    x.fillStyle = '#222034';
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
    // Heavy armored body, dark grey; blocky treads visual
    x.fillStyle = '#3a4053';
    x.beginPath();
    x.ellipse(12, 12, 8, 6, 0, 0, Math.PI * 2);   // main body, broader than worker
    x.fill();
    x.fillStyle = '#2a2f42';                       // darker accent
    x.fillRect(5, 10, 14, 2);                      // bottom plate / tread line
    x.fillStyle = '#4a5063';                       // lighter accent for armor plating
    x.fillRect(4, 8, 3, 8);                        // left armor plate
    x.fillRect(16, 8, 3, 8);                       // right armor plate
    x.fillStyle = '#1a1c2c';                       // very dark head
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
    x.fillStyle = '#262b44';
    x.fillRect(7, 7, 2, 4);                     // fissure line down middle
    x.fillStyle = '#3a4466';
    x.fillRect(4, 3, 2, 2);                     // left head bump
    x.fillRect(10, 3, 2, 2);                    // right head bump
    x.fillStyle = '#181425';
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
    x.fillStyle = '#262b44';
    x.fillRect(3, 2, 2, 4);                     // fissure
    x.fillStyle = '#181425';
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
    x.fillStyle = '#222034';
    x.fillRect(8, 2, 1, 2);                     // dark fuse-like spot on top
    x.fillStyle = '#fee761';
    x.fillRect(8, 1, 1, 1);                     // tiny spark on the fuse tip
    x.fillStyle = '#181425';
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
    x.fillStyle = '#181425';
    x.fillRect(8, 4, 1, 1);                       // antenna L
    x.fillRect(15, 4, 1, 1);                      // antenna R
    return c;
  }

  function makePhase() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    // Ghostly translucent purple body with thin outline.
    x.fillStyle = '#7d4a8c';
    x.fillRect(4, 3, 8, 10);                    // body
    x.fillRect(3, 5, 2, 6);                     // left segment
    x.fillRect(11, 5, 2, 6);                    // right segment
    x.fillStyle = '#5a3a6c';
    x.strokeStyle = '#3d2a5c';
    x.lineWidth = 1;
    x.strokeRect(4, 3, 8, 10);                  // outline
    x.fillStyle = '#181425';
    x.fillRect(6, 1, 1, 1);                     // antenna L
    x.fillRect(9, 1, 1, 1);                     // antenna R
    return c;
  }

  function makeAntSplat() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    x.fillStyle = '#262b44';
    x.fillRect(4, 7, 8, 3);
    x.fillRect(5, 6, 6, 1);
    x.fillRect(5, 10, 6, 1);
    return c;
  }

  function makeCannonBase() {
    var c = makeCanvas(32, 32);
    var x = c.getContext('2d');
    x.fillStyle = '#5a6988';
    x.beginPath();
    x.arc(16, 16, 12, 0, Math.PI * 2);
    x.fill();
    x.fillStyle = '#8b9bb4';
    x.beginPath();
    x.arc(16, 16, 10, 0, Math.PI * 2);
    x.fill();
    return c;
  }

  function makeCannonTurret() {
    var c = makeCanvas(20, 20);
    var x = c.getContext('2d');
    x.fillStyle = '#262b44';
    x.fillRect(8, 4, 4, 12);                    // barrel pointing up (rotated at draw time)
    x.fillStyle = '#181425';
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

  function makeCake(slices) {
    var c = makeCanvas(32, 32);
    var x = c.getContext('2d');
    var maxSlices = 8;
    var fillRatio = slices / maxSlices;
    if (fillRatio > 0) {
      x.fillStyle = '#feae34';
      x.fillRect(8, 12 + (1 - fillRatio) * 12, 16, fillRatio * 12);
      x.fillStyle = '#fee761';
      x.fillRect(10, 8 + (1 - fillRatio) * 12, 12, fillRatio * 6);
      x.fillStyle = '#e43b44';
      x.fillRect(15, 4, 2, 6);                  // candle
      x.fillStyle = '#fee761';
      x.fillRect(14, 2, 4, 3);                  // flame
    }
    x.fillStyle = '#ffffff';
    x.fillRect(6, 24, 20, 3);                   // plate
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
    x.fillStyle = '#181425';
    x.beginPath();
    x.ellipse(16, 22, 4, 2, 0, 0, Math.PI * 2);
    x.fill();
    return c;
  }

  function makeProjectile() {
    var c = makeCanvas(4, 4);
    var x = c.getContext('2d');
    x.fillStyle = '#181425';
    x.fillRect(0, 0, 4, 4);
    return c;
  }

  game.sprites = {
    worker: makeWorkerSprite(),
    soldier: makeSoldierSprite(),
    scout: makeScout(),
    tank: makeTank(),
    splitter: makeSplitter(),
    mini_splitter: makeMiniSplitter(),
    phase: makePhase(),
    bomber: makeBomber(),
    queen: makeQueen(),
    splat: makeAntSplat(),
    cannonBase: makeCannonBase(),
    cannonTurret: makeCannonTurret(),         // kept for backward refs; lookups now go through turrets[kind]
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
      frost: makeCannonTurretFrost(),
      glacier: makeCannonTurretGlacier(),
      cryo: makeCannonTurretCryo()
    },
    anthill: makeAnthill(),
    projectile: makeProjectile()
  };
  // Note: cake sprite is regenerated in render() per frame because it depends on slice count.

  // === RENDERING ===
  function drawShop() {
    var cardX = PLAYFIELD_W + 8;
    var startY = 32;
    var cardH = 70;
    var cardW = SHOP_W - 16;
    var keys = ['howitzer', 'repeater', 'twingun'];
    ui.shopRects = [];
    for (var i = 0; i < keys.length; i++) {
      var k = keys[i];
      var sk = CANNON_KINDS[k];
      var cy = startY + i * (cardH + 8);
      // card background
      ctx.fillStyle = '#262b44';
      ctx.fillRect(cardX, cy, cardW, cardH);
      // turret sprite mini icon (top-left of card)
      var turret = game.sprites.turrets[k];
      if (turret) { ctx.drawImage(turret, cardX + 6, cy + 6); }
      // name
      ctx.fillStyle = '#fee761';
      ctx.font = '10px monospace';
      ctx.textAlign = 'left';
      ctx.fillText(sk.name, cardX + 32, cy + 22);
      // cost
      var canAfford = game.gold >= sk.cost;
      ctx.fillStyle = canAfford ? '#fee761' : '#888';
      ctx.fillText(sk.cost + 'g', cardX + 32, cy + 38);
      // tier or family hint (small)
      ctx.fillStyle = '#999';
      ctx.font = '8px monospace';
      ctx.fillText(sk.family, cardX + 32, cy + 52);
      ui.shopRects.push({ x: cardX, y: cy, w: cardW, h: cardH, kind: k });
    }
    ctx.font = '10px monospace';   // restore default
    ctx.textAlign = 'start';
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
    if (game.gold < udef.cost) { return; }
    game.gold = game.gold - udef.cost;
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

  function drawTowerPanel(c) {
    var sk = CANNON_KINDS[c.kind];
    var px = PLAYFIELD_W + 8;
    var py = 32;
    var pw = SHOP_W - 16;
    ctx.fillStyle = '#262b44';
    ctx.fillRect(px, py, pw, 200);
    ctx.fillStyle = '#fee761';
    ctx.font = '10px monospace';
    ctx.textAlign = 'left';
    ctx.fillText(sk.name, px + 6, py + 14);
    ctx.fillStyle = '#cccccc';
    ctx.fillText('DMG ' + sk.damage, px + 6, py + 30);
    ctx.fillText('RATE ' + sk.fireRate + '/s', px + 6, py + 42);
    ctx.fillText('RANGE ' + sk.range, px + 6, py + 54);
    var killCount;
    if (c.kills) { killCount = c.kills; } else { killCount = 0; }
    ctx.fillText('KILLS ' + killCount, px + 6, py + 66);

    // Upgrade buttons (up to 3) — only present when sk.upgradesTo is non-empty.
    // Base cannons offer 3 mid options, mid cannons offer 2 finals, finals offer 0.
    var upgrades = sk.upgradesTo;
    if (!upgrades) { upgrades = []; }
    for (var i = 0; i < upgrades.length; i++) {
      var uk = upgrades[i];
      var udef = CANNON_KINDS[uk];
      var by = py + 90 + i * 22;
      var canAfford = (game.gold >= udef.cost);
      ctx.fillStyle = canAfford ? '#3a8c3a' : '#444';
      ctx.fillRect(px + 6, by, pw - 12, 18);
      ctx.fillStyle = canAfford ? '#fee761' : '#888';
      ctx.fillText('-> ' + udef.name + ' (' + udef.cost + 'g)', px + 10, by + 12);
      ui.upgradeRects[i] = { x: px + 6, y: by, w: pw - 12, h: 18, toKind: uk };
    }
    for (var k = upgrades.length; k < 3; k++) { ui.upgradeRects[k] = null; }

    // Sell button — pinned near the bottom of the panel rect.
    var refund = sellRefund(c);
    var sy = py + 200 - 26;
    ctx.fillStyle = '#a44';
    ctx.fillRect(px + 6, sy, pw - 12, 18);
    ctx.fillStyle = '#fee761';
    ctx.fillText('Sell (+' + refund + 'g)', px + 10, sy + 12);
    ui.sellRect = { x: px + 6, y: sy, w: pw - 12, h: 18 };

    ctx.textAlign = 'start';
  }

  // === Render helpers (z-order spec §3.5, layers 1–13) ===
  // render() is the single entry point and walks back-to-front through the
  // canonical layer list. Each helper below owns one layer (or one z-slot).

  // Layer 1: blanket + grass tiles + cake plate background.
  function drawBackground() {
    ctx.fillStyle = '#193c3e';
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

    // Shop panel background (right column). Drawn early so HUD/shop content
    // composites over it later in the layer list.
    ctx.fillStyle = '#1a1c2c';
    ctx.fillRect(PLAYFIELD_W, 0, SHOP_W, HEIGHT);
  }

  // Layer 2: cake (regenerated each frame because it depends on slice count).
  function drawCake() {
    var cakeSprite = makeCake(game.cake.slices);
    ctx.drawImage(cakeSprite, game.cake.x - 16, game.cake.y - 16);
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
  function drawRangeCircle(c) {
    var selKind = CANNON_KINDS[c.kind];
    ctx.strokeStyle = 'rgba(255, 231, 97, 0.5)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.arc(c.x, c.y, selKind.range, 0, Math.PI * 2);
    ctx.stroke();

    // Hover-preview: when the player hovers an upgrade button, show the upgrade
    // target's range as a dashed purple circle. Honest preview before spending gold.
    if (ui.hoveredUpgrade) {
      var udef = CANNON_KINDS[ui.hoveredUpgrade];
      if (udef) {
        ctx.strokeStyle = '#a040c0';
        ctx.setLineDash([4, 4]);
        ctx.beginPath();
        ctx.arc(c.x, c.y, udef.range, 0, Math.PI * 2);
        ctx.stroke();
        ctx.setLineDash([]);
      }
    }
  }

  // Layer 6: static cannon bases. Split from the turret pass so the round bases
  // sit under ants while the turrets read on top.
  function drawCannonBases() {
    for (var ci = 0; ci < game.cannons.length; ci++) {
      var c = game.cannons[ci];
      ctx.drawImage(game.sprites.cannonBase, Math.round(c.x - 16), Math.round(c.y - 16));
    }
  }

  // Layer 7: ants, sorted top-down by y so a lower ant draws over a higher one.
  // The slice() copy keeps game.ants stable for indices/refs used elsewhere.
  function drawAnts() {
    var sorted = game.ants.slice().sort(function(a, b) { return a.y - b.y; });
    for (var i = 0; i < sorted.length; i++) {
      var a = sorted[i];
      var k = ANT_KINDS[a.kind];
      var sprite;
      if (a.dead) {
        // Delivered ants vanish silently. Only killed ants splat. Nested ifs avoid
        // the AND operator (gets HTML-encoded inside inline scripts in this theme).
        if (a.deliveredAt === 0) {
          sprite = game.sprites.splat;
        } else {
          continue;
        }
      } else {
        sprite = game.sprites[k.spriteKey];
      }
      if (a.intangible) {
        ctx.save();
        ctx.globalAlpha = 0.3;
        ctx.drawImage(sprite, Math.round(a.x - k.spriteW / 2), Math.round(a.y - k.spriteH / 2));
        ctx.restore();
      } else {
        ctx.drawImage(sprite, Math.round(a.x - k.spriteW / 2), Math.round(a.y - k.spriteH / 2));
      }
      if (!a.dead) {
        if (a.carrying) {
          // Crumb sub-step: tiny orange dot above the ant body.
          ctx.fillStyle = '#feae34';
          ctx.fillRect(Math.round(a.x - 3), Math.round(a.y - 12), 6, 4);
          ctx.fillStyle = '#fee761';
          ctx.fillRect(Math.round(a.x - 2), Math.round(a.y - 11), 4, 2);
        }
        drawAntHpBar(a);
      }
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

    var bx = ant.x - bw / 2;
    var by = ant.y - 14;
    var pct = ant.hp / ant.maxHp;
    if (pct < 0) { pct = 0; }

    if (border) {
      ctx.fillStyle = border;
      ctx.fillRect(bx - 1, by - 1, bw + 2, bh + 2);
    }
    ctx.fillStyle = '#262b44';
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
      ctx.save();
      ctx.translate(Math.round(c.x), Math.round(c.y));
      ctx.rotate(c.heading + Math.PI / 2);
      var turretSprite = game.sprites.turrets[c.kind];
      if (!turretSprite) { turretSprite = game.sprites.turrets.howitzer; }
      ctx.drawImage(turretSprite, -10, -16);
      ctx.restore();
      if (c.disabled) {
        if (Math.floor(game.time * 6) % 2 === 0) {
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
      var arcOffset = 0;
      if (p.arcing) {
        var t;
        if (p.arcDuration > 0) { t = (game.time - p.arcStartedAt) / p.arcDuration; } else { t = 1; }
        if (t > 1) { t = 1; }
        if (t < 0) { t = 0; }
        arcOffset = -Math.sin(t * Math.PI) * p.arcHeight;
      }
      ctx.drawImage(game.sprites.projectile, Math.round(p.x - 2), Math.round(p.y - 2 + arcOffset));
    }
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
      var lifeRemaining = b.expiresAt - game.time;
      var alpha = lifeRemaining / 0.15;
      if (alpha < 0) { alpha = 0; }
      if (alpha > 1) { alpha = 1; }
      ctx.strokeStyle = 'rgba(180, 220, 255, ' + alpha + ')';
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.moveTo(b.points[0].x, b.points[0].y);
      for (var pi = 1; pi < b.points.length; pi++) {
        var jx = b.points[pi].x + (Math.random() * 6 - 3);
        var jy = b.points[pi].y + (Math.random() * 6 - 3);
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
    }
  }

  // Layer 11: HUD strip across the top of the playfield. Gold, cake, lost,
  // wave index, prep timer, send-next button, pause + speed widgets.
  function drawHud() {
    ctx.fillStyle = 'rgba(0, 0, 0, 0.55)';
    ctx.fillRect(0, 0, PLAYFIELD_W, HUD_H);
    ctx.font = '10px monospace';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#fee761';
    ctx.fillText('Gold: ' + game.gold, 54, 12);
    ctx.fillStyle = '#e43b44';
    ctx.fillText('Cake: ' + game.cake.slices + '/8', 124, 12);
    ctx.fillStyle = '#ff8888';
    ctx.fillText('Lost: ' + game.cake.lost + '/8', 200, 12);
    ctx.fillStyle = '#ffffff';
    ctx.fillText('W: ' + (game.waveIndex + 1) + '/' + WAVES.length, 276, 12);
    if (game.waveState === 'prep') {
      ctx.fillStyle = '#aef0a0';
      ctx.fillText('Prep: ' + Math.ceil(game.prepRemaining) + 's', 320, 12);
      var btnX = PLAYFIELD_W - 80;
      var btnY = 4;
      var btnW = 76;
      var btnH = 16;
      ui.sendNextRect = { x: btnX, y: btnY, w: btnW, h: btnH };
      ctx.fillStyle = '#5a7d4a';
      ctx.fillRect(btnX, btnY, btnW, btnH);
      ctx.fillStyle = '#fee761';
      ctx.textAlign = 'center';
      ctx.fillText('Send Next', btnX + btnW / 2, btnY + btnH / 2);
      ctx.textAlign = 'start';
    } else {
      ui.sendNextRect = null;
    }
    // Pause widget: two vertical bars when running, right-pointing triangle
    // when paused.
    ctx.fillStyle = '#2a2f3e';
    ctx.fillRect(ui.pauseRect.x, ui.pauseRect.y, ui.pauseRect.w, ui.pauseRect.h);
    ctx.fillStyle = '#fee761';
    if (game.paused) {
      ctx.beginPath();
      ctx.moveTo(ui.pauseRect.x + 5, ui.pauseRect.y + 4);
      ctx.lineTo(ui.pauseRect.x + 12, ui.pauseRect.y + 8);
      ctx.lineTo(ui.pauseRect.x + 5, ui.pauseRect.y + 12);
      ctx.closePath();
      ctx.fill();
    } else {
      ctx.fillRect(ui.pauseRect.x + 5, ui.pauseRect.y + 4, 2, 8);
      ctx.fillRect(ui.pauseRect.x + 9, ui.pauseRect.y + 4, 2, 8);
    }
    // Speed widget — 1x or 2x label.
    ctx.fillStyle = '#2a2f3e';
    ctx.fillRect(ui.speedRect.x, ui.speedRect.y, ui.speedRect.w, ui.speedRect.h);
    ctx.fillStyle = '#fee761';
    ctx.font = '10px monospace';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    var speedLabel;
    if (game.speed === 2) { speedLabel = '2x'; } else { speedLabel = '1x'; }
    ctx.fillText(speedLabel, ui.speedRect.x + ui.speedRect.w / 2, ui.speedRect.y + ui.speedRect.h / 2);
    ctx.textAlign = 'start';
    ctx.textBaseline = 'alphabetic';
  }

  // Layer 13a: drag preview (ghost cannon under the cursor while dragging from
  // the shop). Sits inside the overlays bucket because it is UI feedback drawn
  // above gameplay, but below pause/win/lose modals.
  function drawDragPreview() {
    if (!ui.dragging) { return; }
    var ok = canPlaceCannon(ui.mouseX, ui.mouseY);
    var dragDef = CANNON_KINDS[ui.dragging.kind];
    var dragTurret = game.sprites.turrets[ui.dragging.kind];
    if (!dragTurret) { dragTurret = game.sprites.turrets.howitzer; }
    ctx.globalAlpha = 0.6;
    ctx.drawImage(game.sprites.cannonBase, Math.round(ui.mouseX - 16), Math.round(ui.mouseY - 16));
    ctx.drawImage(dragTurret, Math.round(ui.mouseX - 10), Math.round(ui.mouseY - 16));
    ctx.globalAlpha = 1;
    ctx.strokeStyle = ok ? 'rgba(255, 255, 255, 0.5)' : 'rgba(228, 59, 68, 0.7)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.arc(ui.mouseX, ui.mouseY, dragDef.range, 0, Math.PI * 2);
    ctx.stroke();
  }

  // Wave-intro banner — slides in (alpha ramp), dwells, fades out over the
  // last 0.5s. Lives in the overlay layer so it can ride above gameplay.
  function drawBanner() {
    if (!game.banner.active) { return; }
    var bt = game.time - game.banner.startedAt;
    if (bt > game.banner.duration) {
      game.banner.active = false;
      return;
    }
    var alpha = 1;
    if (bt < 0.4) { alpha = bt / 0.4; }
    if (bt > game.banner.duration - 0.5) { alpha = (game.banner.duration - bt) / 0.5; }
    ctx.save();
    ctx.globalAlpha = alpha;
    ctx.fillStyle = game.banner.isBoss ? '#a44' : '#3a4466';
    ctx.fillRect(WIDTH / 2 - 48, 36, 96, 16);
    ctx.fillStyle = '#fee761';
    ctx.font = '10px monospace';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(game.banner.text, WIDTH / 2, 44);
    ctx.restore();
  }

  function drawIdleOverlay() {
    ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);
    ctx.fillStyle = '#fee761';
    ctx.font = '16px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('ANT BUSTER', WIDTH / 2, HEIGHT / 2 - 16);
    ctx.fillStyle = '#ffffff';
    ctx.font = '10px monospace';
    ctx.fillText('Defend the cake from the ants', WIDTH / 2, HEIGHT / 2 + 4);
    ctx.fillText('Click to start', WIDTH / 2, HEIGHT / 2 + 22);
    ctx.textAlign = 'start';
  }

  function drawTutorialHint() {
    if (game.state !== 'playing') { return; }
    if (game.time >= 4) { return; }
    if (game.cannons.length !== 0) { return; }
    ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
    ctx.fillRect(60, 100, 200, 70);
    ctx.fillStyle = '#fee761';
    ctx.font = '10px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('Drag a cannon from the shop', 160, 130);
    ctx.fillText('onto the picnic blanket to start!', 160, 145);
    ctx.textAlign = 'start';
  }

  function drawPauseOverlay() {
    ctx.fillStyle = 'rgba(0,0,0,0.5)';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);
    ctx.fillStyle = '#fee761';
    ctx.font = '20px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('PAUSED', WIDTH / 2, HEIGHT / 2 - 12);
    ctx.font = '10px monospace';
    ctx.fillText('Space to resume', WIDTH / 2, HEIGHT / 2 + 8);
    var rrx = WIDTH / 2 - 36;
    var rry = HEIGHT / 2 + 22;
    ctx.fillStyle = '#a44';
    ctx.fillRect(rrx, rry, 72, 14);
    ctx.fillStyle = '#fee761';
    ctx.fillText('Restart Run', WIDTH / 2, rry + 11);
    ctx.textAlign = 'start';
    ui.restartRunRect = { x: rrx, y: rry, w: 72, h: 14 };
  }

  function drawLoseOverlay() {
    ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);
    ctx.fillStyle = '#e43b44';
    ctx.font = '16px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('THE ANTS WIN', WIDTH / 2, HEIGHT / 2 - 12);
    ctx.fillStyle = '#ffffff';
    ctx.font = '10px monospace';
    ctx.fillText('Click to restart', WIDTH / 2, HEIGHT / 2 + 8);
    ctx.textAlign = 'start';
  }

  function drawWinOverlay() {
    ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);
    ctx.fillStyle = '#fee761';
    ctx.font = '16px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('CAKE SAVED', WIDTH / 2, HEIGHT / 2 - 12);
    ctx.fillStyle = '#ffffff';
    ctx.font = '10px monospace';
    ctx.fillText('Click to restart', WIDTH / 2, HEIGHT / 2 + 8);
    ctx.textAlign = 'start';
  }

  // Layer 13: overlays — drag preview, wave banner, then full-screen modals.
  // Modal order: pause first, then win/lose so terminal-state screens still
  // win the z-order if both are somehow active in the same frame.
  function drawOverlays() {
    drawDragPreview();
    drawBanner();
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
    // random offset whose magnitude decays in update(). The drawBackground()
    // call below repaints the whole 320x180 view each frame, so the shake's
    // 1-3 px translate doesn't leave ghosted pixels at the edges.
    var shaking = false;
    if (game.screenshake.magnitude > 0) {
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
    drawCannonBases();         // 6. static cannon bases (under turrets/ants)
    drawAnts();                // 7. ants, sorted top-down by y
    drawCannonTurrets();       // 8. cannon turrets + muzzle/disable indicators
    drawProjectiles();         // 9a. projectiles
    drawBeams();               // 9b. beam lines (Glacier, Lance)
    drawLightningBolts();      // 9c. Tesla arc-jump polylines (~0.15s flash)
    drawEffects();             // 10. AoE flashes + future hit/splat fx
    drawHud();                 // 11. HUD strip
    if (ui.selectedCannon) {   // 12. shop / tower-detail panel
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
  var ANT_KINDS = {
    worker: {
      name: 'Worker',
      hp: 10,
      speed: 30,
      carryingSpeedMult: 0.7,    // Plan 1: carrying ants slow to 70% on the way home
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
    carrier: {
      name: 'Carrier',
      hp: 15,
      speed: 30,
      carryingSpeedMult: 0.7,
      gold: 4,
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
    scout: {
      name: 'Scout',
      hp: 6,
      speed: 60,
      carryingSpeedMult: 0.7,
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
      hp: 40,
      speed: 20,
      gold: 5,
      alignment: 0.2,
      jitterRate: 3.0,
      wanderFreqMin: 0.2, wanderFreqMax: 0.5,
      wanderAmpMin: 0.6, wanderAmpMax: 1.0,
      spriteKey: 'soldier',
      spriteW: 16, spriteH: 16,
      carryingSpeedMult: 0.7,
      slowResist: 0.5,
      damageMult: 1,
      pierceMult: 1,
      canPickup: true,
      onDeath: null,
      armor: 0.5
    },
    tank: {
      name: 'Tank',
      hp: 150,
      speed: 12,
      gold: 10,
      alignment: 0.15,
      jitterRate: 2.0,
      wanderFreqMin: 0.15, wanderFreqMax: 0.35,
      wanderAmpMin: 0.4, wanderAmpMax: 0.7,
      spriteKey: 'tank',
      spriteW: 24, spriteH: 24,
      carryingSpeedMult: 0.7,
      slowResist: 0,
      damageMult: 1,
      pierceMult: 1,
      canPickup: false,
      onDeath: null,
      armor: 0.5
    },
    splitter: {
      name: 'Splitter',
      hp: 25,
      speed: 25,
      gold: 4,
      alignment: 0.25,
      jitterRate: 4.0,
      wanderFreqMin: 0.3, wanderFreqMax: 0.6,
      wanderAmpMin: 0.8, wanderAmpMax: 1.3,
      spriteKey: 'splitter',
      spriteW: 16, spriteH: 16,
      carryingSpeedMult: 0.7,
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
      carryingSpeedMult: 0.7,
      slowResist: 0,
      damageMult: 1,
      pierceMult: 1,
      canPickup: true,
      onDeath: null,
      armor: 0
    },
    phase: {
      name: 'Phase',
      hp: 20,
      speed: 35,
      gold: 6,
      alignment: 0.3,
      jitterRate: 4.0,
      wanderFreqMin: 0.3, wanderFreqMax: 0.7,
      wanderAmpMin: 0.9, wanderAmpMax: 1.4,
      spriteKey: 'phase',
      spriteW: 16, spriteH: 16,
      carryingSpeedMult: 0.7,
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
      hp: 30,
      speed: 40,
      gold: 7,
      alignment: 0.3,
      jitterRate: 4.5,
      wanderFreqMin: 0.4, wanderFreqMax: 0.8,
      wanderAmpMin: 1.0, wanderAmpMax: 1.5,
      spriteKey: 'bomber',
      spriteW: 16, spriteH: 16,
      carryingSpeedMult: 0.7,
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
      hp: 400,
      speed: 15,
      gold: 50,
      alignment: 0.15,
      jitterRate: 1.5,
      wanderFreqMin: 0.1, wanderFreqMax: 0.3,
      wanderAmpMin: 0.3, wanderAmpMax: 0.5,
      spriteKey: 'queen',
      spriteW: 24, spriteH: 24,
      carryingSpeedMult: 0.7,
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
    }
  };

  // === STATUS EFFECTS ===
  // Six-kind framework. Slow/freeze/stun scale movement; burn/poison tick HP damage; knockback
  // is a one-shot positional impulse on apply. Cannons emit these starting in Task 13 (Frost
  // emits 'slow'). Worker/Carrier carry slowResist=0 today; Soldier (Task 16) will use it.
  var STATUS_KINDS = {
    slow:      { name: 'Slow',      onApply: null, perTick: null, speedMult: function(m) { return 1 - m; } },
    freeze:    { name: 'Freeze',    onApply: null, perTick: null, speedMult: function(m) { return 0; } },
    burn:      { name: 'Burn',      onApply: null, perTick: function(ant, eff, dt) { ant.hp = ant.hp - eff.magnitude * dt; }, speedMult: null },
    poison:    { name: 'Poison',    onApply: null, perTick: function(ant, eff, dt) { ant.hp = ant.hp - eff.magnitude * dt; }, speedMult: null },
    stun:      { name: 'Stun',      onApply: null, perTick: null, speedMult: function(m) { return 0; } },
    knockback: { name: 'Knockback', onApply: function(ant, m) { ant.x = ant.x + Math.cos(ant.heading + Math.PI) * m; ant.y = ant.y + Math.sin(ant.heading + Math.PI) * m; }, perTick: null, speedMult: null }
  };

  // Apply a status effect to an ant. Refresh rule: if the ant already has this kind active,
  // the resulting entry takes the LONGER expiry and the HIGHER magnitude (nested ifs only).
  function applyStatus(ant, kindKey, magnitude, duration, source) {
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
    ant.effects[kindKey] = { magnitude: magnitude, expiresAt: newExpiry, source: source };
    var def = STATUS_KINDS[kindKey];
    if (def.onApply) { def.onApply(ant, magnitude); }
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
      deathTime: 0,
      deliveredAt: 0,                            // future tasks read this; unused today
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
    if (ant.dead) {
      ant.deathTime += dt;
      return;
    }
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
      if (dxc * dxc + dyc * dyc < 32 * 32) {
        alignment = 0.9;
        jitterRate = 0.5;
        wanderScale = 0.0;
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
    // Expire effects whose timer has run out. Collect keys first to avoid mutating the
    // object during iteration.
    var keys = [];
    for (ek in ant.effects) { keys.push(ek); }
    for (var ki = 0; ki < keys.length; ki++) {
      if (ant.effects[keys[ki]].expiresAt <= game.time) {
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
    var pickupR = 14;
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
          a.deathTime = 0;             // pruneAnts drops this on the next frame
          if (game.cake.lost >= 8) {
            game.waveState = 'lost';
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
          }
        }
      }
    }
  }

  function pruneAnts() {
    var kept = [];
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      if (!a.dead) {
        kept.push(a);
      } else if (a.deathTime < 0.5) {
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
      cost: 50,
      damage: 5,
      fireRate: 0.6,         // shots per second
      range: 110,
      projectileSpeed: 200,
      family: 'heavy',
      tier: 1,
      upgradesTo: ['siege', 'mortar', 'discus']
    },
    siege: {
      name: 'Siege',
      cost: 50,
      damage: 12,
      fireRate: 0.6,
      range: 110,
      projectileSpeed: 240,
      family: 'heavy',
      tier: 2,
      upgradesTo: ['railgun', 'demolisher']
    },
    railgun: {
      name: 'Railgun',
      cost: 90,
      damage: 25,
      fireRate: 0.6,
      range: 120,
      projectileSpeed: 360,
      family: 'heavy',
      tier: 3,
      linePierce: 999,    // unlimited — pre-Plan-3 Railgun semantics
      armorPierce: true,
      upgradesTo: []
    },
    mortar: {
      name: 'Mortar',
      cost: 60,
      damage: 8,
      fireRate: 0.5,
      range: 130,
      projectileSpeed: 200,
      family: 'heavy',
      tier: 2,
      splash: { radius: 40, falloff: 0.5, splashMult: 0.5 },
      arc: { height: 24, travelTime: 0.6 },
      upgradesTo: ['patriot', 'hellfire']
    },
    patriot: {
      name: 'Patriot',
      cost: 110,
      damage: 20,
      fireRate: 0.5,
      range: 200,
      projectileSpeed: 260,
      family: 'heavy',
      tier: 3,
      homing: { turnRate: 4.0 },
      splash: { radius: 30, falloff: 0.4, splashMult: 0.4 },
      upgradesTo: []
    },
    demolisher: {
      name: 'Demolisher',
      cost: 110,
      damage: 60,
      fireRate: 0.25,
      range: 110,
      projectileSpeed: 260,
      family: 'heavy',
      tier: 3,
      armorPierce: true,
      upgradesTo: []
    },
    hellfire: {
      name: 'Hellfire',
      cost: 90,
      damage: 15,
      fireRate: 0.4,
      range: 180,
      projectileSpeed: 200,
      family: 'heavy',
      tier: 3,
      splash: { radius: 60, falloff: 0.4, splashMult: 0.67 },
      arc: { height: 32, travelTime: 0.7 },
      upgradesTo: []
    },
    discus: {
      name: 'Discus',
      cost: 60,
      damage: 4,
      fireRate: 0.4,
      range: 150,
      projectileSpeed: 220,
      family: 'heavy',
      tier: 2,
      returning: true,
      upgradesTo: ['ricochet', 'shockwave']
    },
    ricochet: {
      name: 'Ricochet',
      cost: 90,
      damage: 8,
      fireRate: 0.4,
      range: 180,
      projectileSpeed: 240,
      family: 'heavy',
      tier: 3,
      returning: true,
      returnPierce: 1,
      upgradesTo: []
    },
    shockwave: {
      name: 'Shockwave',
      cost: 110,
      damage: 50,
      fireRate: 0.15,
      range: 130,
      projectileSpeed: 180,
      family: 'heavy',
      tier: 3,
      splash: { radius: 80, falloff: 0.3, splashMult: 0.6 },
      screenshake: { magnitude: 3, decay: 12 },
      upgradesTo: []
    },
    repeater: {
      name: 'Repeater',
      cost: 50,
      damage: 1,
      fireRate: 4,
      range: 130,
      projectileSpeed: 280,
      family: 'quick',
      tier: 1,
      upgradesTo: ['autocannon', 'marksman', 'spotter']
    },
    autocannon: {
      name: 'Auto-Cannon',
      cost: 50,
      damage: 2,
      fireRate: 6,
      range: 130,
      projectileSpeed: 320,
      family: 'quick',
      tier: 2,
      upgradesTo: ['vulcan', 'plasma_torch']
    },
    marksman: {
      name: 'Marksman',
      cost: 70,
      damage: 8,
      fireRate: 0.6,
      range: 250,
      projectileSpeed: 320,
      family: 'quick',
      tier: 2,
      damageModifier: function (target, sourceCannon) {
        if (target.hp >= target.maxHp - 0.001) { return 1.5; }
        return 1;
      },
      upgradesTo: ['sharpshooter', 'lance']
    },
    spotter: {
      name: 'Spotter',
      cost: 60,
      damage: 3,
      fireRate: 2,
      range: 200,
      projectileSpeed: 280,
      family: 'quick',
      tier: 2,
      reveal: true,
      upgradesTo: ['phantom', 'tesla']
    },
    vulcan: {
      name: 'Vulcan',
      cost: 80,
      damage: 3,
      fireRate: 9,
      range: 140,
      projectileSpeed: 360,
      family: 'quick',
      tier: 3,
      spoolUp: 0.5,
      upgradesTo: []
    },
    plasma_torch: {
      name: 'Plasma Torch',
      cost: 90,
      damage: 0,                 // cone-DoT cannons don't fire projectiles
      fireRate: 0,
      range: 70,                 // mirrors coneDot.range so the panel circle preview matches
      projectileSpeed: 0,
      family: 'quick',
      tier: 3,
      coneDot: {
        range: 70,
        angleDeg: 60,
        tickDamage: 10,
        statusOnTick: { kind: 'burn', magnitude: 1, duration: 2 }
      },
      upgradesTo: []
    },
    sharpshooter: {
      name: 'Sharpshooter',
      cost: 100,
      damage: 25,
      fireRate: 0.5,
      range: 350,
      projectileSpeed: 360,
      family: 'quick',
      tier: 3,
      damageModifier: function (target, sourceCannon) {
        // +50% vs full-HP, same shape as Marksman. The "ignores 50% slow penalty"
        // line in the spec describes a damage debuff that doesn't exist in Plan 2's
        // slow status (which is movement-only), so this clause is a no-op today.
        // Left as a documented hook for any future slow-debuffs-damage mechanic.
        if (target.hp >= target.maxHp - 0.001) { return 1.5; }
        return 1;
      },
      upgradesTo: []
    },
    lance: {
      name: 'Lance',
      cost: 110,
      damage: 0,                 // beam cannons deal damage via beam.tickDamage
      fireRate: 0,
      range: 250,
      projectileSpeed: 0,
      family: 'quick',
      tier: 3,
      beam: {
        tickDamage: 4,           // 4 dps continuous; integrated as tickDamage * dt
        statusOnTick: null,      // direct damage only, no status
        color: '#3acccc',        // saturated cyan, distinct from Glacier's pale ice
        lineWidth: 1.5           // tighter than Glacier's 2 — reads as a precision lance
      },
      upgradesTo: []
    },
    phantom: {
      name: 'Phantom',
      cost: 110,
      damage: 40,
      fireRate: 0.3,
      range: 450,
      projectileSpeed: 380,
      family: 'quick',
      tier: 3,
      damageModifier: function (target, sourceCannon) {
        // +100% vs Queen — anti-boss specialist. King is a separate kind and
        // intentionally does not benefit from this bonus.
        if (target.kind === 'queen') { return 2; }
        return 1;
      },
      upgradesTo: []
    },
    tesla: {
      name: 'Tesla',
      cost: 100,
      damage: 10,
      fireRate: 1,
      range: 200,
      projectileSpeed: 0,        // instant-resolve; no projectile travel
      family: 'quick',
      tier: 3,
      teslaArc: { jumps: 3, range: 60, falloff: 0.5 },
      upgradesTo: []
    },
    twingun: {
      name: 'Twin Gun',
      cost: 75,
      damage: 2,
      fireRate: 1,
      range: 100,
      projectileSpeed: 240,
      family: 'status',
      tier: 1,
      barrels: 2,            // multi-barrel volley logic landed in Task 13
      upgradesTo: ['triple_threat', 'frost', 'toxin']
    },
    triple_threat: {
      name: 'Triple Threat',
      cost: 70,
      damage: 2,
      fireRate: 1,
      range: 110,
      projectileSpeed: 240,
      family: 'double',
      tier: 2,
      barrels: 3,
      spread: 0.10,          // ~5.7 deg per step — wider than Twin Gun's default
      upgradesTo: ['buckshot', 'minigun']
    },
    buckshot: {
      name: 'Buckshot',
      cost: 90,
      damage: 2,
      fireRate: 0.8,
      range: 90,
      projectileSpeed: 220,
      family: 'double',
      tier: 3,
      pellets: 5,
      coneDeg: 50,           // 50 deg fan, ~12.5 deg between pellets
      upgradesTo: []
    },
    minigun: {
      name: 'Minigun',
      cost: 110,
      damage: 1,
      fireRate: 14,
      range: 120,
      projectileSpeed: 320,
      family: 'double',
      tier: 3,
      aimJitter: 0.05,       // ~2.9 deg max per-shot angular wobble
      upgradesTo: []
    },
    toxin: {
      name: 'Toxin',
      cost: 75,
      damage: 1,
      fireRate: 1,
      range: 100,
      projectileSpeed: 200,
      family: 'double',
      tier: 2,
      statusOnHit: { kind: 'poison', magnitude: 1, duration: 5 },   // 1 dps over 5s = 5 dmg DoT
      // upgradesTo: ['plague', 'sonic_pulse'] — populated by Tasks 25/26
      upgradesTo: []
    },
    cryo: {
      name: 'Cryo',
      cost: 110,
      damage: 3,
      fireRate: 1,
      range: 110,
      projectileSpeed: 220,
      family: 'double',
      tier: 3,
      // damageAmp tag on the freeze effect: subsequent damage from any cannon
      // lands +50% while the 0.8s freeze is active. See Step 2 stamp pattern
      // and applyDamage's freeze.damageAmp read.
      statusOnHit: { kind: 'freeze', magnitude: 1, duration: 0.8, damageAmp: 1.5 },
      upgradesTo: []
    },
    frost: {
      name: 'Frost',
      cost: 75,
      damage: 1,
      fireRate: 1.5,
      range: 100,
      projectileSpeed: 220,
      family: 'status',
      tier: 2,
      statusOnHit: { kind: 'slow', magnitude: 0.3, duration: 2 },
      upgradesTo: ['glacier', 'cryo']
    },
    glacier: {
      name: 'Glacier',
      cost: 90,
      damage: 1,
      fireRate: 0,
      range: 130,
      family: 'status',
      tier: 3,
      beam: {
        tickDamage: 1,
        statusOnTick: { kind: 'slow', magnitude: 0.5, duration: 1 },
        color: '#a4d4ff',
        lineWidth: 2
      },
      upgradesTo: []
    }
  };

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
    if (dxCake * dxCake + dyCake * dyCake < 24 * 24) { return false; }
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
    game.cannons.push({
      kind: kindKey,
      kindHistory: [kindKey],   // base kind included per spec SYS-2
      x: x,
      y: y,
      heading: 0,
      cooldown: 0,
      target: null,
      kills: 0,
      disabled: false,
      disabledUntil: 0
    });
    return true;
  }

  function findTarget(cannon) {
    var def = CANNON_KINDS[cannon.kind];
    var rangeSq = def.range * def.range;
    var revealedTarget = null;
    var nearest = null;
    var nearestDistSq = rangeSq;
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      if (a.dead) { continue; }
      if (a.intangible) { continue; }
      var dx = a.x - cannon.x;
      var dy = a.y - cannon.y;
      var distSq = dx * dx + dy * dy;
      if (distSq > rangeSq) { continue; }
      if (a.isRevealed) {
        if (!revealedTarget) {
          revealedTarget = a;
        }
      }
      if (distSq < nearestDistSq) {
        nearest = a;
        nearestDistSq = distSq;
      }
    }
    if (revealedTarget) { return revealedTarget; }
    return nearest;
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
        applyStatus(a, s.kind, s.magnitude, s.duration, 'cone');
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
          applyStatus(btarget, sb.kind, sb.magnitude, sb.duration, 'beam');
        }
        c.beamTarget = btarget;
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

      var bolt = { points: [{ x: cannon.x, y: cannon.y }], expiresAt: game.time + 0.15 };
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
          returning: false
        });
      }
      return;
    }

    var spread = def.spread ? def.spread : 0.06;   // per-step radian offset; ~3.4 deg default (Twin Gun)
    for (var i = 0; i < n; i++) {
      var off;
      if (n === 1) {
        off = 0;
      } else {
        off = (i - (n - 1) / 2) * spread;
      }
      var h = heading + off;
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
        target: target
      };
      if (def.arc) {
        p.arcStartedAt = game.time;
        p.arcDuration = def.arc.travelTime;
        p.arcHeight = def.arc.height;
      }
      if (def.homing) {
        p.homingTurnRate = def.homing.turnRate;
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
  function applyDamage(ant, amount, armorPierce, sourceCannon) {
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
    // any follow-up hit benefits. Nested ifs to avoid inline-script `&&`.
    if (ant.effects) {
      if (ant.effects.freeze) {
        var amp = ant.effects.freeze.damageAmp;
        if (amp) { dmg = dmg * amp; }
      }
    }
    if (!armorPierce) {
      var armor = k.armor;
      if (!armor) { armor = 0; }
      dmg = dmg * (1 - armor);
    }
    ant.hp = ant.hp - dmg;
  }

  // Centralized kill handling. Used by both projectile collision and beam tick
  // so the gold drop and carried-slice return stay in sync. `killer` is the
  // cannon for kill-attribution; pass null when not applicable.
  function killAnt(ant, killer) {
    if (ant.dead) { return; }
    ant.dead = true;
    ant.deathTime = 0;
    var k = ANT_KINDS[ant.kind];
    game.gold = game.gold + k.gold;
    // Task 23: dropping a slice on the ground (replaces Plan 1's instant cake.slices++ snap-back).
    // The slice sits where the ant fell. Another canPickup ant can claim it (ground pickup branch
    // in checkPickupsAndDeliveries); after 8s of nobody claiming, it returns to the cake without
    // a life deduction (updateSlices). Cake accounting: kill->drop is neutral on cake.slices,
    // timeout refunds it, ground pickup just transfers ownership.
    if (ant.carrying) {
      game.slices.push({ x: ant.x, y: ant.y, droppedAt: game.time, claimedBy: null });
      ant.carrying = false;
    }
    if (killer) {
      killer.kills = killer.kills + 1;
    }
    // Fire the kind's onDeath hook (Splitter spawns Mini-Splitters; Bomber/Queen
    // will use this in later tasks). spawnAntAt only pushes onto game.ants, so
    // it is safe even if killAnt is called from inside an iteration over game.ants.
    if (k.onDeath) { k.onDeath(ant); }
  }

  // Splash AoE primitive (Plan 3). Iterates live ants once; applies damage
  // scaled by `falloff` (0 = flat-radius, 1 = linear-to-zero at edge). Skips
  // dead and intangible (Phantom phase-out, future tasks). Reads game.ants
  // by index against the snapshot at call time, so a direct-hit kill before
  // splash is fine — the loop sees positions, not the kill flag of the
  // carrier specifically. `damage` is the already-scaled splash damage
  // (caller multiplies by splashMult).
  function applySplash(x, y, radius, damage, falloff, sourceCannon) {
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
      if (a.hp <= 0) { killAnt(a, sourceCannon); }
    }
  }

  function updateProjectiles(dt) {
    var kept = [];
    for (var i = 0; i < game.projectiles.length; i++) {
      var p = game.projectiles[i];
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
          applyDamage(a, p.damage, p.armorPierce, p.sourceCannon);
          if (p.statusOnHit) {
            var s = p.statusOnHit;
            applyStatus(a, s.kind, s.magnitude, s.duration, 'projectile');
            // Tag-after-apply pattern: extra fields like damageAmp (Cryo's
            // +50% during freeze) live on the cannon's statusOnHit config and
            // get stamped onto the just-applied effect here, so applyStatus
            // itself stays lean. Future emitters carry their own amp without
            // any framework change.
            if (s.damageAmp) {
              if (a.effects[s.kind]) {
                a.effects[s.kind].damageAmp = s.damageAmp;
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
              // Screenshake on splash impact (Plan 3 — Shockwave). Direct hits
              // without splash never trigger shake. Nested ifs to avoid `&&`.
              if (sk.screenshake) {
                game.screenshake.magnitude = sk.screenshake.magnitude;
                game.screenshake.decay = sk.screenshake.decay;
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
          } else {
            // Returning projectile primitive (Plan 3 — Discus). On the first
            // outbound hit, flip velocity toward origin so the projectile can
            // hit more ants on the return leg. CLAUDE.md: no `&&` in inline
            // <script>, so two nested ifs instead of `p.returning && p.phase === 'out'`.
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
              // despawn at origin). Nested ifs to avoid `&&` in inline <script>.
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
  var WAVES = [
    {
      number: 1,
      prep: 30,
      bonus: 21,
      spawns: [
        { type: 'worker', count: 8, interval: 1.0, delay: 0 }
      ]
    }
  ];
  WAVES.push({
    number: 2, prep: 20, bonus: 30,
    spawns: [{ type: 'worker', count: 12, interval: 0.8, delay: 0 }]
  });
  WAVES.push({
    number: 3, prep: 20, bonus: 35,
    spawns: [
      { type: 'worker', count: 6, interval: 0.7, delay: 0 },
      { type: 'worker', count: 6, interval: 0.7, delay: 4 }
    ]
  });
  WAVES.push({
    number: 4, prep: 20, bonus: 45,
    spawns: [
      { type: 'worker', count: 10, interval: 0.7, delay: 0 },
      { type: 'scout', count: 4, interval: 1.0, delay: 1.5 }
    ]
  });
  WAVES.push({
    number: 5, prep: 30, bonus: 75, boss: true,
    spawns: [
      { type: 'queen', count: 1, interval: 1, delay: 0 },
      { type: 'worker', count: 8, interval: 0.8, delay: 2 }
    ]
  });

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
        showBanner('Wave ' + wv.number + '/' + WAVES.length, !!wv.boss);
      }
      return;
    }
    if (game.waveState === 'wave') {
      var wv = WAVES[game.waveIndex];
      if (!game.spawner.queueDone) {
        var grp = wv.spawns[game.spawner.groupIndex];
        if (game.time >= game.spawner.nextSpawnAt) {
          spawnAnt(grp.type);
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
          game.gold = game.gold + wv.bonus;
          if (game.waveIndex + 1 >= WAVES.length) {
            game.waveState = 'won';
          } else {
            game.waveIndex = game.waveIndex + 1;
            game.waveState = 'prep';
            game.prepRemaining = WAVES[game.waveIndex].prep;
          }
        }
      }
    }
  }

  function resetGame() {
    game.state = 'playing';
    game.time = 0;
    game.cake.slices = 8;
    game.cake.lost = 0;
    game.ants = [];
    game.cannons = [];
    game.projectiles = [];
    game.effects = [];
    game.slices = [];
    game.gold = 150;
    game.waveIndex = 0;
    game.waveState = 'prep';
    game.prepRemaining = WAVES[0].prep;
    game.spawner.groupIndex = 0;
    game.spawner.spawnedInGroup = 0;
    game.spawner.nextSpawnAt = 0;
    game.spawner.queueDone = false;
    game.banner.active = false;
    game.banner.text = '';
    game.banner.startedAt = 0;
    game.banner.duration = 2.5;
    game.banner.isBoss = false;
    game.screenshake.magnitude = 0;
    game.screenshake.decay = 0;
    game.paused = false;
    game.speed = 1;
    ui.sendNextRect = null;
    ui.restartRunRect = null;
    ui.dragging = null;
    ui.selectedCannon = null;
    ui.upgradeRects[0] = null;
    ui.upgradeRects[1] = null;
    ui.upgradeRects[2] = null;
    ui.hoveredUpgrade = null;
    ui.sellRect = null;
    ui.shopRects = [];
  }

  // === INPUT ===
  var ui = {
    dragging: null,         // { kind } when dragging from shop
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
    shopRects: []           // populated by drawShop; one rect per shop card
  };

  function canvasToGame(evt) {
    var rect = canvas.getBoundingClientRect();
    var sx = WIDTH / rect.width;
    var sy = HEIGHT / rect.height;
    return {
      x: (evt.clientX - rect.left) * sx,
      y: (evt.clientY - rect.top) * sy
    };
  }

  canvas.addEventListener('mousedown', function (evt) {
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
    // Pause overlay's Restart Run button — only live while paused. Tested first so
    // it wins over the HUD pause toggle behind it (which is in the same x range).
    if (game.paused) {
      if (ui.restartRunRect) {
        if (inRect(p, ui.restartRunRect)) { resetGame(); return; }
      }
    }
    // HUD pause + speed widgets — always live during 'playing' state. Tested before
    // any other hit-test in case the playfield ever extends under them.
    if (game.state === 'playing') {
      if (inRect(p, ui.pauseRect)) { game.paused = !game.paused; return; }
      if (inRect(p, ui.speedRect)) {
        if (game.speed === 1) { game.speed = 2; } else { game.speed = 1; }
        return;
      }
    }
    if (game.waveState === 'won') { resetGame(); return; }
    if (game.waveState === 'lost') { resetGame(); return; }
    if (game.state === 'idle') { game.state = 'playing'; return; }
    if (game.waveState === 'prep') {
      if (ui.sendNextRect) {
        var r = ui.sendNextRect;
        if (p.x >= r.x) {
          if (p.x <= r.x + r.w) {
            if (p.y >= r.y) {
              if (p.y <= r.y + r.h) {
                var bonus = Math.floor(game.prepRemaining / 2);
                game.gold = game.gold + bonus;
                game.prepRemaining = 0;
                return;
              }
            }
          }
        }
      }
    }
    if (game.state === 'playing') {
      if (p.x < PLAYFIELD_W) {
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
      // the upgrade buttons (up to 3) then the sell button.
      if (ui.selectedCannon) {
        if (p.x >= PLAYFIELD_W) {
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
    if (ui.shopRects) {
      for (var si = 0; si < ui.shopRects.length; si++) {
        var rs = ui.shopRects[si];
        if (inRect(p, rs)) {
          // only start drag if affordable
          var sk = CANNON_KINDS[rs.kind];
          if (game.gold >= sk.cost) {
            ui.dragging = { kind: rs.kind };
            canvas.classList.add('ab-dragging');
          }
          return;
        }
      }
    }
  });

  canvas.addEventListener('mousemove', function (evt) {
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
    // Track upgrade-button hover so drawRangeCircle can preview the target's range
    // as a dashed secondary circle. Avoids `&&` for the inline-script gotcha.
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

  canvas.addEventListener('mouseup', function (evt) {
    var p = canvasToGame(evt);
    ui.mouseX = p.x;
    ui.mouseY = p.y;
    if (ui.dragging) {
      placeCannon(ui.dragging.kind, p.x, p.y);
      ui.dragging = null;
      canvas.classList.remove('ab-dragging');
    }
  });

  canvas.addEventListener('mouseleave', function () {
    if (ui.dragging) {
      ui.dragging = null;
      canvas.classList.remove('ab-dragging');
    }
  });

  // === UPDATE ===
  // Speed toggle (1×/2×) scales sim time for ants and projectiles only. game.time
  // advances on real dt so the wave-intro banner and prep countdown animate at
  // wall-clock speed regardless of speed setting (spec §UI-5).
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
  // game.time which advances in update() — but kept in the signature for parity
  // with the other update* functions.
  function updateSlices(dt) {
    var kept = [];
    for (var i = 0; i < game.slices.length; i++) {
      var s = game.slices[i];
      var age = game.time - s.droppedAt;
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
    // Screenshake decay (Plan 3 — Shockwave). Decay runs in real time on dt
    // (not simDt) so 2x speed doesn't drain the shake faster than the eye sees it.
    if (game.screenshake.magnitude > 0) {
      game.screenshake.magnitude = game.screenshake.magnitude - game.screenshake.decay * dt;
      if (game.screenshake.magnitude < 0) { game.screenshake.magnitude = 0; }
    }
    game.time += dt;
    var simDt = dt * game.speed;
    updateWaveState(dt);
    updateAnts(simDt);
    updateCannons(simDt);
    updateProjectiles(simDt);
    updateEffects(simDt);
    // Tesla lightning bolts decay against game.time (already advanced above).
    // Each bolt lives ~0.15s; expired bolts are filtered out before render.
    var keptBolts = [];
    for (var lbi = 0; lbi < game.lightningBolts.length; lbi++) {
      if (game.lightningBolts[lbi].expiresAt > game.time) {
        keptBolts.push(game.lightningBolts[lbi]);
      }
    }
    game.lightningBolts = keptBolts;
    updateSlices(simDt);
    checkPickupsAndDeliveries();
    pruneAnts();
  }

  // === MAIN LOOP ===
  var lastTime = performance.now();
  function frame(now) {
    var elapsed = (now - lastTime) / 1000;
    lastTime = now;
    if (elapsed > 0.25) { elapsed = 0.25; }   // clamp to avoid spiral-of-death
    game.accumulator += elapsed;
    while (game.accumulator >= FIXED_DT) {
      update(FIXED_DT);
      game.accumulator -= FIXED_DT;
    }
    render();
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);

  // Keybinds:
  //   Enter — skip prep window (same as Send-Next button)
  //   Space — toggle pause (preventDefault so the page doesn't scroll)
  //   1 / 2 — set speed to 1× / 2×
  window.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      if (game.waveState === 'prep') {
        if (game.state === 'playing') {
          var bonus = Math.floor(game.prepRemaining / 2);
          game.gold = game.gold + bonus;
          game.prepRemaining = 0;
        }
      }
      return;
    }
    if (e.key === ' ') {
      e.preventDefault();
      game.paused = !game.paused;
      return;
    }
    if (e.key === '1') { game.speed = 1; return; }
    if (e.key === '2') { game.speed = 2; return; }
    if (e.key === 'Escape') { ui.selectedCannon = null; return; }
    if (DEBUG) {
      if (e.key === 't') {
        var dk = DEBUG_KINDS[debugCycleIdx % DEBUG_KINDS.length];
        spawnAntAt(dk, game.anthill.x, game.anthill.y);
        debugCycleIdx = debugCycleIdx + 1;
        return;
      }
      if (e.key === 'T') {
        var dk2 = DEBUG_KINDS[debugCycleIdx % DEBUG_KINDS.length];
        spawnAntAt(dk2, game.anthill.x, game.anthill.y);
        debugCycleIdx = debugCycleIdx + 1;
        return;
      }
    }
  });

  // Expose game for debugging from devtools console
  window.__ab = game;
  window.__abCannonKinds = CANNON_KINDS;
  window.__abAntKinds = ANT_KINDS;
  window.__abUi = ui;
  window.__abStatusKinds = STATUS_KINDS;
  window.__abApplyStatus = applyStatus;
})();
</script>
