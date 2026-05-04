/**
 * Reads the `activeTheme` Inertia shared prop and exposes the bits the
 * `<ThemeProvider>` component needs to inject light + dark CSS-variable
 * overrides into the document head.
 *
 * @see docs/mil-std-498/SDD.md §5.11
 */
import { usePage } from '@inertiajs/vue3';
import type { ComputedRef } from 'vue';
import { computed } from 'vue';
import type { ThemeContext } from '@/types/theme';

export type UseEventThemeReturn = {
    activeTheme: ComputedRef<ThemeContext | null>;
    lightConfig: ComputedRef<Record<string, string>>;
    darkConfig: ComputedRef<Record<string, string>>;
};

export function useEventTheme(): UseEventThemeReturn {
    const page = usePage();

    const activeTheme = computed<ThemeContext | null>(
        () => (page.props?.activeTheme as ThemeContext | null) ?? null,
    );

    const lightConfig = computed<Record<string, string>>(
        () => activeTheme.value?.lightConfig ?? {},
    );

    const darkConfig = computed<Record<string, string>>(
        () => activeTheme.value?.darkConfig ?? {},
    );

    return {
        activeTheme,
        lightConfig,
        darkConfig,
    };
}
