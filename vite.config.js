import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

/**
 * spin-wheel builds `ctx.font` as `${size}px ${family}`, which cannot express
 * font-weight. Prefix 700 so canvas labels render true bold.
 */
function spinWheelBoldLabels() {
  const needle = "ctx.font = this._itemLabelFontSize + 'px ' + this.itemLabelFont;";
  const replacement =
    "ctx.font = '700 ' + this._itemLabelFontSize + 'px ' + this.itemLabelFont;";
  return {
    name: 'spin-wheel-bold-labels',
    transform(code, id) {
      if (!id.includes('spin-wheel') || !id.endsWith('wheel.js')) {
        return null;
      }
      if (!code.includes(needle)) {
        return null;
      }
      return {
        code: code.replace(needle, replacement),
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
