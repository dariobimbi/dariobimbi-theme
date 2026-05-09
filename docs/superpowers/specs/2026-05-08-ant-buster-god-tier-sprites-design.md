# Ant Buster — God-Tier Cannon Sprites Design Spec

**Spec date:** 2026-05-08
**Status:** Draft, awaiting user review.
**Theme version target:** 0.14.0 (current 0.13.0).
**Branch base:** `main` at commit `f233d5a` (Plan 7 merge).

## §1 — Goal & Non-Goals

### Goal

Give each of the 18 god-tier (T4) cannons in `CANNON_KINDS` a distinct 20×20 px sprite that:

- **Reads at a glance** at the actual rendered size (~28 CSS pixels on a 1080p display).
- **Outclasses its T3 ancestor** visually — gold-trim accents, more silhouette geometry, signature elements that hint at the cannon's mechanic.
- **Stays inside the existing visual language** (Heavy = slate-blue + brass, Quick = green/gold, Double = magenta/status-color), so the family hierarchy remains legible.
- **Plays well with the existing gold halo** drawn under every god-tier turret by `drawGodTierHalos`.

Today, all 18 god-tier cannons fall back to the base Howitzer sprite. Visual identity comes from the gold halo + 1.7× muzzle flash + screenshake. The halo and flash do most of the lifting, but the silhouette parity makes wave 15+ rosters feel less rewarding than they should.

### Non-Goals

- **No T1/T2/T3 sprite changes.** The existing procedural factories for the lower tiers stay as-is. Pivoting all 51 cannons would be a separate scope.
- **No animation.** Sprites are static; the existing turret-rotation transform handles aim direction.
- **No ant sprite work.** The brainstorm earlier mentioned both cannons and ants — ants are a separate spec if pursued.
- **No audio, particle systems, or other "polish" categories.** This spec is sprites only.
- **No new cannon kinds.** The 18 god-tier rows already exist in `CANNON_KINDS`.
- **No turret-base changes.** The shared `cannonBase` sprite (the round green pad under every turret) is unchanged.

---

## §2 — Technical Specifications

### 2.1 — Canvas / image dimensions

| Property | Value | Notes |
|---|---|---|
| Source dimensions | **20 × 20 px** | Matches every existing turret sprite produced by `makeCanvas(20, 20)`. |
| Pixel grid | **1:1 native pixel art** | No anti-aliasing, no sub-pixel positioning, no perspective. |
| Per-pixel format | **8-bit RGBA** | Full alpha channel for transparency. |
| In-engine display size | 40 × 40 canvas pixels | The game applies `ctx.scale(2, 2)` once at canvas init (line 177). A 20×20 sprite blits to 40 canvas-px. |
| Final on-screen CSS size | ~28 CSS pixels | Canvas is 1280×720 internal scaled to ~880×494 CSS. |

If sprites are AI-generated (Gemini, etc.), the recommended workflow is:

1. Generate at **80 × 80 px** with explicit "pixel-art, 4× supersampled" prompting. Higher resolution gives the model room to be detailed.
2. **Downscale to 20 × 20 with nearest-neighbor** (NOT bilinear, NOT bicubic). This preserves the pixel grid.
3. Manually clean up any anti-aliased edges or alpha-fringe artifacts the downscale introduced.

If sprites are hand-drawn (e.g., in Aseprite or Piskel), draw at **native 20×20 from the start**. Reference the existing T1/T2/T3 factory output for the exact pixel scale.

### 2.2 — Transparency

- **Background must be fully transparent** (alpha 0, RGB irrelevant).
- The cannon body fills only the silhouette pixels described in §4. Every other pixel is transparent.
- The shared `cannonBase` sprite renders BENEATH each turret — turrets must NOT draw their own base/circle. The breech of each turret should sit cleanly on top of the base without overlap or stray pixels.
- **No semi-transparent edge pixels.** Pixel art is binary alpha (0 or 255). If the source has anti-aliased edges from a downscale, threshold them: alpha ≥ 128 → keep at 255, alpha < 128 → drop to 0.

### 2.3 — Orientation

- **Barrel points UP at 12 o'clock** (toward y=0). The render code rotates each turret by `cannon.heading` at draw time, so the source asset is always in the canonical "north-facing" pose.
- **Pivot point is the canvas center (10, 10).** The barrel base / breech sits roughly at the bottom of the sprite (y=14–18); the muzzle tip sits at the top (y=0–4).
- **Symmetry:** every god-tier sprite must be left-right mirrorable around x=10 (the vertical center). A subtle 1-px asymmetric detail is fine — heavy asymmetry would make the rotated sprite look like it's leaning during aim.

### 2.4 — Color palette

#### Family anchors (kept consistent with existing T1-T3 sprites)

