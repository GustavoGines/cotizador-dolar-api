import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    tailwindcss(), // 👈 activa Tailwind 4 en Vite
  ],
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    rollupOptions: {
      input: ['resources/css/app.css', 'resources/js/app.js'],
    },
  },
})
