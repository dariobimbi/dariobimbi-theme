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
    splat: makeAntSplat(),
    cannonBase: makeCannonBase(),
    cannonTurret: makeCannonTurret(),
    anthill: makeAnthill(),
    projectile: makeProjectile()
  };
  // Note: cake sprite is regenerated in render() per frame because it depends on slice count.

  // === RENDERING ===
  function drawShop() {
    var cardX = PLAYFIELD_W + 8;
    var cardY = 32;
    ctx.fillStyle = '#2a2f3e';
    ctx.fillRect(cardX, cardY, 56, 64);
    ctx.drawImage(game.sprites.cannonBase, cardX + 12, cardY + 4);
    ctx.drawImage(game.sprites.cannonTurret, cardX + 18, cardY + 10);
    ctx.fillStyle = '#ffffff';
    ctx.font = '8px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('Howitzer', cardX + 28, cardY + 50);
    ctx.fillStyle = (game.gold >= CANNON_KINDS.howitzer.cost) ? '#fee761' : '#888';
    ctx.fillText('50g', cardX + 28, cardY + 60);
    ctx.textAlign = 'start';
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
    // Upgrade + sell buttons land in Task 9 — leave that area blank
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
      var sprite;
      if (a.dead) {
        sprite = game.sprites.splat;
      } else {
        sprite = game.sprites[ANT_KINDS[a.kind].spriteKey];
      }
      ctx.drawImage(sprite, Math.round(a.x - 8), Math.round(a.y - 8));
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
    }

    // Placed cannons
    for (var ci = 0; ci < game.cannons.length; ci++) {
      var c = game.cannons[ci];
      ctx.drawImage(game.sprites.cannonBase, Math.round(c.x - 16), Math.round(c.y - 16));
      ctx.save();
      ctx.translate(Math.round(c.x), Math.round(c.y));
      ctx.rotate(c.heading + Math.PI / 2);
      ctx.drawImage(game.sprites.cannonTurret, -10, -16);
      ctx.restore();
    }

    // Projectiles
    for (var pi = 0; pi < game.projectiles.length; pi++) {
      var p = game.projectiles[pi];
      ctx.drawImage(game.sprites.projectile, Math.round(p.x - 2), Math.round(p.y - 2));
    }

    // Drag preview
    if (ui.dragging) {
      var ok = canPlaceCannon(ui.mouseX, ui.mouseY);
      ctx.globalAlpha = 0.6;
      ctx.drawImage(game.sprites.cannonBase, Math.round(ui.mouseX - 16), Math.round(ui.mouseY - 16));
      ctx.drawImage(game.sprites.cannonTurret, Math.round(ui.mouseX - 10), Math.round(ui.mouseY - 16));
      ctx.globalAlpha = 1;
      ctx.strokeStyle = ok ? 'rgba(255, 255, 255, 0.5)' : 'rgba(228, 59, 68, 0.7)';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.arc(ui.mouseX, ui.mouseY, CANNON_KINDS.howitzer.range, 0, Math.PI * 2);
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

  function spawnAnt(kindKey) {
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
      carrying: false,
      wanderPhase: Math.random() * Math.PI * 2,
      wanderFreq: k.wanderFreqMin + Math.random() * (k.wanderFreqMax - k.wanderFreqMin),
      wanderAmp: k.wanderAmpMin + Math.random() * (k.wanderAmpMax - k.wanderAmpMin),
      effects: {}                                   // Task 4 fills this with status-effect entries
    };
    game.ants.push(ant);
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
      var def = CANNON_KINDS[c.kind];
      c.cooldown -= dt;
      var target = findTarget(c);
      c.target = target;
      if (target) {
        c.heading = angleTowards(c.x, c.y, target.x, target.y);
        if (c.cooldown <= 0) {
          fireProjectile(c, target);
          c.cooldown = 1 / def.fireRate;
        }
      }
    }
  }

  // === PROJECTILES ===
  function fireProjectile(cannon, target) {
    var def = CANNON_KINDS[cannon.kind];
    var heading = angleTowards(cannon.x, cannon.y, target.x, target.y);
    game.projectiles.push({
      x: cannon.x,
      y: cannon.y,
      vx: Math.cos(heading) * def.projectileSpeed,
      vy: Math.sin(heading) * def.projectileSpeed,
      damage: def.damage,
      life: 1.5     // seconds
    });
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
        var dx = a.x - p.x;
        var dy = a.y - p.y;
        if (dx * dx + dy * dy < 6 * 6) {
          a.hp -= p.damage;
          if (a.hp <= 0) {
            a.dead = true;
            a.deathTime = 0;
            game.gold += ANT_KINDS[a.kind].gold;
            if (a.carrying) {
              game.cake.slices += 1;
              a.carrying = false;
            }
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
    selectedCannon: null    // currently inspected cannon (click on a placed cannon to select)
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

  function shopCardAt(x, y) {
    if (x < PLAYFIELD_W + 8) { return null; }
    if (x > PLAYFIELD_W + 8 + 56) { return null; }
    if (y < 32) { return null; }
    if (y > 32 + 64) { return null; }
    return 'howitzer';
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
    }
    var card = shopCardAt(p.x, p.y);
    if (card) {
      ui.dragging = { kind: card };
      canvas.classList.add('ab-dragging');
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
