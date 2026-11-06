<script setup>
/**
 * Säulendiagramm (SVG) im App-Design.
 * - Eine Hauptgröße (value) in der Diagrammfarbe, optional eine Kontextgröße (context) als
 *   gedämpfte Säule dahinter – z. B. „gesehen“ vor „ausgestrahlt“.
 * - Säulen ≤ 24 px, oben 4 px abgerundet, an der Grundlinie eckig; Haarlinien-Raster.
 * - Hover/Tippen auf die ganze Spalte, Pfeiltasten bei Fokus; Tooltip zeigt alle Werte.
 * - Tabellenansicht darunter, damit kein Wert nur im Tooltip steht.
 *
 * items: [{ key, label, value, context? }]
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { columnPath, columnWidth, labelStep, niceTicks } from '@/lib/chart.js';

const props = defineProps({
    items: { type: Array, required: true },
    valueLabel: { type: String, required: true },
    contextLabel: { type: String, default: null },
    title: { type: String, required: true },
    height: { type: Number, default: 220 },
});

const AXIS_LEFT = 40;
const AXIS_BOTTOM = 24;
const TOP = 8;

const container = ref(null);
const width = ref(0);
const active = ref(null);
const showTable = ref(false);
let observer = null;

onMounted(() => {
    // Erst die aktuelle Breite übernehmen, danach auf Änderungen reagieren
    width.value = container.value.clientWidth;
    observer = new ResizeObserver(([entry]) => (width.value = Math.floor(entry.contentRect.width)));
    observer.observe(container.value);
});
onBeforeUnmount(() => observer?.disconnect());

const hasContext = computed(() => props.contextLabel !== null);
const plotWidth = computed(() => Math.max(0, width.value - AXIS_LEFT));
const plotHeight = computed(() => props.height - TOP - AXIS_BOTTOM);
const max = computed(() =>
    Math.max(0, ...props.items.map((i) => (hasContext.value ? i.context : i.value) ?? 0)),
);
const ticks = computed(() => niceTicks(max.value));
const top = computed(() => ticks.value.at(-1));
const slot = computed(() => (props.items.length ? plotWidth.value / props.items.length : 0));
const barWidth = computed(() => columnWidth(slot.value));
const step = computed(() => labelStep(props.items.length, plotWidth.value));

const y = (value) => TOP + plotHeight.value - (value / top.value) * plotHeight.value;
const barX = (index) => AXIS_LEFT + index * slot.value + (slot.value - barWidth.value) / 2;
const bar = (index, value) =>
    columnPath(barX(index), y(value), barWidth.value, plotHeight.value + TOP - y(value));

// Beschriftung nur für „runde“ Werte (z. B. jedes 5. Jahr), plus immer die letzte Säule
const showLabel = (item, index) => {
    const numeric = Number(item.label);
    const aligned = Number.isFinite(numeric)
        ? numeric % step.value === 0
        : index % step.value === 0;
    return aligned || (index === props.items.length - 1 && step.value === 1);
};

const formatTick = (value) => value.toLocaleString('de-DE');

function pick(event) {
    const rect = event.currentTarget.getBoundingClientRect();
    const x = event.clientX - rect.left - AXIS_LEFT;
    const index = Math.floor(x / slot.value);
    active.value = index >= 0 && index < props.items.length ? index : null;
}

// Bei Touch meldet der Browser nach dem Loslassen „verlassen“ – dort bleibt der Tooltip stehen,
// bis woanders hingetippt wird (blur). Nur die Maus blendet ihn beim Verlassen aus.
function onLeave(event) {
    if (event.pointerType === 'mouse') {
        active.value = null;
    }
}

function onKeydown(event) {
    if (!props.items.length) return;
    const last = props.items.length - 1;
    const keys = { ArrowRight: 1, ArrowLeft: -1, Home: -Infinity, End: Infinity };
    if (!(event.key in keys)) return;
    event.preventDefault();
    const current = active.value ?? (keys[event.key] > 0 ? -1 : last + 1);
    active.value = Math.min(last, Math.max(0, current + keys[event.key]));
}

const activeItem = computed(() => (active.value === null ? null : props.items[active.value]));
const tooltipLeft = computed(() => {
    if (active.value === null) return 0;
    const center = AXIS_LEFT + (active.value + 0.5) * slot.value;
    return Math.min(Math.max(center, 70), width.value - 70);
});
</script>

<template>
    <figure class="space-y-3">
        <div v-if="hasContext" class="flex flex-wrap gap-4 text-xs text-muted" aria-hidden="true">
            <span class="flex items-center gap-1.5"
                ><span class="h-2.5 w-2.5 rounded-sm bg-chart" />{{ valueLabel }}</span
            >
            <span class="flex items-center gap-1.5"
                ><span class="h-2.5 w-2.5 rounded-sm bg-chart-context" />{{ contextLabel }}</span
            >
        </div>

        <div ref="container" class="relative select-none">
            <svg
                v-if="width > 0"
                :width="width"
                :height="height"
                role="img"
                tabindex="0"
                :aria-label="title"
                class="block touch-pan-y rounded-md focus:outline-none focus-visible:outline-2 focus-visible:outline-accent"
                data-test="chart"
                @pointermove="pick"
                @pointerdown="pick"
                @pointerleave="onLeave"
                @keydown="onKeydown"
                @blur="active = null"
            >
                <!-- Raster und y-Achse -->
                <g class="text-[11px]">
                    <template v-for="tick in ticks" :key="tick">
                        <line
                            :x1="AXIS_LEFT"
                            :x2="width"
                            :y1="y(tick) + 0.5"
                            :y2="y(tick) + 0.5"
                            class="stroke-line"
                            stroke-width="1"
                        />
                        <text
                            :x="AXIS_LEFT - 6"
                            :y="y(tick)"
                            text-anchor="end"
                            dominant-baseline="middle"
                            class="fill-subtle tabular-nums"
                        >
                            {{ formatTick(tick) }}
                        </text>
                    </template>
                </g>

                <!-- Hervorhebung der aktiven Spalte -->
                <rect
                    v-if="active !== null"
                    :x="AXIS_LEFT + active * slot"
                    :y="TOP"
                    :width="slot"
                    :height="plotHeight"
                    class="fill-surface-hover"
                    opacity="0.6"
                />

                <!-- Säulen -->
                <g v-for="(item, index) in items" :key="item.key">
                    <path
                        v-if="hasContext && item.context > 0"
                        :d="bar(index, item.context)"
                        class="fill-chart-context"
                    />
                    <path
                        v-if="item.value > 0"
                        :d="bar(index, item.value)"
                        class="fill-chart transition-opacity"
                        :opacity="active === null || active === index ? 1 : 0.55"
                    />
                </g>

                <!-- x-Beschriftung -->
                <g class="text-[11px]">
                    <template v-for="(item, index) in items" :key="`l-${item.key}`">
                        <text
                            v-if="showLabel(item, index)"
                            :x="AXIS_LEFT + (index + 0.5) * slot"
                            :y="height - 6"
                            text-anchor="middle"
                            class="fill-subtle tabular-nums"
                        >
                            {{ item.label }}
                        </text>
                    </template>
                </g>
            </svg>

            <!-- Tooltip -->
            <div
                v-if="activeItem"
                class="pointer-events-none absolute top-0 z-10 -translate-x-1/2 rounded-lg border border-line bg-surface-raised px-3 py-2 text-sm shadow-xl shadow-black/40"
                :style="{ left: `${tooltipLeft}px` }"
                role="status"
                data-test="tooltip"
            >
                <div class="font-semibold">{{ activeItem.label }}</div>
                <div class="mt-1 flex items-center gap-2 whitespace-nowrap">
                    <span class="h-0.5 w-3 rounded bg-chart" />
                    <strong class="tabular-nums">{{ formatTick(activeItem.value) }}</strong>
                    <span class="text-muted">{{ valueLabel }}</span>
                </div>
                <div v-if="hasContext" class="flex items-center gap-2 whitespace-nowrap">
                    <span class="h-0.5 w-3 rounded bg-chart-context" />
                    <strong class="tabular-nums">{{ formatTick(activeItem.context) }}</strong>
                    <span class="text-muted">{{ contextLabel }}</span>
                </div>
            </div>
        </div>

        <figcaption>
            <button
                type="button"
                class="-mx-1 inline-flex min-h-11 items-center gap-1 rounded-md px-1 text-xs font-semibold text-muted hover:text-fg"
                :aria-expanded="showTable"
                data-test="toggle-table"
                @click="showTable = !showTable"
            >
                {{ showTable ? $t('charts.hide_table') : $t('charts.show_table') }}
            </button>
        </figcaption>

        <div v-if="showTable" class="max-h-72 overflow-y-auto rounded-lg border border-line">
            <table class="w-full text-sm" data-test="table">
                <caption class="sr-only">
                    {{
                        title
                    }}
                </caption>
                <thead
                    class="sticky top-0 bg-surface-raised text-left text-xs uppercase tracking-wide text-subtle"
                >
                    <tr>
                        <th class="px-3 py-2 font-semibold">{{ $t('charts.year') }}</th>
                        <th class="px-3 py-2 text-right font-semibold">{{ valueLabel }}</th>
                        <th v-if="hasContext" class="px-3 py-2 text-right font-semibold">
                            {{ contextLabel }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="item in items" :key="`t-${item.key}`">
                        <td class="px-3 py-1.5 tabular-nums">{{ item.label }}</td>
                        <td class="px-3 py-1.5 text-right tabular-nums">
                            {{ formatTick(item.value) }}
                        </td>
                        <td v-if="hasContext" class="px-3 py-1.5 text-right tabular-nums">
                            {{ formatTick(item.context) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </figure>
</template>
