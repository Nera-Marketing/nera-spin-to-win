/**
 * Read the Spin To Win palette from the island root.
 *
 * The wheel is drawn on a <canvas> / inline SVG, so it can't consume CSS
 * variables passively — it needs resolved colour strings. We read the base
 * tokens (--stw-brand / --stw-accent) from #nera-spin-root, where a var()
 * default is already substituted into the computed value. Do NOT read the
 * color-mix() derived tokens (--stw-brand-dark etc.) here — getComputedStyle
 * returns those unresolved.
 */
export function readThemeColors() {
  const root =
    (typeof document !== 'undefined' && document.getElementById('nera-spin-root')) ||
    (typeof document !== 'undefined' ? document.documentElement : null);
  const style = root ? getComputedStyle(root) : null;
  const pick = (name, fallback) =>
    ((style && style.getPropertyValue(name)) || fallback).trim();

  return {
    brand: pick('--stw-brand', '#c0172e'),
    accent: pick('--stw-accent', '#fbbf24'),
    text: pick('--stw-text', '#0d0d1b'),
    // Wheel widget palette — fixed red/gold defaults, overridable per site.
    wheelA: pick('--stw-wheel-segment-a', '#c0172e'),
    wheelB: pick('--stw-wheel-segment-b', '#e8950a'),
    wheelRim: pick('--stw-wheel-rim', '#f59e0b'),
    wheelBulb: pick('--stw-wheel-bulb', '#fbbf24'),
    wheelPointer: pick('--stw-wheel-pointer', '#dc2626'),
    wheelLabel: pick('--stw-wheel-label', '#ffffff'),
    fontHeading: pick(
      '--font-heading',
      "'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
    ),
  };
}
