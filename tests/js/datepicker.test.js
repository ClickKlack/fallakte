// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { i18n } from '@/lang';
import DatePickerDialog from '@/Components/DatePickerDialog.vue';
import { todayIso } from '@/lib/format.js';

// Modal nutzt <dialog>; für den Test reicht der Inhalt
const mountDialog = (props = {}) =>
    mount(DatePickerDialog, {
        props: { show: true, title: 'Gesehen am', modelValue: null, ...props },
        global: { plugins: [i18n], stubs: { Modal: { template: '<div><slot /></div>' } } },
    });

describe('DatePickerDialog – direct input', () => {
    it('prefills the current date', () => {
        expect(
            mountDialog({ modelValue: '2020-03-14' }).get('[data-test="typed-date"]').element.value,
        ).toBe('14.03.2020');
    });

    it('applies a typed date with enter', async () => {
        const wrapper = mountDialog();

        await wrapper.get('[data-test="typed-date"]').setValue('14.3.20');
        await wrapper.get('form').trigger('submit');

        expect(wrapper.emitted('select')).toEqual([['2020-03-14']]);
        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('calendar jumps to the typed month', async () => {
        const wrapper = mountDialog();

        await wrapper.get('[data-test="typed-date"]').setValue('01.05.1985');

        expect(wrapper.find('[data-iso="1985-05-17"]').exists()).toBe(true);
    });

    it('shows errors and does not apply invalid, future or too early dates', async () => {
        const wrapper = mountDialog();
        const input = wrapper.get('[data-test="typed-date"]');

        for (const [value, message] of [
            ['31.02.2020', 'Kein gültiges Datum.'],
            ['01.01.2099', 'Das Datum liegt in der Zukunft.'],
            ['31.12.1969', 'Das Datum liegt vor 1970.'],
        ]) {
            await input.setValue(value);
            await input.trigger('input');
            await wrapper.get('form').trigger('submit');

            expect(wrapper.text()).toContain(message);
            expect(wrapper.get('[data-test="apply-typed"]').attributes('disabled')).toBeDefined();
        }

        expect(wrapper.emitted('select')).toBeUndefined();
    });

    it('today is allowed', async () => {
        const wrapper = mountDialog();

        await wrapper.get('[data-test="typed-date"]').setValue(todayIso());
        await wrapper.get('form').trigger('submit');

        expect(wrapper.emitted('select')).toEqual([[todayIso()]]);
    });
});

describe('DatePickerDialog – selection on focus', () => {
    it('selects the existing text when focusing the input', async () => {
        const wrapper = mount(DatePickerDialog, {
            props: { show: true, title: 'Gesehen am', modelValue: '2020-03-14' },
            global: { plugins: [i18n], stubs: { Modal: { template: '<div><slot /></div>' } } },
            attachTo: document.body,
        });
        const input = wrapper.get('[data-test="typed-date"]');

        await input.trigger('focus');

        expect(input.element.selectionStart).toBe(0);
        expect(input.element.selectionEnd).toBe('14.03.2020'.length);
        wrapper.unmount();
    });
});
