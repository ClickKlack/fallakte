// @vitest-environment happy-dom
import { beforeAll, describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { i18n } from '@/lang';
import ColumnChart from '@/Components/Charts/ColumnChart.vue';

beforeAll(() => {
    // Feste Breite für das Diagramm (happy-dom misst kein Layout)
    globalThis.ResizeObserver = class {
        constructor(callback) {
            this.callback = callback;
        }
        observe() {
            this.callback([{ contentRect: { width: 600 } }]);
        }
        disconnect() {}
    };
});

const items = [
    { key: 2001, label: '2001', value: 3, context: 10 },
    { key: 2002, label: '2002', value: 0, context: 4 },
    { key: 2003, label: '2003', value: 12, context: 12 },
];

const mountChart = (props = {}) =>
    mount(ColumnChart, {
        props: {
            items,
            title: 'Fortschritt',
            valueLabel: 'Gesehen',
            contextLabel: 'Ausgestrahlt',
            ...props,
        },
        global: { plugins: [i18n] },
    });

describe('ColumnChart', () => {
    it('draws a column per value and context, skipping zero values', async () => {
        const wrapper = mountChart();
        await nextTick();

        expect(wrapper.findAll('path.fill-chart')).toHaveLength(2);
        expect(wrapper.findAll('path.fill-chart-context')).toHaveLength(3);
    });

    it('arrow keys show a tooltip with all values', async () => {
        const wrapper = mountChart();
        await nextTick();
        const chart = wrapper.get('[data-test="chart"]');

        await chart.trigger('keydown', { key: 'ArrowRight' });
        expect(wrapper.get('[data-test="tooltip"]').text()).toContain('2001');

        await chart.trigger('keydown', { key: 'End' });
        const tooltip = wrapper.get('[data-test="tooltip"]').text();
        expect(tooltip).toContain('2003');
        expect(tooltip).toContain('12');
        expect(tooltip).toContain('Ausgestrahlt');
    });

    it('offers every value in a table view', async () => {
        const wrapper = mountChart();
        await wrapper.get('[data-test="toggle-table"]').trigger('click');

        const rows = wrapper.findAll('[data-test="table"] tbody tr').map((r) =>
            r
                .findAll('td')
                .map((td) => td.text())
                .join(' '),
        );
        expect(rows).toEqual(['2001 3 10', '2002 0 4', '2003 12 12']);
    });

    it('works without context series and shows no legend', async () => {
        const wrapper = mountChart({
            contextLabel: null,
            items: [{ key: 1, label: '2024', value: 5 }],
        });
        await nextTick();

        expect(wrapper.findAll('path.fill-chart-context')).toHaveLength(0);
        expect(wrapper.text()).not.toContain('Ausgestrahlt');
    });
});
