/**
 * Suche, Sortierung und Gruppierung der Team-Übersicht (ohne Vue-Abhängigkeit, daher gut testbar).
 */
import { normalize } from '@/lib/combobox.js';
import { percent } from '@/lib/format.js';

export const TEAM_SORTS = ['name', 'episodes', 'progress', 'unseen', 'recent'];

/** Trifft die Suche auf Name, Stadt, Ermittler oder eine Wikipedia-Schreibweise? */
export function matchesTeam(team, query) {
    const needle = normalize(query);

    if (needle === '') {
        return true;
    }

    return [team.name, team.city, team.investigators, ...(team.aliases ?? [])].some((text) =>
        normalize(text).includes(needle),
    );
}

const byName = (a, b) => a.name.localeCompare(b.name, 'de');

const comparators = {
    name: byName,
    // Meiste Folgen zuerst
    episodes: (a, b) => b.aired_count - a.aired_count || byName(a, b),
    // Höchster Fortschritt zuerst; Teams ohne ausgestrahlte Folgen ans Ende
    progress: (a, b) =>
        (b.aired_count > 0) - (a.aired_count > 0) ||
        percent(b.seen_count, b.aired_count) - percent(a.seen_count, a.aired_count) ||
        b.aired_count - a.aired_count ||
        byName(a, b),
    // Meiste ungesehene Folgen zuerst
    unseen: (a, b) => b.aired_count - b.seen_count - (a.aired_count - a.seen_count) || byName(a, b),
    // Zuletzt ausgestrahlt zuerst; ohne Ausstrahlung ans Ende
    recent: (a, b) => (b.last_year ?? -1) - (a.last_year ?? -1) || byName(a, b),
};

export function sortTeams(teams, sort = 'name') {
    return [...teams].sort(comparators[sort] ?? byName);
}

/**
 * Gefilterte, sortierte Teams je Reihe (Reihenfolge der Reihen wie übergeben; leere Gruppen entfallen).
 *
 * @returns {Array<{series: object, teams: Array}>}
 */
export function groupTeams(teams, seriesList, { query = '', series = null, sort = 'name' } = {}) {
    return seriesList
        .filter((s) => !series || s.slug === series)
        .map((s) => ({
            series: s,
            teams: sortTeams(
                teams.filter((team) => team.series.slug === s.slug && matchesTeam(team, query)),
                sort,
            ),
        }))
        .filter((group) => group.teams.length > 0);
}

/** „1970–1980“, „2024“ oder null, wenn noch nichts ausgestrahlt wurde. */
export function periodLabel(team) {
    if (!team.first_year) {
        return null;
    }

    return team.first_year === team.last_year
        ? String(team.first_year)
        : `${team.first_year}–${team.last_year}`;
}

/** Mögliche Zielteams beim Zusammenführen: gleiche Reihe, nicht das Team selbst. */
export function mergeTargets(teams, source) {
    return sortTeams(
        teams.filter((team) => team.series.slug === source.series.slug && team.id !== source.id),
        'name',
    ).map((team) => ({ value: team.id, label: team.name, hint: periodLabel(team) }));
}
