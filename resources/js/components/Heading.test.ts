import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Heading from './Heading.vue';

describe('Heading', () => {
    it('renders the title', () => {
        const wrapper = mount(Heading, {
            props: { title: 'Hello LanCore' },
        });

        expect(wrapper.find('h2').text()).toBe('Hello LanCore');
    });

    it('renders the description when provided', () => {
        const wrapper = mount(Heading, {
            props: { title: 'Events', description: 'Browse upcoming events' },
        });

        expect(wrapper.find('p').text()).toBe('Browse upcoming events');
    });

    it('hides the description when not provided', () => {
        const wrapper = mount(Heading, {
            props: { title: 'Venues' },
        });

        expect(wrapper.find('p').exists()).toBe(false);
    });

    it('applies small variant classes', () => {
        const wrapper = mount(Heading, {
            props: { title: 'Small', variant: 'small' },
        });

        expect(wrapper.find('h2').classes()).toContain('text-base');
    });
});
