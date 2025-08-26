import { usePage } from '@inertiajs/react';

interface Translations {
    [key: string]: string;
}

interface PageProps {
    translations?: Translations;
}

export function useTranslations() {
    const { props } = usePage<PageProps>();
    const translations = props.translations || {};

    const t = (key: string, replacements?: Record<string, string | number>): string => {
        let translation = translations[key] || key;

        if (replacements) {
            Object.entries(replacements).forEach(([placeholder, value]) => {
                translation = translation.replace(
                    new RegExp(`:${placeholder}`, 'g'),
                    String(value)
                );
            });
        }

        return translation;
    };

    return { t };
}

// Shorthand export for convenience
export const t = (key: string, replacements?: Record<string, string | number>): string => {
    const { t: translate } = useTranslations();
    return translate(key, replacements);
};