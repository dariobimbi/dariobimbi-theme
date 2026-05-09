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
  var DEBUG_KINDS = ['worker', 'carrier', 'scout', 'soldier', 'tank',
                     'splitter', 'mini_splitter', 'phase', 'bomber',
                     'queen', 'king'];
  var debugCycleIdx = 0;

  // === GAME STATE ===
  // Two flag layers, intentionally kept separate (Plan 2 Task 5):
  //   game.state     — 'idle' | 'playing'   (top-level: has the player started?)
  //   game.waveState — 'prep' | 'wave' | 'won' | 'lost'   (campaign progress)
  // Rationale: 'idle' is a pre-game splash concern; win/lose belongs to the wave
  // state machine. The render and input layers read whichever flag is relevant.
  var game = {
    state: 'idle',            // 'idle' | 'playing'
    time: 0,                  // seconds since game start (real-time, ignores speed multiplier)
    simTime: 0,               // simulated seconds (scales with game.speed); used for slice timeout
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
    banner: { active: false, text: '', startedAt: 0, duration: 2.5, isBoss: false, isFinal: false },
    screenshake: { magnitude: 0, decay: 0 },
    paused: false,
    speed: 1,
    // Plan 4 — wave-15 god-tier unlock state. In-session only (no
    // localStorage). godTierUnlocked flips true when wave 15 clears;
    // godTierUnlockPulseStart stamps game.time so the selected-tower
    // panel can render a 0.5s golden halo flash on T2 finals' god-tier
    // upgrade buttons. Both reset on game-over → restart.
    godTierUnlocked: false,
    godTierUnlockPulseStart: 0
  };

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

  // Show a wave-intro banner. Duration scales up for bosses, longer still
  // for the final wave (Plan 3 Task 32) so the FINAL WAVE: THE KING text
  // gets enough screen time to register before the King strolls onto the field.
  function showBanner(text, isBoss, isFinal) {
    game.banner.active = true;
    game.banner.text = text;
    game.banner.startedAt = game.time;
    if (isFinal) {
      game.banner.duration = 4;
    } else {
      game.banner.duration = isBoss ? 3 : 2.5;
    }
    game.banner.isBoss = isBoss;
    game.banner.isFinal = !!isFinal;
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

  function makeRoyalGuardSprite() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    x.fillStyle = '#3a4466';
    x.beginPath();
    x.ellipse(8, 8, 5, 4, 0, 0, Math.PI * 2);   // body — same as worker
    x.fill();
    x.fillStyle = '#2c3a8c';
    x.fillRect(6, 7, 2, 2);                     // royal-blue accent (left shoulder)
    x.fillRect(9, 7, 2, 2);                     // royal-blue accent (right shoulder)
    x.fillStyle = '#262b44';
    x.fillRect(7, 3, 2, 2);                     // head
    x.fillStyle = '#fee761';
    x.fillRect(6, 1, 4, 1);                     // crown base, forward of head
    x.fillRect(6, 0, 1, 1);                     // crown spike L
    x.fillRect(8, 0, 1, 1);                     // crown spike center
    x.fillRect(9, 0, 1, 1);                     // crown spike R (intentional 3-spike pattern)
    x.fillStyle = '#181425';
    x.fillRect(7, 1, 1, 1);                     // antenna L (shorter, crown takes the upper space)
    x.fillRect(8, 1, 1, 1);                     // antenna R
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

  function makeRoyalGuardSplat() {
    var c = makeCanvas(16, 16);
    var x = c.getContext('2d');
    x.fillStyle = '#2c3a8c';
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
    x.fillStyle = '#181425';
    x.fillRect(13, 8, 2, 1);                    // chain link 1 (dark)
    x.fillRect(13, 11, 2, 1);                   // chain link 2
    x.fillRect(13, 14, 2, 1);                   // chain link 3
    x.fillStyle = '#ff8fc4';
    x.fillRect(11, 1, 2, 2);                    // muzzle highlight
    return c;
  }

  // === Plan 8a — God-tier turret factories (vertical slice, 9 of 18) ===
  // Stubs delegate to makeCannonTurret() until each cannon's body is filled
  // in by its own task. All 9 register in the turrets table below; the live
  // demo will show howitzer fallback until each task lands.

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
  function makeCannonTurretBlizzard() { return makeCannonTurret(); }
  function makeCannonTurretTempest() { return makeCannonTurret(); }
  function makeCannonTurretBiohazard() { return makeCannonTurret(); }

  function makeCake(slices) {
    var c = makeCanvas(48, 48);
    var x = c.getContext('2d');
    var maxSlices = 8;
    var fillRatio = slices / maxSlices;
    // Drop shadow under the plate so the whole element pops off the
    // red/cream blanket regardless of which checker square it lands on.
    x.fillStyle = 'rgba(0,0,0,0.35)';
    x.fillRect(6, 42, 36, 3);
    // Plate: dark navy (was white) for hard contrast against both blanket
    // colors. The white plate disappeared on the cream squares.
    x.fillStyle = '#181425';
    x.fillRect(6, 36, 36, 6);
    x.fillStyle = '#262b44';
    x.fillRect(6, 36, 36, 1);                 // plate top highlight
    if (fillRatio > 0) {
      // Dark outline ring under the cake body so it reads as a silhouette
      // even when sitting on a red square.
      x.fillStyle = '#181425';
      x.fillRect(11, 17 + (1 - fillRatio) * 18, 26, fillRatio * 19 + 2);
      // Cake body
      x.fillStyle = '#feae34';
      x.fillRect(12, 18 + (1 - fillRatio) * 18, 24, fillRatio * 18);
      // Icing
      x.fillStyle = '#fee761';
      x.fillRect(15, 12 + (1 - fillRatio) * 18, 18, fillRatio * 9);
      // Candle outline + body
      x.fillStyle = '#181425';
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

  // King — final-wave boss (Plan 3 Task 29). 32x32 dark navy body with brown
  // legs, a yellow three-spike crown over a dark band, and a red gem on the
  // chest so the ant reads as a regal threat at any zoom level.
  function makeKingSprite() {
    var c = makeCanvas(32, 32);
    var x = c.getContext('2d');
    x.fillStyle = '#262b44';
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

  game.sprites = {
    worker: makeWorkerSprite(),
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
      plague: makeCannonTurretPlague(),
      sonic_pulse: makeCannonTurretSonicPulse(),
      frost: makeCannonTurretFrost(),
      glacier: makeCannonTurretGlacier(),
      cryo: makeCannonTurretCryo(),
      // Plan 4 — Pink Rubber Family (Bucket B). Linear chain, no T3 god.
      pink_pea: makeCannonTurretPinkPea(),
      bouncy_bubble: makeCannonTurretBouncyBubble(),
      rubber_wrecking_ball: makeCannonTurretRubberWreckingBall(),
      // Plan 8a — God-tier vertical slice (9 of 18). Plan 8b fills in the rest.
      particle_cannon: makeCannonTurretParticleCannon(),
      annihilator: makeCannonTurretAnnihilator(),
      earthshaker: makeCannonTurretEarthshaker(),
      phoenix: makeCannonTurretPhoenix(),
      reaper: makeCannonTurretReaper(),
      hawkeye: makeCannonTurretHawkeye(),
      blizzard: makeCannonTurretBlizzard(),
      tempest: makeCannonTurretTempest(),
      biohazard: makeCannonTurretBiohazard()
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
    // Plan 4: Pink Pea joins the base-cannon shop column as the 4th card.
    // The Pink Rubber Family is a "casual / approachable" 4th family per
    // Plan 3 §9 — available from wave 1 with no gating.
    var keys = ['howitzer', 'repeater', 'twingun', 'pink_pea'];
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
    // Plan 4: block god-tier upgrades when locked. Defense-in-depth alongside
    // the visual greying in drawTowerPanel (Bucket F). Without this guard a
    // misclick on a 50%-alpha button would still commit the upgrade.
    if (udef.tier === 'god') {
      if (!game.godTierUnlocked) { return; }
    }
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

  // Plan 7 — Targeting row: 5 buttons (C, F, L, S, W) + label below.
  // Returns the y-coordinate immediately after the row, so drawTowerPanel
  // can push the upgrade buttons down by the row's vertical extent.
  // Sets ui.targetingRects to a 5-array of hit-test rects in mode order:
  //   [closest, first, last, strongest, weakest].
  function drawTargetingRow(c, px, py) {
    var def = CANNON_KINDS[c.kind];
    // Aura cannons (MP-N etc.) don't call findTarget, so the row is omitted.
    // Cone-DoT (Plasma Torch) and beam (Glacier, Solar Lance) DO call findTarget
    // and use targeting modes meaningfully, so they receive the row.
    if (def.aura) {
      ui.targetingRects = [];
      return py;
    }

    var modes = ['closest', 'first', 'last', 'strongest', 'weakest'];
    var glyphs = ['C', 'F', 'L', 'S', 'W'];
    var modeNames = ['Closest', 'First', 'Last', 'Strongest', 'Weakest'];
    var activeColors = ['#3a4a8c', '#9a7820', '#3a3a3a', '#7a2020', '#7a2040'];
    var inactiveBg = '#1a1a28';
    var btnW = 22;
    var btnH = 22;
    var gap = 4;
    var rowY = py;

    var active = c.targetingMode;
    if (!active) { active = 'closest'; }

    ui.targetingRects = [];
    for (var i = 0; i < 5; i++) {
      var bx = px + i * (btnW + gap);
      var by = rowY;
      var isActive = (modes[i] === active);

      if (isActive) {
        ctx.fillStyle = activeColors[i];
      } else {
        ctx.fillStyle = inactiveBg;
      }
      ctx.fillRect(bx, by, btnW, btnH);

      if (isActive) {
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 2;
        ctx.strokeRect(bx + 1, by + 1, btnW - 2, btnH - 2);
        ctx.lineWidth = 1;
      }

      ctx.fillStyle = '#ffffff';
      ctx.font = 'bold 12px monospace';
      ctx.textAlign = 'center';
      ctx.fillText(glyphs[i], bx + btnW / 2, by + btnH / 2 + 4);
      ctx.textAlign = 'left';

      ui.targetingRects.push({ x: bx, y: by, w: btnW, h: btnH });
    }

    var labelY = rowY + btnH + 12;
    var activeIdx = 0;
    for (var li = 0; li < modes.length; li++) {
      if (modes[li] === active) { activeIdx = li; break; }
    }
    ctx.fillStyle = '#cccccc';
    ctx.font = '10px monospace';
    ctx.fillText('Targeting: ' + modeNames[activeIdx], px, labelY);

    return labelY + 6;
  }

  function drawTowerPanel(c) {
    var sk = CANNON_KINDS[c.kind];
    var px = PLAYFIELD_W + 8;
    var py = 32;
    var pw = SHOP_W - 16;
    ctx.fillStyle = '#262b44';
    ctx.fillRect(px, py, pw, 232);  // Plan 7 — +32 to fit targeting row
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

    // Plan 7 — Targeting row sits between stats and upgrades. Returns the
    // y-coordinate where the next element can start. For aura cannons the row
    // is omitted and the function returns the y it received, so the upgrade
    // base-Y clamp below preserves the original layout.
    var afterTargetingY = drawTargetingRow(c, px + 6, py + 78);

    // Upgrade buttons (up to 3) — only present when sk.upgradesTo is non-empty.
    // Base cannons offer 3 mid options, mid cannons offer 2 finals, finals
    // offer 1 god-tier (Plan 4). God-tier buttons render at 50% alpha with a
    // tooltip when locked, full alpha when unlocked, and an additive gold
    // halo for the first 0.5s after unlock fires (game.godTierUnlockPulseStart).
    var upgrades = sk.upgradesTo;
    if (!upgrades) { upgrades = []; }
    var hoveredLockedRect = null;
    // Plan 7 — upgradeBaseY shifts to accommodate the targeting row. For aura
    // cannons (no row), afterTargetingY equals py + 78, which is below py + 90,
    // so the clamp keeps the original layout.
    var upgradeBaseY = afterTargetingY;
    if (upgradeBaseY < py + 90) { upgradeBaseY = py + 90; }
    for (var i = 0; i < upgrades.length; i++) {
      var uk = upgrades[i];
      var udef = CANNON_KINDS[uk];
      var by = upgradeBaseY + i * 22;
      var bx = px + 6;
      var bw = pw - 12;
      var bh = 18;
      var canAfford = (game.gold >= udef.cost);
      var isGod = (udef.tier === 'god');
      var isLocked = false;
      if (isGod) {
        if (!game.godTierUnlocked) { isLocked = true; }
      }

      // Plan 4 — unlock pulse halo. Renders behind the button for the first
      // 0.5s after godTierUnlockPulseStart, fading from full to zero alpha.
      if (isGod) {
        var pulseAge = game.time - game.godTierUnlockPulseStart;
        if (pulseAge >= 0) {
          if (pulseAge < 0.5) {
            var pulseAlpha = 1 - (pulseAge / 0.5);
            ctx.save();
            ctx.globalAlpha = pulseAlpha;
            ctx.fillStyle = '#fee761';
            ctx.fillRect(bx - 4, by - 4, bw + 8, bh + 8);
            ctx.restore();
          }
        }
      }

      ctx.save();
      if (isLocked) { ctx.globalAlpha = 0.5; }
      ctx.fillStyle = canAfford ? '#3a8c3a' : '#444';
      if (isGod) {
        if (!isLocked) {
          // Unlocked god-tier gets a distinct gold-on-dark scheme so the
          // upgrade reads as apex tier even at full alpha. Affordability
          // still gates the inner fill — locked god uses the muted gray.
          ctx.fillStyle = canAfford ? '#a07020' : '#444';
        }
      }
      ctx.fillRect(bx, by, bw, bh);
      ctx.fillStyle = canAfford ? '#fee761' : '#888';
      if (isGod) {
        if (!isLocked) {
          ctx.fillStyle = canAfford ? '#fee761' : '#888';
        }
      }
      ctx.fillText('-> ' + udef.name + ' (' + udef.cost + 'g)', bx + 4, by + 12);
      ctx.restore();

      ui.upgradeRects[i] = { x: bx, y: by, w: bw, h: bh, toKind: uk, locked: isLocked };

      // Capture the rect for tooltip rendering after the loop, so the
      // tooltip composites over neighbour buttons cleanly.
      if (isLocked) {
        if (ui.mouseX >= bx) {
          if (ui.mouseX <= bx + bw) {
            if (ui.mouseY >= by) {
              if (ui.mouseY <= by + bh) {
                hoveredLockedRect = { x: bx, y: by, w: bw, h: bh };
              }
            }
          }
        }
      }
    }
    for (var k = upgrades.length; k < 3; k++) { ui.upgradeRects[k] = null; }

    // Plan 4 — locked god-tier tooltip. Drawn after the loop so a tooltip
    // for an upper button never sits under the button below it. 140 px wide
    // matches the panel column width minus padding.
    if (hoveredLockedRect) {
      var tipX = hoveredLockedRect.x;
      var tipY = hoveredLockedRect.y - 22;
      ctx.save();
      ctx.fillStyle = 'rgba(20, 22, 36, 0.92)';
      ctx.fillRect(tipX, tipY, 140, 18);
      ctx.fillStyle = '#fee761';
      ctx.font = '10px monospace';
      ctx.fillText('Unlocks at wave 15', tipX + 6, tipY + 12);
      ctx.restore();
    }

    // Sell button — pinned near the bottom of the panel rect.
    var refund = sellRefund(c);
    var sy = py + 232 - 26;  // Plan 7 — panel height bumped to 232
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
    ctx.drawImage(cakeSprite, game.cake.x - 24, game.cake.y - 24);
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

  // Layer 5c: persistent cannon auras (Blizzard, Plan 4 — MP-N). Translucent
  // light-blue radial fill that visually communicates the aura range. Drawn
  // under bases/turrets/ants so ants walking through don't get obscured by
  // it. Renders only for cannons whose def carries the aura field.
  function drawCannonAuras() {
    for (var i = 0; i < game.cannons.length; i++) {
      var c = game.cannons[i];
      var def = CANNON_KINDS[c.kind];
      if (!def.aura) { continue; }
      var au = def.aura;
      ctx.save();
      ctx.fillStyle = 'rgba(150, 200, 255, 0.15)';
      ctx.strokeStyle = 'rgba(150, 200, 255, 0.4)';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.arc(c.x, c.y, au.radius, 0, Math.PI * 2);
      ctx.fill();
      ctx.stroke();
      ctx.restore();
    }
  }

  // Plan 4 — god-tier halo. Renders a soft pulsing gold disc under any cannon
  // whose def.tier === 'god'. Drawn between auras and bases so the halo sits
  // beneath the round base sprite without bleeding over the turret.
  // Period ~2s (sin of game.time * PI), alpha range 0.30 to 0.60.
  function drawGodTierHalos() {
    for (var i = 0; i < game.cannons.length; i++) {
      var c = game.cannons[i];
      var def = CANNON_KINDS[c.kind];
      if (def.tier !== 'god') { continue; }
      var alpha = 0.45 + 0.15 * Math.sin(game.time * Math.PI);
      ctx.save();
      ctx.globalAlpha = alpha;
      ctx.fillStyle = '#fee761';
      ctx.beginPath();
      ctx.arc(c.x, c.y, 22, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
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
          if (a.kind === 'royal_guard') {
            sprite = game.sprites.royalGuardSplat;
          } else {
            sprite = game.sprites.splat;
          }
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
        // Plan 4 — Biohazard infected outline. Bright cyan pulse around any
        // ant whose poison effect carries damageAmp >= 1.25 (Biohazard's
        // signature). Identifies the +25% priority target at a glance,
        // distinct from poison's existing green tint. Sin-based alpha pulse
        // (period ~0.6s); ~2px stroke width on top of the ant sprite.
        if (a.effects) {
          if (a.effects.poison) {
            if (a.effects.poison.damageAmp) {
              if (a.effects.poison.damageAmp >= 1.25) {
                var biAlpha = 0.65 + 0.25 * Math.sin(game.time * 10);
                ctx.save();
                ctx.globalAlpha = biAlpha;
                ctx.strokeStyle = '#00ffff';
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.arc(a.x, a.y, 10, 0, Math.PI * 2);
                ctx.stroke();
                ctx.restore();
              }
            }
          }
        }
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
      var def = CANNON_KINDS[c.kind];
      ctx.save();
      ctx.translate(Math.round(c.x), Math.round(c.y));
      ctx.rotate(c.heading + Math.PI / 2);
      var turretSprite = game.sprites.turrets[c.kind];
      if (!turretSprite) { turretSprite = game.sprites.turrets.howitzer; }
      ctx.drawImage(turretSprite, -10, -16);
      ctx.restore();
      // Plan 4 — muzzle flash. Renders for FLASH_DUR seconds after the cannon
      // fires; alpha fades linearly. flashScale on def scales the flash
      // radius for god-tier rows (default 1.0, god-tier 1.7).
      if (c.lastFiredAt) {
        var FLASH_DUR = 0.08;
        var dtSinceFire = game.time - c.lastFiredAt;
        if (dtSinceFire >= 0) {
          if (dtSinceFire < FLASH_DUR) {
            var fAlpha = 1 - dtSinceFire / FLASH_DUR;
            var fScale = def.flashScale ? def.flashScale : 1.0;
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
      ctx.drawImage(game.sprites.projectile, Math.round(p.x - pOff), Math.round(p.y - pOff + arcOffset), pSize, pSize);
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
    if (game.banner.isFinal) {
      ctx.fillStyle = '#7a1a1a';
    } else {
      ctx.fillStyle = game.banner.isBoss ? '#a44' : '#3a4466';
    }
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
    drawCannonAuras();         // 5c. persistent radial auras (Blizzard MP-N)
    drawGodTierHalos();        // 5d. pulsing gold halo under T3 god-tier turrets
    drawCannonBases();         // 6. static cannon bases (under turrets/ants)
    drawAnts();                // 7. ants, sorted top-down by y
    drawShatterParticles();    // 7b. cyan shatter particles (Plan 6 Brittle)
    drawTargetingOverlay();    // 7c. selected-cannon target line + reticle (Plan 7)
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
      hp: 15,
      speed: 30,
      carryingSpeedMult: 1.5,    // Plan 3 Task 33 — flees at 45 px/s once a slice is grabbed (panic sprint)
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

  // Plan 6: Shatter particle factory. Called by the Brittle predicate inside
  // applyDamage when a heavy-family direct hit lands on a frozen ant. Pushes
  // 10 cyan particles onto game.shatterParticles with random radial velocity.
  // The particles tick on real-time dt (see updateShatterParticles below) so
  // the visual stays readable at any game speed.
  function spawnShatterParticles(x, y) {
    for (var i = 0; i < 10; i++) {
      var angle = Math.random() * Math.PI * 2;
      var speed = 80 + Math.random() * 60;
      game.shatterParticles.push({
        x: x,
        y: y,
        vx: Math.cos(angle) * speed,
        vy: Math.sin(angle) * speed,
        age: 0,
        lifetime: 0.18
      });
    }
  }

  // Plan 6: Particle tick. Caller passes real-time dt (NOT simDt) so the burst
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
    ctx.fillStyle = '#88f0ff';
    for (var i = 0; i < game.shatterParticles.length; i++) {
      var p = game.shatterParticles[i];
      var alpha = 1 - (p.age / p.lifetime);
      ctx.globalAlpha = alpha;
      ctx.fillRect(Math.round(p.x) - 1, Math.round(p.y) - 1, 2, 2);
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
    if (k.onSpawn) { k.onSpawn(ant); }
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
      tier: 'base',
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
      tier: 'mid',
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
      tier: 'final',
      linePierce: 999,    // unlimited — pre-Plan-3 Railgun semantics
      armorPierce: true,
      upgradesTo: ['particle_cannon']
    },
    mortar: {
      name: 'Mortar',
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
      cost: 70,
      damage: 2,
      fireRate: 1,
      range: 110,
      projectileSpeed: 240,
      family: 'double',
      tier: 'mid',
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
      tier: 'final',
      pellets: 5,
      coneDeg: 50,           // 50 deg fan, ~12.5 deg between pellets
      upgradesTo: ['scattergun']
    },
    minigun: {
      name: 'Minigun',
      cost: 110,
      damage: 1,
      fireRate: 14,
      range: 120,
      projectileSpeed: 320,
      family: 'double',
      tier: 'final',
      aimJitter: 0.05,       // ~2.9 deg max per-shot angular wobble
      upgradesTo: ['phalanx']
    },
    toxin: {
      name: 'Toxin',
      cost: 75,
      damage: 1,
      fireRate: 1,
      range: 100,
      projectileSpeed: 200,
      family: 'double',
      tier: 'mid',
      statusOnHit: { kind: 'poison', magnitude: 1, duration: 5 },   // 1 dps over 5s = 5 dmg DoT
      upgradesTo: ['plague', 'sonic_pulse']
    },
    cryo: {
      name: 'Cryo',
      cost: 110,
      damage: 3,
      fireRate: 1,
      range: 110,
      projectileSpeed: 220,
      family: 'double',
      tier: 'final',
      // damageAmp tag on the freeze effect: subsequent damage from any cannon
      // lands +50% while the 0.8s freeze is active. See Step 2 stamp pattern
      // and applyDamage's freeze.damageAmp read.
      statusOnHit: { kind: 'freeze', magnitude: 1, duration: 0.8, damageAmp: 1.5 },
      upgradesTo: ['absolute_zero']
    },
    plague: {
      name: 'Plague',
      cost: 90,
      damage: 2,
      fireRate: 1,
      range: 110,
      projectileSpeed: 200,
      family: 'double',
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
      cost: 90,
      damage: 5,
      fireRate: 0.8,
      range: 100,
      projectileSpeed: 240,
      family: 'double',
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

    // === DOUBLE FAMILY GOD-TIER (Plan 4 — Bucket E, Task 21) ===
    // Six T3 rows for the Double / status lineage. Blizzard uses MP-N (Task
    // 11). Absolute Zero and Tempest carry splashStatus: true with
    // splashMult: 0 — the splash exists only to broadcast the status, not to
    // damage. Biohazard's spreadRadius reads in poison.onTargetDeath (below).
    scattergun: {
      name: 'Scattergun',
      cost: 260,
      damage: 3,
      fireRate: 0.7,
      range: 100,
      projectileSpeed: 320,
      family: 'double',
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
      cost: 280,
      damage: 2,
      fireRate: 18,
      range: 140,
      projectileSpeed: 380,
      family: 'double',
      tier: 'god',
      barrels: 3,
      aimJitter: 0,
      flashScale: 1.7,
      upgradesTo: []
    },
    blizzard: {
      name: 'Blizzard',
      cost: 260,
      damage: 0,
      fireRate: 0,
      range: 150,
      projectileSpeed: 0,
      family: 'double',
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
      cost: 280,
      damage: 6,
      fireRate: 1,
      range: 130,
      projectileSpeed: 280,
      family: 'double',
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
      cost: 260,
      damage: 4,
      fireRate: 1,
      range: 130,
      projectileSpeed: 260,
      family: 'double',
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
      cost: 260,
      damage: 10,
      fireRate: 0.7,
      range: 110,
      projectileSpeed: 280,
      family: 'double',
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
      disabledUntil: 0,
      targetingMode: 'closest'  // Plan 7 — per-cannon targeting dial
    });
    return true;
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
      } else {
        // first or last: compare squared distance to cake.
        var ccdx = cur.ant.x - game.cake.x;
        var ccdy = cur.ant.y - game.cake.y;
        var bcdx = best.ant.x - game.cake.x;
        var bcdy = best.ant.y - game.cake.y;
        var cToCake = ccdx * ccdx + ccdy * ccdy;
        var bToCake = bcdx * bcdx + bcdy * bcdy;
        if (mode === 'first') {
          if (cToCake < bToCake) { best = cur; }
        } else if (mode === 'last') {
          if (cToCake > bToCake) { best = cur; }
        }
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
    cannon.lastFiredAt = game.time;

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
    // Plan 6: Brittle synergy. Heavy-family direct hit on a frozen ant deals
    // 2x damage on top of any existing freeze.damageAmp (composes multiplicatively),
    // consumes the freeze status, and spawns a cyan shatter visual. Direct hit
    // only — splash, beam, aura, and chain-link calls leave isDirectHit unset.
    // Nested ifs to dodge the inline-script and-operator encoding gotcha.
    if (isDirectHit) {
      if (sourceCannon) {
        var sk2 = CANNON_KINDS[sourceCannon.kind];
        if (sk2.family === 'heavy') {
          if (ant.effects) {
            if (ant.effects.freeze) {
              dmg = dmg * 2;
              delete ant.effects.freeze;
              spawnShatterParticles(ant.x, ant.y);
            }
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
    ant.deathTime = 0;
    var k = ANT_KINDS[ant.kind];
    game.gold = game.gold + k.gold;
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
            applyStatus(a, s.kind, s.magnitude, s.duration, 'splash');
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
              applyStatus(a, s.kind, s.magnitude, s.duration, 'projectile');
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
                applyStatus(a, sfe.kind, sfe.magnitude, sfe.duration, 'first-hit');
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
        showBanner(wv.bannerText, !!wv.isBoss, wv.number === 25);
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
          // Plan 4: wave-15 unlocks god-tier upgrades. Fires once per run.
          // Stamps godTierUnlockPulseStart so the selected-tower panel can
          // render a 0.5s golden halo on T2 finals' god-tier buttons.
          if (wv.number === 15) {
            if (!game.godTierUnlocked) {
              game.godTierUnlocked = true;
              game.godTierUnlockPulseStart = game.time;
            }
          }
          if (game.waveIndex + 1 >= WAVES.length) {
            game.waveState = 'won';
          } else {
            game.waveIndex = game.waveIndex + 1;
            game.waveState = 'prep';
            game.prepRemaining = wavePrepDuration(WAVES[game.waveIndex]);
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
    game.spawnJobs = [];
    game.slices = [];
    // game.shatterParticles is ephemeral; do not persist if Save State is added later.
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
    game.banner.text = '';
    game.banner.startedAt = 0;
    game.banner.duration = 2.5;
    game.banner.isBoss = false;
    game.banner.isFinal = false;
    game.screenshake.magnitude = 0;
    game.screenshake.decay = 0;
    game.paused = false;
    game.speed = 1;
    game.godTierUnlocked = false;
    game.godTierUnlockPulseStart = 0;
    ui.sendNextRect = null;
    ui.restartRunRect = null;
    ui.dragging = null;
    ui.selectedCannon = null;
    ui.upgradeRects[0] = null;
    ui.upgradeRects[1] = null;
    ui.upgradeRects[2] = null;
    ui.hoveredUpgrade = null;
    ui.sellRect = null;
    ui.targetingRects = [];   // Plan 7 — clear targeting hit-test rects on restart
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
    targetingRects: [],     // Plan 7 — five mode-button rects (or empty for aura cannons)
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
      // the targeting buttons first (Plan 7), then upgrade buttons (up to 3),
      // then the sell button.
      if (ui.selectedCannon) {
        if (p.x >= PLAYFIELD_W) {
          // Plan 7 — Targeting row hit-test. Mode strings are positional and
          // must match the order in drawTargetingRow's `modes` array.
          if (ui.targetingRects) {
            var tModes = ['closest', 'first', 'last', 'strongest', 'weakest'];
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
    // Screenshake decay (Plan 3 — Shockwave). Decay runs in real time on dt
    // (not simDt) so 2x speed doesn't drain the shake faster than the eye sees it.
    if (game.screenshake.magnitude > 0) {
      game.screenshake.magnitude = game.screenshake.magnitude - game.screenshake.decay * dt;
      if (game.screenshake.magnitude < 0) { game.screenshake.magnitude = 0; }
    }
    updateShatterParticles(dt);
    game.time += dt;
    var simDt = dt * game.speed;
    // Plan 4 Task 26 — simulated-time accumulator. Slice timeout (and any
    // future age-based mechanic that should "feel" the speed multiplier)
    // reads game.simTime so an 8-second slice still ages 8 simulated seconds
    // at 0.5x or 2x speed. game.time stays on the real-world clock for
    // screenshake decay and unlock-pulse animations.
    game.simTime = game.simTime + simDt;
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
      // Plan 4 — ?debug=1 G: toggle godTierUnlocked. When toggling true,
      // also stamps godTierUnlockPulseStart so the panel-button pulse fires
      // for visual testing. Branched two ifs to avoid the inline-script
      // and-operator (CLAUDE.md note on || encoding).
      var keyG = false;
      if (e.key === 'g') { keyG = true; }
      if (e.key === 'G') { keyG = true; }
      if (keyG) {
        game.godTierUnlocked = !game.godTierUnlocked;
        if (game.godTierUnlocked) {
          game.godTierUnlockPulseStart = game.time;
        }
        return;
      }
      // Plan 4 — ?debug=1 W: soft-reset to wave 16 prep with god-tier
      // already unlocked. Skips the 15-wave grind so King-fight tuning
      // (post-implementation playtest, spec §9) doesn't require a 20-min
      // run-up every iteration. Field names align with resetGame.
      var keyW = false;
      if (e.key === 'w') { keyW = true; }
      if (e.key === 'W') { keyW = true; }
      if (keyW) {
        game.ants = [];
        game.projectiles = [];
        game.lightningBolts = [];
        game.effects = [];
        game.spawnJobs = [];
        game.waveIndex = 15;
        game.waveState = 'prep';
        game.prepRemaining = wavePrepDuration(WAVES[game.waveIndex]);
        game.godTierUnlocked = true;
        game.godTierUnlockPulseStart = game.time;
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
  window.__abApplyDamage = applyDamage;
  window.__abWaves = WAVES;
  // Plan 4: read/write the god-tier unlock state. Pass a boolean to set,
  // omit to read. Used by the ?debug=1 G hotkey (Task 23) and Playwright
  // tests that don't want to grind 15 waves to access the gated tier.
  window.__abGodTierUnlocked = function (v) {
    if (typeof v === 'boolean') {
      game.godTierUnlocked = v;
      if (v) { game.godTierUnlockPulseStart = game.time; }
    }
    return game.godTierUnlocked;
  };
})();
</script>
