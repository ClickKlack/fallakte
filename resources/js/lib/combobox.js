/**
 * Suche für Eingabefelder mit Vorschlägen (ohne Vue-Abhängigkeit, daher gut testbar).
 */

/** Vereinheitlicht Text für den Vergleich: klein, ohne Akzente („Şahin“ → „sahin“). */
export function normalize(text) {
    return String(text ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/ß/g, 'ss')
        .toLowerCase()
        .trim();
}

/**
 * Passende Optionen zur Eingabe, höchstens `limit` Stück.
 * Reihenfolge: Treffer am Anfang des Textes, dann am Wortanfang, dann irgendwo; innerhalb gleich
 * guter Treffer bleibt die ursprüngliche Reihenfolge erhalten.
 *
 * @param {Array<{value: any, label: string}>} options
 */
export function filterOptions(options, query, limit = 8) {
    const needle = normalize(query);

    if (needle === '') {
        return options.slice(0, limit);
    }

    const rank = (label) => {
        const haystack = normalize(label);

        if (haystack.startsWith(needle)) return 0;
        if (haystack.split(/[\s,/–-]+/).some((word) => word.startsWith(needle))) return 1;
        if (haystack.includes(needle)) return 2;
        return -1;
    };

    return options
        .map((option, index) => ({ option, index, rank: rank(option.label) }))
        .filter((entry) => entry.rank >= 0)
        .sort((a, b) => a.rank - b.rank || a.index - b.index)
        .slice(0, limit)
        .map((entry) => entry.option);
}

/** Option, deren Text genau der Eingabe entspricht (für Übernahme beim Verlassen des Feldes). */
export function exactMatch(options, query) {
    const needle = normalize(query);

    return needle === ''
        ? null
        : (options.find((option) => normalize(option.label) === needle) ?? null);
}
