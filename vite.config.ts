import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import viteBasicSslPlugin from '@vitejs/plugin-basic-ssl';
import * as os from 'node:os';
function getIPv4(): string | undefined {
    const ifaces = os.networkInterfaces();
    for (const entries of Object.values(ifaces)) {
        for (const i of entries ?? []) {
            if (i.family === 'IPv4' && !i.internal) {
                return i.address;

            }
        }
    }
    return undefined;
}

const viteIP =  getIPv4() || '127.0.0.1';
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: false,
        }),
        viteBasicSslPlugin({
            certDir: "./certs",
            domains: [process.env.APP_URL ?? "localhost", viteIP],
            name: "laravel-react-starter-kit",
        }),
        react(),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    server: {
        cors: true,
        host: viteIP,
        port: 5173,
        strictPort: true,
        hmr: {
            host: viteIP,
            protocol: 'wss',
            port: 5173
        },
    },
    esbuild: {
        jsx: 'automatic',
    },
});
