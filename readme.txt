=== Nera Spin To Win ===
Contributors: nera
Tags: woocommerce, lottery, spin wheel, competition
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later

Spin wheel for lottery competitions — spins from ticket purchases, site credit and physical prizes.

== Description ==

**Nera Spin To Win** adds a configurable spin wheel experience for WooCommerce lottery competition products.

**Requires** WooCommerce.

== Installation ==

1. Install and activate **WooCommerce**.
2. Upload and activate **Nera Spin To Win**.
3. Configure wheel segments and options per product as needed.

== Changelog ==

= 1.2.1 =
* New: public REST endpoint `GET /nera-stw/v1/product/{id}/prizes` returns physical and site-credit prize segments with availability counts, recent winners (privacy-masked), totals, a 60-second server-side cache, and per-IP rate limiting (30 req/min) — powers the new prize banner on single-product pages.
* New: site-credit (wallet) segments support an optional stock cap in the product admin; leaving the field empty means unlimited, setting it to 0 marks the prize sold out.
* Fix: spin eligibility and stock decrement/rollback logic now apply to capped site-credit prizes the same way they apply to physical prizes.
* Fix: prize banner cache is automatically cleared when product segment settings are saved.

= 1.2.0 =
* New: per-segment **Enabled** toggle in the Spin To Win admin tab — disable a prize without deleting it. Disabled segments are excluded from the draw exactly like out-of-stock physicals.
* New: **Turbo Spin** now opens a confirmation dialog ("Are you sure you want to use turbo spin, it will reveal all prizes instantly") before consuming spins. On confirm, every remaining spin (capped at 50 per click) resolves at once with no wheel animation, and a results list shows every outcome.
* New: **Auditable RNG** — every spin now uses HMAC-SHA256 with a server seed, client seed, and nonce; outcomes are logged to a new `wp_nera_stw_spin_audit` table for dispute resolution and future provably-fair UI.
* Change: the post-spin "You won!" modal now shows the segment's label (e.g. "Cash 20") instead of generic type-derived copy, matching the wheel slice and history sidebar.
* Fix: non-ASCII characters in segment labels (£, €, accented characters, emoji) round-trip cleanly through save — previously mangled by the `update_post_meta` + `wp_unslash` interaction.
* Polish: modal spacing improvements; turbo-results dialog enlarged for readability.
* Docs: new client-facing PDFs `docs/weight-explained.pdf` and `docs/spin-to-win-flow.pdf`.

= 1.1.7 =
* GitHub updates: align Plugin Update Checker with `nera-instant-win-threshold` — `main` branch, release list + semver tag order, and download the published `nera-spin-to-win-*.zip` release asset (fixes sites stuck when GitHub “latest” release is not the highest version).

= 1.1.4 =
* Release packaging aligned with `nera-instant-win-threshold`: `release.sh`, `build-wp-release-zip.php`, `readme.txt`, `plugin.json`; Dashboard → Updates icons via `assets/icon-128x128.png` and `assets/icon-256x256.png`.

= 1.1.3 =
* Maintenance and tooling alignment with other Nera plugins (release script parity; plugin icons for Updates screen).
