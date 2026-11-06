<script setup>
// Fläche für Inhaltsblöcke.
defineProps({
    title: { type: String, default: null },
});
</script>

<template>
    <section class="rounded-xl border border-line bg-surface p-4 sm:p-6">
        <header v-if="title || $slots.header" class="mb-4">
            <slot name="header">
                <h2 class="text-lg font-semibold text-fg">{{ title }}</h2>
            </slot>
        </header>
        <slot />
    </section>
</template>
