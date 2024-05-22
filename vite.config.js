import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    resolve: {
        alias: {
            '@laravel': path.resolve(__dirname, './resources/kenlight/node_modules'), // Adjust the path as needed
        },
    },
    plugins: [
        laravel({
            entries: [
                'resources/kenlight/src/main.jsx',
            ],
            refresh: true,
        }),
    ],
});
