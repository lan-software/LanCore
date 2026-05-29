import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import { configDefaults } from 'vitest/config';

const projectRoot = path.dirname(fileURLToPath(import.meta.url));

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
    // Vitest reads `resolve.alias` at the top level (the previous nesting
    // inside `test.resolve` was silently ignored, which is fine for `vite build`
    // since TS path mapping covers that path — but JSDOM tests of SFCs that
    // pull `@/...` imports failed at transform time).
    resolve: {
        alias: {
            '@': path.resolve(projectRoot, 'resources/js'),
        },
    },
    test: {
        globals: true,
        environment: 'jsdom',
        exclude: [...configDefaults.exclude, 'tests/e2e/**'],
        include: ['resources/js/**/*.{test,spec}.{ts,js}'],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'lcov'],
            include: ['resources/js/**/*.{ts,vue}'],
            exclude: [
                // Wayfinder-generated route/action bindings (verified upstream).
                'resources/js/wayfinder/**',
                'resources/js/actions/**',
                'resources/js/routes/**',
                // Inertia page components are exercised by Playwright e2e and
                // backend feature tests, not Vitest. Their `.ts` siblings
                // (e.g. `columns.ts`) stay in scope and ARE unit-tested.
                'resources/js/pages/**/*.vue',
                // Type-only declarations contribute no executable lines.
                'resources/js/types/**',
                'resources/js/**/*.d.ts',
                // Bootstrap entrypoints — not unit-testable in isolation.
                'resources/js/app.ts',
                'resources/js/ssr.ts',
            ],
        },
    },
});
