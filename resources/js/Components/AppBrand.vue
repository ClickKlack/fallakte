<script setup>
// Bildmarke plus Schriftzug; der Name kommt aus der App-Konfiguration (VITE_APP_NAME).
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    size: { type: String, default: 'md' }, // md | lg
});

const appName = import.meta.env.VITE_APP_NAME ?? '';
const [first, ...rest] = appName.split(' ');
</script>

<template>
    <span class="inline-flex items-center gap-2.5 text-fg">
        <ApplicationLogo :class="size === 'lg' ? 'h-14 w-14' : 'h-9 w-9'" />
        <span class="flex flex-col leading-none" :class="size === 'lg' ? 'text-2xl' : 'text-lg'">
            <span
                v-if="rest.length"
                class="text-[0.6em] font-medium uppercase tracking-[0.25em] text-muted"
                >{{ first }}</span
            >
            <span class="font-bold tracking-tight">{{ rest.length ? rest.join(' ') : first }}</span>
        </span>
    </span>
</template>
