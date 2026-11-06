<script setup>
/**
 * Feldbeschriftung im Bearbeitungsformular. Ist das Feld manuell geändert (geschützt),
 * zeigt sie ein Schloss und bietet „wieder von Wikipedia übernehmen“ an.
 */
defineProps({
    for: { type: String, required: true },
    label: { type: String, required: true },
    protected: { type: Boolean, default: false },
    resettable: { type: Boolean, default: false },
});

defineEmits(['reset']);
</script>

<template>
    <div class="flex min-h-7 flex-wrap items-center justify-between gap-x-3 gap-y-1">
        <label :for="$props.for" class="flex items-center gap-1.5 text-sm font-medium text-muted">
            {{ label }}
            <span
                v-if="protected"
                class="inline-flex items-center gap-1 rounded-full bg-accent-soft px-2 py-0.5 text-xs font-semibold text-accent-strong"
                :title="$t('episodes.form.protected_hint')"
                data-test="protected"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3" aria-hidden="true">
                    <path
                        fill-rule="evenodd"
                        d="M10 2a4 4 0 0 0-4 4v2H5.5A1.5 1.5 0 0 0 4 9.5v7A1.5 1.5 0 0 0 5.5 18h9a1.5 1.5 0 0 0 1.5-1.5v-7A1.5 1.5 0 0 0 14.5 8H14V6a4 4 0 0 0-4-4Zm2 6V6a2 2 0 1 0-4 0v2h4Z"
                        clip-rule="evenodd"
                    />
                </svg>
                {{ $t('episodes.form.protected') }}
            </span>
        </label>
        <button
            v-if="protected && resettable"
            type="button"
            class="-my-2 inline-flex min-h-11 items-center text-xs font-semibold text-accent underline-offset-2 hover:underline"
            :data-test="`reset-${$props.for}`"
            @click="$emit('reset')"
        >
            {{ $t('episodes.form.reset_protection') }}
        </button>
    </div>
</template>