| Family | Anchor color | Secondary | Accent |
|---|---|---|---|
| Heavy | Slate blue (`#262b44`, `#181425`) | Brass / copper (`#aa7822`, `#806020`) | Burnished gold (`#d8a040`) for god-tier only |
| Quick | Olive green (`#5a7d4a`) / Teal (`#3a8c8c`) | Gold (`#ffd75e`) | Pearl white (`#e0e0e8`) for sniper-like rows |
| Double | Magenta (`#7d4a7d`, `#cc4080`) | Status color (per cannon) | Bright accent (e.g. `#fff080` lightning, `#a0d8ff` ice) |

#### Status-color overrides (Double family, status-coded)

| Mechanic | Primary status color | Highlight |
|---|---|---|
| Freeze | Ice blue `#3060a0` | `#a0d8ff` crystal facets, `#ffffff` sparkle |
| Poison | Bile green `#7acc20` | `#4a8c10` reservoir shadow, `#0a0a14` biohazard glyph |
| Fire / Burn | Deep orange `#ff6020` | `#ffcc20` flame, `#fff0b0` white-hot core |
| Stun / Shock | Electric yellow `#fff080` | `#ffd040` outer edge, `#80c0ff` shockwave ring |

#### God-tier signature trim

Every god-tier sprite carries a **subtle gold-trim accent** somewhere on its silhouette (a brass band, a gold rune, a sun-disc relief). This signals the T4 status across families and harmonizes with the gold halo pulsed by `drawGodTierHalos`. Suggested gold tones: `#aa7822` (deeper bronze), `#d8a040` (mid gold), `#ffd75e` (bright gold for Quick-family or sun-aligned cannons).

### 2.5 — Halo clearance zone

`drawGodTierHalos` renders a pulsing gold radial halo CENTERED on the turret base, extending to a radius of approximately 14 game-px (28 canvas-px). The halo blends additively at moderate alpha.

- **Bottom 4 px of every god-tier sprite (y=15–18)** should be tonally **dark or neutral** (slate blue, dark steel, gunmetal). Avoid bright colors here — they will fight the gold halo and read as visual noise.
- The Earthshaker's "orange seep at y=18" and Stormcaller's "storm-cloud puff" are deliberate halo-friendly accents (low alpha or limited area). When in doubt, keep y=15–18 dark.
- **No alpha-transparent ground-shadow** baked into the sprite. The halo is the cannon's "ground presence" — adding a sprite shadow would double up.

### 2.6 — File location & naming

Existing sprite system is procedural via `function makeCannonTurret*()` in `patterns/demo-ant-buster.php` (lines 411+). The Plan 4 comment at line 182 explicitly flags the long-term direction: "Plan 4 will swap these out for real PNGs loaded from disk."

This spec lets the implementer choose between two paths.

#### Path A: Procedural canvas factories (recommended for this polish pass)

- Add 18 new `function makeCannonTurret<Name>()` factories in `patterns/demo-ant-buster.php` after the existing factories (around line 700+).
- Register each in the `game.sprites.turrets[<kind>]` table at sprite-init time.
- Naming: `makeCannonTurretParticleCannon`, `makeCannonTurretAnnihilator`, etc. — PascalCase from the cannon's display name, no underscores.
- Pros: zero new asset loading, matches existing T1-T3 style, all changes in one PHP file, no PNG rasterization concerns.
- Cons: hand-coding 18 sprites in `ctx.fillRect` calls is tedious; fine-grained details are limited by what the procedural API can express.

#### Path B: PNG asset files (if AI-generated)

- New asset folder: `wp-content/themes/dariobimbi/assets/sprites/cannons/`
- Filenames: `<kind>.png` matching the `CANNON_KINDS` key exactly: `particle_cannon.png`, `annihilator.png`, `solar_lance.png`, `cluster_strike.png`, etc.
- All 18 PNGs are 20×20, transparent background, RGBA.
- Loading mechanism: a new `loadTurretSprites()` helper that creates `Image` objects, sets `src` to each path, and populates `game.sprites.turrets[<kind>]` once `onload` fires. Handle the race condition where the first render frame may run before all images load (fall back to `cannonTurret` for any not-yet-loaded sprite).
- Pros: AI-generated sprites can be richer than 20×20 procedural code; faster iteration cycle for visual tweaks.
- Cons: introduces async loading + asset folder; existing T1-T3 sprites stay procedural so the loader has to coexist with the procedural factory table.

#### Recommendation

**Path A** for this polish pass. The existing sprites are all procedural; introducing PNG loading is a tangential infrastructure change. If we later want to migrate the entire turret library to PNGs, that's a separate plan. For now, hand-code the 18 god-tier factories to match the existing aesthetic.

If the user prefers Path B (Gemini-generated), the loader code is small and well-contained. Both paths produce identical render-side output.

### 2.7 — Image-smoothing convention

The game sets `ctx.imageSmoothingEnabled = false` at canvas init (line 178). All sprite blits are nearest-neighbor scaled. **Sprites must be designed assuming hard pixel edges** — no implicit smoothing will rescue a soft-edged source.

