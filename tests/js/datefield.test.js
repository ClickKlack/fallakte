// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { i18n } from '@/lang';
import DateField from '@/Components/DateField.vue';

const mountField = (props = {}) =>
    mount(DateField, {
        props: { id: 'd', dialogTitle: 'Datum', modelValue: null, ...props },
        global: { plugins: [i18n], stubs: { DatePickerDialog: true } },
    });

describe('DateField', () => {
    it('shows the date in German notation', () => {
        expect(
            mountField({ modelValue: '2002-10-20' }).get('[data-test="date-text"]').element.value,
        ).toBe('20.10.2002');
    });

    it('takes over a typed date when leaving the field', async () => {
        const wrapper = mountField();
        const input = wrapper.get('[data-test="date-text"]');

        await input.setValue('20.10.02');
        await input.trigger('blur');

        expect(wrapper.emitted('update:modelValue')).toEqual([['2002-10-20']]);
        expect(input.element.value).toBe('20.10.2002');
    });

    it('enter also takes over the date', async () => {
        const wrapper = mountField();
        const input = wrapper.get('[data-test="date-text"]');

        await input.setValue('1.1.99');
        await input.trigger('keydown', { key: 'Enter' });

        expect(wrapper.emitted('update:modelValue')).toEqual([['1999-01-01']]);
    });

    it('empty input clears the date', async () => {
        const wrapper = mountField({ modelValue: '2002-10-20' });
        const input = wrapper.get('[data-test="date-text"]');

        await input.setValue('');
        await input.trigger('blur');

        expect(wrapper.emitted('update:modelValue')).toEqual([[null]]);
    });

    it('invalid or out of range input restores the previous value', async () => {
        const wrapper = mountField({ modelValue: '2002-10-20', max: '2026-11-06' });
        const input = wrapper.get('[data-test="date-text"]');

        for (const value of ['quatsch', '31.02.2020', '01.01.2099']) {
            await input.setValue(value);
            await input.trigger('blur');
            expect(input.element.value).toBe('20.10.2002');
        }
        expect(wrapper.emitted('update:modelValue')).toBeUndefined();
    });

    it('can be disabled', () => {
        const wrapper = mountField({ disabled: true });

        expect(wrapper.get('[data-test="date-text"]').attributes('disabled')).toBeDefined();
        expect(wrapper.get('[data-test="date-open"]').attributes('disabled')).toBeDefined();
    });
});
