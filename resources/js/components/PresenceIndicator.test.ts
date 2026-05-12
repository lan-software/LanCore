import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { createI18n } from 'vue-i18n';
import PresenceIndicator from './PresenceIndicator.vue';

const i18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: {
        en: {
            presence: {
                status: {
                    active: 'Active',
                    idle: 'Idle',
                    offline: 'Offline',
                },
            },
        },
    },
});

function makeWrapper(props: {
    status: 'active' | 'idle' | 'offline';
    size?: 'sm' | 'md';
    withLabel?: boolean;
}) {
    return mount(PresenceIndicator, {
        props,
        global: { plugins: [i18n] },
    });
}

describe('PresenceIndicator', () => {
    it('renders a green dot with a tick for active', () => {
        const wrapper = makeWrapper({ status: 'active' });
        const dot = wrapper.find('[role="img"]');
        expect(dot.classes().some((c) => c.startsWith('bg-green-'))).toBe(true);
        expect(dot.attributes('aria-label')).toBe('Active');
        expect(dot.find('svg').exists()).toBe(true);
    });

    it('renders a yellow dot without a tick for idle', () => {
        const wrapper = makeWrapper({ status: 'idle' });
        const dot = wrapper.find('[role="img"]');
        expect(dot.classes().some((c) => c.startsWith('bg-yellow-'))).toBe(
            true,
        );
        expect(dot.find('svg').exists()).toBe(false);
    });

    it('renders a grey dot for offline', () => {
        const wrapper = makeWrapper({ status: 'offline' });
        const dot = wrapper.find('[role="img"]');
        expect(dot.classes().some((c) => c.startsWith('bg-zinc-'))).toBe(true);
        expect(dot.attributes('aria-label')).toBe('Offline');
    });

    it('hides the label by default', () => {
        const wrapper = makeWrapper({ status: 'active' });
        expect(wrapper.text()).not.toContain('Active');
    });

    it('shows the label when withLabel is true', () => {
        const wrapper = makeWrapper({ status: 'idle', withLabel: true });
        expect(wrapper.text()).toContain('Idle');
    });

    it('applies a smaller dot for size=sm', () => {
        const wrapper = makeWrapper({ status: 'active', size: 'sm' });
        const dot = wrapper.find('[role="img"]');
        expect(dot.classes()).toContain('h-2');
        expect(dot.classes()).toContain('w-2');
    });
});
