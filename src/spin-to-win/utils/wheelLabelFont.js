/**
 * Canvas label fonts for spin-wheel.
 *
 * spin-wheel builds `ctx.font` as `${size}px ${itemLabelFont}`, so a CSS
 * font-weight cannot be placed before the size. We register a dedicated
 * family whose glyphs are Poppins 700, exposed at weight 400 for that
 * family name — canvas then draws true bold without an invalid font string.
 */

const BOLD_FAMILY = 'NeraSTWWheelLabel';

/** Poppins 700 woff2 (Google Fonts v24) — latin + latin-ext. */
const POPPINS_700_SOURCES = [
  "url(https://fonts.gstatic.com/s/poppins/v24/pxiByp8kv8JHgFVrLCz7Z1xlFd2JQEk.woff2) format('woff2')",
  "url(https://fonts.gstatic.com/s/poppins/v24/pxiByp8kv8JHgFVrLCz7Z1JlFd2JQEl8qw.woff2) format('woff2')",
].join(', ');

let registerPromise = null;

function primaryFamily(fontHeading) {
  const raw = String(fontHeading || '').trim();
  if (!raw) {
    return 'Poppins';
  }
  // First family in a CSS font-family stack, strip quotes.
  const first = raw.split(',')[0].trim().replace(/^['"]|['"]$/g, '');
  return first || 'Poppins';
}

function ensureBoldLabelFace() {
  if (typeof document === 'undefined' || !document.fonts) {
    return Promise.resolve();
  }
  if (document.fonts.check(`16px "${BOLD_FAMILY}"`)) {
    return Promise.resolve();
  }
  if (registerPromise) {
    return registerPromise;
  }

  registerPromise = (async () => {
    const face = new FontFace(BOLD_FAMILY, POPPINS_700_SOURCES, {
      style: 'normal',
      weight: '400',
      display: 'swap',
    });
    try {
      await face.load();
      document.fonts.add(face);
    } catch {
      // Fall through to theme heading stack if the remote face fails.
      registerPromise = null;
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

  await ensureBoldLabelFace();

  return `'${BOLD_FAMILY}', ${heading}`;
}
