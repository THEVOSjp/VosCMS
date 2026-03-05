import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  root: 'resources',
  base: '/assets/',
  build: {
    outDir: '../assets',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        app: path.resolve(__dirname, 'resources/css/app.css'),
        admin: path.resolve(__dirname, 'resources/css/admin.css'),
      },
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'css/[name][extname]';
          }
          return 'assets/[name]-[hash][extname]';
        },
      },
    },
  },
  server: {
    origin: 'http://localhost:5173',
  },
});
