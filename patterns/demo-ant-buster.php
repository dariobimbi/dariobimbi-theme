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
  var PLAYFIELD_H = 336;
  var HUD_H = 24;
  var SHOP_W = 160;
  var TARGET_FPS = 60;
  var FIXED_DT = 1 / TARGET_FPS;

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
    sprites: {},              // populated in Task 4
    gold: 150,
    waveIndex: 0,             // index into WAVES
    waveState: 'prep',        // 'prep' | 'wave' | 'won' | 'lost'
    prepRemaining: 30,        // seconds remaining in current prep window
    spawner: { groupIndex: 0, spawnedInGroup: 0, nextSpawnAt: 0, queueDone: false },
    banner: { active: false, text: '', startedAt: 0, duration: 2.5, isBoss: false },
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
      repeater: makeCannonTurretRepeater(),
      autocannon: makeCannonTurretAutocannon(),
      vulcan: makeCannonTurretVulcan(),
      twingun: makeCannonTurretTwinGun(),
      frost: makeCannonTurretFrost(),
      glacier: makeCannonTurretGlacier()
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

    // Upgrade buttons (up to 2) — only present when sk.upgradesTo is non-empty.
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
    for (var k = upgrades.length; k < 2; k++) { ui.upgradeRects[k] = null; }

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

  function render() {
    // Background
    ctx.fillStyle = '#193c3e';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);

    // Grass border (whole canvas is grass-colored already; blanket sits on top)
    var blanketX = 48;
    var blanketY = 48;
    var blanketW = 384;
    var blanketH = 272;

    // Picnic blanket — alternating red/white check
    var checkSize = 16;
    for (var by = 0; by < blanketH; by += checkSize) {
      for (var bx = 0; bx < blanketW; bx += checkSize) {
        var isRed = ((bx / checkSize) + (by / checkSize)) % 2 === 0;
        ctx.fillStyle = isRed ? '#e43b44' : '#feeed8';
        ctx.fillRect(blanketX + bx, blanketY + by, checkSize, checkSize);
      }
    }

    // Anthill
    ctx.drawImage(game.sprites.anthill, game.anthill.x - 16, game.anthill.y - 16);

    // Cake (regenerated each frame since it depends on slice count)
    var cakeSprite = makeCake(game.cake.slices);
    ctx.drawImage(cakeSprite, game.cake.x - 16, game.cake.y - 16);

    // HUD bar
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
      // Send-Next button on the right of the HUD strip
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
    // Pause + speed HUD widgets (left edge of the HUD strip, above gold).
    // Pause: two vertical bars when running, single right-pointing triangle when paused.
    ctx.fillStyle = '#2a2f3e';
    ctx.fillRect(ui.pauseRect.x, ui.pauseRect.y, ui.pauseRect.w, ui.pauseRect.h);
    ctx.fillStyle = '#fee761';
    if (game.paused) {
      // Play triangle
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

    // Shop panel (right)
    ctx.fillStyle = '#1a1c2c';
    ctx.fillRect(PLAYFIELD_W, 0, SHOP_W, HEIGHT);

    // Range circle for the selected cannon — drawn under ants + cannons so it
    // never obscures moving entities (Plan 2 spec §3.5 z-order layer 5).
    if (ui.selectedCannon) {
      var selKind = CANNON_KINDS[ui.selectedCannon.kind];
      ctx.strokeStyle = 'rgba(255, 231, 97, 0.5)';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.arc(ui.selectedCannon.x, ui.selectedCannon.y, selKind.range, 0, Math.PI * 2);
      ctx.stroke();
    }

    // Ants
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      var k = ANT_KINDS[a.kind];
      var sprite;
      if (a.dead) {
        sprite = game.sprites.splat;
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
          ctx.fillStyle = '#feae34';
          ctx.fillRect(Math.round(a.x - 3), Math.round(a.y - 12), 6, 4);
          ctx.fillStyle = '#fee761';
          ctx.fillRect(Math.round(a.x - 2), Math.round(a.y - 11), 4, 2);
        }
      }
    }

    // Right column: tower-detail panel when a cannon is selected, otherwise shop.
    if (ui.selectedCannon) {
      drawTowerPanel(ui.selectedCannon);
    } else {
      drawShop();
      ui.upgradeRects[0] = null;
      ui.upgradeRects[1] = null;
      ui.sellRect = null;
    }

    // Placed cannons
    for (var ci = 0; ci < game.cannons.length; ci++) {
      var c = game.cannons[ci];
      ctx.drawImage(game.sprites.cannonBase, Math.round(c.x - 16), Math.round(c.y - 16));
      ctx.save();
      ctx.translate(Math.round(c.x), Math.round(c.y));
      ctx.rotate(c.heading + Math.PI / 2);
      var turretSprite = game.sprites.turrets[c.kind];
      if (!turretSprite) { turretSprite = game.sprites.turrets.howitzer; }
      ctx.drawImage(turretSprite, -10, -16);
      ctx.restore();
      // Disabled-spark indicator (Task 20): two tiny pixels above the turret that
      // blink on game.time so a Bomber-disabled cannon reads as "out of action".
      if (c.disabled) {
        if (Math.floor(game.time * 6) % 2 === 0) {
          ctx.fillStyle = '#ff5040';
          ctx.fillRect(c.x - 1, c.y - 16, 2, 2);
          ctx.fillRect(c.x + 3, c.y - 14, 2, 2);
        }
      }
    }

    // Projectiles
    for (var pi = 0; pi < game.projectiles.length; pi++) {
      var p = game.projectiles[pi];
      ctx.drawImage(game.sprites.projectile, Math.round(p.x - 2), Math.round(p.y - 2));
    }

    // Beams (z-order layer 9, post-projectiles): thin icy line from a beam
    // cannon to its current target. Only drawn while the target is alive.
    for (var bi = 0; bi < game.cannons.length; bi++) {
      var bc = game.cannons[bi];
      var bdef = CANNON_KINDS[bc.kind];
      if (bdef.beam) {
        if (bc.beamTarget) {
          if (!bc.beamTarget.dead) {
            ctx.strokeStyle = '#a4d4ff';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(bc.x, bc.y);
            ctx.lineTo(bc.beamTarget.x, bc.beamTarget.y);
            ctx.stroke();
          }
        }
      }
    }

    // Effects (z-order layer 10, post-beams, pre-HUD): transient gameplay flashes.
    // Each entry advances its own `t` in updateEffects; render uses p = t/life as
    // an animation parameter (0 → 1) for ring expansion + fade.
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

    // Drag preview
    if (ui.dragging) {
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

    // Wave-intro banner — slides in (alpha ramp), dwells, fades out over the last 0.5s.
    if (game.banner.active) {
      var bt = game.time - game.banner.startedAt;
      if (bt > game.banner.duration) {
        game.banner.active = false;
      } else {
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
    }

    if (game.state === 'idle') {
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

    if (game.state === 'playing') {
      if (game.time < 4) {
        if (game.cannons.length === 0) {
          ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
          ctx.fillRect(60, 100, 200, 70);
          ctx.fillStyle = '#fee761';
          ctx.font = '10px monospace';
          ctx.textAlign = 'center';
          ctx.fillText('Drag a cannon from the shop', 160, 130);
          ctx.fillText('onto the picnic blanket to start!', 160, 145);
          ctx.textAlign = 'start';
        }
      }
    }

    // Pause overlay — drawn after gameplay, before win/lose overlays so those still
    // win the z-order if the game is paused at terminal state (shouldn't happen, but safe).
    if (game.paused) {
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
    } else {
      ui.restartRunRect = null;
    }

    if (game.waveState === 'lost') {
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

    if (game.waveState === 'won') {
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
  }

  // === ANTS ===
  // Per-kind ant stats. Plan 2 introduces this table; Plan 1's literals are migrated here.
  // Worker and Carrier share most numbers in Plan 2; the table is structured for the
  // 8 remaining kinds (Brood, Soldier, Scout, etc.) added in later tasks. Effects slot
  // (per-ant `effects` map) is wired in Task 4 — for now it just exists, empty.
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
          spawnAntAt('mini_splitter', ant.x + Math.cos(ang) * 6, ant.y + Math.sin(ang) * 6);
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
          spawnAntAt('worker', ant.x + Math.cos(ang) * 12, ant.y + Math.sin(ang) * 12);
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
      magnitude = magnitude * (1 - (k.slowResist || 0));
    }
    var existing = ant.effects[kindKey];
    var newExpiry = (game.time || 0) + duration;
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
  function spawnAntAt(kindKey, x, y) {
    var k = ANT_KINDS[kindKey];
    var ant = {
      kind: kindKey,
      x: x,
      y: y,
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
      phaseT: 0
    };
    game.ants.push(ant);
    return ant;
  }

  function spawnAnt(kindKey) {
    return spawnAntAt(kindKey, game.anthill.x, game.anthill.y + (Math.random() * 60 - 30));
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
  // Ants treat cannons as soft obstacles; the hard overlap push-out runs after movement.
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
      targetX = game.anthill.x;
      targetY = game.anthill.y;
    } else {
      targetX = game.cake.x;
      targetY = game.cake.y;
    }
    var k = ANT_KINDS[ant.kind];
    if (k.phaseInterval) {
      ant.phaseT = (ant.phaseT || 0) + dt;
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
    // Hard push-out: cannons are solid, ants cannot end a frame inside one.
    var minD = 18;
    for (var ci = 0; ci < game.cannons.length; ci++) {
      var cn = game.cannons[ci];
      var dxN = ant.x - cn.x;
      var dyN = ant.y - cn.y;
      var dN = Math.sqrt(dxN * dxN + dyN * dyN);
      if (dN < minD) {
        if (dN < 0.0001) {
          // Exactly on top of the cannon (e.g. cannon placed on ant): nudge perpendicular to heading.
          ant.x = cn.x + Math.cos(ant.heading + Math.PI / 2) * minD;
          ant.y = cn.y + Math.sin(ant.heading + Math.PI / 2) * minD;
        } else {
          ant.x = cn.x + (dxN / dN) * minD;
          ant.y = cn.y + (dyN / dN) * minD;
        }
      }
    }
    // Final non-reflective clamp in case push-out drove us back through a wall.
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
        var dxHill = a.x - game.anthill.x;
        var dyHill = a.y - game.anthill.y;
        if (dxHill * dxHill + dyHill * dyHill < deliverR * deliverR) {
          game.cake.lost += 1;
          a.dead = true;
          a.deathTime = 0.5;     // pruneAnts drops this immediately, no splat
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
        if (game.cake.slices > 0) {
          var dxCake = a.x - game.cake.x;
          var dyCake = a.y - game.cake.y;
          if (dxCake * dxCake + dyCake * dyCake < pickupR * pickupR) {
            game.cake.slices -= 1;
            a.carrying = true;
            a.heading = angleTowards(a.x, a.y, game.anthill.x, game.anthill.y);
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
      upgradesTo: ['siege']
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
      upgradesTo: ['railgun']
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
      pierce: true,
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
      upgradesTo: ['autocannon']
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
      upgradesTo: ['vulcan']
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
      upgradesTo: ['frost']
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
      upgradesTo: ['glacier']
    },
    glacier: {
      name: 'Glacier',
      cost: 90,
      damage: 1,
      fireRate: 0,
      range: 130,
      family: 'status',
      tier: 3,
      beam: true,
      beamDamagePerSec: 1,
      statusOnBeam: { kind: 'slow', magnitude: 0.5, duration: 1 },
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
    var nearest = null;
    var nearestDistSq = rangeSq;
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      if (a.dead) { continue; }
      if (a.intangible) { continue; }
      var dx = a.x - cannon.x;
      var dy = a.y - cannon.y;
      var distSq = dx * dx + dy * dy;
      if (distSq < nearestDistSq) {
        nearest = a;
        nearestDistSq = distSq;
      }
    }
    return nearest;
  }

  function updateCannons(dt) {
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

      // Beam cannons (Glacier and any future beam emitter): per-tick damage +
      // status refresh, no projectile fired. Status refresh sets duration to
      // ~1s each tick so the slow naturally lingers ~1s after disconnect.
      if (def.beam) {
        var btarget = findTarget(c);
        c.target = btarget;
        if (!btarget) {
          c.beamTarget = null;
          continue;
        }
        c.heading = angleTowards(c.x, c.y, btarget.x, btarget.y);
        applyDamage(btarget, def.beamDamagePerSec * dt, false);
        if (btarget.hp <= 0) {
          killAnt(btarget, c);
        }
        if (def.statusOnBeam) {
          var sb = def.statusOnBeam;
          applyStatus(btarget, sb.kind, sb.magnitude, sb.duration, 'beam');
        }
        c.beamTarget = btarget;
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
    var heading = angleTowards(cannon.x, cannon.y, target.x, target.y);
    var n = def.barrels || 1;
    var spread = 0.06;                          // ~3.4 deg per step
    for (var i = 0; i < n; i++) {
      var off;
      if (n === 1) {
        off = 0;
      } else {
        off = (i - (n - 1) / 2) * spread;
      }
      var h = heading + off;
      game.projectiles.push({
        x: cannon.x + Math.cos(h) * 12,
        y: cannon.y + Math.sin(h) * 12,
        vx: Math.cos(h) * def.projectileSpeed,
        vy: Math.sin(h) * def.projectileSpeed,
        damage: def.damage,
        pierce: !!def.pierce,
        statusOnHit: def.statusOnHit || null,
        life: 1.5     // seconds
      });
    }
  }

  // Centralized damage application. Soldier armor (Task 16) hooks here:
  // armored ants will reduce damage unless `pierce` is set. For now this is
  // a thin wrapper so the hook point exists.
  function applyDamage(ant, amount, pierce) {
    var k = ANT_KINDS[ant.kind];
    var dmg = amount;
    if (pierce === false || pierce === null || !pierce) {
      dmg = dmg * (1 - (k.armor || 0));
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
    if (ant.carrying) {
      game.cake.slices = game.cake.slices + 1;
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

  function updateProjectiles(dt) {
    var kept = [];
    for (var i = 0; i < game.projectiles.length; i++) {
      var p = game.projectiles[i];
      p.x += p.vx * dt;
      p.y += p.vy * dt;
      p.life -= dt;
      var hit = false;
      for (var j = 0; j < game.ants.length; j++) {
        var a = game.ants[j];
        if (a.dead) { continue; }
        if (a.intangible) { continue; }
        var dx = a.x - p.x;
        var dy = a.y - p.y;
        if (dx * dx + dy * dy < 6 * 6) {
          applyDamage(a, p.damage, p.pierce);
          if (p.statusOnHit) {
            var s = p.statusOnHit;
            applyStatus(a, s.kind, s.magnitude, s.duration, 'projectile');
          }
          if (a.hp <= 0) {
            killAnt(a, null);
          }
          hit = true;
          break;
        }
      }
      if (!hit) {
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
    game.paused = false;
    game.speed = 1;
    ui.sendNextRect = null;
    ui.restartRunRect = null;
    ui.selectedCannon = null;
    ui.upgradeRects[0] = null;
    ui.upgradeRects[1] = null;
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
    upgradeRects: [null, null], // up-to-2 upgrade buttons in the tower panel
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
      // the upgrade buttons (up to 2) then the sell button.
      if (ui.selectedCannon) {
        if (p.x >= PLAYFIELD_W) {
          for (var ri = 0; ri < 2; ri++) {
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

  function update(dt) {
    if (game.paused) { return; }
    if (game.state !== 'playing') { return; }
    if (game.waveState === 'won') { return; }
    if (game.waveState === 'lost') { return; }
    game.time += dt;
    var simDt = dt * game.speed;
    updateWaveState(dt);
    updateAnts(simDt);
    updateCannons(simDt);
    updateProjectiles(simDt);
    updateEffects(simDt);
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
  });

  // Expose game for debugging from devtools console
  window.__ab = game;
  window.__abUi = ui;
  window.__abStatusKinds = STATUS_KINDS;
  window.__abApplyStatus = applyStatus;
})();
</script>
