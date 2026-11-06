<script setup>
// Zeigt Kurzmeldungen aus der Session (flash.success) für einige Sekunden an.
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const message = ref(null);
let timer = null;

watch(
    () => page.props.flash?.success,
    (value) => {
        if (!value) return;
        message.value = value;
        clearTimeout(timer);
        timer = setTimeout(() => (message.value = null), 4000);
    },
    { immediate: true },
);
</script>

<template>
    <Transition
        enter-active-class="transition duration-200"
        enter-from-class="translate-y-4 opacity-0"
        leave-active-class="transition duration-150"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div
            v-if="message"
            class="fixed inset-x-4 bottom-4 z-50 mx-auto flex max-w-md items-center gap-3 rounded-xl border border-accent/50 bg-surface-raised p-4 text-sm text-fg shadow-2xl shadow-black/40"
            role="status"
        >
            <svg
                viewBox="0 0 20 20"
                fill="currentColor"
                class="h-5 w-5 shrink-0 text-success"
                aria-hidden="true"
            >
                <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z"
                    clip-rule="evenodd"
                />
            </svg>
            <span class="grow">{{ message }}</span>
            <button
                type="button"
                class="-m-2 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-muted hover:text-fg"
                :aria-label="$t('common.close')"
                @click="message = null"
            >
                ✕
            </button>
        </div>
    </Transition>
</template>
