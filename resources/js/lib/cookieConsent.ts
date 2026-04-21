import * as CookieConsent from 'vanilla-cookieconsent';
import 'vanilla-cookieconsent/dist/cookieconsent.css';
import { loadPlausible, unloadPlausible } from '@/lib/analytics';

type AnalyticsConfig = {
    plausible: {
        domain: string;
        src: string;
    };
} | null;

type SavedPreferences = {
    categories?: string[];
    revision?: number;
} | null;

type BootOptions = {
    analytics: AnalyticsConfig;
    initialPreferences: SavedPreferences;
    isAuthenticated: boolean;
    csrfToken: string;
    locale: string;
    translations: Record<string, CookieConsent.Translation>;
};

const REVISION = 1;

function persistToBackend(csrfToken: string): void {
    const accepted = CookieConsent.getUserPreferences().acceptedCategories;

    fetch('/cookie-preferences', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            categories: accepted,
            revision: REVISION,
        }),
    }).catch(() => {
        /* best-effort; localStorage is still authoritative for next load */
    });
}

function applyAnalytics(analytics: AnalyticsConfig): void {
    if (!analytics) {
        return;
    }

    if (CookieConsent.acceptedCategory('analytics')) {
        loadPlausible(analytics.plausible);
    } else {
        unloadPlausible();
    }
}

export async function bootCookieConsent(options: BootOptions): Promise<void> {
    // Hydrate localStorage from the server copy (logged-in users only) if the
    // client has no record yet — lets consent follow the account across devices.
    if (
        options.isAuthenticated &&
        options.initialPreferences?.categories &&
        !localStorage.getItem('cc_cookie')
    ) {
        localStorage.setItem(
            'cc_cookie',
            JSON.stringify({
                categories: options.initialPreferences.categories,
                revision: options.initialPreferences.revision ?? REVISION,
                data: null,
                consentId: '',
                consentTimestamp: new Date().toISOString(),
                lastConsentTimestamp: new Date().toISOString(),
                expirationTime: Date.now() + 1000 * 60 * 60 * 24 * 182,
            }),
        );
    }

    await CookieConsent.run({
        guiOptions: {
            consentModal: {
                layout: 'box inline',
                position: 'bottom right',
                flipButtons: false,
                equalWeightButtons: false,
            },
            preferencesModal: {
                layout: 'box',
                position: 'right',
                flipButtons: false,
                equalWeightButtons: true,
            },
        },
        revision: REVISION,
        categories: {
            necessary: {
                enabled: true,
                readOnly: true,
            },
            analytics: {
                enabled: false,
                autoClear: {
                    cookies: [{ name: /^_pk_/ }, { name: 'plausible_ignore' }],
                },
                services: options.analytics
                    ? {
                          plausible: {
                              label: 'Plausible Analytics',
                          },
                      }
                    : {},
            },
        },
        language: {
            default: options.locale,
            autoDetect: 'document',
            translations: options.translations,
        },
        onFirstConsent: () => {
            applyAnalytics(options.analytics);

            if (options.isAuthenticated) {
                persistToBackend(options.csrfToken);
            }
        },
        onConsent: () => {
            applyAnalytics(options.analytics);
        },
        onChange: () => {
            applyAnalytics(options.analytics);

            if (options.isAuthenticated) {
                persistToBackend(options.csrfToken);
            }
        },
    });
}

export function showCookiePreferences(): void {
    CookieConsent.showPreferences();
}

export function showCookieBanner(): void {
    CookieConsent.show(true);
}
