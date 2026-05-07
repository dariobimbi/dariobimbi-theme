# Ant Buster — Design Spec

**Status:** Design complete. All sections locked pending final user review. Ready to hand off to implementation planning.
**Date opened:** 2026-05-06
**Author:** Dario, with Claude assist

---

## 1. Project overview

A faithful tribute to the 2007 Flash tower defense game *Ant Buster* (rstein.org), built as a single-file demo on dariobimbi.com under `/demos/ant-buster/`. The player places cannons around a picnic blanket to defend a cake from waves of ants. Ants pick up cake crumbs and try to flee back to their anthill; cannons must stop them on the way in *and* the way out.

The pitch:

> Use your vast array of cannons to keep the ants from stealing all your cake and ruining your day in this tower defense game. It's often said that when faced with a problem, it's important to bring the right tool for the job. So if you're ever at a picnic, and you find yourself having to fend off an endless swarm of ants, then hopefully you brought along your ant-sized collection of cannons. Then, it's just a matter of preventing the ants from reaching the cake and feasting on it before you can.

**Categories / tags:** Strategy, Tower Defense, Gross, Random.

---

## 2. Scope and approach

| Decision | Choice | Notes |
|---|---|---|
| Scope | Faithful recreation (multi-week) | Full upgrade tree, multiple ant types, real game-feel work |
| Tech | Vanilla JS, canvas 2D, no build step | Same shape as `patterns/demo-pokedex.php` |
| File layout | Single-file pattern | Pattern PHP holds markup + inline `<style>` + inline `<script>` |
| URL | `/demos/ant-buster/` | Child of the existing `demos` parent page |
| Art direction | Pixel art, made by Dario | 16×16 ants, 32×32 cannons, 480×270 native canvas scaled 2–3× via `image-rendering: pixelated` |
| Palette | [Endesga 32](https://lospec.com/palette-list/endesga-32) | Role assignments in §9 |
| Sprite tool | Aseprite recommended (paid), Piskel or LibreSprite as free alternatives | |
| IP framing | "Tribute to Ant Buster" (rstein.org, 2007), not a clone | Mechanics inspired by the original; not bound to verbatim fidelity |

---

## 3. Cannon system

Three base cannon families. Each base upgrades into one of three mid-tier cannons. Each mid-tier upgrades into one of two final-tier cannons, and each final upgrades into one **god-tier (T3)** apex cannon. **48 cannons total** (3 base + 9 mid + 18 final + 18 god), arranged as a 3-3-2-1 tree. Numbering: `1` → `1.1` → `1.11` → `1.111`.

### Base economy assumptions

These set the scale for the numbers in the tree below. Tunable later.

- Ant HP scale: Worker **10**, Scout **6**, Soldier **40**, Carrier **15**, Tank **150**, Splitter **25** (+ 2 minis at 8 each), Phase **20**, Bomber **30**, Queen **800** (full strength), King **3000**. Full ant table in §4.1.
- Starting gold: **150**. Ants drop **2–10 gold** by type; Queen drops 50; King drops 200.
- Cake has **8 slices** = **8 lives**.
- Sells refund **70%** of total gold spent on a tower.

**Cost curve (flatter through finals, expensive at god tier):**

- Build a base: 50–75 gold.
- T1 upgrade: +50–70.
- T2 (final) upgrade: +80–110. _A player can reach a final-tier cannon for ~180–235 total — much faster than before._
- T3 (god) upgrade: +250–300. _The wallet hit lives at the apex, which is where it should be: god tier is a real commitment._

### 3.1 Family — Heavy (Ordnance)

Single-target damage, splash, slow fire. Worst against a swarm, best against soldiers and the Queen. Visually: thick barrels, big muzzle flash, slow heavy projectiles.

| ID | Name | Damage | Fire rate | Range | Special | Upgrade cost |
|---|---|---|---|---|---|---|
| **1** | **Howitzer** | 5 | 0.6/s | 110 | — | 50 (build) |
| 1.1 | Siege | 12 | 0.6/s | 110 | — | +50 |
| 1.11 | **Railgun** | 25 | 0.6/s | 120 | — | +90 |
| **1.111** | **Particle Cannon** *(god)* | 60 | 0.6/s | 140 | pierces every ant in a line, no falloff | +250 |
| 1.12 | **Demolisher** | 60 | 0.25/s | 110 | armor-pierce (full damage vs Soldier) | +110 |
| **1.121** | **Annihilator** *(god)* | 120 | 0.3/s | 130 | armor-pierce + splash radius 60 (splash dmg 30) | +280 |
| 1.2 | Mortar | 8 | 0.5/s | 130 | splash radius 40, splash 4 | +60 |
| 1.21 | **Hellfire** | 15 | 0.4/s | 180 | arcing shell, splash radius 60, splash 10 | +90 |
| **1.211** | **Saturation** *(god)* | 25 + 20 splash | 0.5/s | 200 | fires 3 shells per volley, splash radius 80 | +260 |
| 1.22 | **Patriot** | 20 | 0.5/s | 200 | homing, splash radius 30, splash 8 | +110 |
| **1.221** | **Cluster Strike** *(god)* | 30 + 12 splash | 0.5/s | 220 | homing missile splits into 3 mid-flight, each homes | +280 |
| 1.3 | Discus | 4 | 0.4/s | 150 | hits twice (out + return) | +60 |
| 1.31 | **Ricochet** | 8 | 0.4/s | 180 | hits 3 times (out, pierce, return) | +90 |
| **1.311** | **Vortex** *(god)* | 12 | 0.4/s | 200 | fires 2 simultaneous discs, each hits 4 times | +250 |
| 1.32 | **Shockwave** | 50 | 0.15/s | 130 | massive splash radius 80, splash 30, brief screenshake | +110 |
| **1.321** | **Earthshaker** *(god)* | 80 + 50 splash | 0.12/s | 140 | splash radius 120, brief stun on all ants in radius | +300 |

### 3.2 Family — Quick (Precision)

Precision, range, fast fire. Lightweight, fast trigger, long sight lines. High fire rate forgives misses against erratic ants. Late-game variants are the sniper backbone. Visually: thin barrels, scopes, tracer rounds, beams.

| ID | Name | Damage | Fire rate | Range | Special | Upgrade cost |
|---|---|---|---|---|---|---|
| **2** | **Repeater** | 1 | 4/s | 130 | — | 50 (build) |
| 2.1 | Auto-Cannon | 2 | 6/s | 130 | — | +50 |
| 2.11 | **Vulcan** | 3 | 9/s | 140 | gatling spool-up: full rate after 0.5s of firing | +80 |
| **2.111** | **Hailstorm** *(god)* | 4 | 14/s | 160 | 100% accuracy on moving targets, no spread | +250 |
| 2.12 | **Plasma Torch** | 1/tick | 10/s | 70 (cone 60°) | continuous, ignites for +1/s for 2s | +90 |
| **2.121** | **Phoenix** *(god)* | 2/tick | 12/s | 80 (cone 70°) | ignites for +3/s for 3s, burn spreads to ants within 30px on burn-end | +260 |
| 2.2 | Marksman | 8 | 0.6/s | 250 | bonus damage vs full-HP targets | +70 |
| 2.21 | **Sharpshooter** | 25 | 0.5/s | 350 | +50% dmg vs full-HP, ignores 50% slow penalty | +100 |
| **2.211** | **Reaper** *(god)* | 60 | 0.5/s | 380 | +100% vs full-HP, instakills Workers and Scouts | +280 |
| 2.22 | **Lance** | 4/tick | continuous beam | 250 | no projectile travel, never misses | +110 |
| **2.221** | **Solar Lance** *(god)* | 8/tick | continuous beam | 280 | beam pierces 2 ants in a line | +290 |
| 2.3 | Spotter | 3 | 2/s | 200 | reveals Carriers (highlights ants holding crumbs) | +60 |
| 2.31 | **Phantom** | 40 | 0.3/s | 450 | bonus damage vs Queen +100% | +110 |
| **2.311** | **Hawkeye** *(god)* | 80 | 0.3/s | 600 | +200% vs Queen, no minimum range, can shoot through obstacles | +300 |
| 2.32 | **Tesla** | 10 | 1/s | 200 | arcs to 3 nearest ants within 60px each, dmg falls 50% per jump | +100 |
| **2.321** | **Stormcaller** *(god)* | 18 | 1/s | 240 | arcs to 6 ants within 80px each, no damage falloff | +280 |

### 3.3 Family — Double (Swarm Control)

Multi-shot, status effects, support. The swarm-control family. Spread fire, slows, poisons, knockback. Weakest in pure damage but strongest against waves and Carriers. Visually: side-by-side barrels, status-tinted projectiles (blue ice, green poison, white pulse).

| ID | Name | Damage | Fire rate | Range | Special | Upgrade cost |
|---|---|---|---|---|---|---|
| **3** | **Twin Gun** | 2 (×2 barrels) | 1/s | 100 | fires 2 shots per volley | 75 (build) |
| 3.1 | Triple Threat | 2 (×3 barrels) | 1/s | 110 | fires 3 shots per volley, slight spread | +70 |
| 3.11 | **Buckshot** | 2 (×5 pellets) | 0.8/s | 90 | shotgun cone 50°, all pellets land in arc | +90 |
| **3.111** | **Scattergun** *(god)* | 3 (×8 pellets) | 0.7/s | 100 | wide cone 70°, pierces 1 ant per pellet | +260 |
| 3.12 | **Minigun** | 1 | 14/s | 120 | single barrel, hose of fire, slight spray inaccuracy | +110 |
| **3.121** | **Phalanx** *(god)* | 2 | 18/s | 140 | 3 alternating barrels, no spray, slight tracking lead | +280 |
| 3.2 | Frost | 1 | 1.5/s | 100 | applies 30% slow for 2s | +75 |
| 3.21 | **Glacier** | 1/tick | continuous | 130 | applies 50% slow while beam holds, 1s lingering | +90 |
| **3.211** | **Blizzard** *(god)* | 1/tick AoE | continuous (radial) | 150 | always-on aura, 50% slow to all ants in radius, freezes a random one for 2s every 5s | +260 |
| 3.22 | **Cryo** | 3 | 1/s | 110 | freezes target solid for 0.8s (0 movement, takes +50% damage) | +110 |
| **3.221** | **Absolute Zero** *(god)* | 6 | 1/s | 130 | freezes target 1.5s + AoE freeze radius 40 on impact for 0.5s | +280 |
| 3.3 | Toxin | 1 | 1/s | 100 | applies 5 dmg over 5s (DoT, refreshes, doesn't stack) | +75 |
| 3.31 | **Plague** | 2 | 1/s | 110 | 15 dmg over 6s, spreads to ants within 30px on target's death | +90 |
| **3.311** | **Biohazard** *(god)* | 4 | 1/s | 130 | 30 dmg over 8s, spreads on death radius 50, infected ants take +25% dmg from all sources | +260 |
| 3.32 | **Sonic Pulse** | 5 | 0.8/s | 100 | knocks ants back 30px, briefly stuns 0.4s | +90 |
| **3.321** | **Tempest** *(god)* | 10 | 0.7/s | 110 | knockback ring 50px around target, stun 0.8s on all in ring | +260 |

### 3.4 Design principles baked into the tree

1. **Each family has a "pure power" path, an "exotic" path, and a "utility" path.** Keeps the three families distinct but each one self-sufficient if a player commits to it.
2. **Every final-tier cannon has a reason to exist** — none are just "+X% better" versions of their sibling. Forces a real player decision at the final upgrade.
3. **God tier is the dramatic apex, not just bigger numbers.** Each god-tier cannon adds a *new mechanic* (pierce, multi-projectile, freeze AoE, infection spread) on top of stat bumps. The visual treatment should match — bigger muzzle flash, screen shake on Earthshaker / Annihilator, a continuous aura ring on Blizzard.
4. **The Carrier ant has a hard counter (Spotter → Phantom → Hawkeye).** Makes the "stop them on the way out" mechanic tractable instead of frustrating.
5. **Status effects are concentrated in Family 3.** A balanced lategame setup wants towers from at least two families, which is a more interesting strategic puzzle than "build 5 of the best cannon."
6. **Cost is front-loaded into god tier.** A player can reach a final-tier cannon by mid-game and feel rewarded; god tier is a stretch goal that needs real wave income to afford. This keeps the early game varied (you'll cycle through builds) and the late game decisive.
7. **Sells refund 70% of total spent.** Standard TD convention; gives players room to commit to a path without permanent regret.

### 3.5 God-tier gating

**Decision:** God-tier upgrades are **wave-gated**. The upgrade button on a T2 cannon shows the god-tier option but is locked (greyed-out, with a "Unlocks at wave 15" tooltip) until the player completes wave 15. This pairs naturally with the boss-wave structure (see §4) — the wave 15 Queen is both a difficulty spike *and* the dramatic moment when god tier becomes available.

### 3.6 Cannon-tree decisions log

- **Names locked.** Modernization pass complete (Howitzer / Repeater / Twin Gun replace Heavy / Quick / Double; full table in §3.1–3.3). User signed off; no further changes.
- **God-tier gating: gold + wave-15 unlock only.** No "max one god-tier" cap — keeping the rule simple.

---

## 4. Ants and wave structure

### 4.1 Ant roster

10 enemy types: 8 regulars + Queen mini-boss + King final boss. Each type maps to a specific cannon counter, which is what justifies the 48-cannon variety.

| Ant | HP | Speed (px/s) | Gold | Special | Counter |
|---|---|---|---|---|---|
| **Worker** | 10 | 30 | 2 | baseline ant | anything |
| **Scout** | 6 | 60 | 3 | small, fast, low HP | high-fire-rate (Repeater line, Buckshot) |
| **Soldier** | 40 | 20 | 5 | 50% slow resistance, armored | armor-pierce (Demolisher, Annihilator) |
| **Carrier** | 15 | 30 / 45 fleeing | 4 (8 if killed mid-flee) | picks up cake crumbs, flees back to anthill | Spotter → Phantom → Hawkeye |
| **Tank** | 150 | 12 | 10 | takes 50% damage from non-pierce attacks | armor-pierce (Particle Cannon, Annihilator) |
| **Splitter** | 25 | 25 | 4 | on death, spawns 2 mini-Splitters (8 HP each, 35 px/s, drop 1 gold each) | AoE / DoT (Plague, Mortar, Hellfire) so the splits also die |
| **Phase ant** | 20 | 35 | 6 | becomes intangible (untargetable) for 1s every 3s | continuous-beam (Lance, Glacier) catches it on re-entry; Spotter highlights it through phase |
| **Bomber** | 30 | 40 | 7 | on death, AoE radius 60 disables cannons in radius for 5s | long-range kills before it closes (Phantom, Hawkeye, Sharpshooter) |
| **Queen (mini-boss)** | 800 | 15 | 50 | on death, spawns 6 Workers from her corpse | concentrated heavy fire; Sniper III bonus damage |
| **King (final boss, wave 25)** | 3000 | 10 | 200 (+ run completion) | at 75% / 50% / 25% HP, spawns 4 Soldiers each threshold | sustained high-DPS apex setup; god-tier required |

### 4.2 Campaign structure (25 waves + endless)

**Pacing rules:**
- 30s prep time before wave 1; 20s prep between regular waves; 30s prep before boss waves.
- Wave length: 30–60s of active spawning. Player can press a "Send Next Wave Early" button for a small gold bonus.
- Wave completion bonus: 20 gold + 1 gold per wave number (wave 5 pays 25, wave 20 pays 40).
- Boss waves at **5, 10, 15, 20, 25**. Wave 5/10/20 are increasing-difficulty Queens; wave 15 is the *unlock-god-tier* Queen; wave 25 is the King.

| Wave | Composition (in spawn order) | Notes |
|---|---|---|
| 1 | 8 Workers | Tutorial: place a tower, watch it fire |
| 2 | 12 Workers | First real test of placement |
| 3 | 6 Workers, 4 Workers (gap), 6 Workers | Wave shape introduces sub-groups |
| 4 | 10 Workers, 4 Scouts | Scout intro |
| 5 | **Mini-Queen #1** (HP 400) + 8 Workers | First boss, half-strength Queen |
| 6 | 12 Workers, 6 Scouts | |
| 7 | 8 Scouts, 6 Workers, 4 Scouts | Scout-heavy |
| 8 | 12 Workers, 2 Soldiers | Soldier intro |
| 9 | 6 Soldiers, 8 Workers | Armor focus |
| 10 | **Queen #2** (HP 600) + 6 Soldiers + 6 Workers | Mid-game milestone |
| 11 | 8 Workers, 4 Carriers, 6 Workers | Carrier intro — runners try to flee |
| 12 | 4 Soldiers, 6 Carriers, 4 Scouts | Carrier swarm |
| 13 | 6 Splitters, 8 Workers | Splitter intro |
| 14 | 4 Splitters, 4 Soldiers, 6 Carriers, 8 Scouts | Mixed escalation |
| 15 | **Queen #3** (HP 800, full strength) + 8 Splitters + 6 Carriers | **God tier unlocks on completion** |
| 16 | 2 Tanks, 8 Workers | Tank intro — needs pierce |
| 17 | 6 Phase ants, 8 Workers, 4 Soldiers | Phase intro |
| 18 | 3 Tanks, 4 Phase ants, 6 Splitters | Cocktail of late-game types |
| 19 | 4 Bombers, 8 Workers, 4 Carriers | Bomber intro — careful tower placement |
| 20 | **Queen #4** (HP 1200) + 4 Bombers + 6 Tanks | Late-game brutal |
| 21 | 6 Tanks, 6 Phase ants, 8 Carriers | Endurance |
| 22 | 8 Bombers, 8 Splitters, 4 Soldiers | Pressure on tower placement + AoE |
| 23 | 4 Tanks, 6 Phase ants, 6 Bombers, 8 Splitters, 8 Carriers, 12 Workers | Everything wave |
| 24 | 8 Tanks, 12 Carriers, 8 Bombers | Pre-boss attrition |
| 25 | **King** + summons (4 Soldiers at each 75/50/25% HP threshold), continuous Worker drip from secondary spawn | Final boss, ~2-minute fight |

**Endless mode (wave 26+):**
After wave 25 completes, endless mode begins. Each endless wave pulls from a weighted random ant pool (heavy late-game types) with a +5% HP / +3% speed multiplier per wave above 25. Every 5 waves spawns an additional Queen. No King re-fights. High score = highest wave reached.

### 4.3 Ants and waves decisions log

- **Roster locked at 10** (Worker, Scout, Soldier, Carrier, Tank, Splitter, Phase, Bomber, Queen, King).
- **Campaign locked at 25 hand-tuned waves + endless mode.**
- **King mechanic:** threshold-summons (4 Soldiers at 75/50/25% HP) + continuous Worker drip from secondary anthill. Adequate for the final boss; can revisit during playtesting if the fight feels flat.
- **No replay-individual-waves.** Run is strict 1→25.
- **Endless scaling exponent:** +5% HP / +3% speed per wave above 25 is the starting point. Will need tuning during playtesting — the only number in this spec that's explicitly expected to change.

---

## 5. Map and pathing

Single picnic blanket scene. Cake in middle on a plate. Anthill on the left edge as the primary spawn. The pathing model is the design pillar of the game (see §5.2) — ants wander erratically toward the cake, which makes hit/miss feel earned and justifies the cannon variety.

### 5.1 Map dimensions

- **Native canvas:** 480×270 pixels (16:9), scaled 2× or 3× via CSS `image-rendering: pixelated`. Final on-screen size: 960×540 or 1440×810 depending on viewport.
- **Playfield (where ants and cannons live):** roughly 320×240, occupying the left and center of the canvas.
- **Cannon shop side panel:** right 160px column.
- **HUD bar:** top 24px row over the playfield.
- **Cake position:** center of the playfield, approx (200, 135) in native pixels.
- **Primary anthill:** left edge, approx (16, 135). One ant emerges every spawn-tick.
- **Secondary anthill (wave 25 King fight only):** top-right of playfield. Continuous Worker drip during the boss fight.

### 5.2 Ant movement model — erratic wandering

Each ant has a **steering** state per frame:

```
target = (carryingCrumb) ? anthillPos : cakePos
desiredVelocity = normalize(target - antPos) * antSpeed
heading += jitter(±15° per second, low-freq Perlin or filtered random)
heading lerp toward angleOf(desiredVelocity) at "alignment rate" (per ant type)
velocity = unitVector(heading) * antSpeed
position += velocity * dt
```

**Per-type alignment rate** (how strongly the ant pulls toward its target — lower = more erratic):

| Ant | Alignment rate | Effective behavior |
|---|---|---|
| Worker | 0.6 | Standard wander, drifts noticeably |
| Scout | 0.4 | Very erratic, hard to track |
| Soldier | 0.8 | Determined, mostly straight |
| Carrier (no crumb) | 0.5 | Wandering toward cake |
| Carrier (with crumb) | 0.9 | Beelines for anthill — the "stop the runner" mechanic needs them to be catchable but committed |
| Tank | 0.95 | Straight line, ignores noise |
| Splitter | 0.6 | Standard wander |
| Phase ant | 0.5 | Erratic, plus phase teleports a small distance forward on phase-out |
| Bomber | 0.7 | Aggressive forward, slightly determined |
| Queen | 0.85 | Slow, steady advance |
| King | 0.95 | Slow grind toward cake |

### 5.3 Crumb pickup and flee

- Cake has 8 slices (lives). Any ant that touches the cake bounding box steals 1 crumb.
- After pickup, the ant's `carryingCrumb = true`, target switches to anthill, and a **red `!` indicator** floats above its body.
- Carrier ants are 50% faster while carrying a crumb (45 px/s vs 30). Other ant types keep their normal speed when carrying — only Carriers get the speed bonus, since they're the dedicated runners.
- Killing an ant *with* a crumb returns the crumb to the cake (cake regains 1 slice). This is the "stop them on the way back" reward.
- An ant that reaches the anthill with a crumb consumes that slice permanently (game-over at 0 slices).

### 5.4 Tower placement

- **Free placement.** Player drags a cannon from the shop and drops it anywhere on the playfield, with two restrictions: (a) cannot overlap the cake, anthill, plate, or any other cannon; (b) cannot block the anthill's immediate exit (5px exclusion ring around the anthill).
- **No grid.** Faithful to the original. Adds slight implementation cost (placement collision check, snap-to-valid-position preview) but is core to the feel.
- **Tower hitbox for placement:** 16px radius circle centered on the cannon base.
- **Drag preview:** while dragging, a translucent cannon sprite follows the cursor with the range circle drawn underneath. The preview tints red if placement is invalid.

### 5.5 Tower collision for ants

Ants don't pathfind around towers (that would be C, which we rejected). Instead they use **soft repulsion**:

- Each tower contributes a repulsion force inversely proportional to distance, applied only within a small radius (≈ tower radius + 4px).
- Ants steering toward the cake will naturally flow around towers, occasionally bumping but never stuck.
- If a tower is dropped on top of a moving ant, the ant is shoved out of the placement radius (no damage).

### 5.6 Map and pathing decisions log

- **One primary anthill for waves 1–24.** Secondary anthill is *only* active during the wave-25 King fight, providing the continuous Worker drip described in §4.2.
- **No decorative obstacles** on the playfield. Free placement is cleaner without them. Easy to add a juice-box or watermelon-slice obstacle later if the game feels too placement-flat in playtesting.

---

## 6. Economy, save state, and win condition

### 6.1 Gold income

- **Starting gold:** 150.
- **Ant drops on kill:** per the ant table in §4.1 (2 / 3 / 5 / 4 (8 mid-flee) / 10 / 4+1×2 / 6 / 7 / 50 / 200).
- **Wave completion bonus:** 20 gold + wave number. Wave 5 pays 25, wave 20 pays 40.
- **Send-Next-Wave-Early bonus:** half the unused prep-time seconds, in gold (e.g. press at 12s remaining of a 20s prep → +6 gold). Encourages confident play.
- **No passive interest** on banked gold (Bloons-style). Rewards turtling and adds a balance variable the design doesn't need.
- **No upgrade-cost reductions** or cost-modifier mechanics. The cannon cost is the cannon cost.

### 6.2 Save state (localStorage)

Saved across sessions:

- **Highest endless wave reached.** Single integer, used for the high-score badge on the title screen.
- **Campaign completion flag** + fastest-clear time. Used for a "✓ Cleared (best: 38:12)" badge on the title.
- **Sound on/off, music on/off** preferences.

NOT saved:

- **Mid-run resume.** Closing the tab abandons the run. Serializing every cannon, projectile, ant, and wave state is implementation cost the portfolio demo doesn't warrant.
- **Per-wave best.** No replay-individual-waves feature, so per-wave records aren't needed.

localStorage key: `dariobimbi_antbuster_v1`. Bumping the `_v1` suffix invalidates old saves cleanly if the schema ever changes.

### 6.3 Win condition

- **Win:** complete wave 25. Victory screen shows: waves completed (25), total towers built, total ants killed, total cake slices saved (8 of 8 if perfect), run time. Buttons: "Continue into endless mode" / "Return to title."
- **Lose:** cake hits 0 slices. Game-over screen shows: wave reached, towers built, ants killed. Buttons: "Retry from wave 1" / "Return to title."
- **Endless mode end:** can only end by losing the cake. Game-over screen shows endless-wave reached and updates the high score if beaten.

### 6.4 Replay and difficulty

- **No replay-individual-waves.** Waves run 1→25 in order, one-shot. No level select grind.
- **No difficulty selector.** One curve, tuned to be challenging but completable. Endless mode handles the "I want it harder" case.

---

## 7. UI shell

The whole UI lives inside the 480×270 canvas — no surrounding HTML chrome (besides the page-pattern shell). All UI rendered with canvas drawing for visual consistency with the pixel-art game world.

### 7.1 Layout zones (480×270 canvas)

```
┌───────────────────────────────────────────────────────┬──────────────┐
│  HUD BAR (top, 24px tall, full playfield width)        │              │
│  Gold: 250  ❤8  Wave 7/25  [▶ Send Next]  [⏸] [×1/×2] │              │
├───────────────────────────────────────────────────────┤              │
│                                                       │  CANNON      │
│                                                       │  SHOP        │
│                                                       │  (right,     │
│              PLAYFIELD                                │   160×270)   │
│              320×246                                  │              │
│              cake at center, anthill on left edge     │  3 base      │
│                                                       │  cannons     │
│                                                       │  to drag     │
│                                                       │  out         │
│                                                       │              │
└───────────────────────────────────────────────────────┴──────────────┘
```

### 7.2 Title screen

- Game logo at top ("ANT BUSTER" in pixel font).
- Three buttons centered:
  1. **Start Campaign** — begins a fresh run from wave 1.
  2. **Endless Mode** — always available. Skips the campaign and drops the player straight into procedurally escalating waves with starting gold 250 (a bit higher than campaign, since there's no early-game ramp-up).
  3. **How to Play** — opens a 2-panel tutorial overlay (see §7.7).
- **Badges (top-right corner):** "✓ Campaign cleared (best 38:12)" and "Endless best: wave 47" if the player has those records.
- Tiny **sound on/off** toggle, bottom-left.

### 7.3 HUD bar (24px tall, top of playfield)

Left to right:

- **Gold counter** with coin icon. e.g. `🪙 250`.
- **Lives** (cake slices remaining) with cake-slice icon. e.g. `❤ 8`.
- **Wave counter.** `Wave 7/25` during campaign, `Wave 47` during endless.
- **Send Next Wave** button. Only visible during prep time. Pressing it skips the rest of the timer and grants the early-send gold bonus.
- **Pause** button (pause icon, far right).
- **Speed toggle** (1× / 2×, far right next to pause). Affects ant speed and projectile speed proportionally; UI animations stay 1×.

### 7.4 Cannon shop side panel (160×246, right of playfield)

- **Title row:** "TOWERS" header.
- **Three cannon cards** stacked vertically. Each card is 56×64:
  - Cannon icon (composite of base + barrel sprite at 32×32).
  - Name below ("Howitzer", "Repeater", "Twin Gun").
  - Cost ("50g", "50g", "75g"). Greyed out if player can't afford.
- **Drag-to-place interaction:** mousedown on a card grabs a translucent cannon sprite; mouse follows it across the playfield with a range circle preview; mouseup drops it. Invalid placements (overlap, off-playfield) tint the preview red and the drop is rejected with a small shake animation.
- **Tooltip on hover:** shows the cannon's stats (damage, fire rate, range) and one-line role description.

### 7.5 Selected-tower panel

When the player clicks a placed cannon, the **shop panel is replaced** by a tower-detail panel (same 160×246 area):

- **Cannon name and current tier.** e.g. "Sharpshooter (T2)".
- **Sprite preview** (composite icon at 48×48).
- **Current stats** (damage, fire rate, range, special).
- **Upgrade options:** for non-final cannons, two side-by-side buttons showing the two upgrade paths with their next-tier name and cost. T2 cannons show one button labeled "GOD TIER" (locked greyed-out until wave 15 is cleared).
- **Sell button** at the bottom, showing the 70% refund value.
- **Range circle** drawn over the playfield while this panel is open.
- **Click outside the cannon** to close the panel and return to the shop.

### 7.6 Wave banners and overlays

- **Wave intro banner:** at wave start, a 96×16 banner slides in from top showing "Wave X/25" and dwells for 2s before fading.
- **Boss wave intro:** boss waves get a larger banner ("Wave 5: Mini-Queen", "Wave 25: King") with a brief animated flash and a deeper-toned sound cue.
- **Pause overlay:** dims the canvas, shows "PAUSED" + buttons: Resume, Restart Run, Quit to Title.
- **Victory overlay:** dims the canvas, shows the run stats and Continue/Title buttons (see §6.3).
- **Game-over overlay:** dims the canvas, "THE ANTS WIN" in red pixel font + Retry/Title buttons.

### 7.7 Tutorial (first-run only)

A 2-panel popover that appears on the first wave of the player's first campaign run:

- **Panel 1 (during prep time before wave 1):** "Drag a tower from the right →" with a small arrow pointer animation drawing attention to the shop.
- **Panel 2 (after the first kill):** "Click a tower to upgrade or sell."

Both dismissable. After dismissal, a `tutorialSeen: true` flag is saved to localStorage so they don't appear again. Title screen "How to Play" replays the same panels.

### 7.8 Keyboard shortcuts

Optional but nice to have:

- `Space` — pause/resume.
- `Enter` — Send Next Wave (when in prep time).
- `1` — set speed to 1×. `2` — set speed to 2×.
- `Esc` — closes any open overlay (tower panel, pause, tutorial).
- `S` — sells currently selected tower (with confirmation prompt).

---

## 8. Asset list

**Working resolution:** native 480×270 canvas, scaled 2× or 3× via CSS `image-rendering: pixelated`. All sprites authored at native pixel scale.

**Format:** PNG with transparency. One file per sprite, or sheet per related set (walk-cycle, damage states). Naming convention: `kebab-case.png`, organized in subfolders.

**Asset path:** `assets/demos/ant-buster/sprites/`. Loaded once on page-load as `Image` objects, drawn via `ctx.drawImage` with `ctx.imageSmoothingEnabled = false`.

### 8.1 Ants — 10 types

Each regular ant has a walk cycle (4 frames, 8 fps), a carrying-crumb cycle (4 frames), and a splat (1 frame). Drawn from a top-down 3/4 perspective.

| ID | Sheet | Size | Frames | Notes |
|---|---|---|---|---|
| 1 | `ant-worker.png` | 16×16 | 9 (4 walk + 4 carry + 1 splat) | Brown/black baseline ant |
| 2 | `ant-scout.png` | 16×16 | 9 | Smaller, redder, leaner |
| 3 | `ant-soldier.png` | 16×16 | 9 | Bigger thorax, dark gray armored shell, mandibles |
| 4 | `ant-carrier.png` | 16×16 | 9 | Yellow/gold tinted, visually distinct (must read as "the runner") |
| 5 | `ant-tank.png` | 24×24 | 9 | Massive, dark exoskeleton, shows armor segments |
| 6 | `ant-splitter.png` | 16×16 | 9 | Mottled green-brown, slightly translucent abdomen |
| 7 | `ant-splitter-mini.png` | 8×8 | 5 (4 walk + 1 splat) | Spawned from Splitter on death |
| 8 | `ant-phase.png` | 16×16 | 12 (4 walk + 4 carry + 1 splat + 3 phase-shimmer) | Purple-blue, semi-transparent during phase |
| 9 | `ant-bomber.png` | 16×16 | 13 (4 walk + 4 carry + 1 splat + 4 death-explosion) | Red and black, glowing belly, fuse-like antenna |
| 10 | `ant-queen.png` | 32×32 | 5 (4 walk + 1 splat) | Regal, fat abdomen, slight crown silhouette. Doesn't carry crumbs. |
| 11 | `ant-king.png` | 64×64 | 14 (4 walk + 4 idle-breathe + 1 hit-flash + 5 death) | Massive, ornate, distinct from Queen — bigger crown, scarred shell |

**Per-ant: ~110 unique frames total.**

### 8.2 Cannons — shared bases + 48 turrets

Cannon art is split into a static **base** (per family) and a rotating **turret/barrel** (per cannon). Barrel rotates at runtime via canvas `transform`. This is the single biggest art-budget saving in the project.

| ID | Sheet | Size | Frames | Notes |
|---|---|---|---|---|
| 12 | `cannon-base-heavy.png` | 32×32 | 1 | Wood + brass platform for Family 1 |
| 13 | `cannon-base-quick.png` | 32×32 | 1 | Lighter scaffold for Family 2 |
| 14 | `cannon-base-double.png` | 32×32 | 1 | Wider twin-mount platform for Family 3 |

**48 turret sprites** (16–24px, mounted to base center, rotates per frame to face target):

Family 1 — Heavy (16 turrets): `turret-howitzer.png`, `turret-siege.png`, `turret-railgun.png`, `turret-particle-cannon.png`, `turret-demolisher.png`, `turret-annihilator.png`, `turret-mortar.png`, `turret-hellfire.png`, `turret-saturation.png`, `turret-patriot.png`, `turret-cluster-strike.png`, `turret-discus.png`, `turret-ricochet.png`, `turret-vortex.png`, `turret-shockwave.png`, `turret-earthshaker.png`.

Family 2 — Quick (16 turrets): `turret-repeater.png`, `turret-auto-cannon.png`, `turret-vulcan.png`, `turret-hailstorm.png`, `turret-plasma-torch.png`, `turret-phoenix.png`, `turret-marksman.png`, `turret-sharpshooter.png`, `turret-reaper.png`, `turret-lance.png`, `turret-solar-lance.png`, `turret-spotter.png`, `turret-phantom.png`, `turret-hawkeye.png`, `turret-tesla.png`, `turret-stormcaller.png`.

Family 3 — Double (16 turrets): `turret-twin-gun.png`, `turret-triple-threat.png`, `turret-buckshot.png`, `turret-scattergun.png`, `turret-minigun.png`, `turret-phalanx.png`, `turret-frost.png`, `turret-glacier.png`, `turret-blizzard.png`, `turret-cryo.png`, `turret-absolute-zero.png`, `turret-toxin.png`, `turret-plague.png`, `turret-biohazard.png`, `turret-sonic-pulse.png`, `turret-tempest.png`.

Plus **18 god-tier glow overlays** (subtle pulsing aura layer drawn under each god-tier turret to mark it visually): `glow-godtier-1.png` through `glow-godtier-18.png`, 32×32 each, 4-frame loop.

**Per-cannon: 3 bases + 48 turrets + 18 god overlays = 69 sprites (~135 frames).**

### 8.3 Muzzle flashes

One flash per visually distinct firing motion — group by mechanic, not by cannon:

| ID | Sheet | Size | Frames | Used by |
|---|---|---|---|---|
| 15 | `flash-cannon.png` | 12×12 | 2 | Howitzer line, Siege, Railgun, Particle, Demolisher, Annihilator |
| 16 | `flash-mortar.png` | 14×14 | 3 | Mortar, Hellfire, Saturation, Patriot, Cluster Strike |
| 17 | `flash-disc-launch.png` | 10×10 | 2 | Discus, Ricochet, Vortex |
| 18 | `flash-impact-ground.png` | 16×8 | 3 | Shockwave, Earthshaker (ground-pulse base) |
| 19 | `flash-rapid.png` | 10×10 | 2 | Repeater, Auto-Cannon, Vulcan, Hailstorm |
| 20 | `flash-plasma.png` | 14×12 | 3 | Plasma Torch, Phoenix |
| 21 | `flash-sniper.png` | 12×6 | 2 | Marksman, Sharpshooter, Reaper, Phantom, Hawkeye |
| 22 | `flash-beam-emitter.png` | 8×8 | 3 | Lance, Solar Lance (beam start point) |
| 23 | `flash-tesla.png` | 8×8 | 4 | Tesla, Stormcaller (electric arc origin) |
| 24 | `flash-spread.png` | 14×12 | 2 | Twin Gun, Triple Threat, Buckshot, Scattergun |
| 25 | `flash-minigun.png` | 10×10 | 2 | Minigun, Phalanx |
| 26 | `flash-frost.png` | 12×12 | 3 | Frost, Glacier, Cryo, Absolute Zero |
| 27 | `flash-sonic.png` | 14×14 | 4 | Sonic Pulse, Tempest |

**Per muzzle flash: 13 sheets, ~32 frames.**

### 8.4 Projectiles

Most are tiny. Some effects (laser beam, sonic ring, frost beam, chain lightning arc) are drawn programmatically with canvas paths — no sprite needed.

| ID | Sheet | Size | Frames | Notes |
|---|---|---|---|---|
| 28 | `proj-cannonball.png` | 4×4 | 1 | Howitzer line, Siege, Railgun |
| 29 | `proj-pierce-streak.png` | 16×4 | 2 | Particle Cannon (instant-line render) |
| 30 | `proj-heavy-shell.png` | 6×6 | 1 | Demolisher, Annihilator |
| 31 | `proj-mortar-shell.png` | 6×6 | 1 | Mortar, Hellfire, Saturation (with separate shadow) |
| 32 | `proj-shadow.png` | 6×3 | 1 | Soft semi-transparent ellipse for arcing projectiles |
| 33 | `proj-missile.png` | 8×4 | 2 | Patriot, Cluster Strike (with flame-trail frames) |
| 34 | `proj-discus.png` | 8×8 | 4 | Spinning disc for Discus, Ricochet, Vortex |
| 35 | `proj-tracer.png` | 4×2 | 1 | Repeater family rapid-fire |
| 36 | `proj-sniper-streak.png` | 8×2 | 1 | Marksman line |
| 37 | `proj-plasma-flame.png` | 4×4 | 3 | Plasma Torch, Phoenix particles |
| 38 | `proj-pellet.png` | 3×3 | 1 | Buckshot, Scattergun |
| 39 | `proj-ice-shard.png` | 4×4 | 2 | Frost, Cryo, Absolute Zero |
| 40 | `proj-poison-droplet.png` | 4×4 | 1 | Toxin, Plague, Biohazard |

(Laser beams, frost beams, sonic rings, chain-lightning arcs, blizzard aura: drawn programmatically with `ctx.beginPath` / `ctx.arc` / `ctx.lineTo`. No sprites.)

**Per projectile: 13 sheets, ~24 frames.**

### 8.5 Effects (impacts, status, environmental)

| ID | Sheet | Size | Frames | Notes |
|---|---|---|---|---|
| 41 | `fx-explosion-small.png` | 24×24 | 5 | Mortar, Hellfire impact |
| 42 | `fx-explosion-large.png` | 48×48 | 6 | Annihilator, Earthshaker, King-death |
| 43 | `fx-frost-cloud.png` | 24×24 | 4 | Cryo, Absolute Zero AoE |
| 44 | `fx-hit-spark.png` | 8×8 | 3 | Generic hit on any ant |
| 45 | `fx-carrier-alert.png` | 8×8 | 2 | Red `!` floating over a Carrier holding a crumb |
| 46 | `fx-crumb-particle.png` | 4×4 | 1 | Falls when Carrier is killed mid-flee |
| 47 | `fx-bomber-explosion.png` | 32×32 | 5 | Bomber's death AoE (disables cannons) |
| 48 | `fx-cannon-disabled.png` | 32×32 | 4 | Sparks/smoke over a stunned cannon for the 5s disable window |
| 49 | `fx-stun-stars.png` | 12×8 | 4 | Tempest knockback / stunned ant |
| 50 | `fx-slow-icon.png` | 6×6 | 1 | Snowflake floating over slowed ant |
| 51 | `fx-burn-icon.png` | 8×8 | 3 | Flame flicker over ignited ant |
| 52 | `fx-poison-drip.png` | 4×4 | 2 | Drip over poisoned ant |
| 53 | `fx-summon-flash.png` | 24×24 | 4 | Spawn flash for Queen-on-death Workers, King threshold Soldiers |
| 54 | `fx-queen-aura.png` | 48×48 | 4 | Faint pulsing aura under the Queen / King to make them feel important |

**Per effect: 14 sheets, ~46 frames.**

### 8.6 Cake and environment

| ID | Sheet | Size | Frames | Notes |
|---|---|---|---|---|
| 55 | `cake-states.png` | 32×32 | 9 | 8 damage states (full → empty) + 1 destroyed |
| 56 | `cake-plate.png` | 36×8 | 1 | White china plate under cake |
| 57 | `tile-blanket.png` | 16×16 | 1 | Red-and-white check, tileable |
| 58 | `tile-grass.png` | 16×16 | 1 | Green grass, tileable, used as blanket border |
| 59 | `anthill.png` | 32×32 | 5 (1 idle + 4 ant-emerging) | Brown mound with dark hole |
| 60 | `anthill-secondary.png` | 24×24 | 5 | Smaller alternate anthill for King wave |
| 61 | `decor-ladybug.png` | 8×8 | 2 | Idle-flap, ambient detail |
| 62 | `decor-leaf.png` | 12×8 | 1 | Scattered on grass |
| 63 | `decor-pebble.png` | 6×4 | 1 | Scattered on grass |

**Per environment: 9 sheets, ~26 frames.**

### 8.7 UI sprites

| ID | Sheet | Size | Frames | Notes |
|---|---|---|---|---|
| 64 | `ui-coin.png` | 12×12 | 4 | Spinning coin animation for the gold counter |
| 65 | `ui-cake-slice.png` | 12×12 | 1 | Lives icon (slice with strawberry) |
| 66 | `ui-wave-banner.png` | 96×16 | 1 | Background panel for "Wave X/25" |
| 67 | `ui-boss-banner.png` | 144×24 | 1 | Background panel for boss-wave intros |
| 68 | `ui-shop-card.png` | 56×64 | 2 | Idle + hover states for shop cannon cards |
| 69 | `ui-tower-panel.png` | 160×246 | 1 | Frame for the selected-tower detail panel |
| 70 | `ui-button-primary.png` | 56×24 | 3 | Idle / hover / disabled — used for upgrade, send-next, etc. |
| 71 | `ui-button-sell.png` | 56×24 | 2 | Red-tinted, idle + hover |
| 72 | `ui-button-locked.png` | 56×24 | 1 | Greyed-out variant for god-tier (pre-wave-15) |
| 73 | `ui-icon-pause.png` | 16×16 | 1 | |
| 74 | `ui-icon-play.png` | 16×16 | 1 | |
| 75 | `ui-icon-speed.png` | 16×16 | 2 | 1× and 2× states |
| 76 | `ui-icon-sound.png` | 16×16 | 2 | On / off |
| 77 | `ui-logo.png` | 200×40 | 1 | "ANT BUSTER" title-screen logo |
| 78 | `ui-tutorial-arrow.png` | 24×16 | 4 | Animated pointer for tutorial popovers |

**Per UI: 15 sheets, ~26 frames.**

**Pixel font:** use a free embedded font rather than authoring glyphs. Recommended: [m6x11](https://managore.itch.io/m6x11) or [Press Start 2P](https://fonts.google.com/specimen/Press+Start+2P) (Google Fonts, easy to load). Decide once during implementation.

### 8.8 Asset budget summary

| Category | Sheets | Frames |
|---|---|---|
| Ants | 11 | ~110 |
| Cannons (bases + turrets + glows) | 69 | ~135 |
| Muzzle flashes | 13 | ~32 |
| Projectiles | 13 | ~24 |
| Effects | 14 | ~46 |
| Environment + cake | 9 | ~26 |
| UI | 15 | ~26 |
| **Total** | **~144 sheets** | **~400 frames** |

**Realistic art-time estimate:** 60–100 hours for someone reasonably comfortable with pixel art. **Plan to draw the assets in waves** so you can test gameplay before committing fully:

- **Pass 1 (MVP, ~30 hours):** 3 base cannons + 9 mid-tier turrets, 4 ants (Worker, Scout, Soldier, Queen), cake, anthill, blanket/grass tiles, basic UI. Gets the campaign through wave 10 playable.
- **Pass 2 (~30 hours):** remaining ant types (Carrier, Tank, Splitter, Phase, Bomber, King), 18 final-tier turrets, full muzzle flashes / projectiles / effects.
- **Pass 3 (~20 hours):** 18 god-tier turrets + glow overlays, polish frames (idle-breathe, decorative ambient), title logo.

This way you're never sunk too deep in art before the gameplay is proving out.

---

## 9. Palette

**Pick:** [**Endesga 32**](https://lospec.com/palette-list/endesga-32). 32 colors, broad and warm. Includes everything we need — three useful greens for grass, four browns for ants and ground, full warm range (red, orange, yellow, pink) for cake and warm UI accents, four blues for ice/frost effects, three purples for Phase and electric, dark-to-light grays for cannon metal and shadows.

**Why not DB16:** only 16 colors total. Forces you to share a single green between grass, Splitter ants, and poison effects, which dulls the visual hierarchy. The art will look more like "16-color demoscene" than "indie pixel-art game" — a fine choice for a code-puzzle, but not the right fit for this project.

**Why not custom:** designing a palette is its own discipline (~5 hours of color theory for a balanced 32-color palette) and the result will be variations on Endesga 32 anyway. Use the existing one and spend that time on sprites instead.

**Hard rule:** every pixel drawn comes from the palette. No off-palette anti-aliasing, no gradients. Pixel art looks cohesive precisely because of this constraint.

**Palette role assignments:**

| Role | Palette colors |
|---|---|
| Cake (warm pinks, white, cream) | `#fee761`, `#feae34`, `#ffffff`, `#cdf7e2` |
| Blanket (red-white check) | `#e43b44`, `#ffffff` |
| Grass | `#3e8948`, `#265c42`, `#193c3e` |
| Worker / Scout / Soldier ants | `#3a4466`, `#262b44`, `#181425` |
| Carrier (gold) | `#fee761`, `#feae34`, `#e4a672` |
| Tank (deep dark) | `#181425`, `#262b44`, `#5a6988` |
| Splitter (sickly green) | `#63c74d`, `#3e8948` |
| Phase (purple) | `#68386c`, `#b55088`, `#f6757a` |
| Bomber (red glow) | `#e43b44`, `#feae34`, `#fee761` |
| Queen / King (royal) | `#68386c`, `#b55088`, `#fee761` |
| Cannon metals | `#5a6988`, `#8b9bb4`, `#c0cbdc`, `#ffffff` |
| Frost / ice projectiles | `#0099db`, `#2ce8f5`, `#cdf7e2` |
| Plasma / fire | `#feae34`, `#e43b44`, `#f77622` |
| Tesla / electric | `#fee761`, `#ffffff`, `#68386c` |
| Poison | `#63c74d`, `#3e8948` |
| God-tier glow | `#fee761` + alpha pulse |

---

## 10. Tech notes specific to this site

(Cross-references to existing project conventions so future readers don't lose context.)

- **Pattern caching gotcha:** after creating `patterns/demo-ant-buster.php` and the `templates/page-demo-ant-buster.html` shell, bump the theme `Version:` in `style.css`. Without the bump, the new pattern file exists on disk but `<!-- wp:pattern {"slug":"dariobimbi/demo-ant-buster"} /-->` renders empty. See CLAUDE.md "Notes for editing".
- **Inline script gotchas:** WordPress's content filter encodes `&&` to `&#038;&#038;` inside inline `<script>` blocks inconsistently. Default to writing without `&&`; use nested `if`, helper booleans, or `indexOf` instead. See CLAUDE.md "Inline `<script>` gotchas inside pattern PHP".
- **Full-bleed background:** the `useRootPaddingAwareAlignments` + `is-layout-constrained` + `theme.json` `body { background-color }` combo can leak strips of page background at the canvas edges. Add a body class from the demo's script (`document.body.classList.add('has-ant-buster-page')`) and paint `body.has-ant-buster-page` with the demo's surface color via `!important`.
- **Asset path:** sprites go under `assets/demos/ant-buster/` (parallel to existing `assets/projects/<slug>/`). Sprites are loaded once on page-load, cached as `Image` objects, drawn via `ctx.drawImage` with `ctx.imageSmoothingEnabled = false`.

---

## 11. Decision log (cross-spec summary)

Quick at-a-glance table of every major decision. All sections are locked.

| Topic | Decision | Spec section |
|---|---|---|
| Scope | Faithful tribute, multi-week project | §1, §2 |
| Tech stack | Vanilla JS, canvas 2D, single-file pattern, no build step | §2 |
| Art direction | Pixel art, user-drawn, Endesga 32 palette | §2, §9 |
| Cannon roster | 48 cannons in 3-3-2-1 tree (3 base + 9 mid + 18 final + 18 god) | §3 |
| Cannon costs | Flatter through finals (~180–235 to reach T2), expensive at T3 | §3 |
| God-tier gating | Gold + wave-15 unlock | §3.5 |
| Ant roster | 10 types: 8 regulars + Queen + King | §4.1 |
| Campaign | 25 hand-tuned waves + endless mode | §4.2 |
| Boss waves | 5 / 10 / 15 (god unlock) / 20 / 25 (King) | §4.2 |
| Replay-individual-waves | No | §6.4 |
| Map size | 480×270 native canvas, 320×246 playfield + 160-wide shop | §5.1 |
| Pathing | Erratic wandering toward cake, no fixed path, soft tower repulsion | §5.2, §5.5 |
| Tower placement | Free, no grid | §5.4 |
| Anthills | One primary; second only for wave-25 King | §5.6 |
| Decorative obstacles | None | §5.6 |
| Gold sources | Ant drops + wave bonus + early-send bonus | §6.1 |
| Passive interest | None | §6.1 |
| Save state | LocalStorage for high scores + completion + sound prefs only | §6.2 |
| Mid-run resume | Not implemented | §6.2 |
| Win condition | Beat wave 25; endless mode optional after | §6.3 |
| Difficulty selector | None | §6.4 |
| UI surface | Entirely inside the canvas, no surrounding HTML chrome | §7 |
| Endless mode access | Always available from title (not locked behind campaign) | §7.2 |
| Tutorial | 2-panel first-run popover, replayable from "How to Play" | §7.7 |

**The only number explicitly expected to change during playtesting:** endless-mode HP/speed scaling exponent (§4.3).
