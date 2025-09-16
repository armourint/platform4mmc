import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  server: {
    host: true,           // 0.0.0.0
    port: 5173,
    strictPort: true,
    hmr: {
      host: 'localhost',  // or your dev host/IP if not localhost
      port: 5173,
    },
    watch: {
      usePolling: true
    }
  },
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
  ],
})
