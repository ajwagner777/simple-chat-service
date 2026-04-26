import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        watch: {
            usePolling: process.env.CHOKIDAR_USEPOLLING === 'true',
            interval: Number(process.env.CHOKIDAR_INTERVAL || 100),
        },
        hmr: {
            host: process.env.VITE_HMR_HOST || 'localhost',
            port: Number(process.env.VITE_HMR_PORT || 5173),
        },
    },
});
