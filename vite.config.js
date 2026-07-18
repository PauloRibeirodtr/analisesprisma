import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin-prisma.css',
                'resources/js/admin/contratacao-plano.js',
                'resources/js/admin/reativacao-assinatura.js',
                'resources/js/app.js',
            ],
            refresh: true,
        }),

        vue(),
    ],
});
