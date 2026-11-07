import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import { useFilterSync } from '@/composables/useFilterSync.js';
import {
    activeFilterCount,
    DEFAULT_FILTERS,
    nextSort,
    normalizeFilters,
    resetFilters,
    teamMatchesSeries,
    teamOptions,
    toQuery,
} from '@/lib/filters.js';

const series = [
    { slug: 'tatort', name: 'Tatort' },
    { slug: 'polizeiruf-110', name: 'Polizeiruf 110' },
];
const teams = [
    { id: 1, name: 'Thiel und Boerne', series_slug: 'tatort' },
    { id: 2, name: 'König und Böwe', series_slug: 'polizeiruf-110' },
];

describe('filter values', () => {
    it('normalizes values from the server or form fields', () => {
        expect(
            normalizeFilters({ team: '12', year_from: '1990', q: '  Taxi ', per_page: '100' }),
        ).toEqual({
            ...DEFAULT_FILTERS,
            team: 12,
            year_from: 1990,
            q: 'Taxi',
            per_page: 100,
        });
        expect(normalizeFilters({ q: '   ', series: '' })).toEqual(DEFAULT_FILTERS);
    });

    it('builds short queries without defaults', () => {
        expect(toQuery(DEFAULT_FILTERS)).toEqual({});
        expect(
            toQuery({
                ...DEFAULT_FILTERS,
                series: 'tatort',
                status: 'unseen',
                sort: 'date',
                direction: 'desc',
            }),
        ).toEqual({
            series: 'tatort',
            status: 'unseen',
            sort: 'date',
            direction: 'desc',
        });
    });

    it('counts only restricting filters', () => {
        expect(activeFilterCount(DEFAULT_FILTERS)).toBe(0);
        expect(activeFilterCount({ ...DEFAULT_FILTERS, sort: 'title', per_page: 100 })).toBe(0);
        expect(
            activeFilterCount({
                ...DEFAULT_FILTERS,
                series: 'tatort',
                q: 'x',
                year_to: 2000,
                status: 'seen',
            }),
        ).toBe(4);
    });

    it('treats the seen date range as restricting filter', () => {
        const filters = normalizeFilters({ seen_from: '2024-01-01', seen_to: '' });

        expect(filters.seen_from).toBe('2024-01-01');
        expect(filters.seen_to).toBeNull();
        expect(toQuery(filters)).toEqual({ seen_from: '2024-01-01' });
        expect(activeFilterCount(filters)).toBe(1);
        expect(resetFilters(filters).seen_from).toBeNull();
    });

    it('reset keeps sorting and page size', () => {
        const reset = resetFilters({
            ...DEFAULT_FILTERS,
            series: 'tatort',
            team: 1,
            sort: 'date',
            direction: 'desc',
            per_page: 25,
        });

        expect(reset).toEqual({
            ...DEFAULT_FILTERS,
            sort: 'date',
            direction: 'desc',
            per_page: 25,
        });
    });
});

describe('team options', () => {
    it('lists all teams with series name when no series is chosen', () => {
        expect(teamOptions(teams, series)).toEqual([
            { value: 1, label: 'Thiel und Boerne', hint: 'Tatort' },
            { value: 2, label: 'König und Böwe', hint: 'Polizeiruf 110' },
        ]);
    });

    it('lists only teams of the chosen series', () => {
        expect(teamOptions(teams, series, 'polizeiruf-110')).toEqual([
            { value: 2, label: 'König und Böwe', hint: null },
        ]);
    });

    it('checks whether a team belongs to a series', () => {
        expect(teamMatchesSeries(teams, 1, 'tatort')).toBe(true);
        expect(teamMatchesSeries(teams, 1, 'polizeiruf-110')).toBe(false);
        expect(teamMatchesSeries(teams, null, 'tatort')).toBe(true);
        expect(teamMatchesSeries(teams, 1, null)).toBe(true);
    });
});

