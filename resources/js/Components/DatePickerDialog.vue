<script setup>
/**
 * Datumsauswahl als Dialog (auf Smartphone und Desktop gleich gut bedienbar).
 * Oben ein Feld zur direkten Eingabe (z. B. „14.3.20“) – praktisch zum Nachtragen alter Sichtungen;
 * der Kalender springt beim Tippen mit.
 */
import { computed, ref, watch } from 'vue';
import CalendarPanel from '@/Components/CalendarPanel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { t } from '@/lang';
import { isWithin, parseTypedDate } from '@/lib/calendar.js';
import { formatDate, todayIso } from '@/lib/format.js';

const props = defineProps({
    show: { type: Boolean, required: true },
    modelValue: { type: String, default: null },
    title: { type: String, required: true },
    min: { type: String, default: '1970-01-01' },
    max: { type: String, default: null },
    allowEmpty: { type: Boolean, default: true },
});

const emit = defineEmits(['select', 'close']);
const today = todayIso();
const upper = computed(() => props.max ?? today);

const typed = ref('');
const touched = ref(false);

// Beim Öffnen das aktuelle Datum vorbelegen
watch(
    () => props.show,
    (open) => {
        if (open) {
            typed.value = formatDate(props.modelValue);
            touched.value = false;
        }
    },
    { immediate: true },
);

const parsed = computed(() => parseTypedDate(typed.value, today));
const error = computed(() => {
    if (!touched.value || typed.value.trim() === '') return null;
    if (!parsed.value) return t('datepicker.invalid');
    if (!isWithin(parsed.value, props.min, upper.value)) {
        return parsed.value > upper.value ? t('datepicker.in_future') : t('datepicker.too_early');
    }
    return null;
});
const canApply = computed(() => parsed.value && isWithin(parsed.value, props.min, upper.value));

// Beim Hineinklicken den bisherigen Inhalt markieren, damit man direkt überschreiben kann.
// Safari setzt beim Loslassen der Maustaste den Cursor und hebt die Markierung auf – deshalb wird
// genau dieses erste Loslassen nach dem Fokussieren abgefangen. Weitere Klicks setzen den Cursor normal.
let justFocused = false;

function selectAllOnFocus(event) {
    const input = event.target;
    justFocused = true;
    input.select();
    // Touch-Geräte (iOS) wenden die Markierung erst nach dem Fokus-Ereignis zuverlässig an
    setTimeout(() => {
        if (document.activeElement === input) {
            input.setSelectionRange(0, input.value.length);
        }
    }, 0);
}

function keepSelectionOnFirstMouseUp(event) {
    if (justFocused) {
        justFocused = false;
        event.preventDefault();
        event.target.select();
    }
}

function choose(value) {
    emit('select', value);
    emit('close');
}

function apply() {
    touched.value = true;
    if (canApply.value) {
        choose(parsed.value);
    }
}
</script>

<template>
    <Modal :show="show" max-width="sm" @close="$emit('close')">
        <div class="p-4 sm:p-5">
            <h2 class="mb-3 text-lg font-semibold">{{ title }}</h2>

            <form class="mb-4" novalidate @submit.prevent="apply">
                <label for="datepicker-input" class="block text-sm font-medium text-muted">
                    {{ $t('datepicker.type_label') }}
                </label>
                <div class="mt-1 flex gap-2">
                    <input
                        id="datepicker-input"
                        v-model="typed"
                        type="text"
                        inputmode="decimal"
                        autocomplete="off"
                        enterkeyhint="done"
                        :placeholder="$t('datepicker.type_placeholder')"
                        :aria-invalid="!!error"
                        aria-describedby="datepicker-hint"
                        class="min-h-11 w-full min-w-0 rounded-lg border-line bg-surface px-3 text-base tabular-nums text-fg placeholder:text-subtle focus:border-accent focus:ring-1 focus:ring-accent"
                        data-test="typed-date"
                        @input="touched = true"
                        @focus="selectAllOnFocus"
                        @mouseup="keepSelectionOnFirstMouseUp"
                        @blur="justFocused = false"
                    />
                    <PrimaryButton class="shrink-0" :disabled="!canApply" data-test="apply-typed">
                        {{ $t('datepicker.apply') }}
                    </PrimaryButton>
                </div>
                <p
                    id="datepicker-hint"
                    class="mt-1 text-xs"
                    :class="error ? 'text-danger' : 'text-subtle'"
                    role="status"
                >
                    {{ error ?? $t('datepicker.type_hint') }}
                </p>
            </form>

            <CalendarPanel
                v-if="show"
                :model-value="modelValue"
                :view-date="parsed"
                :min="min"
                :max="upper"
                :today="today"
                @select="choose"
            />

            <div class="mt-4 flex flex-wrap gap-2">
                <SecondaryButton class="grow" @click="choose(today)">
                    {{ $t('datepicker.today') }}
                </SecondaryButton>
                <SecondaryButton v-if="allowEmpty" class="grow" @click="choose(null)">
                    {{ $t('datepicker.no_date') }}
                </SecondaryButton>
                <SecondaryButton class="grow" @click="$emit('close')">
                    {{ $t('common.cancel') }}
                </SecondaryButton>
            </div>
        </div>
    </Modal>
</template>
