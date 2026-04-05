import { describe, expect, it } from 'vitest';
import { cn, toUrl } from './utils';

describe('cn', () => {
    it('merges class names', () => {
        expect(cn('foo', 'bar')).toBe('foo bar');
    });

    it('handles conditional classes', () => {
        expect(cn('base', false && 'hidden', 'visible')).toBe('base visible');
    });

    it('merges tailwind classes correctly', () => {
        expect(cn('p-4', 'p-2')).toBe('p-2');
    });

    it('handles empty input', () => {
        expect(cn()).toBe('');
    });

    it('handles undefined and null values', () => {
        expect(cn('foo', undefined, null, 'bar')).toBe('foo bar');
    });
});

describe('toUrl', () => {
    it('returns string href as-is', () => {
        expect(toUrl('/users')).toBe('/users');
    });

    it('extracts url from object href', () => {
        expect(toUrl({ url: '/events', method: 'get' } as any)).toBe('/events');
    });
});
