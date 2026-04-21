import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import 'flag-icons/css/flag-icons.min.css';
import '../css/app.css';
import DemoShell from '@/components/demo/DemoShell.vue';
import { initializeTheme } from '@/composables/useAppearance';
import i18n from '@/i18n';
import type { AvailableLocale } from '@/i18n';
import { bootCookieConsent } from '@/lib/cookieConsent';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

type CookiesMessages = {
    banner: {
        title: string;
        description: string;
        acceptAll: string;
        acceptNecessary: string;
        showPreferences: string;
        close: string;
    };
    preferences: {
        title: string;
        acceptAll: string;
        acceptNecessary: string;
        savePreferences: string;
        close: string;
        serviceCounter: string;
        necessary: { title: string; description: string };
        analytics: { title: string; description: string };
    };
};

function buildConsentTranslations(): Record<string, object> {
    const byLocale: Record<string, object> = {};

    for (const locale of i18n.global.availableLocales) {
        const messages = i18n.global.getLocaleMessage(locale) as {
            cookies?: CookiesMessages;
        };

        if (!messages.cookies) {
            continue;
        }

        const c = messages.cookies;

        byLocale[locale] = {
            consentModal: {
                title: c.banner.title,
                description: c.banner.description,
                acceptAllBtn: c.banner.acceptAll,
                acceptNecessaryBtn: c.banner.acceptNecessary,
                showPreferencesBtn: c.banner.showPreferences,
                closeIconLabel: c.banner.close,
            },
            preferencesModal: {
                title: c.preferences.title,
                acceptAllBtn: c.preferences.acceptAll,
                acceptNecessaryBtn: c.preferences.acceptNecessary,
                savePreferencesBtn: c.preferences.savePreferences,
                closeIconLabel: c.preferences.close,
                serviceCounterLabel: c.preferences.serviceCounter,
                sections: [
                    {
                        title: c.preferences.necessary.title,
                        description: c.preferences.necessary.description,
                        linkedCategory: 'necessary',
                    },
                    {
                        title: c.preferences.analytics.title,
                        description: c.preferences.analytics.description,
                        linkedCategory: 'analytics',
                    },
                ],
            },
        };
    }

    return byLocale;
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const shared = props.initialPage.props as {
            locale?: AvailableLocale;
            auth?: { user?: { locale?: AvailableLocale } };
            analytics?: {
                plausible: { domain: string; src: string };
            } | null;
            cookiePreferences?: {
                categories?: string[];
                revision?: number;
            } | null;
        };
        const locale = shared.auth?.user?.locale ?? shared.locale;

        if (locale && i18n.global.availableLocales.includes(locale)) {
            i18n.global.locale.value = locale;
        }

        const csrfToken =
            document
                .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';

        bootCookieConsent({
            analytics: shared.analytics ?? null,
            initialPreferences: shared.cookiePreferences ?? null,
            isAuthenticated: !!shared.auth?.user,
            csrfToken,
            locale: (i18n.global.locale.value as string) ?? 'en',
            translations: buildConsentTranslations(),
        });

        createApp({
            render: () => h(DemoShell, null, { default: () => h(App, props) }),
        })
            .use(plugin)
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
