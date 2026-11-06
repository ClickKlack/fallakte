<script setup>
/**
 * Datumsfeld: direkte Eingabe (z. B. „14.3.20“) plus Kalenderknopf mit Datumsdialog.
 * v-model ist ein ISO-Datum (YYYY-MM-DD) oder null.
 */
import { computed, ref, watch } from 'vue';
import DatePickerDialog from '@/Components/DatePickerDialog.vue';
import { isWithin, parseTypedDate } from '@/lib/calendar.js';
import { formatDate, todayIso } from '@/lib/format.js';

const model = defineModel({ type: [String, null], default: null });

const props = defineProps({
    id: { type: String, required: true },
    min: { type: String, default: '1970-01-01' },
    max: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    dialogTitle: { type: String, required: true },
    invalid: { type: Boolean, default: false },
});

const today = todayIso();
const upper = computed(() => props.max ?? today);
const text = ref(formatDate(model.value));
const open = ref(false);

watch(model, (value) => {
    if (parseTypedDate(text.value, today) !== value) {
        text.value = formatDate(value);
    }
});

// Getippten Text beim Verlassen übernehmen: leer → null, gültig → Datum, sonst zurück zum alten Wert
function commit() {
    if (text.value.trim() === '') {
        model.value = null;
        return;
    }

    const parsed = parseTypedDate(text.value, today);

    if (parsed && isWithin(parsed, props.min, upper.value)) {
        model.value = parsed;
        text.value = formatDate(parsed);
    } else {
        text.value = formatDate(model.value);
    }
}

function select(event) {
    event.target.select();
}
</script>

<template>
    <div class="flex gap-2">
        <input
            :id="id"
            v-model="text"
            type="text"
            inputmode="decimal"
            autocomplete="off"
            :placeholder="$t('datepicker.type_placeholder')"
            :disabled="disabled"
            :aria-invalid="invalid"
            class="min-h-11 w-full min-w-0 rounded-lg border-line bg-surface px-3 text-base tabular-nums text-fg placeholder:text-subtle focus:border-accent focus:ring-1 focus:ring-accent disabled:opacity-50"
            :class="invalid ? 'border-danger' : ''"
            data-test="date-text"
            @focus="select"
            @blur="commit"
            @keydown.enter.prevent="commit"
        />
        <button
            type="button"
            class="inline-flex min-h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-line bg-surface text-muted transition hover:text-fg disabled:opacity-50"
            :disabled="disabled"
            :aria-label="$t('datepicker.open', { label: dialogTitle })"
            data-test="date-open"
            @click="open = true"
        >
            <svg viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                <path
                    fill-rule="evenodd"
                    d="M5.8 2a.8.8 0 0 1 .8.8V4h6.8V2.8a.8.8 0 0 1 1.6 0V4h.5A2.5 2.5 0 0 1 18 6.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 2 15.5v-9A2.5 2.5 0 0 1 4.5 4H5V2.8a.8.8 0 0 1 .8-.8ZM3.6 8.5v7c0 .5.4.9.9.9h11c.5 0 .9-.4.9-.9v-7H3.6Z"
                    clip-rule="evenodd"
                />
            </svg>
        </button>

        <DatePickerDialog
            :show="open"
            :model-value="model"
            :title="dialogTitle"
            :min="min"
            :max="upper"
            @select="(value) => (model = value)"
            @close="open = false"
        />
    </div>
</template>
