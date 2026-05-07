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
    <canvas id="ab-canvas" data-role="canvas" aria-label="Ant Buster game canvas" width="480" height="270"></canvas>
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
  width: 960px;
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
  var WIDTH = 480;
  var HEIGHT = 270;
  var PLAYFIELD_W = 320;
  var PLAYFIELD_H = 246;
  var HUD_H = 24;
  var SHOP_W = 160;
  var TARGET_FPS = 60;
  var FIXED_DT = 1 / TARGET_FPS;

  // === GAME STATE ===
  var game = {
    state: 'playing',         // 'playing' | 'won' | 'lost'
    time: 0,                  // seconds since game start
    accumulator: 0,           // for fixed-timestep
    cake: { x: 200, y: 135, slices: 8 },
    anthill: { x: 16, y: 135 },
    ants: [],
    cannons: [],
    projectiles: [],
    sprites: {},              // populated in Task 4
    gold: 150,
    wave: 1
  };

  // === CANVAS SETUP ===
  var canvas = document.getElementById('ab-canvas');
  if (!canvas) { return; }
  var ctx = canvas.getContext('2d');
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
  function render() {
    // Background
    ctx.fillStyle = '#193c3e';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);

    // Grass border (whole canvas is grass-colored already; blanket sits on top)
    var blanketX = 32;
    var blanketY = 32;
    var blanketW = 256;
    var blanketH = 200;

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

    // HUD bar (top, dimmed)
    ctx.fillStyle = 'rgba(0, 0, 0, 0.4)';
    ctx.fillRect(0, 0, PLAYFIELD_W, HUD_H);

    // Shop panel (right)
    ctx.fillStyle = '#1a1c2c';
    ctx.fillRect(PLAYFIELD_W, 0, SHOP_W, HEIGHT);

    // Ants
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      var sprite = a.dead ? game.sprites.splat : game.sprites.worker;
      ctx.drawImage(sprite, Math.round(a.x - 8), Math.round(a.y - 8));
    }

    // Shop card for Howitzer
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
    ctx.fillStyle = (game.gold >= CANNON_DEFS.howitzer.cost) ? '#fee761' : '#888';
    ctx.fillText('50g', cardX + 28, cardY + 60);
    ctx.textAlign = 'start';

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
      ctx.arc(ui.mouseX, ui.mouseY, CANNON_DEFS.howitzer.range, 0, Math.PI * 2);
      ctx.stroke();
    }

    if (game.state === 'lost') {
      ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
      ctx.fillRect(0, 0, WIDTH, HEIGHT);
      ctx.fillStyle = '#e43b44';
      ctx.font = '16px monospace';
      ctx.textAlign = 'center';
      ctx.fillText('THE ANTS WIN', WIDTH / 2, HEIGHT / 2);
      ctx.textAlign = 'start';
    }
  }

  // === ANTS ===
  function spawnAnt(type) {
    var ant = {
      type: type,
      x: game.anthill.x,
      y: game.anthill.y + (Math.random() * 6 - 3),
      hp: 10,
      maxHp: 10,
      speed: 30,
      heading: 0,         // radians; 0 = east
      dead: false,
      deathTime: 0
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

  function updateAnt(ant, dt) {
    if (ant.dead) {
      ant.deathTime += dt;
      return;
    }
    var targetX = game.cake.x;
    var targetY = game.cake.y;
    var desiredHeading = angleTowards(ant.x, ant.y, targetX, targetY);
    var alignment = 0.6;       // Worker default; per-type table comes in Plan 2
    var jitterRate = 0.5;      // radians/sec of low-freq noise
    ant.heading += angleDelta(ant.heading, desiredHeading) * alignment * dt;
    ant.heading += (Math.random() * 2 - 1) * jitterRate * dt;
    ant.x += Math.cos(ant.heading) * ant.speed * dt;
    ant.y += Math.sin(ant.heading) * ant.speed * dt;
  }

  function updateAnts(dt) {
    for (var i = 0; i < game.ants.length; i++) {
      updateAnt(game.ants[i], dt);
    }
  }

  function checkCakeCollision() {
    var cakeR = 14;
    for (var i = 0; i < game.ants.length; i++) {
      var a = game.ants[i];
      if (a.dead) { continue; }
      var dx = a.x - game.cake.x;
      var dy = a.y - game.cake.y;
      if (dx * dx + dy * dy < cakeR * cakeR) {
        game.cake.slices -= 1;
        a.dead = true;
        a.deathTime = 0.5;       // mark for cleanup
        if (game.cake.slices <= 0) {
          game.cake.slices = 0;
          game.state = 'lost';
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

  // Bootstrap ants for early-task verification (removed in Task 13)
  for (var ai = 0; ai < 5; ai++) {
    spawnAnt('worker');
  }

  // === CANNONS ===
  var CANNON_DEFS = {
    howitzer: {
      name: 'Howitzer',
      cost: 50,
      damage: 5,
      fireRate: 0.6,         // shots per second
      range: 110,
      projectileSpeed: 200
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

  function placeCannon(defKey, x, y) {
    var def = CANNON_DEFS[defKey];
    if (!def) { return false; }
    if (game.gold < def.cost) { return false; }
    if (!canPlaceCannon(x, y)) { return false; }
    game.gold -= def.cost;
    game.cannons.push({
      defKey: defKey,
      x: x,
      y: y,
      heading: 0,
      cooldown: 0,
      target: null
    });
    return true;
  }

  function findTarget(cannon) {
    var def = CANNON_DEFS[cannon.defKey];
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
      var def = CANNON_DEFS[c.defKey];
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
    var def = CANNON_DEFS[cannon.defKey];
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

  // === INPUT ===
  var ui = {
    dragging: null,         // { defKey } when dragging from shop
    mouseX: 0,
    mouseY: 0
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
    var card = shopCardAt(p.x, p.y);
    if (card) {
      ui.dragging = { defKey: card };
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
      placeCannon(ui.dragging.defKey, p.x, p.y);
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
  function update(dt) {
    game.time += dt;
    if (game.state !== 'playing') { return; }
    updateAnts(dt);
    updateCannons(dt);
    updateProjectiles(dt);
    checkCakeCollision();
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

  // Expose game for debugging from devtools console
  window.__ab = game;
})();
</script>
