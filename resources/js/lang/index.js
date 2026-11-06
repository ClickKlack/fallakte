import de from './de.js';

/**
 * Liefert den Text zu einem Schlüssel in Punktnotation (z. B. "auth.login")
 * und ersetzt Platzhalter der Form :name. Unbekannte Schlüssel werden
 * unverändert zurückgegeben, damit fehlende Übersetzungen sichtbar bleiben.
 */
export function translate(messages, key, replacements = {}) {
    const value = key
        .split('.')
        .reduce(
            (node, part) => (node !== null && typeof node === 'object' ? node[part] : undefined),
            messages,
        );

    if (typeof value !== 'string') {
        return key;
    }

    return Object.entries(replacements).reduce(
        (text, [name, replacement]) => text.replaceAll(`:${name}`, String(replacement)),
        value,
    );
}

/** Übersetzt mit den deutschen Standardtexten. */
export function t(key, replacements = {}) {
    return translate(de, key, replacements);
}

/** Vue-Plugin: stellt $t in allen Templates bereit. */
export const i18n = {
    install(app) {
        app.config.globalProperties.$t = t;
    },
};
