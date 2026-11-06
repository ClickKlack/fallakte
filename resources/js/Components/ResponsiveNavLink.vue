<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: { type: String, required: true },
    active: { type: Boolean },
});
</script>

<template>
    <Link
        :href="href"
        class="flex min-h-12 w-full items-center border-l-4 ps-4 pe-4 text-start text-base font-semibold transition"
        :class="
            active
                ? 'border-accent bg-surface text-fg'
                : 'border-transparent text-muted hover:bg-surface hover:text-fg'
        "
        :aria-current="active ? 'page' : undefined"
    >
        <slot />
    </Link>
</template>
