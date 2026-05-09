import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import * as fs from 'node:fs';

export default defineConfig({
    plugins: [
        laravel({
            hotFile: 'public/hot',
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: false,
        }),
        react(),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        // this is necessary for the "hot" file which contains the dev server url that laravel uses to attach the frontend to the vite server
        {
            name: 'patch-hot-file',
            enforce: 'post' as const,
            configureServer(server) {
                server.httpServer?.once('listening', () => {
                    const devUrl = process.env.VITE_DEV_URL;
                    if (devUrl) {
                        fs.writeFileSync('public/hot', `${devUrl}/vite`);
                    }
                });
            },
        },
    ],
    base: '/vite',
    server: {
        cors: true,
        host: '0.0.0.0',
        port: 80,
        allowedHosts: true,
        hmr: {
            host: process.env.VITE_DEV_URL?.replace(/^https?:\/\//, ''),
            protocol: 'wss',
            clientPort: 443,
        },
    },
    esbuild: {
        jsx: 'automatic',
    },
});
