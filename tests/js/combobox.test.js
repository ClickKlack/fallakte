import { describe, expect, it } from 'vitest';
import { exactMatch, filterOptions, normalize } from '@/lib/combobox.js';

const teams = [
    { value: 1, label: 'Batic und Leitmayr' },
    { value: 2, label: 'Borowski und Sahin' },
    { value: 3, label: 'Borowski und Şahin' },
    { value: 4, label: 'Thiel und Boerne' },
    { value: 5, label: 'Şahin und Krieger' },
    { value: 6, label: 'Odenthal und Kopper' },
];

describe('normalize', () => {
    it('ignores case and accents', () => {
        expect(normalize('Şahin')).toBe('sahin');
        expect(normalize('  Flückiger ')).toBe('fluckiger');
        expect(normalize('Weiß')).toBe('weiss');
        expect(normalize(null)).toBe('');
    });
});

describe('filterOptions', () => {
    it('finds matches regardless of accents and case', () => {
        expect(filterOptions(teams, 'SAHIN').map((o) => o.value)).toEqual([5, 2, 3]);
    });

    it('ranks start of text before start of word before anywhere', () => {
        const options = [
            { value: 'a', label: 'Kopper und Odenthal' }, // Wortanfang
            { value: 'b', label: 'Odenthal und Kopper' }, // Textanfang
            { value: 'c', label: 'Schnodenthaler' }, // irgendwo
        ];

        expect(filterOptions(options, 'odenthal').map((o) => o.value)).toEqual(['b', 'a', 'c']);
    });

    it('limits the number of suggestions', () => {
        expect(filterOptions(teams, '', 3).map((o) => o.value)).toEqual([1, 2, 3]);
        expect(filterOptions(teams, 'und', 2)).toHaveLength(2);
    });

    it('returns nothing without match', () => {
        expect(filterOptions(teams, 'Derrick')).toEqual([]);
    });

    it('works for years by prefix', () => {
        const years = Array.from({ length: 57 }, (_, i) => ({
            value: 2026 - i,
            label: String(2026 - i),
        }));

        expect(filterOptions(years, '198', 20).map((o) => o.value)).toEqual([
            1989, 1988, 1987, 1986, 1985, 1984, 1983, 1982, 1981, 1980,
        ]);
    });
});

describe('exactMatch', () => {
    it('finds the option with exactly this text', () => {
        expect(exactMatch(teams, 'thiel und boerne')?.value).toBe(4);
        expect(exactMatch(teams, 'Thiel')).toBeNull();
        expect(exactMatch(teams, '')).toBeNull();
    });
});
