<script setup>
/**
 * Monatskalender im App-Design: Montag als Wochenbeginn, Monats-/Jahresauswahl,
 * Tage außerhalb von min/max sind gesperrt. Gibt den gewählten Tag als "select" (ISO) aus.
 */
import { computed, ref } from 'vue';
import {
    addMonths,
    isWithin,
    MONTHS,
    monthGrid,
    parseIso,
    WEEKDAYS,
    yearRange,
} from '@/lib/calendar.js';

const props = defineProps({
    modelValue: { type: String, default: null },
    min: { type: String, default: null },
    max: { type: String, default: null },
    today: { type: String, required: true },
});

const emit = defineEmits(['select']);

const start = parseIso(props.modelValue) ?? parseIso(props.today);
const view = ref({ year: start.year, month: start.month });

const days = computed(() => monthGrid(view.value.year, view.value.month));
const years = computed(() => yearRange(props.min, props.max));
const monthOptions = MONTHS.map((label, value) => ({ label, value }));

const canGoBack = computed(() => {
    const prev = addMonths(view.value, -1);
    const lastOfPrev = new Date(prev.year, prev.month + 1, 0).getDate();
    return (
        !props.min ||
        isWithin(
            `${prev.year}-${String(prev.month + 1).padStart(2, '0')}-${lastOfPrev}`,
            props.min,
            null,
        )
    );
});
const canGoForward = computed(() => {
    const next = addMonths(view.value, 1);
    return (
        !props.max ||
        isWithin(`${next.year}-${String(next.month + 1).padStart(2, '0')}-01`, null, props.max)
    );
});

function move(delta) {
    view.value = addMonths(view.value, delta);
}

function select(day) {
    if (isWithin(day.iso, props.min, props.max)) {
        emit('select', day.iso);
    }
}
</script>

<template>
    <div class="select-none">
        <div class="flex items-center justify-between gap-2">
            <button
                type="button"
                class="flex h-11 w-11 items-center justify-center rounded-lg text-muted transition hover:bg-surface-hover hover:text-fg disabled:opacity-30"
                :disabled="!canGoBack"
                :aria-label="$t('datepicker.previous_month')"
                data-test="previous"
                @click="move(-1)"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                    <path
                        fill-rule="evenodd"
                        d="M12.7 5.3a1 1 0 0 1 0 1.4L9.4 10l3.3 3.3a1 1 0 1 1-1.4 1.4l-4-4a1 1 0 0 1 0-1.4l4-4a1 1 0 0 1 1.4 0Z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>

            <div class="flex gap-2">
                <select
                    v-model="view.month"
                    class="min-h-11 rounded-lg border-line bg-surface py-1 pl-3 pr-8 text-sm font-semibold text-fg focus:border-accent focus:ring-1 focus:ring-accent"
                    :aria-label="$t('datepicker.month')"
                    data-test="month"
                >
                    <option
                        v-for="option in monthOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <select
                    v-model="view.year"
                    class="min-h-11 rounded-lg border-line bg-surface py-1 pl-3 pr-8 text-sm font-semibold tabular-nums text-fg focus:border-accent focus:ring-1 focus:ring-accent"
                    :aria-label="$t('datepicker.year')"
                    data-test="year"
                >
                    <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                </select>
            </div>

            <button
                type="button"
                class="flex h-11 w-11 items-center justify-center rounded-lg text-muted transition hover:bg-surface-hover hover:text-fg disabled:opacity-30"
                :disabled="!canGoForward"
                :aria-label="$t('datepicker.next_month')"
                data-test="next"
                @click="move(1)"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                    <path
                        fill-rule="evenodd"
                        d="M7.3 14.7a1 1 0 0 1 0-1.4l3.3-3.3-3.3-3.3a1 1 0 1 1 1.4-1.4l4 4a1 1 0 0 1 0 1.4l-4 4a1 1 0 0 1-1.4 0Z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
        </div>

        <div class="mt-3 grid grid-cols-7 gap-1 text-center" role="grid">
            <div
                v-for="(weekday, index) in WEEKDAYS"
                :key="weekday"
                class="py-1 text-xs font-semibold uppercase"
                :class="index >= 5 ? 'text-accent' : 'text-subtle'"
                role="columnheader"
            >
                {{ weekday }}
            </div>

            <button
                v-for="day in days"
                :key="day.iso"
                type="button"
                role="gridcell"
                class="flex h-11 items-center justify-center rounded-lg text-sm tabular-nums transition disabled:cursor-not-allowed disabled:opacity-25"
                :class="[
                    day.iso === modelValue
                        ? 'bg-accent font-bold text-on-accent'
                        : day.inMonth
                          ? 'text-fg hover:bg-surface-hover'
                          : 'text-subtle hover:bg-surface-hover',
                    day.iso === today && day.iso !== modelValue
                        ? 'ring-1 ring-accent ring-inset'
                        : '',
                ]"
                :disabled="!isWithin(day.iso, min, max)"
                :aria-selected="day.iso === modelValue"
                :aria-current="day.iso === today ? 'date' : undefined"
                :data-iso="day.iso"
                @click="select(day)"
            >
                {{ day.day }}
            </button>
        </div>
    </div>
</template>
