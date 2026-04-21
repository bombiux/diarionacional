import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  plugins: [
    {
      name: 'twig-php-reload',
      handleHotUpdate({ file, server }) {
        if (file.endsWith('.php') || file.endsWith('.twig')) {
          server.ws.send({ type: 'full-reload' });
        }
      }
    }
  ],
  root: '',
  base: './',
  build: {
    outDir: path.resolve(__dirname, './dist'),
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'src/main.js'),
      },
    },
  },
  server: {
    origin: 'http://localhost:3000',
    cors: true,
    strictPort: true,
    port: 3000,
    hmr: {
      host: 'localhost',
    },
  },
});