<script setup>
/**
 * Einklappbarer Rahmen für Filter: auf dem Smartphone standardmäßig zu (Button mit Anzahl
 * aktiver Filter), ab Tablet-Breite immer offen.
 */
import { ref } from 'vue';

defineProps({
    activeCount: { type: Number, default: 0 },
});

const open = ref(false);
</script>

<template>
    <section class="rounded-xl border border-line bg-surface">
        <button
            type="button"
            class="flex min-h-12 w-full items-center justify-between gap-3 px-4 text-left font-semibold sm:hidden"
            :aria-expanded="open"
            @click="open = !open"
        >
            <span class="flex items-center gap-2">
                <svg
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    class="h-5 w-5 text-muted"
                    aria-hidden="true"
                >
                    <path
                        d="M3 4.5A1.5 1.5 0 0 1 4.5 3h11A1.5 1.5 0 0 1 17 4.5v1.1a1.5 1.5 0 0 1-.4 1L12 11.4V16a1 1 0 0 1-1.4.9l-2-1A1 1 0 0 1 8 15v-3.6L3.4 6.6a1.5 1.5 0 0 1-.4-1V4.5Z"
                    />
                </svg>
                {{ $t('filters.title') }}
                <span
                    v-if="activeCount > 0"
                    class="rounded-full bg-accent px-2 py-0.5 text-xs font-bold text-on-accent"
                    >{{ activeCount }}</span
                >
            </span>
            <svg
                viewBox="0 0 20 20"
                fill="currentColor"
                class="h-5 w-5 text-muted transition"
                :class="open ? 'rotate-180' : ''"
                aria-hidden="true"
            >
                <path
                    fill-rule="evenodd"
                    d="M5.3 7.3a1 1 0 0 1 1.4 0L10 10.6l3.3-3.3a1 1 0 1 1 1.4 1.4l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 0-1.4Z"
                    clip-rule="evenodd"
                />
            </svg>
        </button>

        <div
            :class="open ? 'block' : 'hidden sm:block'"
            class="border-t border-line p-4 sm:border-t-0"
        >
            <slot />
        </div>
    </section>
</template>
