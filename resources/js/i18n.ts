import { createI18n } from 'vue-i18n';
import de from './locales/de.json';
import en from './locales/en.json';
import es from './locales/es.json';
import fr from './locales/fr.json';
import ko from './locales/ko.json';
import nds from './locales/nds.json';
import sv from './locales/sv.json';
import sxu from './locales/sxu.json';
import tlh from './locales/tlh.json';
import uk from './locales/uk.json';

export type MessageSchema = typeof en;
export type AvailableLocale =
    | 'en'
    | 'de'
    | 'fr'
    | 'es'
    | 'sv'
    | 'uk'
    | 'ko'
    | 'tlh'
    | 'nds'
    | 'sxu';

const defaultLocale =
    (import.meta.env.VITE_DEFAULT_LOCALE as AvailableLocale) || 'en';

const i18n = createI18n<[MessageSchema], AvailableLocale>({
    legacy: false,
    locale: defaultLocale,
    fallbackLocale: 'en',
    messages: {
        en,
        de,
        fr,
        es,
        sv,
        uk,
        ko,
        tlh,
        nds,
        sxu,
    },
});

export default i18n;