---

## §3 — Family Palette Anchors (recap)

Sprite descriptions in §4 reference these anchors. New god-tier sprites must keep their family anchor visible (e.g., a slate-blue base on every Heavy god-tier) so the family hierarchy is readable across the roster.

- **Heavy:** `#262b44` body, `#181425` deep-shadow, `#aa7822`–`#d8a040` brass/gold accents.
- **Quick:** `#5a7d4a` olive (projectile rows) / `#3a8c8c` teal (multi-barrel rows) / `#ffd75e` gold (Solar Lance, Reaper trim).
- **Double:** `#7d4a7d` purple base + status-color signature (varies per cannon — see §2.4).

---

## §4 — The 18 God-Tier Sprite Descriptions

Each entry includes:

- **Mechanic recap** (so the visual choices are grounded in gameplay).
- **Silhouette** (overall shape language).
- **Color stops** (specific hex per region).
- **Pixel landmarks** (key x,y coordinates so the implementer can build the factory directly).
- **Vibe** (one-line gut-check of what the silhouette communicates).

### HEAVY family — six god-tier rows

The Heavy family's existing T1-T3 sprite vocabulary is **dark slate-blue (#262b44, #181425) bodies, chunky stubby barrels, wide breech bands.** God-tier Heavy cannons should feel **massive** — broader breech footprints, brass and burnished-copper accents, a hint of gold trim where the gold halo will pulse.

#### 4.1 — Particle Cannon

- **Mechanic:** line-pierce 999, armor-pierce, 60 dmg, fireRate 0.6, range 140.
- **Silhouette:** long narrow accelerator barrel, three plasma rings wrapped around it, glowing purple core in the breech.
- **Color stops:** Barrel `#d8e0f0` (silver-white). Rings `#3affff` core with `#80ffff` highlights. Core `#a050ff` with `#e0a0ff` 1-px highlight. Side rails `#5a5a6a`. Breech `#1a1a30` (slate, halo-safe).
- **Pixel landmarks:** Barrel x=9–10, y=2–16. Rings at y=5, y=9, y=13 (each spans x=8–11). Purple core at x=9–10, y=15–16. Side rails at x=4 and x=15, y=14–17. Breech base x=4–15, y=16–18.
- **Vibe:** laboratory-grade, surgical, the cleanest silhouette of the bunch.

#### 4.2 — Annihilator

- **Mechanic:** 120 dmg, armor-pierce, 60 px splash, screenshake 5, fireRate 0.3.
- **Silhouette:** the shortest, stubbiest barrel of any god-tier — a 6-px-wide siege mortar that looks like it shouldn't fit on the chassis.
- **Color stops:** Barrel `#3a3540`. Bore `#000000` with `#cc4020` flicker inside. Brass bands `#aa7822` with `#d8a040` highlights. Cast-iron breech `#2a2530` with `#806040` corner bolts.
- **Pixel landmarks:** Barrel x=7–13, y=3–11. Bore black void at x=8–12, y=0–2 with one orange pixel at x=10, y=1. Brass bands at y=5 (x=6–14, 1px) and y=9 (x=6–14, 1px). Breech x=3–17, y=11–18. Bolt heads 2×2 at corners (3,11), (15,11), (3,16), (15,16).
- **Vibe:** this thing kicks like a mule and the chassis knows it.

#### 4.3 — Saturation

- **Mechanic:** 3 staggered barrels, arc trajectory, 80 px splash, fireRate 0.5.
- **Silhouette:** three side-by-side mortar tubes with upturned flared muzzles, each angled slightly outward to suggest spread.
- **Color stops:** Tubes `#3a3548`. Flared muzzles `#cc6020` (scorched orange ring). Brass loading deck `#aa7822`. Loading hatches `#604020`. Breech `#3a3a44`. Bottom brass band `#d8a040`.
- **Pixel landmarks:** Left tube x=4–6, y=3–14, tilted 1 px left at top (x=3 at y=3). Center tube x=9–11, y=2–14, vertical. Right tube x=13–15, y=3–14, tilted 1 px right at top (x=16 at y=3). Each tube has a 2-px scorched orange ring at its top (y=0–2). Loading deck x=2–17, y=14–16 with three 2×2 hatches centered under each tube. Breech x=4–15, y=16–18.
- **Vibe:** artillery battery, the rolling-rain mortar.

#### 4.4 — Cluster Strike

- **Mechanic:** homing missile that splits at 50% distance into 3 children, 30 dmg, fireRate 0.5, range 220.
- **Silhouette:** single bullet-nosed cylindrical missile pod with a clean white nose-cone and a red targeting-laser dot.
- **Color stops:** Body `#3a4055`. Nose-cone `#e0e0e8`. Laser dot `#ff3030`. Fin-vents `#8090a0`. Launch tube outer `#806020`, inner `#1a1a25`.
- **Pixel landmarks:** Missile body x=7–12, y=4–14. Nose-cone x=8–11, y=2–4 (tapered). Apex laser dot at x=9–10, y=1. Fin-vents at x=5–7, y=8–10 and x=12–14, y=8–10. Launch tube outer ring x=4–15, y=14–18; inner dark x=5–14, y=15–17.
- **Vibe:** missile silo, the only god-tier with a discrete "warhead" silhouette rather than a barrel.

