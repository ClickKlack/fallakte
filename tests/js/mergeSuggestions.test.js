import { describe, expect, it } from 'vitest';
import { mergeSuggestions, surnames } from '@/lib/mergeSuggestions.js';

const keys = (name) => surnames(name).map((n) => n.key);
const tatort = { slug: 'tatort' };
const polizeiruf = { slug: 'polizeiruf-110' };
let id = 0;
const team = (name, series = polizeiruf, aired = 1) => ({
    id: ++id,
    name,
    series,
    aired_count: aired,
    episodes_count: aired,
});

describe('surnames', () => {
    it('takes the last word of each person', () => {
        expect(keys('Peter Fuchs, Vera Arndt, Lutz Subras')).toEqual(['fuchs', 'arndt', 'subras']);
        expect(keys('Brasch und Köhler')).toEqual(['brasch', 'kohler']);
        expect(keys('Bukow und König und Brasch und Drexler')).toEqual([
            'bukow',
            'konig',
            'brasch',
            'drexler',
        ]);
        expect(keys('Batic und Leitmayr / Faber')).toEqual(['batic', 'leitmayr', 'faber']);
    });

    it('ignores additions, parentheses and duplicates', () => {
        expect(keys('Delius a. D.')).toEqual(['delius']);
        expect(keys('Kommissar Markowitz')).toEqual(['markowitz']);
        expect(keys('Stein, Berg (Gastauftritt Fuchs)')).toEqual(['stein', 'berg']);
        expect(keys('Münster – Thiel/Boerne')).toEqual(['munster', 'thiel', 'boerne']);
        expect(keys('WDR/WDR')).toEqual(['wdr']);
    });

    it('keeps a readable label', () => {
        expect(surnames('Borowski und Şahin').map((n) => n.label)).toEqual(['Borowski', 'Şahin']);
    });
});

describe('mergeSuggestions', () => {
    it('groups teams sharing an investigator and proposes the biggest as target', () => {
        const teams = [
            team('Brasch und Drexler', polizeiruf, 3),
            team('Brasch und Köhler', polizeiruf, 6),
            team('Brasch', polizeiruf, 11),
            team('Tauber und Obermaier', polizeiruf, 17),
        ];

        const groups = mergeSuggestions(teams);

        expect(groups).toHaveLength(1);
        expect(groups[0].label).toBe('Brasch');
        expect(groups[0].target.name).toBe('Brasch');
        expect(groups[0].teams.map((t) => t.name)).toEqual([
            'Brasch',
            'Brasch und Köhler',
            'Brasch und Drexler',
        ]);
    });

    it('matches spellings with accents; typo variants are found via a shared partner', () => {
        const groups = mergeSuggestions([
            team('Borowski und Sahin', tatort, 10),
            team('Borowski und Şahin', tatort, 3),
            team('Hinrichs und Dieckmann', polizeiruf, 1),
            team('Hinrichs und Diekmann', polizeiruf, 3),
        ]);

        expect(groups.map((g) => g.label)).toEqual(['Hinrichs', 'Borowski']);
    });

    it('does not pair similar but different names', () => {
        expect(
            mergeSuggestions([
                team('Bienzle und Gächter', tatort),
                team('Murot und Wächter', tatort),
                team('Borowski', tatort),
                team('Roiter und Zorowski', tatort),
                team('Schmidt', tatort),
                team('Falke und Schmitt', tatort),
            ]),
        ).toEqual([]);
    });

    it('never groups across series', () => {
        expect(mergeSuggestions([team('Schmidt', tatort), team('Schmidt', polizeiruf)])).toEqual(
            [],
        );
    });

    it('drops groups contained in a bigger group', () => {
        const groups = mergeSuggestions([
            team('Bukow und König', polizeiruf, 22),
            team('König und Böwe', polizeiruf, 7),
            team('Bukow und König und Brasch und Drexler', polizeiruf, 2),
        ]);

        // „Bukow“-Gruppe (2 Teams) steckt komplett in der „König“-Gruppe (3 Teams)
        expect(groups.map((g) => g.label)).toEqual(['König']);
    });

    it('keeps only one of two groups with identical members', () => {
        const groups = mergeSuggestions([
            team('Thiel und Boerne', tatort, 46),
            team('Boerne und Thiel', tatort, 1),
        ]);

        expect(groups).toHaveLength(1);
    });

    it('signature identifies a group independent of order', () => {
        const a = team('Lenz', polizeiruf);
        const b = team('Lenz und Bergemann', polizeiruf);

        expect(mergeSuggestions([a, b])[0].signature).toBe(mergeSuggestions([b, a])[0].signature);
    });
});
