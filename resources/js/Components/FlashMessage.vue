<script setup>
// Zeigt Kurzmeldungen aus der Session (flash.success / flash.error) für einige Sekunden an.
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const message = ref(null);
const kind = ref('success');
let timer = null;

function show(text, type, duration) {
    message.value = text;
    kind.value = type;
    clearTimeout(timer);
    timer = setTimeout(() => (message.value = null), duration);
}

watch(
    () => page.props.flash?.success,
    (value) => value && show(value, 'success', 5000),
    { immediate: true },
);
watch(
    () => page.props.flash?.error,
    (value) => value && show(value, 'error', 10000),
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
            class="fixed inset-x-4 bottom-4 z-50 mx-auto flex max-w-md items-start gap-3 rounded-xl border p-4 text-sm text-fg shadow-2xl shadow-black/40"
            :class="
                kind === 'error'
                    ? 'border-danger/60 bg-danger-soft'
                    : 'border-accent/50 bg-surface-raised'
            "
            :role="kind === 'error' ? 'alert' : 'status'"
            :data-test="`flash-${kind}`"
        >
            <svg
                v-if="kind === 'success'"
                viewBox="0 0 20 20"
                fill="currentColor"
                class="mt-0.5 h-5 w-5 shrink-0 text-success"
                aria-hidden="true"
            >
                <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z"
                    clip-rule="evenodd"
                />
            </svg>
            <svg
                v-else
                viewBox="0 0 20 20"
                fill="currentColor"
                class="mt-0.5 h-5 w-5 shrink-0 text-danger"
                aria-hidden="true"
            >
                <path
                    fill-rule="evenodd"
                    d="M8.5 3.5a1.7 1.7 0 0 1 3 0l6 10.5A1.7 1.7 0 0 1 16 16.5H4a1.7 1.7 0 0 1-1.5-2.5l6-10.5ZM10 7a.8.8 0 0 0-.8.8v3.4a.8.8 0 0 0 1.6 0V7.8A.8.8 0 0 0 10 7Zm0 7.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
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