#### 4.5 — Vortex

- **Mechanic:** 2 barrels, returning/boomerang projectiles, pierce 2, fireRate 0.4.
- **Silhouette:** twin curved blades angled outward in a V, brass spinning hub at the breech where they meet.
- **Color stops:** Blade body `#3a4050` (gunmetal). Leading edge `#c0c8d0` (chrome). Brass hub `#aa7822`. Slit window `#0a0a14`. Gold rune `#d8a040`.
- **Pixel landmarks:** Left blade leans 15° left from x=8, y=12 (root) to x=4, y=3 (tip). Right blade mirrors to x=12, y=12 → x=15, y=3. Each blade is 2 px thick. Chrome leading edge is the outermost 1-px line of each blade. Hub x=7–13, y=12–16, brass body. Slit window 1×3 at center (x=10, y=13–15). Gold rune single pixel at x=10, y=14 inside the slit.
- **Vibe:** an eagle's wings or twin tomahawks — clearly a thrown-and-returns weapon.

#### 4.6 — Earthshaker

- **Mechanic:** 80 dmg, 120 px splash + stun, screenshake 8, fireRate 0.12.
- **Silhouette:** massive stamping piston barrel, very short and very wide, with the widest base footprint of any god-tier.
- **Color stops:** Piston `#2a2530`. Hammer-face studs `#604035` with `#806040` highlights. Breech base `#1a1820` (cast iron). Bolt-heads `#604035`. Ground-cracks `#000000`. Orange seep `#cc4020`.
- **Pixel landmarks:** Piston x=6–13, y=4–10. Hammer-face top at y=2–4 with 5 stud dots in a + pattern: center (10,3), top (10,1), bottom (10,4 — overlap), left (8,3), right (12,3). Breech base x=2–17, y=11–18. Bolt heads 2×2 at (2,11), (15,11), (2,16), (15,16). Ground-crack jags as scattered single pixels around the bottom edge (y=18). Orange seep 1-px line at y=18, x=5–14.
- **Vibe:** this turret literally pounds the dirt; the wider base implies it's anchored deep.

---

### QUICK family — six god-tier rows

The Quick family's existing T1-T3 sprites are **light olive-greens (#5a7d4a, #4a7d6a) and teals (#3a8c8c) with thin tall barrels.** God-tier Quick cannons should feel **light, lethal, and exotic** — desaturated emerald → gold-plated, with each one carrying a unique signature element (storm clouds, sun-glow, scope reticle, etc.).

#### 4.7 — Hailstorm

- **Mechanic:** fireRate 14, 0.5s spool-up, 4 dmg per shot.
- **Silhouette:** 6 barrel tubes in a 2×3 vertical grid (looks like a Gatling-pod compressed vertically), frost-blue spinning ring behind.
- **Color stops:** Barrel tubes `#a0a8b0`. Bore dots `#0a0a14`. Frost ring `#80c0ff` with `#ffffff` sparkle. Breech `#4a6d4a` (olive). Round-counter window `#aa7822`.
- **Pixel landmarks:** 6 barrels in 3 columns (x=4–6, 7–9, 10–12) × 2 rows (top y=2–10, bottom y=8–14, but bottom row sits behind so visible portion is y=10–14). Each barrel-bore is a single black pixel at the top of each tube (y=2 for top row). Frost ring x=2–13, y=9–11 (1-px thick). Breech x=3–14, y=14–18. Counter-window 2×3 at x=12–13, y=15–17.
- **Vibe:** micro-Gatling, hailstones literally raining out.

#### 4.8 — Phoenix

- **Mechanic:** coneDot, 70° spread, 12 tickDamage, burn-spreads-on-expire.
- **Silhouette:** flared cone-emitter horn (NOT a barrel) — wide at the muzzle, narrow at the breech.
- **Color stops:** Mouth deep orange `#ff6020` fading inward through `#ffcc20` to a `#fff0b0` white-hot core. Cone walls `#cc4020` (darker orange). Stem `#aa7822` (gold-bronze). Flame-feathers `#ff9020`. Breech `#3a2520` with `#cc4020` ember glow.
- **Pixel landmarks:** Mouth y=0–4, x=3–16, with concentric color bands: outer ring (x=3–4, 15–16) deep orange; mid ring (x=5–6, 13–14) bright orange; core (x=7–12) yellow with white-hot 2-px center at x=9–10, y=2. Cone walls slope inward: y=5 at x=4–15, y=8 at x=6–13. Stem x=8–11, y=10–14. Flame-feather curls at x=2, y=5–7 (3-px curl) and x=17, y=5–7 mirrored. Breech x=4–15, y=14–18 with ember line at y=18 (x=6–13, `#cc4020`).
- **Vibe:** a dragon's breath, the only Quick with NO clear "barrel" — it's pure flame mouth.

