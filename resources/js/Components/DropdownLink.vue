<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: { type: String, required: true },
});
</script>

<template>
    <Link
        :href="href"
        class="flex min-h-11 w-full items-center px-4 text-start text-sm text-fg transition hover:bg-surface-hover focus:bg-surface-hover focus:outline-hidden"
    >
        <slot />
    </Link>
</template>
