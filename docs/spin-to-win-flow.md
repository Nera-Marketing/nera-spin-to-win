# Spin To Win — How the Game Works

A complete walk-through of the Spin To Win wheel for the people who run it: what staff see in the editor, what players see on the site, and what happens behind the scenes when the wheel is spun.

This document is for **operators and admins**. It does not assume any technical background.

For the maths of how the **Weight** field controls each prize's chance of winning, see the companion document **`weight-explained.pdf`**. This document mostly points at it rather than repeating that content.

---

## 1. The big picture

Spin To Win is a bonus wheel attached to a competition product. Players who buy tickets earn **spins**; each spin gives them a chance at a prize from the wheel. Prizes can be **site credit** (added to their wallet), a **physical prize**, or a **"Try again"** outcome (no win).

The wheel is configured per competition product. So a "£10,000 Money Wheel" and a "Mystery Box Wheel" can have completely different segments, weights, and stock levels.

---

## 2. What players see

When a logged-in player visits the Spin To Win page for a competition, they see:

- **The wheel** in the centre, showing each prize segment as a slice.
- A **Spin now** button and a **Turbo mode** button below the wheel.
- **Spins remaining** — how many spins they've earned and not yet used.
- **All prizes** — the full list of prizes available on this wheel, on the left.
- **History** — their own past spin outcomes for this wheel, on the right.

Players cannot edit anything from this page. It's purely the play view.

---

## 3. Earning spins

Spins are granted automatically when a player buys tickets to the parent competition. The exact rule (e.g. "one spin per ticket", "one spin per £X spent") is configured per product. Spins accumulate in the player's balance until they're used.

A spin can only be used by the player who earned it, on the wheel attached to the product they bought tickets for. They cannot be transferred between products or between players.

---

## 4. The wheel editor (admin side)

Each competition product has a **Spin To Win** tab in its WooCommerce edit screen. That tab is where staff configure the wheel.

At the top of the tab is a single switch:

- **Enable Spin To Win** — when ticked, the wheel is active for this product. When unticked, the wheel is hidden and no spins are granted.

Below that is the segments table. Each row is one slice of the wheel. The columns are:

| Column | What it does |
|---|---|
| **Enabled** | A tick-box. Tick = the segment is in the draw. Untick = it is excluded from every spin until you re-enable it. See section 5. |
| **Label** | The text shown on the wheel slice and in the player's history (e.g. "£20 Site Credit", "Headphones", "Try again"). |
| **Type** | What the prize *is*: **Try again** (no win), **Site credit** (added to wallet), or **Physical prize**. |
| **Weight** | A number controlling how often this segment wins. Weights are *relative* — see `weight-explained.pdf`. |
| **Credit amount** | For Site credit prizes only. The £ amount added to the wallet on a win. |
| **Physical title** | For Physical prizes only. The longer-form prize name used in admin notifications. |
| **Stock** | For Physical prizes only. How many of this prize exist. Counts down with each win and excludes the segment when it reaches zero. |
| **Image** | Optional thumbnail shown in the All prizes list and (where supported) on the wheel. |

There are also **Add segment**, **Export JSON**, and **Import JSON** buttons. Export/import lets you back up a wheel's configuration or copy it between products.

---

## 5. Turning a prize off without deleting it

Each segment row has an **Enabled** tick-box on the left.

- **Tick** = the segment is in the draw.
- **Untick** = the segment is **completely excluded from every spin** until you re-enable it. Players will never win it. The wheel still works using the remaining segments, with their weights re-normalised automatically — just like an out-of-stock physical prize.

Use Enabled when you want to **temporarily** pull a prize out of rotation (e.g. while you swap out images, between draws, while you wait for new stock to arrive). The label, weight, image, and stock all stay set, ready to bring back with a single click.

> **Do not use a Weight of zero to disable a prize.** The system clamps tiny weights to a small non-zero value, so a "0" weight technically still wins on rare spins. The Enabled tick-box is the correct, predictable way to turn a prize off. (See the Common Mistakes table in `weight-explained.pdf` for more.)

To remove a prize **permanently**, click the **Remove** button on its row instead.

---

## 6. Out-of-stock physical prizes

Physical prizes have a **Stock** count. Every time a player wins one, stock drops by one. When stock hits zero, the segment is automatically removed from the draw — exactly like an unticked Enabled box. Staff don't need to do anything; the wheel keeps working using the remaining eligible segments.

If you want to bring a physical prize back into the draw later, top up its Stock value and save the product.

---

## 7. A normal spin

When a player clicks **Spin now**:

1. The wheel begins spinning (a ~2.6-second animation).
2. The system picks the winning segment using the configured weights, with any disabled or out-of-stock segments removed.
3. The wheel decelerates and lands on the winning slice.
4. A result dialog appears:
   - **"You won! Site credit added: £X"** — the credit is already in their wallet by the time they see this.
   - **"You won! Your prize will be fulfilled — our team may email you if needed."** — for physical prizes, with a notification email automatically sent to the admin.
   - **"Close, but not this time."** — for a Try again outcome.
