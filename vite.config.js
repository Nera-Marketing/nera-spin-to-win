import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig({
  plugins: [react()],

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
