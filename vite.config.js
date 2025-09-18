import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        outDir: 'public/build',   // 👈 asegura que se genere en /public/build
        manifest: true,           // 👈 obligatorio para Laravel en prod
        emptyOutDir: true,
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
    },
});
