import { describe, expect, it } from 'vitest';
import { groupTeams, matchesTeam, mergeTargets, periodLabel, sortTeams } from '@/lib/teams.js';

const tatort = { slug: 'tatort', name: 'Tatort' };
const polizeiruf = { slug: 'polizeiruf-110', name: 'Polizeiruf 110' };
const team = (id, name, series, aired, seen, first, last, extra = {}) => ({
    id,
    name,
    series,
    aired_count: aired,
    seen_count: seen,
    first_year: first,
    last_year: last,
    aliases: [],
    ...extra,
});

const teams = [
    team(1, 'Thiel und Boerne', tatort, 46, 46, 2002, 2026, {
        city: 'Münster',
        aliases: ['Boerne und Thiel'],
    }),
    team(2, 'Batic und Leitmayr', tatort, 99, 10, 1991, 2026, { city: 'München' }),
    team(3, 'Borowski und Şahin', tatort, 10, 5, 2020, 2024),
    team(4, 'Grandjean und Ott', tatort, 0, 0, null, null),
    team(5, 'König und Böwe', polizeiruf, 7, 7, 2023, 2026),
];

describe('matchesTeam', () => {
    it('searches name, city, investigators and aliases ignoring accents', () => {
        expect(matchesTeam(teams[0], 'münster')).toBe(true);
        expect(matchesTeam(teams[0], 'boerne und thiel')).toBe(true);
        expect(matchesTeam(teams[2], 'sahin')).toBe(true);
        expect(matchesTeam(teams[1], 'boerne')).toBe(false);
        expect(matchesTeam(teams[1], '')).toBe(true);
    });
});

describe('sortTeams', () => {
    const ids = (sort) => sortTeams(teams.slice(0, 4), sort).map((t) => t.id);

    it('sorts by name', () => expect(ids('name')).toEqual([2, 3, 4, 1]));
    it('sorts by number of aired episodes', () => expect(ids('episodes')).toEqual([2, 1, 3, 4]));
    it('sorts by progress, teams without aired episodes last', () =>
        expect(ids('progress')).toEqual([1, 3, 2, 4]));
    it('sorts by unseen episodes', () => expect(ids('unseen')).toEqual([2, 3, 4, 1]));
    it('sorts by most recent, without airing last', () =>
        expect(ids('recent')).toEqual([2, 1, 3, 4]));
});

describe('groupTeams', () => {
    it('groups by series in the given order', () => {
        const groups = groupTeams(teams, [tatort, polizeiruf]);

        expect(groups.map((g) => [g.series.slug, g.teams.length])).toEqual([
            ['tatort', 4],
            ['polizeiruf-110', 1],
        ]);
    });

    it('filters by series and search and drops empty groups', () => {
        expect(
            groupTeams(teams, [tatort, polizeiruf], { series: 'polizeiruf-110' }).map(
                (g) => g.series.slug,
            ),
        ).toEqual(['polizeiruf-110']);
        expect(
            groupTeams(teams, [tatort, polizeiruf], { query: 'böwe' }).map((g) => g.series.slug),
        ).toEqual(['polizeiruf-110']);
        expect(groupTeams(teams, [tatort, polizeiruf], { query: 'derrick' })).toEqual([]);
    });
});

describe('periodLabel', () => {
    it('shows a range, a single year or nothing', () => {
        expect(periodLabel(teams[0])).toBe('2002–2026');
        expect(periodLabel(team(9, 'X', tatort, 1, 0, 2024, 2024))).toBe('2024');
        expect(periodLabel(teams[3])).toBeNull();
    });
});

describe('mergeTargets', () => {
    it('offers only other teams of the same series', () => {
        expect(mergeTargets(teams, teams[2]).map((o) => o.value)).toEqual([2, 4, 1]);
        expect(mergeTargets(teams, teams[4])).toEqual([]);
    });
});
