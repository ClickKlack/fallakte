/**
 * Vorschläge für zusammengehörige Teams (ohne Vue-Abhängigkeit, daher gut testbar).
 *
 * Idee: Teams derselben Reihe mit einem gemeinsamen Ermittler-Nachnamen gehören wahrscheinlich
 * zur selben Einheit (wechselnde Partner, abweichende Schreibweisen). Akzente werden ignoriert
 * („Sahin“ = „Şahin“). Tippfehler-Toleranz gibt es bewusst nicht: Sie erzeugte mit echten Daten
 * falsche Paare („Gächter“/„Wächter“, „Borowski“/„Zorowski“); echte Tippfehler wie
 * „Dieckmann“/„Diekmann“ landen über den gemeinsamen Partner („Hinrichs“) trotzdem in einer Gruppe.
 */
import { normalize } from '@/lib/combobox.js';

// Wörter, die keine Namen sind (Dienstgrade, Zusätze)
const STOPWORDS = new Set([
    'kommissar',
    'kommissarin',
    'hauptkommissar',
    'hauptkommissarin',
    'oberkommissar',
    'gastauftritt',
    'und',
    'a',
    'd',
    'mit',
    'dr',
    'prof',
]);

/**
 * Nachnamen aus einem Teamnamen: „Peter Fuchs, Vera Arndt“ → [{key: 'fuchs', label: 'Fuchs'}, …].
 * Je Person zählt das letzte Wort; Klammern und Zusätze wie „a. D.“ werden ignoriert.
 */
export function surnames(teamName) {
    const cleaned = String(teamName ?? '')
        .replace(/\([^)]*\)/g, ' ')
        .replace(/\ba\.\s*D\./gi, ' ');

    return cleaned
        .split(/\s*(?:,|\/|&|;|\s–\s|\bund\b)\s*/u)
        .map((person) =>
            person
                .trim()
                .split(/\s+/)
                .filter((w) => !STOPWORDS.has(normalize(w).replace(/\./g, ''))),
        )
        .map((words) => words.at(-1))
        .filter((word) => word && normalize(word).length >= 3)
        .map((word) => ({
            key: normalize(word).replace(/[^a-z]/g, ''),
            label: word.replace(/[^\p{L}-]/gu, ''),
        }))
        .filter(
            (name, index, all) => name.key && all.findIndex((n) => n.key === name.key) === index,
        );
}

const signature = (teams) =>
    teams
        .map((t) => t.id)
        .sort((a, b) => a - b)
        .join('-');
const weight = (team) => (team.aired_count ?? 0) * 1000 + (team.episodes_count ?? 0);

/**
 * Gruppen wahrscheinlich zusammengehöriger Teams je Reihe.
 *
 * @returns {Array<{key: string, label: string, series: string, teams: Array, target: object, signature: string}>}
 *          teams sortiert mit dem vorgeschlagenen Ziel (meiste Folgen) zuerst
 */
export function mergeSuggestions(teams) {
    const groups = [];

    for (const seriesSlug of [...new Set(teams.map((t) => t.series.slug))]) {
        const inSeries = teams.filter((t) => t.series.slug === seriesSlug);
        const named = inSeries.map((team) => ({ team, names: surnames(team.name) }));

        const byName = new Map();
        for (const { team, names } of named) {
            names.forEach((name, position) => {
                const key = name.key;
                const entry = byName.get(key) ?? {
                    key,
                    label: name.label,
                    teams: new Map(),
                    position: 0,
                };
                entry.teams.set(team.id, team);
                entry.position += position; // vorne genannte Namen (meist die Hauptfigur) bevorzugen
                byName.set(key, entry);
            });
        }

        for (const entry of byName.values()) {
            if (entry.teams.size < 2) continue;
            const members = [...entry.teams.values()].sort(
                (a, b) => weight(b) - weight(a) || a.name.localeCompare(b.name, 'de'),
            );
            groups.push({
                key: `${seriesSlug}:${entry.key}`,
                position: entry.position,
                label: entry.label,
                series: seriesSlug,
                teams: members,
                target: members[0],
                signature: signature(members),
            });
        }
    }

    // Gruppen weglassen, die vollständig in einer größeren Gruppe derselben Reihe enthalten sind
    const contained = (small, big) =>
        small !== big &&
        small.series === big.series &&
        small.teams.length <= big.teams.length &&
        small.teams.every((t) => big.teams.some((o) => o.id === t.id)) &&
        (small.teams.length < big.teams.length ||
            small.position > big.position ||
            (small.position === big.position && small.key > big.key));

    return groups
        .filter((group) => !groups.some((other) => contained(group, other)))
        .sort((a, b) => a.series.localeCompare(b.series) || a.label.localeCompare(b.label, 'de'));
}