#### 4.9 — Reaper

- **Mechanic:** instakill Worker/Scout, +100% vs full HP, 60 dmg, range 380.
- **Silhouette:** long thin scythe-curved barrel (NOT straight), bone-white with a rusted-iron cutting edge, dark hood at the muzzle with a single glowing red eye.
- **Color stops:** Barrel `#e8e0d0` (bone-white). Cutting edge `#604035` (rusted iron). Hood `#1a1a20`. Red eye `#ff2020` with `#ff8080` highlight. Breech `#0a0a14` with `#602080` purple aura ring.
- **Pixel landmarks:** Barrel curves from breech (x=10, y=14) to muzzle (x=7, y=2): pixels at x=10 y=14, x=10 y=12, x=9 y=10, x=9 y=8, x=8 y=6, x=8 y=4, x=7 y=3, x=7 y=2 (8 pixels forming the curve, 1 px wide). Cutting-edge line on the forward (left) side: parallel curve 1 px to the left of the barrel. Hood at x=5–9, y=0–3. Red eye at x=7, y=2 (single pixel) with `#ff8080` highlight at x=8, y=2. Breech base x=8–13, y=14–18 (dark) with `#602080` 1-px outer rim.
- **Vibe:** literally the Grim Reaper's silhouette compressed into a turret — the curve is the signature.

#### 4.10 — Solar Lance

- **Mechanic:** beam, pierce 2, gold beam color (`#ffd75e`).
- **Silhouette:** single ornate gold-plated barrel with a 3-pointed sun-flare muzzle and a horizontal gold ring mid-shaft.
- **Color stops:** Barrel `#ffd75e` (polished gold). Underside `#aa7822` (deeper bronze). Sun-flare spikes `#fff0a0` with `#ffd75e` fade. Mid-ring `#ffea80`. Breech `#3a4d3a` (dark olive). Sun-disc relief `#ffd75e` with `#aa7822` darker center.
- **Pixel landmarks:** Barrel x=8–11, y=2–16. Underside line at x=11, y=2–16 (1 px deeper bronze). Sun-flare: center spike at x=9, y=0 single pixel `#fff0a0`; left spike at x=7, y=1; right spike at x=12, y=1. Mid-ring x=7–12, y=8 (1 px). Breech x=5–14, y=14–18. Sun-disc relief 3×3 at x=8–10, y=15–17 with darker bronze at x=9, y=16.
- **Vibe:** a solar staff, the lord-of-the-rings sunlight weapon — pure regal gold.

#### 4.11 — Hawkeye

- **Mechanic:** range 600 (largest in the game), 80 dmg, 3× vs Queen.
- **Silhouette:** rifle-scope silhouette — thinnest barrel in the roster + perpendicular scope cylinder + wood-grain stock.
- **Color stops:** Barrel `#4a5560` (gunmetal). Suppressor bulge `#2a3540`. Scope body `#1a1820` with `#aa7822` brass edge rings. Wood stock `#604030` with `#805040` grain lines.
- **Pixel landmarks:** Barrel x=9–10, y=2–14 (only 2 px wide). Suppressor bulge x=8–11, y=0–2. Scope cylinder x=4–15, y=11–14 (perpendicular to barrel). Brass edge rings: 1-px columns at x=4 and x=15 within the scope. Stock x=6–13, y=14–18 with grain lines as 1-px `#805040` pixels at y=15 and y=17 (broken pattern).
- **Vibe:** predator drone meets sniper rifle. The scope is the visual hook — no other god-tier has cross-axis geometry.

#### 4.12 — Stormcaller

- **Mechanic:** Tesla arc, 6 jumps, no falloff, 18 dmg.
- **Silhouette:** tall vertical antenna spike with a forked Y-tip + two crackling violet electric coils + squat Tesla-coil base with green grounding rods.
- **Color stops:** Antenna `#c0c8d0`. Y-tip same. Electric coils `#c080ff` with `#ffffff` sparkle pixels. Tesla base `#1a1820`. Grounding rods `#3acc3a`. Storm-cloud puff `#5a5a8a`.
- **Pixel landmarks:** Antenna spike x=9–10, y=0–10 (vertical rod). Y-tip pixels at (8,1), (9,0), (10,0), (11,1). Lower coil at y=4: pixels at (7,4), (8,4), (11,4), (12,4); sparkle at (10,4) `#ffffff`. Upper coil at y=8: same x-pattern (7,8), (8,8), (11,8), (12,8); sparkle at (9,8) `#ffffff`. Tesla base x=4–15, y=11–18. Grounding rods 1-px green at corners (4,17), (15,17), (4,12), (15,12). Storm-cloud puff scattered `#5a5a8a` pixels at y=15–16 (3-4 pixels, e.g. (6,15), (8,16), (11,15), (13,16)).
- **Vibe:** Tesla coil, all-vertical, all-energy — electric-purple is the family-wide tell that this cannon zaps.

