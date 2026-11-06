/**
 * Kalenderlogik für den Datumspicker (ohne Vue-Abhängigkeit, daher gut testbar).
 * Datumswerte sind Strings im Format YYYY-MM-DD; Monate zählen 0–11 wie bei Date.
 */

export const MONTHS = [
    'Januar',
    'Februar',
    'März',
    'April',
    'Mai',
    'Juni',
    'Juli',
    'August',
    'September',
    'Oktober',
    'November',
    'Dezember',
];

export const WEEKDAYS = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

const pad = (n) => String(n).padStart(2, '0');

/** Baut aus Jahr, Monat (0–11) und Tag einen ISO-String. */
export function toIso(year, month, day) {
    return `${year}-${pad(month + 1)}-${pad(day)}`;
}

/** Zerlegt einen ISO-String in { year, month (0–11), day } oder null. */
export function parseIso(iso) {
    const match = typeof iso === 'string' ? iso.match(/^(\d{4})-(\d{2})-(\d{2})$/) : null;

    return match ? { year: +match[1], month: +match[2] - 1, day: +match[3] } : null;
}

/** Liegt das Datum innerhalb der Grenzen? (ISO-Strings lassen sich direkt vergleichen.) */
export function isWithin(iso, min = null, max = null) {
    return (!min || iso >= min) && (!max || iso <= max);
}

/**
 * Raster eines Monats: 6 Wochen à 7 Tage, beginnend am Montag.
 * Tage aus Vor- und Folgemonat sind mit inMonth = false markiert.
 *
 * @returns {Array<{iso: string, day: number, inMonth: boolean, weekend: boolean}>}
 */
export function monthGrid(year, month) {
    const first = new Date(year, month, 1);
    // getDay(): 0 = Sonntag … 6 = Samstag → Montag soll Index 0 sein
    const offset = (first.getDay() + 6) % 7;
    const start = new Date(year, month, 1 - offset);

    return Array.from({ length: 42 }, (_, i) => {
        const date = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
        const weekday = (date.getDay() + 6) % 7;

        return {
            iso: toIso(date.getFullYear(), date.getMonth(), date.getDate()),
            day: date.getDate(),
            inMonth: date.getMonth() === month,
            weekend: weekday >= 5,
        };
    });
}

/** Verschiebt { year, month } um delta Monate. */
export function addMonths({ year, month }, delta) {
    const date = new Date(year, month + delta, 1);

    return { year: date.getFullYear(), month: date.getMonth() };
}

/** Liste der Jahre zwischen min und max (absteigend, neueste zuerst). */
export function yearRange(min, max) {
    const from = parseIso(min)?.year ?? 1970;
    const to = parseIso(max)?.year ?? new Date().getFullYear();

    return Array.from({ length: Math.max(0, to - from + 1) }, (_, i) => to - i);
}
