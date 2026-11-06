<script setup>
/**
 * Datum „gesehen am“ einer gesehenen Folge, kompakt für eine feste Spalte am Zeilenende.
 * Antippen öffnet den Datumspicker; das Datum lässt sich ändern oder leeren (die Folge bleibt gesehen).
 * Gibt Änderungen als Ereignis "change" mit { seen: true, seen_at } weiter.
 */
import { ref } from 'vue';
import DatePickerDialog from '@/Components/DatePickerDialog.vue';
import { formatDate, formatDateShort } from '@/lib/format.js';

defineProps({
    seenAt: { type: String, default: null },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['change']);
const open = ref(false);

function change(value) {
    emit('change', { seen: true, seen_at: value });
}
</script>

<template>
    <button
        type="button"
        class="inline-flex min-h-11 items-center justify-start gap-1 rounded-lg px-1.5 text-sm sm:gap-1.5 sm:px-2 tabular-nums transition hover:bg-surface-hover hover:text-fg"
        :class="seenAt ? 'text-accent-strong' : 'text-subtle italic'"
        :disabled="disabled"
        :aria-label="$t('seen.change_date')"
        :title="
            seenAt ? $t('seen.seen_on', { date: formatDate(seenAt) }) : $t('seen.seen_without_date')
        "
        @click="open = true"
    >
        <svg
            viewBox="0 0 20 20"
            fill="currentColor"
            class="h-4 w-4 shrink-0 opacity-70"
            aria-hidden="true"
        >
            <path
                fill-rule="evenodd"
                d="M5.8 2a.8.8 0 0 1 .8.8V4h6.8V2.8a.8.8 0 0 1 1.6 0V4h.5A2.5 2.5 0 0 1 18 6.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 2 15.5v-9A2.5 2.5 0 0 1 4.5 4H5V2.8a.8.8 0 0 1 .8-.8ZM3.6 8.5v7c0 .5.4.9.9.9h11c.5 0 .9-.4.9-.9v-7H3.6Z"
                clip-rule="evenodd"
            />
        </svg>
        <!-- Auf dem Smartphone kurz („06.11.26“ bzw. „–“), sonst ausführlich -->
        <span class="sm:hidden" data-test="label-short">{{
            seenAt ? formatDateShort(seenAt) : '–'
        }}</span>
        <span class="hidden sm:inline" data-test="label">{{
            seenAt ? formatDate(seenAt) : $t('seen.no_date')
        }}</span>
    </button>

    <DatePickerDialog
        :show="open"
        :model-value="seenAt"
        :title="$t('seen.dialog_title')"
        @select="change"
        @close="open = false"
    />
</template>
