<script setup>
// Hauptaktion: weiße Fläche mit dunkelblauer Schrift.
defineProps({
    type: { type: String, default: 'submit' },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-fg px-4 py-2 text-sm font-semibold text-on-accent transition hover:bg-accent-strong active:bg-accent disabled:cursor-not-allowed disabled:opacity-50"
    >
        <slot />
    </button>
</template>
