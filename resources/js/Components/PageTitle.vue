<script setup>
// Seitenüberschrift mit optionaler Unterzeile und Aktionen rechts.
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
});
</script>

<template>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-fg sm:text-3xl">
                {{ title }}
            </h1>
            <p v-if="subtitle" class="mt-1 text-sm text-muted">
                {{ subtitle }}
            </p>
        </div>
        <div v-if="$slots.actions" class="flex gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
