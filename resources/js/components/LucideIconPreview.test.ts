import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import LucideIconPreview from './LucideIconPreview.vue';

function makeWrapper(props: {
    name: string | null | undefined;
    sizeClass?: string;
    withTooltip?: boolean;
}) {
    return mount(LucideIconPreview, { props });
}

describe('LucideIconPreview', () => {
    it('renders the matching lucide icon for a known kebab-case name', () => {
        const wrapper = makeWrapper({ name: 'trophy' });
        expect(wrapper.find('svg').exists()).toBe(true);
    });

    it('renders the matching lucide icon for a Pascal-case name', () => {
        const wrapper = makeWrapper({ name: 'Trophy' });
        expect(wrapper.find('svg').exists()).toBe(true);
    });

    it('renders the matching lucide icon for an underscore-separated name', () => {
        const wrapper = makeWrapper({ name: 'shield_check' });
        expect(wrapper.find('svg').exists()).toBe(true);
    });

    it('renders the fallback badge for an unknown name', () => {
        const wrapper = makeWrapper({
            name: 'definitely-not-an-icon',
            withTooltip: true,
        });
        const fallback = wrapper.find('span');
        expect(fallback.exists()).toBe(true);
        expect(fallback.attributes('title')).toContain(
            'Unknown icon: definitely-not-an-icon',
        );
    });

    it('renders the fallback badge for an empty name', () => {
        const wrapper = makeWrapper({ name: '', withTooltip: true });
        const fallback = wrapper.find('span');
        expect(fallback.exists()).toBe(true);
        expect(fallback.attributes('title')).toBe('No icon');
    });

    it('renders the fallback badge when name is null', () => {
        const wrapper = makeWrapper({ name: null });
        expect(wrapper.find('span').exists()).toBe(true);
        expect(wrapper.find('svg').exists()).toBe(true);
    });

    it('does not emit a title attribute when withTooltip is false', () => {
        const wrapper = makeWrapper({ name: 'trophy' });
        const icon = wrapper.find('svg');
        expect(icon.attributes('title')).toBeUndefined();
    });
});
