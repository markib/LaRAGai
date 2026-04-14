import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.tsx'],
      refresh: true,
    }),
    react(),
  ],
  server: {
    hmr: false,
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: 'resources/js/tests/setupTests.ts',
    include: ['resources/js/tests/**/*.test.tsx'],
  },
});
