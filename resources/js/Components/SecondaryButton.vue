<script setup>
// Nebenaktion: umrandet, transparente Fläche.
defineProps({
    type: { type: String, default: 'button' },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-line-strong bg-transparent px-4 py-2 text-sm font-semibold text-fg transition hover:bg-surface-hover disabled:cursor-not-allowed disabled:opacity-50"
    >
        <slot />
    </button>
</template>
