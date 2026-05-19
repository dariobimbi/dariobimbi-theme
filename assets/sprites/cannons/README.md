# Cannon Sprite Authoring

These are the production PNG assets loaded by the Ant Buster demo
([patterns/demo-ant-buster.php](../../../patterns/demo-ant-buster.php)). The full
contract is in [docs/superpowers/specs/2026-05-18-cannon-sprite-refresh-design.md](../../../docs/superpowers/specs/2026-05-18-cannon-sprite-refresh-design.md).

## Recipe for adding a new cannon sprite

1. Open or create the PSD at `tools/sprites/cannons/<class>/<kind>.psd`.
2. Author at **32 × 32 px**, transparent background, barrel pointing **up**,
   barrel tip at **y = 2** (14 px above the pivot — matches the existing
   muzzle-flash spawn distance), flat top-down lighting (no directional
   shading), 3 px transparent margin on LEFT, RIGHT, and BOTTOM.
3. Export PNG to `assets/sprites/cannons/<class>/<kind>.png`. The filename
   must match the `CANNON_KINDS` JavaScript key exactly (case-sensitive).
4. In `patterns/demo-ant-buster.php`, set `spriteFile: '<class>/<kind>.png'`
   on the `CANNON_KINDS[<kind>]` entry.
5. Bump `style.css` Version (cache-busts the sprite via the `?v=` query string).
6. Hard-reload `/demos/ant-buster/` in an incognito window. Place the cannon
   on the playfield. Confirm it renders as PNG, rotates smoothly to face
   ants, and the muzzle flash sits at the visible barrel tip.
7. Commit.

## What does NOT belong in this folder

- Numbered iteration variants (`howitzer-2.png`, `howitzer-3.png`, …) —
  `.gitignore`'d. Pick a winner, export it as `howitzer.png`, delete the rest.
- PSD or RAW sources — `.gitignore`'d. They live under `tools/sprites/`.
- Sprite strips with multiple frames per cannon. Each cannon is one frame.
  Existing muzzle-flash and recoil math provide firing feedback.
