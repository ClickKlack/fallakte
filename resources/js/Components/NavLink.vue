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
        class="inline-flex min-h-11 items-center border-b-2 px-1 text-sm font-semibold transition"
        :class="
            active
                ? 'border-accent text-fg'
                : 'border-transparent text-muted hover:border-line-strong hover:text-fg'
        "
        :aria-current="active ? 'page' : undefined"
    >
        <slot />
    </Link>
</template>
