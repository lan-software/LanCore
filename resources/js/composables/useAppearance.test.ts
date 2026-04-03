import { describe, expect, it, vi, beforeEach } from 'vitest';
import { updateTheme } from './useAppearance';

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
