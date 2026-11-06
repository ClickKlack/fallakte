<script setup>
import { computed } from 'vue';
import { percent } from '@/lib/format.js';

const props = defineProps({
    done: { type: Number, required: true },
    total: { type: Number, required: true },
    showLabel: { type: Boolean, default: true },
});

const value = computed(() => percent(props.done, props.total));
</script>

<template>
    <div>
        <div v-if="showLabel" class="mb-1 flex items-baseline justify-between gap-2 text-sm">
            <span class="text-muted">
                {{ $t('progress.of', { done, total }) }}
            </span>
            <span class="font-semibold tabular-nums text-fg">{{ value }} %</span>
        </div>
        <div
            class="h-2.5 overflow-hidden rounded-full bg-surface-raised"
            role="progressbar"
            :aria-valuenow="value"
            aria-valuemin="0"
            aria-valuemax="100"
            :aria-label="$t('progress.of', { done, total })"
        >
            <div
                class="h-full rounded-full bg-accent transition-[width] duration-500"
                :style="{ width: `${value}%` }"
            />
        </div>
    </div>
</template>
