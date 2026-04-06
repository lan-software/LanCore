import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { configDefaults } from 'vitest/config';

const isVitest = !!process.env.VITEST;
const skipWayfinder = !!process.env.WAYFINDER_SKIP;

export default defineConfig({
    plugins: [
        !isVitest &&
            laravel({
                input: ['resources/js/app.ts'],
                ssr: 'resources/js/ssr.ts',
                refresh: true,
            }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        !isVitest &&
            !skipWayfinder &&
            wayfinder({
                formVariants: true,
            }),
    ],
    test: {
        globals: true,
        environment: 'jsdom',
        exclude: [...configDefaults.exclude, 'tests/e2e/**'],
        include: ['resources/js/**/*.{test,spec}.{ts,js}'],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'lcov'],
            include: ['resources/js/**/*.{ts,vue}'],
            exclude: ['resources/js/wayfinder/**', 'resources/js/actions/**', 'resources/js/routes/**'],
        },
        resolve: {
            alias: {
                '@': '/resources/js',
            },
        },
    },
});
