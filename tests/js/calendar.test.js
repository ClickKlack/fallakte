import { describe, expect, it } from 'vitest';
import { addMonths, isWithin, monthGrid, parseIso, toIso, yearRange } from '@/lib/calendar.js';

describe('monthGrid', () => {
    it('has six full weeks starting on monday', () => {
        const grid = monthGrid(2026, 10); // November 2026, der 1. ist ein Sonntag

        expect(grid).toHaveLength(42);
        expect(grid[0]).toEqual({ iso: '2026-10-26', day: 26, inMonth: false, weekend: false });
        expect(grid[6]).toEqual({ iso: '2026-11-01', day: 1, inMonth: true, weekend: true });
        expect(grid.filter((d) => d.inMonth)).toHaveLength(30);
        expect(grid[41].iso).toBe('2026-12-06');
    });

    it('starts directly when the first is a monday', () => {
        expect(monthGrid(2026, 5)[0].iso).toBe('2026-06-01'); // 1. Juni 2026 ist ein Montag
    });

    it('handles leap years and year boundaries', () => {
        expect(monthGrid(2024, 1).filter((d) => d.inMonth)).toHaveLength(29);
        expect(monthGrid(2023, 1).filter((d) => d.inMonth)).toHaveLength(28);
        expect(monthGrid(2026, 11).at(-1).iso.startsWith('2027-01')).toBe(true);
    });

    it('marks saturday and sunday as weekend', () => {
        const week = monthGrid(2026, 5).slice(0, 7);

        expect(week.map((d) => d.weekend)).toEqual([false, false, false, false, false, true, true]);
    });
});

describe('helpers', () => {
    it('converts between parts and ISO strings', () => {
        expect(toIso(2026, 0, 5)).toBe('2026-01-05');
        expect(parseIso('2026-01-05')).toEqual({ year: 2026, month: 0, day: 5 });
        expect(parseIso('05.01.2026')).toBeNull();
        expect(parseIso(null)).toBeNull();
    });

    it('checks limits', () => {
        expect(isWithin('2026-11-06', '1970-01-01', '2026-11-06')).toBe(true);
        expect(isWithin('2026-11-07', '1970-01-01', '2026-11-06')).toBe(false);
        expect(isWithin('1969-12-31', '1970-01-01', null)).toBe(false);
        expect(isWithin('2026-11-07')).toBe(true);
    });

    it('moves months across years', () => {
        expect(addMonths({ year: 2026, month: 0 }, -1)).toEqual({ year: 2025, month: 11 });
        expect(addMonths({ year: 2026, month: 11 }, 1)).toEqual({ year: 2027, month: 0 });
    });

    it('lists years newest first', () => {
        expect(yearRange('2020-05-01', '2023-01-01')).toEqual([2023, 2022, 2021, 2020]);
    });
});
