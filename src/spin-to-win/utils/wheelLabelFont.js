/**
 * Canvas label fonts for spin-wheel.
 *
 * spin-wheel builds `ctx.font` as `${size}px ${itemLabelFont}`, so a CSS
 * font-weight cannot be placed before the size. We register a dedicated
 * family whose glyphs are Poppins 700, exposed at weight 400 for that
 * family name — canvas then draws true bold without an invalid font string.
 *
 * Do NOT gate on document.fonts.check(family) — Chromium often returns true
 * for unknown families (fallback), which skipped registration and left labels
 * at Poppins 400.
 */

const BOLD_FAMILY = 'NeraSTWWheelLabel';

/** Poppins 700 latin woff2 (Google Fonts v24). */
const POPPINS_700_SRC =
  "url(https://fonts.gstatic.com/s/poppins/v24/pxiByp8kv8JHgFVrLCz7Z1xlFd2JQEk.woff2) format('woff2')";

let registerPromise = null;

function primaryFamily(fontHeading) {
  const raw = String(fontHeading || '').trim();
  if (!raw) {
    return 'Poppins';
  }
  const first = raw.split(',')[0].trim().replace(/^['"]|['"]$/g, '');
  return first || 'Poppins';
}

function ensureBoldLabelFace() {
  if (typeof document === 'undefined' || !document.fonts) {
    return Promise.resolve(false);
  }
  if (registerPromise) {
    return registerPromise;
  }

  registerPromise = (async () => {
    try {
      const face = new FontFace(BOLD_FAMILY, POPPINS_700_SRC, {
        style: 'normal',
        weight: '400',
        display: 'swap',
      });
      await face.load();
      document.fonts.add(face);
      // Ensure the face is usable for canvas before callers create the wheel.
      await document.fonts.load(`48px "${BOLD_FAMILY}"`);
      return true;
    } catch {
      registerPromise = null;
      return false;
    }
  })();

  return registerPromise;
}

/**
 * @param {string} fontHeading CSS font-family stack from the theme.
 * @returns {Promise<string>} Font stack safe for spin-wheel itemLabelFont.
 */
export async function resolveBoldWheelLabelFont(fontHeading) {
  const heading =
    fontHeading ||
    "'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
  const family = primaryFamily(heading);

  if (typeof document !== 'undefined' && document.fonts) {
    try {
      await document.fonts.load(`700 48px "${family}"`);
    } catch {
      // Theme may not have loaded this family yet; bold face still helps.
    }
  }

  const ok = await ensureBoldLabelFace();
  if (!ok) {
    // Last resort: still prefer a bold-capable stack name; browser may synthesize.
    return heading;
  }

  return `'${BOLD_FAMILY}', ${heading}`;
}

export { BOLD_FAMILY };
