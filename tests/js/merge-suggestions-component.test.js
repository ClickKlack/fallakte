// @vitest-environment happy-dom
import { beforeEach, describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { i18n } from '@/lang';
import MergeSuggestions from '@/Components/Teams/MergeSuggestions.vue';

const polizeiruf = { slug: 'polizeiruf-110', name: 'Polizeiruf 110' };
const team = (id, name, count) => ({
    id,
    name,
    series: polizeiruf,
    aired_count: count,
    episodes_count: count,
    aliases: [],
});
const teams = [
    team(1, 'Brasch', 11),
    team(2, 'Brasch und Köhler', 6),
    team(3, 'Brasch und Drexler', 3),
    team(4, 'Tauber und Obermaier', 17),
];

const mountList = (props = {}) =>
    mount(MergeSuggestions, {
        props: { teams, series: [polizeiruf], ...props },
        global: { plugins: [i18n] },
    });

describe('MergeSuggestions', () => {
    beforeEach(() => localStorage.clear());

    it('shows the group with the biggest team as target', async () => {
        const wrapper = mountList();
        await wrapper.get('button[aria-expanded]').trigger('click');

        expect(wrapper.find('[data-group="Brasch"]').exists()).toBe(true);
        expect(wrapper.text()).toContain('Ziel');
        expect(wrapper.find('[data-test="suggest-merge-1"]').exists()).toBe(false);
        expect(wrapper.find('[data-test="suggest-merge-2"]').text()).toBe('→ in „Brasch“');
    });

    it('asks to merge a team into the proposed target', async () => {
        const wrapper = mountList();

        await wrapper.get('[data-test="suggest-merge-3"]').trigger('click');

        const [source, target] = wrapper.emitted('merge')[0];
        expect(source.id).toBe(3);
        expect(target.id).toBe(1);
    });

    it('groups can be dismissed and restored, remembered in the browser', async () => {
        const wrapper = mountList();

        await wrapper.get('[data-test="dismiss"]').trigger('click');
        expect(wrapper.find('[data-group="Brasch"]').exists()).toBe(false);
        expect(wrapper.text()).toContain('1 ausgeblendete anzeigen');

        expect(mountList().find('[data-group="Brasch"]').exists()).toBe(false);
    });

    it('respects search and series filter', () => {
        expect(
            mountList({ query: 'tauber' }).find('[data-test="merge-suggestions"]').exists(),
        ).toBe(false);
        expect(mountList({ query: 'köhler' }).find('[data-group="Brasch"]').exists()).toBe(true);
        expect(
            mountList({ seriesFilter: 'tatort' }).find('[data-test="merge-suggestions"]').exists(),
        ).toBe(false);
    });
});
