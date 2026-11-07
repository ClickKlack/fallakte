// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { i18n } from '@/lang';
import SeenQuickFilter from '@/Components/Episodes/SeenQuickFilter.vue';

const mountFilter = (status) =>
    mount(SeenQuickFilter, { props: { status }, global: { plugins: [i18n] } });

describe('SeenQuickFilter', () => {
    it('shows all three options and marks the active one', () => {
        const wrapper = mountFilter('seen');
        const radios = wrapper.findAll('[role="radio"]');

        expect(radios.map((r) => r.text())).toEqual(['Alle', 'Gesehen', 'Ungesehen']);
        expect(radios.map((r) => r.attributes('aria-checked'))).toEqual(['false', 'true', 'false']);
    });

    it('emits the chosen status', async () => {
        const wrapper = mountFilter('all');

        await wrapper.get('[data-test="quick-unseen"]').trigger('click');

        expect(wrapper.emitted('update:status')).toEqual([['unseen']]);
    });

    it('has no active option while "announced" is filtered', () => {
        const wrapper = mountFilter('announced');

        expect(wrapper.findAll('[aria-checked="true"]')).toHaveLength(0);
    });
});
