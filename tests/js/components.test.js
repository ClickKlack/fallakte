// @vitest-environment happy-dom
import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { i18n } from '@/lang';
import ProgressBar from '@/Components/ProgressBar.vue';
import CalendarPanel from '@/Components/CalendarPanel.vue';
import SeenDate from '@/Components/SeenDate.vue';
import SeenToggle from '@/Components/SeenToggle.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { todayIso } from '@/lib/format.js';

const mountWithI18n = (component, props) =>
    mount(component, { props, global: { plugins: [i18n] } });

describe('SeenToggle', () => {
    it('marks an unseen episode as seen with today', async () => {
        const wrapper = mountWithI18n(SeenToggle, { seen: false, label: 'Taxi nach Leipzig' });

        await wrapper.get('[role="checkbox"]').trigger('click');

        expect(wrapper.emitted('change')).toEqual([[{ seen: true, seen_at: todayIso() }]]);
        expect(wrapper.get('[role="checkbox"]').attributes('aria-label')).toBe(
            '„Taxi nach Leipzig“ als gesehen markieren',
        );
    });

    it('removes the check mark and the date', async () => {
        const wrapper = mountWithI18n(SeenToggle, { seen: true });

        await wrapper.get('[role="checkbox"]').trigger('click');

        expect(wrapper.emitted('change')).toEqual([[{ seen: false, seen_at: null }]]);
    });

    it('has the same size whether seen or not (no layout shift)', () => {
        const seen = mountWithI18n(SeenToggle, { seen: true }).get('[role="checkbox"]').classes();
        const unseen = mountWithI18n(SeenToggle, { seen: false })
            .get('[role="checkbox"]')
            .classes();

        expect(seen).toEqual(unseen);
        expect(seen).toContain('w-11');
    });

    it('announced episodes cannot be checked', async () => {
        const wrapper = mountWithI18n(SeenToggle, { seen: false, disabled: true });

        await wrapper.get('[role="checkbox"]').trigger('click');

        expect(wrapper.get('[role="checkbox"]').attributes('disabled')).toBeDefined();
        expect(wrapper.emitted('change')).toBeUndefined();
    });

    it('ignores clicks while a request is running', async () => {
        const wrapper = mountWithI18n(SeenToggle, { seen: false, busy: true });

        await wrapper.get('[role="checkbox"]').trigger('click');

        expect(wrapper.emitted('change')).toBeUndefined();
    });
});

describe('SeenDate', () => {
    it('shows the date or a hint without date', () => {
        expect(
            mountWithI18n(SeenDate, { seenAt: '2026-10-12' }).get('[data-test="label"]').text(),
        ).toBe('12.10.2026');
        expect(mountWithI18n(SeenDate, { seenAt: null }).get('[data-test="label"]').text()).toBe(
            'ohne Datum',
        );
    });

    it('has a short label for small screens', () => {
        const label = (seenAt) =>
            mountWithI18n(SeenDate, { seenAt }).get('[data-test="label-short"]').text();

        expect(label('2026-10-12')).toBe('12.10.26');
        expect(label(null)).toBe('–');
    });

    it('forwards a date chosen in the picker and keeps the episode seen', async () => {
        const wrapper = mountWithI18n(SeenDate, { seenAt: '2026-10-12' });

        await wrapper.findComponent({ name: 'DatePickerDialog' }).vm.$emit('select', '2026-10-01');
        await wrapper.findComponent({ name: 'DatePickerDialog' }).vm.$emit('select', null);

        expect(wrapper.emitted('change')).toEqual([
            [{ seen: true, seen_at: '2026-10-01' }],
            [{ seen: true, seen_at: null }],
        ]);
    });
});

describe('CalendarPanel', () => {
    const mountPanel = (props = {}) =>
        mountWithI18n(CalendarPanel, {
            today: '2026-11-06',
            max: '2026-11-06',
            min: '1970-01-01',
            ...props,
        });

    it('opens on the month of the selected date and marks it', () => {
        const wrapper = mountPanel({ modelValue: '2026-10-12' });

        expect(wrapper.get('[data-test="month"]').element.value).toBe('9');
        expect(wrapper.get('[data-iso="2026-10-12"]').attributes('aria-selected')).toBe('true');
    });

    it('opens on the current month without selection and marks today', () => {
        const wrapper = mountPanel();

        expect(wrapper.get('[data-test="year"]').element.value).toBe('2026');
        expect(wrapper.get('[data-iso="2026-11-06"]').attributes('aria-current')).toBe('date');
    });

    it('selects a day', async () => {
        const wrapper = mountPanel();

        await wrapper.get('[data-iso="2026-11-03"]').trigger('click');

        expect(wrapper.emitted('select')).toEqual([['2026-11-03']]);
    });

    it('future days are disabled and cannot be selected', async () => {
        const wrapper = mountPanel();
        const tomorrow = wrapper.get('[data-iso="2026-11-07"]');

        await tomorrow.trigger('click');

        expect(tomorrow.attributes('disabled')).toBeDefined();
        expect(wrapper.emitted('select')).toBeUndefined();
        expect(wrapper.get('[data-test="next"]').attributes('disabled')).toBeDefined();
    });

    it('navigates between months and jumps to a year', async () => {
        const wrapper = mountPanel();

        await wrapper.get('[data-test="previous"]').trigger('click');
        expect(wrapper.find('[data-iso="2026-10-15"]').exists()).toBe(true);

        await wrapper.get('[data-test="year"]').setValue('1999');
        expect(wrapper.find('[data-iso="1999-10-15"]').exists()).toBe(true);
    });

    it('weeks start on monday', () => {
        const headers = mountPanel()
            .findAll('[role="columnheader"]')
            .map((h) => h.text());

        expect(headers).toEqual(['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']);
    });
});

describe('ProgressBar', () => {
    it('shows count and percentage', () => {
        const wrapper = mountWithI18n(ProgressBar, { done: 512, total: 1344 });

        expect(wrapper.text()).toContain('512 von 1344 gesehen');
        expect(wrapper.text()).toContain('38 %');
        expect(wrapper.get('[role="progressbar"]').attributes('aria-valuenow')).toBe('38');
    });

    it('can hide the label', () => {
        expect(
            mountWithI18n(ProgressBar, {
                done: 1,
                total: 2,
                showLabel: false,
            }).text(),
        ).toBe('');
    });
});

describe('StatusBadge', () => {
    it('marks only announced episodes', () => {
        expect(mountWithI18n(StatusBadge, { status: 'announced' }).text()).toBe('Angekündigt');
        expect(mountWithI18n(StatusBadge, { status: 'aired' }).text()).toBe('');
    });
});
