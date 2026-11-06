<script setup>
/**
 * Kästchen „gesehen“. Ein Klick hakt ab (Datum: heute) bzw. entfernt den Haken samt Datum.
 * Angekündigte Folgen sind nicht abhakbar. Das Datum selbst zeigt und ändert SeenDate.
 * Gibt Änderungen als Ereignis "change" mit { seen, seen_at } weiter.
 */
import { todayIso } from '@/lib/format.js';

const props = defineProps({
    seen: { type: Boolean, required: true },
    disabled: { type: Boolean, default: false },
    busy: { type: Boolean, default: false },
    label: { type: String, default: '' },
});

const emit = defineEmits(['change']);

function toggle() {
    if (props.disabled || props.busy) {
        return;
    }

    emit(
        'change',
        props.seen ? { seen: false, seen_at: null } : { seen: true, seen_at: todayIso() },
    );
}
</script>

<template>
    <button
        type="button"
        role="checkbox"
        :aria-checked="seen"
        :aria-label="label ? $t('seen.toggle_label', { title: label }) : $t('seen.toggle')"
        :disabled="disabled || busy"
        :title="disabled ? $t('seen.not_yet_aired') : undefined"
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg transition disabled:cursor-not-allowed"
        :class="busy ? 'opacity-60' : ''"
        @click="toggle"
    >
        <span
            class="flex h-6 w-6 items-center justify-center rounded-md border-2 transition"
            :class="
                seen
                    ? 'border-accent bg-accent text-on-accent'
                    : disabled
                      ? 'border-line bg-transparent'
                      : 'border-line-strong bg-surface hover:border-accent'
            "
        >
            <svg
                v-if="seen"
                viewBox="0 0 20 20"
                fill="currentColor"
                class="h-4 w-4"
                aria-hidden="true"
            >
                <path
                    fill-rule="evenodd"
                    d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4l3.3 3.3 7.3-7.3a1 1 0 0 1 1.4 0Z"
                    clip-rule="evenodd"
                />
            </svg>
        </span>
    </button>
</template>
