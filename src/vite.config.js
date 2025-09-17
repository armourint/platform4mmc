import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
  ],
  server: {
    host: '0.0.0.0',      // listen on all interfaces in the container
    port: 5173,
    strictPort: true,
    hmr: {
      host: 'localhost',  // what the BROWSER uses to reach the dev server
      port: 5173,
      protocol: 'ws',
    },
  },
})
