<script setup>
defineProps({
    message: { type: String },
});
</script>

<template>
    <div v-show="message" role="alert">
        <p class="flex items-start gap-1.5 text-sm text-danger">
            <svg
                viewBox="0 0 20 20"
                fill="currentColor"
                class="mt-0.5 h-4 w-4 shrink-0"
                aria-hidden="true"
            >
                <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm0-12a.9.9 0 0 0-.9.9v3.6a.9.9 0 0 0 1.8 0V6.9A.9.9 0 0 0 10 6Zm0 8.2a1.1 1.1 0 1 0 0-2.2 1.1 1.1 0 0 0 0 2.2Z"
                    clip-rule="evenodd"
                />
            </svg>
            <span>{{ message }}</span>
        </p>
    </div>
</template>
