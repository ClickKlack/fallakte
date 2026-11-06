<script setup>
import AppBrand from '@/Components/AppBrand.vue';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-app px-4 py-10">
        <AppBrand size="lg" />

        <div
            class="mt-8 w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-2xl shadow-black/30 sm:p-8"
        >
            <slot />
        </div>
    </div>
</template>
