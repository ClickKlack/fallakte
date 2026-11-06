<script setup>
// Kennzeichen für den Ausstrahlungsstatus; nur „angekündigt“ wird sichtbar markiert.
defineProps({
    status: { type: String, required: true },
});
</script>

<template>
    <span
        v-if="status === 'announced'"
        class="inline-flex items-center gap-1 rounded-full bg-announced px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-announced-fg"
    >
        <svg viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
            <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.8-12.2a.8.8 0 0 0-1.6 0V10c0 .2.1.4.2.6l2.5 2.5a.8.8 0 1 0 1.1-1.1l-2.2-2.3V5.8Z"
                clip-rule="evenodd"
            />
        </svg>
        {{ $t('status.announced') }}
    </span>
</template>
