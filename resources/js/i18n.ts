import { createI18n } from 'vue-i18n';
import de from './locales/de.json';
import en from './locales/en.json';
import es from './locales/es.json';
import fr from './locales/fr.json';

export type MessageSchema = typeof en;
export type AvailableLocale = 'en' | 'de' | 'fr' | 'es';

const defaultLocale = (import.meta.env.VITE_DEFAULT_LOCALE as AvailableLocale) || 'en';

const i18n = createI18n<[MessageSchema], AvailableLocale>({
    legacy: false,
    locale: defaultLocale,
    fallbackLocale: 'en',
    messages: {
        en,
        de,
        fr,
        es,
    },
});

export default i18n;
