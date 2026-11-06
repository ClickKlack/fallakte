/**
 * Filterzustand der Folgenliste ↔ URL-Query (ohne Vue-Abhängigkeit, daher gut testbar).
 * Muss zu App\Queries\EpisodeFilters passen.
 */

export const DEFAULT_FILTERS = Object.freeze({
    series: null,
    team: null,
    broadcaster: null,
    status: 'all',
    year_from: null,
    year_to: null,
    q: null,
    sort: 'number',
    direction: 'asc',
    per_page: 50,
});

/** Felder, die die Auswahl einschränken (Sortierung und Seitengröße zählen nicht dazu). */
export const RESTRICTING_FIELDS = [
    'series',
    'team',
    'broadcaster',
    'status',
    'year_from',
    'year_to',
    'q',
];

const isEmpty = (value) => value === null || value === undefined || value === '';

/** Bringt Werte (z. B. aus dem Server oder Formularfeldern) in eine einheitliche Form. */
export function normalizeFilters(filters = {}) {
    const result = { ...DEFAULT_FILTERS };

    for (const key of Object.keys(DEFAULT_FILTERS)) {
        const value = filters[key];

        if (isEmpty(value)) {
            continue;
        }

        result[key] = ['team', 'year_from', 'year_to', 'per_page'].includes(key)
            ? Number(value)
            : typeof value === 'string'
              ? value.trim() || DEFAULT_FILTERS[key]
              : value;
    }

    return result;
}

/** URL-Query ohne Standardwerte und leere Felder – kurze, teilbare Links. */
export function toQuery(filters) {
    const normalized = normalizeFilters(filters);

    return Object.fromEntries(
        Object.entries(normalized).filter(
            ([key, value]) => !isEmpty(value) && value !== DEFAULT_FILTERS[key],
        ),
    );
}

/** Anzahl gesetzter einschränkender Filter (für das Abzeichen am Filter-Button). */
export function activeFilterCount(filters) {
    const normalized = normalizeFilters(filters);

    return RESTRICTING_FIELDS.filter((key) => normalized[key] !== DEFAULT_FILTERS[key]).length;
}

/** Setzt alle einschränkenden Filter zurück, Sortierung und Seitengröße bleiben. */
export function resetFilters(filters) {
    const normalized = normalizeFilters(filters);

    return {
        ...normalized,
        ...Object.fromEntries(RESTRICTING_FIELDS.map((k) => [k, DEFAULT_FILTERS[k]])),
    };
}

/**
 * Team-Auswahl: bei gewählter Reihe nur deren Teams, sonst alle mit Reihenname als Zusatz (hint).
 *
 * @param {Array<{id: number, name: string, series_slug: string}>} teams
 * @param {Array<{slug: string, name: string}>} series
 */
export function teamOptions(teams, series, seriesSlug = null) {
    const names = Object.fromEntries(series.map((s) => [s.slug, s.name]));

    return teams
        .filter((team) => !seriesSlug || team.series_slug === seriesSlug)
        .map((team) => ({
            value: team.id,
            label: team.name,
            hint: seriesSlug ? null : (names[team.series_slug] ?? team.series_slug),
        }));
}

/** Gehört das Team zur gewählten Reihe? (Sonst muss der Teamfilter beim Reihenwechsel weg.) */
export function teamMatchesSeries(teams, teamId, seriesSlug) {
    if (!teamId || !seriesSlug) {
        return true;
    }

    return teams.some((team) => team.id === teamId && team.series_slug === seriesSlug);
}

/** Nächster Sortierzustand beim Klick auf eine Spaltenüberschrift. */
export function nextSort(filters, column) {
    if (filters.sort === column) {
        return { sort: column, direction: filters.direction === 'asc' ? 'desc' : 'asc' };
    }

    // Datum zuerst absteigend (neueste oben), alles andere aufsteigend
    return { sort: column, direction: column === 'date' ? 'desc' : 'asc' };
}
