import { describe, expect, it } from 'vitest';
import { columnPath, columnWidth, labelStep, niceTicks } from '@/lib/chart.js';

describe('niceTicks', () => {
    it('uses round 1-2-5 steps up to at least the maximum', () => {
        expect(niceTicks(37)).toEqual([0, 10, 20, 30, 40]);
        expect(niceTicks(40)).toEqual([0, 10, 20, 30, 40]);
        expect(niceTicks(7)).toEqual([0, 2, 4, 6, 8]);
        expect(niceTicks(1)).toEqual([0, 0.5, 1]);
        expect(niceTicks(1344)).toEqual([0, 500, 1000, 1500]);
    });

    it('handles empty data', () => {
        expect(niceTicks(0)).toEqual([0, 1]);
        expect(niceTicks(NaN)).toEqual([0, 1]);
    });
});

describe('labelStep', () => {
    it('shows every label when there is room', () => {
        expect(labelStep(10, 800)).toBe(1);
    });

    it('thins labels to round steps on narrow screens', () => {
        expect(labelStep(57, 1100)).toBe(5);
        expect(labelStep(57, 330)).toBe(10);
        expect(labelStep(57, 600)).toBe(5);
    });
});

describe('columnPath', () => {
    it('rounds the top and keeps the baseline square', () => {
        expect(columnPath(10, 20, 8, 30)).toBe('M10,50 V24 Q10,20 14,20 H14 Q18,20 18,24 V50 Z');
    });

    it('shrinks the radius for short columns and draws nothing for zero', () => {
        expect(columnPath(0, 98, 10, 2)).toBe('M0,100 V100 Q0,98 2,98 H8 Q10,98 10,100 V100 Z');
        expect(columnPath(0, 100, 10, 0)).toBe('');
    });
});

describe('columnWidth', () => {
    it('caps the width and keeps a gap', () => {
        expect(columnWidth(60)).toBe(24);
        expect(columnWidth(10)).toBe(8);
        expect(columnWidth(2)).toBe(1);
    });
});
