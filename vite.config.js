import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

/**
 * Patches the spin-wheel library at build time.
 *
 * The package's "browser" export resolves to the *minified* dist bundle
 * (variable names/quoting differ from src/wheel.js), so match loosely with
 * regexes instead of exact strings.
 *
 * 1. Bold labels — spin-wheel builds `ctx.font` as `${size}px ${family}`,
 *    which can't express a weight. Prefix `700 `.
 * 2. Upright flip — after the per-slice rotates, flip any label whose absolute
 *    rotation falls in the upside-down band (90–270°) and swap textAlign so
 *    reading direction stays center → rim.
 */
function spinWheelLabelPatches() {
  const fontPattern =
    /(\w+)\.font\s*=\s*this\._itemLabelFontSize\s*\+\s*(["'])px \2\s*\+\s*this\.itemLabelFont/;
  // Vite resolves spin-wheel via "import" → src/wheel.js:
  //   ctx.rotate(util.degRad(angle + Constants.arcAdjust));
  //   ctx.rotate(util.degRad(this.itemLabelRotation));
  const rotateSrcPattern =
    /(\w+)\.rotate\((\w+)\.degRad\((\w+)\s*\+\s*(\w+)\.arcAdjust\)\);\s*\1\.rotate\(\2\.degRad\(this\.itemLabelRotation\)\);/;
  // Fallback if a bundler resolves the pre-minified dist instead:
  //   e.rotate(m(_+-90)),e.rotate(m(this.itemLabelRotation))
  const rotateMinPattern =
    /(\w+)\.rotate\((\w+)\((\w+)\+-90\)\),\1\.rotate\(\2\(this\.itemLabelRotation\)\)/;

  function uprightFlip(ctxVar, angleExpr) {
    return (
      `{let __r=((${angleExpr}+this.itemLabelRotation)%360+360)%360;` +
      `if(__r>90&&__r<270){${ctxVar}.rotate(Math.PI);` +
      `${ctxVar}.textAlign=${ctxVar}.textAlign==="left"?"right":` +
      `${ctxVar}.textAlign==="right"?"left":${ctxVar}.textAlign}}`
    );
  }

  return {
    name: 'spin-wheel-label-patches',
    transform(code, id) {
      if (!id.includes('spin-wheel')) {
        return null;
      }

      let next = code;
      let changed = false;

      if (fontPattern.test(next)) {
        next = next.replace(
          fontPattern,
          (_match, ctxVar, quote) =>
            `${ctxVar}.font='700 '+this._itemLabelFontSize+${quote}px ${quote}+this.itemLabelFont`,
        );
        changed = true;
      }

      if (rotateSrcPattern.test(next)) {
        next = next.replace(
          rotateSrcPattern,
          (_match, ctxVar, utilVar, angleVar, constVar) =>
            `${ctxVar}.rotate(${utilVar}.degRad(${angleVar} + ${constVar}.arcAdjust));` +
            `${ctxVar}.rotate(${utilVar}.degRad(this.itemLabelRotation));` +
            uprightFlip(ctxVar, `${angleVar}+${constVar}.arcAdjust`),
        );
        changed = true;
      } else if (rotateMinPattern.test(next)) {
        next = next.replace(
          rotateMinPattern,
          (_match, ctxVar, degFn, angleVar) =>
            `${ctxVar}.rotate(${degFn}(${angleVar}+-90)),${ctxVar}.rotate(${degFn}(this.itemLabelRotation)),` +
            uprightFlip(ctxVar, `${angleVar}+-90`),
        );
        changed = true;
      }

      if (!changed) {
        return null;
      }

      return { code: next, map: null };
    },
  };
}

export default defineConfig({
  plugins: [react(), spinWheelLabelPatches()],

  build: {
    outDir: 'dist',
    manifest: true,
    rollupOptions: {
      input: {
        'spin-to-win': resolve(__dirname, 'src/spin-to-win.js'),
      },
    },
  },

  server: {
    cors: true,
    host: true,
    port: 5174,
    strictPort: true,
    hmr: { host: 'localhost' },
    watch: {
      include: ['**/*.php'],
      ignored: ['**/node_modules/**', '**/dist/**'],
    },
  },
});
