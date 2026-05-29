import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';
import { initializeTheme, updateTheme, useAppearance } from './useAppearance';

function setSystemPrefersDark(prefersDark: boolean) {
    Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: vi.fn().mockImplementation((query: string) => ({
            matches:
                query === '(prefers-color-scheme: dark)' ? prefersDark : false,
            media: query,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
        })),
    });
}

describe('updateTheme', () => {
    beforeEach(() => {
        document.documentElement.classList.remove('dark');
    });

    it('adds dark class when value is dark', () => {
        updateTheme('dark');
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('removes dark class when value is light', () => {
        document.documentElement.classList.add('dark');
        updateTheme('light');
        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });

    it('uses system preference when value is system', () => {
        Object.defineProperty(window, 'matchMedia', {
            writable: true,
            value: vi.fn().mockImplementation((query: string) => ({
                matches: query === '(prefers-color-scheme: dark)',
                media: query,
                addEventListener: vi.fn(),
                removeEventListener: vi.fn(),
            })),
        });

        updateTheme('system');
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('removes dark class for system light preference', () => {
        Object.defineProperty(window, 'matchMedia', {
            writable: true,
            value: vi.fn().mockImplementation((query: string) => ({
                matches: false,
                media: query,
                addEventListener: vi.fn(),
                removeEventListener: vi.fn(),
            })),
        });

        document.documentElement.classList.add('dark');
        updateTheme('system');
        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });
});

describe('initializeTheme', () => {
    beforeEach(() => {
        localStorage.clear();
        document.documentElement.classList.remove('dark');
    });

    it('applies the stored appearance and registers a system listener', () => {
        const addEventListener = vi.fn();
        Object.defineProperty(window, 'matchMedia', {
            writable: true,
            value: vi.fn().mockImplementation((query: string) => ({
                matches: query === '(prefers-color-scheme: dark)',
                media: query,
                addEventListener,
                removeEventListener: vi.fn(),
            })),
        });
        localStorage.setItem('appearance', 'dark');

        initializeTheme();

        expect(document.documentElement.classList.contains('dark')).toBe(true);
        expect(addEventListener).toHaveBeenCalledWith(
            'change',
            expect.any(Function),
        );
    });

    it('falls back to the system preference with no stored value', () => {
        setSystemPrefersDark(true);
        initializeTheme();
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });
});

describe('useAppearance', () => {
    beforeEach(() => {
        localStorage.clear();
        document.cookie = 'appearance=;path=/;max-age=0';
        document.documentElement.classList.remove('dark');
        setSystemPrefersDark(false);
        // Reset the shared module-level ref.
        useAppearance().appearance.value = 'system';
    });

    it('updateAppearance persists to localStorage + cookie and applies the theme', () => {
        const { appearance, updateAppearance } = useAppearance();

        updateAppearance('dark');

        expect(appearance.value).toBe('dark');
        expect(localStorage.getItem('appearance')).toBe('dark');
        expect(document.cookie).toContain('appearance=dark');
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('resolvedAppearance follows the system preference when set to system', () => {
        const { resolvedAppearance, updateAppearance } = useAppearance();

        // Toggle through a distinct value each time so the computed (which only
        // depends on `appearance`, not matchMedia) actually re-evaluates.
        setSystemPrefersDark(true);
        updateAppearance('dark');
        updateAppearance('system');
        expect(resolvedAppearance.value).toBe('dark');

        setSystemPrefersDark(false);
        updateAppearance('light');
        updateAppearance('system');
        expect(resolvedAppearance.value).toBe('light');
    });

    it('resolvedAppearance echoes an explicit light/dark choice', () => {
        const { resolvedAppearance, updateAppearance } = useAppearance();
        updateAppearance('light');
        expect(resolvedAppearance.value).toBe('light');
    });

    it('hydrates the shared ref from localStorage on mount', async () => {
        localStorage.setItem('appearance', 'dark');

        const Probe = defineComponent({
            setup() {
                const { appearance } = useAppearance();

                return { appearance };
            },
            template: '<span>{{ appearance }}</span>',
        });

        const wrapper = mount(Probe);
        // onMounted sets the ref after the initial render; flush the update.
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toBe('dark');
    });
});
