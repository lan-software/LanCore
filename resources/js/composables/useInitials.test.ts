import { describe, expect, it } from 'vitest';
import { getInitials, useInitials } from './useInitials';

describe('getInitials', () => {
    it('returns empty string for undefined input', () => {
        expect(getInitials()).toBe('');
    });

    it('returns empty string for empty string', () => {
        expect(getInitials('')).toBe('');
    });

    it('returns single initial for one name', () => {
        expect(getInitials('Alice')).toBe('A');
    });

    it('returns first and last initials for two names', () => {
        expect(getInitials('Alice Smith')).toBe('AS');
    });

    it('returns first and last initials for three names', () => {
        expect(getInitials('Alice Marie Smith')).toBe('AS');
    });

    it('uppercases lowercase input', () => {
        expect(getInitials('alice smith')).toBe('AS');
    });

    it('trims whitespace', () => {
        expect(getInitials('  Alice Smith  ')).toBe('AS');
    });
});

describe('useInitials', () => {
    it('returns getInitials function', () => {
        const { getInitials: fn } = useInitials();
        expect(fn('Test User')).toBe('TU');
    });
});
