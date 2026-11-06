// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { reactive } from 'vue';
import { i18n } from '@/lang';
import EpisodeFilterBar from '@/Components/Episodes/EpisodeFilterBar.vue';
import { DEFAULT_FILTERS } from '@/lib/filters.js';

const options = {
    series: [{ slug: 'tatort', name: 'Tatort' }],
    teams: [],
    broadcasters: ['NDR'],
    statuses: ['all', 'seen', 'unseen', 'announced'],
    sorts: ['number', 'date', 'title', 'team'],
    per_page: [25, 50, 100],
    years: { min: 1970, max: 2026 },
};

const mountBar = (form) =>
    mount(EpisodeFilterBar, {
        props: { form: reactive(form), options },
        global: { plugins: [i18n] },
    });

describe('EpisodeFilterBar', () => {
    it('shows a clear button for the search only when text is entered', () => {
        expect(
            mountBar({ ...DEFAULT_FILTERS })
                .find('[data-test="clear-search"]')
                .exists(),
        ).toBe(false);
        expect(
            mountBar({ ...DEFAULT_FILTERS, q: 'Taxi' })
                .find('[data-test="clear-search"]')
                .exists(),
        ).toBe(true);
    });

    it('clear button asks to clear the search', async () => {
        const wrapper = mountBar({ ...DEFAULT_FILTERS, q: 'Taxi' });

        await wrapper.get('[data-test="clear-search"]').trigger('click');

        expect(wrapper.emitted('clear-search')).toHaveLength(1);
    });
});
