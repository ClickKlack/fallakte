import { describe, expect, it } from 'vitest';
import { formatDate, formatDateShort, percent, todayIso } from '@/lib/format.js';

describe('formatDate', () => {
    it('formats ISO dates in German notation', () => {
        expect(formatDate('2026-10-11')).toBe('11.10.2026');
        expect(formatDate('1970-11-29T00:00:00Z')).toBe('29.11.1970');
    });

    it('returns an empty string for missing or invalid values', () => {
        expect(formatDate(null)).toBe('');
        expect(formatDate(undefined)).toBe('');
        expect(formatDate('')).toBe('');
        expect(formatDate('11.10.2026')).toBe('');
    });
});

describe('formatDateShort', () => {
    it('uses a two-digit year', () => {
        expect(formatDateShort('2026-10-11')).toBe('11.10.26');
        expect(formatDateShort('1999-01-02')).toBe('02.01.99');
        expect(formatDateShort(null)).toBe('');
    });
});

describe('todayIso', () => {
    it('uses the local date with leading zeros', () => {
        expect(todayIso(new Date(2026, 0, 5, 23, 59))).toBe('2026-01-05');
        expect(todayIso(new Date(2026, 10, 6, 0, 1))).toBe('2026-11-06');
    });
});

describe('percent', () => {
    it('calculates rounded percentages', () => {
        expect(percent(512, 1344)).toBe(38);
        expect(percent(1, 3)).toBe(33);
        expect(percent(2, 3)).toBe(67);
    });

    it('shows 100 only when everything is done', () => {
        expect(percent(46, 46)).toBe(100);
        expect(percent(1343, 1344)).toBe(99);
        expect(percent(50, 40)).toBe(100);
    });

    it('shows at least 1 once something is done', () => {
        expect(percent(1, 1344)).toBe(1);
    });

    it('handles empty or invalid input', () => {
        expect(percent(0, 0)).toBe(0);
        expect(percent(0, 12)).toBe(0);
        expect(percent(5, 0)).toBe(0);
        expect(percent(-1, 10)).toBe(0);
        expect(percent(NaN, 10)).toBe(0);
    });
});
