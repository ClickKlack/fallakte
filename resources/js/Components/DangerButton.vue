<script setup>
// Folgenreiche Aktion (z. B. Teams zusammenführen): gedämpftes Rot mit Warnsymbol.
defineProps({
    type: { type: String, default: 'button' },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-danger/60 bg-danger-soft px-4 py-2 text-sm font-semibold text-danger transition hover:bg-danger/20 disabled:cursor-not-allowed disabled:opacity-50"
    >
        <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
            <path
                fill-rule="evenodd"
                d="M8.5 3.5a1.7 1.7 0 0 1 3 0l6 10.5A1.7 1.7 0 0 1 16 16.5H4a1.7 1.7 0 0 1-1.5-2.5l6-10.5ZM10 7a.8.8 0 0 0-.8.8v3.4a.8.8 0 0 0 1.6 0V7.8A.8.8 0 0 0 10 7Zm0 7.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                clip-rule="evenodd"
            />
        </svg>
        <slot />
    </button>
</template>
