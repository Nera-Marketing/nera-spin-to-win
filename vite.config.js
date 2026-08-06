import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

/**
 * spin-wheel builds `ctx.font` as `${size}px ${itemLabelFont}`, which can't
 * express a weight (weight must come *before* the size in a CSS font
 * shorthand). Patch the library's font-assignment line to prefix `700 ` so
 * prize labels render true bold.
 *
 * The package's "browser" export resolves to the *minified* dist bundle
 * (variable names/quoting differ from src/wheel.js), so match loosely with a
 * regex instead of an exact string.
 */
function spinWheelBoldLabels() {
  const pattern =
    /(\w+)\.font\s*=\s*this\._itemLabelFontSize\s*\+\s*(["'])px \2\s*\+\s*this\.itemLabelFont/;
  return {
    name: 'spin-wheel-bold-labels',
    transform(code, id) {
      if (!id.includes('spin-wheel') || !pattern.test(code)) {
        return null;
      }
      return {
        code: code.replace(
          pattern,
          (_match, ctxVar, quote) =>
            `${ctxVar}.font='700 '+this._itemLabelFontSize+${quote}px ${quote}+this.itemLabelFont`,
        ),
        map: null,
      };
    },
  };
}

export default defineConfig({
  plugins: [react(), spinWheelBoldLabels()],

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
