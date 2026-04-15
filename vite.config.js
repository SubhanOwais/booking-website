import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            '@prime': path.resolve(__dirname, 'resources/js/Primevue'),
            '@img': path.resolve(__dirname, 'public/images'),
        },
    },
});
