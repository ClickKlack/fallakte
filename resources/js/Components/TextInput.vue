<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: [String, Number],
    default: '',
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="min-h-11 rounded-lg border-line bg-surface px-3 text-base text-fg placeholder:text-subtle focus:border-accent focus:ring-1 focus:ring-accent"
        v-model="model"
        ref="input"
    />
</template>
