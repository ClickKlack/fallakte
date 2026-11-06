// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { i18n } from '@/lang';
import Combobox from '@/Components/Combobox.vue';

const options = [
    { value: 1, label: 'Batic und Leitmayr', hint: 'Tatort' },
    { value: 2, label: 'Borowski und Şahin', hint: 'Tatort' },
    { value: 3, label: 'König und Böwe', hint: 'Polizeiruf 110' },
];

const mountBox = (props = {}) =>
    mount(Combobox, {
        props: { id: 'team', options, modelValue: null, ...props },
        global: { plugins: [i18n] },
        attachTo: document.body,
    });

const shownLabels = (wrapper) =>
    wrapper.findAll('[role="option"]').map((o) =>
        o
            .findAll('span')
            .map((s) => s.text())
            .join(' | '),
    );

describe('Combobox', () => {
    it('shows the selected label', () => {
        expect(mountBox({ modelValue: 3 }).get('input').element.value).toBe('König und Böwe');
    });

    it('typing filters the suggestions (accent insensitive) and shows hints', async () => {
        const wrapper = mountBox();
        const input = wrapper.get('input');

        await input.trigger('focus');
        await input.setValue('sahin');

        expect(shownLabels(wrapper)).toEqual(['Borowski und Şahin | Tatort']);
        expect(input.attributes('aria-expanded')).toBe('true');
    });

    it('selects with arrow keys and enter', async () => {
        const wrapper = mountBox();
        const input = wrapper.get('input');

        await input.trigger('focus');
        await input.setValue('und');
        await input.trigger('keydown', { key: 'ArrowDown' });
        await input.trigger('keydown', { key: 'Enter' });

        expect(wrapper.emitted('update:modelValue')).toEqual([[2]]);
        expect(input.element.value).toBe('Borowski und Şahin');
    });

    it('selects by click', async () => {
        const wrapper = mountBox();

        await wrapper.get('input').trigger('focus');
        await wrapper.findAll('[role="option"]')[2].trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([[3]]);
    });

    it('escape keeps the previous selection', async () => {
        const wrapper = mountBox({ modelValue: 1 });
        const input = wrapper.get('input');

        await input.trigger('focus');
        await input.setValue('kön');
        await input.trigger('keydown', { key: 'Escape' });

        expect(wrapper.emitted('update:modelValue')).toBeUndefined();
        expect(input.element.value).toBe('Batic und Leitmayr');
    });

    it('leaving the field: empty clears, exact text selects, other text reverts', async () => {
        const wrapper = mountBox({ modelValue: 1 });
        const input = wrapper.get('input');

        await input.trigger('focus');
        await input.setValue('könig und böwe');
        await input.trigger('blur');
        expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([3]);

        await input.trigger('focus');
        await input.setValue('');
        await input.trigger('blur');
        expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([null]);

        await wrapper.setProps({ modelValue: 1 });
        await input.trigger('focus');
        await input.setValue('quatsch');
        await input.trigger('blur');
        expect(wrapper.emitted('update:modelValue')).toHaveLength(2);
        expect(input.element.value).toBe('Batic und Leitmayr');
    });

    it('clear button removes the selection', async () => {
        const wrapper = mountBox({ modelValue: 2 });

        await wrapper.get('[data-test="clear"]').trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([[null]]);
    });

    it('shows a hint when nothing matches', async () => {
        const wrapper = mountBox();

        await wrapper.get('input').trigger('focus');
        await wrapper.get('input').setValue('Derrick');

        expect(wrapper.text()).toContain('Keine Treffer');
    });
});
