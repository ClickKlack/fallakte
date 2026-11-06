<script setup>
// Auswahlliste im App-Design. Optionen: [{ value, label }].
const model = defineModel({ type: [String, Number, null], default: null });

defineProps({
    options: { type: Array, required: true },
    placeholder: { type: String, default: null },
});
</script>

<template>
    <select
        v-model="model"
        class="min-h-11 rounded-lg border-line bg-surface py-2 pl-3 pr-9 text-base text-fg focus:border-accent focus:ring-1 focus:ring-accent"
    >
        <option v-if="placeholder !== null" :value="null">
            {{ placeholder }}
        </option>
        <option v-for="option in options" :key="option.value" :value="option.value">
            {{ option.label }}
        </option>
    </select>
</template>