---

### DOUBLE family — six god-tier rows

The Double family's existing T1-T3 sprites are **purples (#7d4a7d), magentas, and status-coded colors (yellow horn for Sonic, green for Plague, ice-blue for Cryo).** God-tier Double cannons each carry a **distinct status icon embedded in the silhouette** — frost crystals, biohazard symbol, lightning chevron — so their mechanic reads at a glance.

#### 4.13 — Scattergun

- **Mechanic:** 8 pellets, 70° cone, line-pierce 1, 3 dmg per pellet.
- **Silhouette:** wide flared blunderbuss bell (widest muzzle of any god-tier) + short stocky neck + walnut-stock breech.
- **Color stops:** Bell `#a07535` (burnished copper). Bell trim `#604020` (1-px rivet line). Pellet bores `#0a0a14`. Neck `#604530`. Walnut stock `#5a3825` with `#7a5535` grain.
- **Pixel landmarks:** Bell-mouth y=0–5, x=2–17 (flared trapezoid: y=0 spans x=2–17, y=5 spans x=7–12). Trim 1-px line along the bell's leading edge at y=0. Pellet bores: 4 dots top row at y=1 (x=4, 7, 10, 13); 4 dots bottom row at y=3 (x=5, 8, 11, 14). Neck x=8–11, y=5–10. Walnut stock x=5–14, y=11–18 with 1-px lighter grain pixels at (6,13), (9,15), (12,14).
- **Vibe:** pirate's blunderbuss, sawed-off shotgun — wide-mouthed and obviously meant for cone fire.

#### 4.14 — Phalanx

- **Mechanic:** 3 barrels, fireRate 18 (highest in the game), no jitter, 2 dmg.
- **Silhouette:** three identical magenta barrels in tight horizontal alignment + two horizontal cooling fins + brass sync-gear breech.
- **Color stops:** Barrels `#cc4080`. Tips `#802050`. Highlights `#ff80c0`. Cooling fins `#5a2540`. Breech `#2a1525` (gunmetal magenta). Sync-gear `#aa7822` with darker `#604020` center.
- **Pixel landmarks:** Left barrel x=4–6, y=2–14. Center barrel x=8–10, y=2–14. Right barrel x=12–14, y=2–14. Each barrel has dark tip at y=0–2 and 1-px highlight stripe on the left edge at y=4–12. Cooling fin 1 at y=6 (x=3–15, 1 px). Cooling fin 2 at y=10 (x=3–15, 1 px). Breech x=3–15, y=14–18. Sync-gear 3×3 at x=8–10, y=15–17 with darker center pixel at (9, 16).
- **Vibe:** zero-tolerance precision triple — it's a Triple Threat that grew up and stopped joking around.

#### 4.15 — Blizzard

- **Mechanic:** aura cannon (radius 150, no findTarget), freeze + slow + persistent damage.
- **Silhouette:** NO barrel. Central tall ice-spire crystal + four shorter ice fragments around the base (snowflake arrangement) + frosted base with rime crawling up.
- **Color stops:** Spire `#a0d8ff` body. Sparkle `#ffffff`. Right-edge shadow `#5090c0`. Ice fragments `#c8e8ff`. Base `#3a4055` (dark frosted). Rime `#a0d8ff` (scattered single pixels). Snowflake glyph `#ffffff`.
- **Pixel landmarks:** Spire x=8–11, y=2–14, hexagonal cross-section (4 px middle, narrowing to 2 px at top y=2 and base y=14). Sparkle pixels at (9, 4) and (10, 10) `#ffffff`. Right-edge shadow 1-px line at x=11, y=4–12. Four ice fragments: at x=4–6 y=10–13 (3-px triangle); at x=14–16 y=10–13 (mirrored); plus implied front/back via puff pixels at (10, 9) and (10, 14). Base x=4–15, y=14–18. Rime crawls: scattered `#a0d8ff` pixels at (5, 14), (15, 14), (4, 16), (15, 17). Snowflake glyph 5-pixel asterisk at center (9, 16): pixels at (9,15), (8,16), (10,16), (9,17), (9,16) — the asterisk is `#ffffff`.
- **Vibe:** the only god-tier with vertical symmetry around a non-barrel center — a literal ice obelisk.

#### 4.16 — Absolute Zero

