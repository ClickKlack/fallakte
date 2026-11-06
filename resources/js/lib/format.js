/**
 * Formatierungs- und Rechenhilfen für die Oberfläche (ohne Vue-Abhängigkeit, daher gut testbar).
 */

/** "2026-10-11" → "11.10.2026"; leere oder ungültige Werte → "". */
export function formatDate(isoDate) {
    if (typeof isoDate !== 'string') {
        return '';
    }

    const match = isoDate.match(/^(\d{4})-(\d{2})-(\d{2})/);

    return match ? `${match[3]}.${match[2]}.${match[1]}` : '';
}

/** "2026-10-11" → "11.10.26" (kurz, für schmale Bildschirme); leere oder ungültige Werte → "". */
export function formatDateShort(isoDate) {
    const long = formatDate(isoDate);

    return long ? long.slice(0, 6) + long.slice(8) : '';
}

/** Heutiges Datum im Format YYYY-MM-DD (lokale Zeit, nicht UTC). */
export function todayIso(now = new Date()) {
    const pad = (n) => String(n).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

/**
 * Fortschritt in ganzen Prozent, begrenzt auf 0–100.
 * 100 % nur, wenn wirklich alles erledigt ist; ein Rest unter 1 % zeigt 99 %.
 */
export function percent(done, total) {
    if (!Number.isFinite(done) || !Number.isFinite(total) || total <= 0 || done <= 0) {
        return 0;
    }

    if (done >= total) {
        return 100;
    }

    return Math.min(99, Math.max(1, Math.round((done / total) * 100)));
}
