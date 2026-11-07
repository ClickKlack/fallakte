<script setup>
/**
 * Schnellumschalter über der Folgenliste: Alle / Gesehen / Ungesehen.
 * Setzt den Status-Filter; ist „angekündigt“ gewählt (nur über die Filterleiste), ist keiner aktiv.
 */
defineProps({
    status: { type: String, required: true },
});

defineEmits(['update:status']);

const options = ['all', 'seen', 'unseen'];
</script>

<template>
    <div
        class="inline-flex rounded-full border border-line bg-surface p-1"
        role="radiogroup"
        :aria-label="$t('episodes.filters.status')"
    >
        <button
            v-for="option in options"
            :key="option"
            type="button"
            role="radio"
            :aria-checked="status === option"
            class="inline-flex min-h-10 items-center rounded-full px-4 text-sm font-semibold transition"
            :class="status === option ? 'bg-fg text-on-accent' : 'text-muted hover:text-fg'"
            :data-test="`quick-${option}`"
            @click="$emit('update:status', option)"
        >
            {{ $t(`episodes.status.${option}`) }}
        </button>
    </div>
</template>