- **Mechanic:** freeze + 1.5× damage amp, status splash, 6 dmg.
- **Silhouette:** single thick crystal-edged barrel with three ice-crystal facets + frozen-fang muzzle + ice-rune breech ring.
- **Color stops:** Barrel `#3060a0` (deep arctic blue, darker than Cryo's #80c0ff). Crystal facets `#80c0ff`. Muzzle fang `#a0d8ff` with `#ffffff` highlight. Breech `#1a3050`. Ice runes `#ffffff`.
- **Pixel landmarks:** Barrel x=7–12, y=2–14. Left facet 3-px triangle at x=4–6, y=5 (pixels (6,5), (5,5), (5,6), (4,5)). Right facet mirrored at x=13–15, y=5. Muzzle fang at x=8–11, y=0–2 (`#a0d8ff` body, single `#ffffff` pixel at (9, 1)). Breech x=4–15, y=12–17. Ice runes 4 single-pixel crosshatch marks at cardinal points: (9, 13) top, (4, 14) left, (15, 14) right, (9, 16) bottom.
- **Vibe:** a frozen-over Cryo, the temperature dropped another 50 degrees.

#### 4.17 — Biohazard

- **Mechanic:** poison spreads on death, 1.25× damage amp, 4 dmg + 30 dot.
- **Silhouette:** pair of bulbous chemical reservoirs flanking a thin central injection nozzle + biohazard trefoil glyph.
- **Color stops:** Reservoirs `#7acc20` (sickly bile-green) with `#4a8c10` shadow. Liquid line `#5a8c10`. Nozzle `#8a90a0` (dull stainless). Venom drip `#7acc20`. Biohazard glyph `#0a0a14`. Base `#3a2a20` (rust patina).
- **Pixel landmarks:** Left reservoir x=2–7, y=8–17 (rounded rectangle, with darker shadow column at x=2). Right reservoir x=12–17, y=8–17 (mirrored). Liquid line 1-px horizontal at y=11 inside each reservoir (`#5a8c10`). Nozzle x=9–10, y=2–10. Single venom-drip pixel at x=9, y=0 `#7acc20`. Biohazard trefoil at y=6: three 2×2 black squares clustered around x=9–10. Base x=4–15, y=14–18 with rust patina (dark with 1-px lighter `#5a3a30` pixels scattered).
- **Vibe:** lab horror — the trefoil is the unmistakable signature.

#### 4.18 — Tempest

- **Mechanic:** knockback + stun, splash status, 10 dmg.
- **Silhouette:** stylized lightning-bolt-shaped emitter (Z-bolt zigzag, NOT a straight barrel) + two concentric shock-wave rings on the ground + lightning-glyph breech.
- **Color stops:** Bolt core `#fff080` (electric yellow-white). Bolt outer edge `#ffd040`. Shock rings `#80c0ff`. Breech `#3a2a40` (stormy purple-grey). Lightning glyph `#fff080`.
- **Pixel landmarks:** Bolt segment 1 (top): x=8–10, y=2–6 with 1-px left lean (e.g., x=10 at y=2, x=9 at y=3, x=8 at y=5). Bolt kink (middle): x=10–13, y=6–9 right diagonal (x=10 at y=6, x=11 at y=7, x=12 at y=8, x=13 at y=9). Bolt segment 3 (bottom): x=13–15, y=9–14 right vertical (x=13 at y=9–11, x=14 at y=12–14). Outer-edge `#ffd040` 1-px around each segment. Shock-ring 1 at y=15, x=4–15 (1-px thin). Shock-ring 2 at y=17, x=2–17 (1-px wider). Breech x=6–13, y=15–18. Lightning glyph 5-pixel zig at x=8–11, y=16–17 (`#fff080`).
- **Vibe:** the air-element Reaper — a literal lightning bolt has replaced the barrel; the shockwave rings sell the AoE knockback.

---

## §5 — Generation Workflow

### 5.1 — Path A (procedural canvas factory) workflow

1. Open `patterns/demo-ant-buster.php`, locate the existing turret factories (lines 411–700ish).
2. For each god-tier cannon, copy the template pattern from a similar T3 factory (e.g., Vulcan for multi-barrel, Cryo for ice, Toxin for poison).
3. Replace the `ctx.fillStyle` + `ctx.fillRect` calls with the §4 color stops + pixel landmarks.
4. Register the new sprite in the `game.sprites.turrets[<kind>]` table.
5. Use the existing inline-script conventions: `var` only, no `&&`/`||`, no arrow functions.
6. Verify in the browser by placing the cannon and confirming the sprite reads cleanly at 28 CSS px and rotates correctly.

### 5.2 — Path B (Gemini-generated PNG) workflow

1. For each cannon, use a prompt structured as:
   ```
   Pixel art icon, 80×80 px, 4× supersampled (will be downscaled to 20×20), transparent background.
   Top-down turret view, barrel pointing up at 12 o'clock.
   Style: hand-drawn pixel art, hard pixel edges, no anti-aliasing, no perspective.
   Subject: <Mechanic>. Silhouette: <§4 silhouette description>.
   Colors: <§4 color stops, hex codes>.
   Pixel landmarks: <§4 pixel landmarks>.
   Vibe: <§4 vibe>.
   Constraint: bottom 4 rows (y=15–19) must be tonally dark or neutral to leave room for an underlying gold halo at render time.
   No shadows. No background. Pure isolated turret silhouette on transparent.
   ```
2. Generate. If the output has anti-aliased edges or is rendered at higher fidelity than 20×20, downscale with nearest-neighbor and threshold the alpha.
3. Save to `wp-content/themes/dariobimbi/assets/sprites/cannons/<kind>.png`.
4. Wire the new loader into the sprite-init code.

### 5.3 — Iteration tip

For both paths: place ONE god-tier cannon in the live demo first (use `?debug=1` and inject via `window.__ab.cannons`), screenshot it at 28 CSS px scale, and confirm it reads. Iterate until satisfying, then move to the next cannon. Do NOT batch-generate all 18 before checking any.

---

## §6 — Acceptance Criteria

QA walk via Playwright against `http://localhost/dariobimbi.com/demos/ant-buster/?debug=1`. Each item is a binary pass/fail.

1. **All 18 god-tier sprites load without console errors.** `window.__ab.sprites.turrets[<kind>]` is a non-null canvas/image for each of the 18 keys.
2. **Each sprite's effective dimensions are 20×20 px.** Sprite source `width === 20` and `height === 20`.
3. **Background is fully transparent.** Sample any corner pixel: alpha === 0.
4. **Bottom 4 rows are halo-friendly.** For each sprite, sample 4 pixels at y=16, 17, 18, 19. None of them should be at full saturation in a non-anchor color (no bright neon at the base).
5. **No more Howitzer fallback.** Place each god-tier cannon in turn and visually confirm the turret silhouette is unique to its kind.
6. **Family hierarchy reads.** Place the entire Heavy family side-by-side (T1 Howitzer → T2 Siege/Mortar/Discus → T3 Patriot/Demolisher/Hellfire → T4 Particle Cannon/Annihilator/Saturation/Cluster Strike/Vortex/Earthshaker) and confirm the slate-blue + brass anchor is visible across all tiers. Repeat for Quick (olive/gold) and Double (magenta + status-color).
7. **Rotation works without artifacts.** Aim each turret in 8 cardinal/diagonal directions; sprite rotates cleanly with no shearing or sub-pixel ghosting (this is automatic if §2.3 symmetry is honored, but worth confirming).
8. **Gold halo plays well underneath.** Wave 15+ unlock animation and the per-frame halo render don't visually clash with any of the 18 new sprites.
9. **PHP lint passes.** `php -l patterns/demo-ant-buster.php` returns 0.
10. **Hygiene check passes.** `curl ... | grep -c "&#038;&#038;"` returns 0.
11. **Theme version bumped to 0.14.0.**

---

## §7 — Out-of-Scope / Plan 9+ Candidates

- **T1/T2/T3 sprite remasters.** Out of scope for this pass; the existing sprites work fine.
- **Ant sprites.** A separate spec if the user wants them. Workers, Soldiers, Tank, King, Royal Guard, Carrier, Stalker, etc. all have distinct procedural sprites today; god-tier-style polish for ants is a separate scope.
- **Animation frames.** Walk cycles, recoil animation, idle animation. None today; would require new asset-loading architecture.
- **Sprite atlas / texture packing.** With 18 PNGs (Path B), one-PNG-per-cannon is fine. A texture atlas would be an optimization, not a feature.
- **Color-blind alternate palettes.** Out of scope.
- **Migrate ALL 51 cannon sprites to PNG.** Out of scope. If this polish pass succeeds and PNG path proves preferable, it logs naturally as a follow-up.

---

## §8 — Notes for Plan Author

The implementation plan should bite-size as follows:

- **Task 1: Pick path (A procedural or B PNG).** If A, scaffold the 18 empty `makeCannonTurret*()` factories returning the cannonBase as a placeholder. If B, scaffold the loader + PNG file paths.
- **Tasks 2-7: Heavy family — Particle Cannon, Annihilator, Saturation, Cluster Strike, Vortex, Earthshaker.** One cannon per task; verify visually before moving on.
- **Tasks 8-13: Quick family — Hailstorm, Phoenix, Reaper, Solar Lance, Hawkeye, Stormcaller.** One cannon per task.
- **Tasks 14-19: Double family — Scattergun, Phalanx, Blizzard, Absolute Zero, Biohazard, Tempest.** One cannon per task.
- **Task 20: Hygiene + version bump (0.13.0 → 0.14.0) + acceptance walk.**
- **Task 21: Status doc + merge to main.** Following Plan 5/6/7 precedent.

That's a 21-task plan, biggest yet. Could be split into Plan 8a (Heavy + Quick = 14 tasks) and Plan 8b (Double + hygiene + status = 8 tasks) if the user prefers smaller batches.

Each per-cannon task should ship with a Playwright verify probe: place the cannon, screenshot, and confirm the sprite renders.