5. The player's spins-remaining count drops by one and the History list (right column) updates.

The player can spin again immediately as long as they have spins left.

---

## 8. Turbo Spin — the fast path

Some players want their prizes immediately rather than sitting through ten wheel animations. **Turbo mode** does that.

When the player clicks **Turbo mode**:

1. **A confirmation dialog appears first.** The exact copy is:
   > *Are you sure you want to use turbo spin, it will reveal all prizes instantly*

   With **Cancel** and **Confirm** buttons. **No spins are used yet.**

2. **If they click Cancel**, nothing happens. Their spins remain untouched and the dialog closes.

3. **If they click Confirm**, the system resolves **every remaining spin at once**. There is **no wheel animation**. Each individual spin still goes through the same weighted draw, with disabled and out-of-stock segments correctly excluded — Turbo just runs them all in one server call.

4. A results list dialog appears, showing every outcome in order. For each row:
   - A coloured dot (red for a win, grey for "Try again").
   - The prize label (e.g. "£20 Site Credit", "Try again").
   - For Site credit wins, the £ amount on the right in green.
   - For Try again outcomes, "Close, but not this time." on the right.

5. Site-credit wallet credits and physical-prize admin emails are processed exactly as for normal spins. Nothing is silently skipped.

When the dialog closes, the player's spins-remaining count is zero, and the History list now contains every Turbo outcome (most recent at the top).

---

## 9. Limits and safety on Turbo Spin

A few invisible safety rails sit behind Turbo mode:

- **50-spin cap per click.** A single Turbo press will never resolve more than 50 spins, even if the player has more than that. They can press Turbo again to resolve the next batch. This stops a single click from running indefinitely.
- **Concurrent-tab safety.** If a player opens the spin page in two browser tabs and presses Turbo in both at once, the system queues them — the second tab cannot accidentally spend the same balance twice.
- **Balance re-check before each spin.** Inside a Turbo batch, the remaining-spins balance is checked again before each individual spin. If somehow the balance reaches zero mid-batch (e.g. a refund processed at the same moment), the batch stops cleanly and returns whatever did resolve.

For staff, the practical takeaway: Turbo never produces results that a slow drip of normal spins wouldn't have produced, and it cannot consume more spins than the player has.

---

## 10. History

Each player has a personal history list on the right of the spin page, most-recent-first, capped at 20 rows. It shows the prize label and a timestamp for every spin they've taken on this wheel.

History is per-player and per-wheel. Players don't see anyone else's spins; staff don't see this list (they have a separate admin view of physical-prize wins).

---

## 11. Behind the scenes: spin auditing

For trust and dispute resolution, every spin — both normal and Turbo — is recorded in a tamper-evident audit log. Each entry stores:

- A unique reference for that one spin.
- A snapshot of which segments were eligible and their weights at the moment of the spin.
- The outcome that was selected.
- A pair of seed values that, together with the snapshot, allow the spin to be **replayed and verified** later. If a player ever asks "how do I know this wasn't rigged?", any one of these audit entries can be reproduced from scratch and shown to give the same result.

Staff don't need to do anything for this. The log fills itself in automatically. It's there as a safety net.

---

## 12. Quick checklist for setting up a new wheel

When configuring a new competition's wheel from scratch:

- [ ] On the WooCommerce product edit screen, open the **Spin To Win** tab.
- [ ] Tick **Enable Spin To Win**.
- [ ] Add one segment per prize via **Add segment**. For each:
  - [ ] Tick **Enabled**.
  - [ ] Set a clear **Label**.
  - [ ] Pick the right **Type** (Try again / Site credit / Physical).
  - [ ] Set the **Weight** (see `weight-explained.pdf` for the maths).
  - [ ] If Site credit: set **Credit amount**.
  - [ ] If Physical: set **Physical title** and **Stock**.
  - [ ] Optional: upload an **Image**.
- [ ] Add at least one **Try again** segment if you want some spins to lose (most wheels do).
- [ ] Save the product.
- [ ] Buy a test ticket as a test player and confirm the wheel works end-to-end (normal spin and Turbo).

To pause a wheel without deleting it, just untick **Enable Spin To Win** at the top of the tab.

---

## 13. Glossary

- **Segment** — one slice of the wheel. One prize (or a Try again).
- **Type** — the *kind* of prize a segment represents: Try again, Site credit, or Physical.
- **Weight** — a number that controls how often a segment wins, relative to the others. See `weight-explained.pdf`.
- **Enabled** — the on/off tick-box for a single segment. Disabled segments are completely excluded from the draw.
- **Eligible** — the set of segments that *could* be drawn for a given spin. A segment is eligible if it's Enabled **and** (for Physical prizes) has stock remaining.
- **Turbo Spin** — a single-click way to resolve every remaining spin at once, with a confirmation dialog first and no wheel animation.
- **Audit log** — the internal record of every spin, kept for dispute resolution.

---

## 14. Where to go next

- For the maths of weights and probabilities, see **`weight-explained.pdf`** in this same folder.
- For technical setup, deployment, and developer-facing details, contact the development team — those topics live in a separate handover document.
