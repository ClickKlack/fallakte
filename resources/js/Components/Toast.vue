<script setup>
// Kurze Meldung am unteren Bildschirmrand (z. B. wenn das Abhaken fehlschlägt).
defineProps({
    message: { type: String, default: null },
});

defineEmits(['close']);
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
            class="fixed inset-x-4 bottom-4 z-50 mx-auto flex max-w-md items-start gap-3 rounded-xl border border-danger/60 bg-danger-soft p-4 text-sm text-fg shadow-2xl shadow-black/40"
            role="alert"
        >
            <span class="grow">{{ message }}</span>
            <button
                type="button"
                class="-m-2 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-muted hover:text-fg"
                :aria-label="$t('common.close')"
                @click="$emit('close')"
            >
                ✕
            </button>
        </div>
    </Transition>
</template>