describe('sorting by column header', () => {
    it('switches column with sensible default direction', () => {
        expect(nextSort({ sort: 'number', direction: 'asc' }, 'title')).toEqual({
            sort: 'title',
            direction: 'asc',
        });
        expect(nextSort({ sort: 'number', direction: 'asc' }, 'date')).toEqual({
            sort: 'date',
            direction: 'desc',
        });
        expect(nextSort({ sort: 'number', direction: 'asc' }, 'seen_at')).toEqual({
            sort: 'seen_at',
            direction: 'desc',
        });
    });

    it('toggles direction on the same column', () => {
        expect(nextSort({ sort: 'title', direction: 'asc' }, 'title')).toEqual({
            sort: 'title',
            direction: 'desc',
        });
        expect(nextSort({ sort: 'title', direction: 'desc' }, 'title')).toEqual({
            sort: 'title',
            direction: 'asc',
        });
    });
});

describe('useFilterSync', () => {
    let navigate;

    beforeEach(() => {
        vi.useFakeTimers();
        navigate = vi.fn();
    });

    afterEach(() => vi.useRealTimers());

    it('starts with the server filters without navigating', () => {
        const { form } = useFilterSync({ series: 'tatort', status: 'seen' }, navigate, { teams });

        expect(form.series).toBe('tatort');
        expect(form.status).toBe('seen');
        expect(navigate).not.toHaveBeenCalled();
    });

    it('navigates immediately when a select changes', () => {
        const { form } = useFilterSync({}, navigate, { teams });

        form.status = 'unseen';

        expect(navigate).toHaveBeenCalledOnce();
        expect(navigate).toHaveBeenLastCalledWith({ status: 'unseen' });
    });

    it('waits for a typing pause before searching', () => {
        const { form } = useFilterSync({}, navigate, { teams, debounceMs: 300 });

        form.q = 'Ta';
        form.q = 'Taxi';
        vi.advanceTimersByTime(299);
        expect(navigate).not.toHaveBeenCalled();

        vi.advanceTimersByTime(1);
        expect(navigate).toHaveBeenCalledOnce();
        expect(navigate).toHaveBeenLastCalledWith({ q: 'Taxi' });
    });

    it('a select change sends a pending search right away', () => {
        const { form } = useFilterSync({}, navigate, { teams, debounceMs: 300 });

        form.q = 'Taxi';
        form.series = 'tatort';
        vi.advanceTimersByTime(1000);

        expect(navigate).toHaveBeenCalledOnce();
        expect(navigate).toHaveBeenLastCalledWith({ series: 'tatort', q: 'Taxi' });
    });

    it('removes a team that does not belong to the new series', async () => {
        const { form } = useFilterSync({ team: 1 }, navigate, { teams });

        form.series = 'polizeiruf-110';
        await nextTick();

        expect(form.team).toBeNull();
        expect(navigate).toHaveBeenCalledOnce();
        expect(navigate).toHaveBeenLastCalledWith({ series: 'polizeiruf-110' });
    });

    it('keeps a team that belongs to the new series', async () => {
        const { form } = useFilterSync({ team: 1 }, navigate, { teams });

        form.series = 'tatort';
        await nextTick();

        expect(form.team).toBe(1);
        expect(navigate).toHaveBeenLastCalledWith({ series: 'tatort', team: 1 });
    });

    it('takes over server filters (back/forward) without navigating again', () => {
        const { form, setFromServer } = useFilterSync({}, navigate, { teams });

        setFromServer({ series: 'tatort', sort: 'title' });

        expect(form.series).toBe('tatort');
        expect(form.sort).toBe('title');
        expect(navigate).not.toHaveBeenCalled();
    });

    it('update applies several values with a single navigation', () => {
        const { update } = useFilterSync({}, navigate, { teams });

        update({ sort: 'date', direction: 'desc' });

        expect(navigate).toHaveBeenCalledOnce();
        expect(navigate).toHaveBeenLastCalledWith({ sort: 'date', direction: 'desc' });
    });
});
