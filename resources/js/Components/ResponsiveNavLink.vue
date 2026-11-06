<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: { type: String, required: true },
    active: { type: Boolean },
    // Nebensächlicher Menüpunkt (z. B. „Über“): kleiner und gedämpfter
    subtle: { type: Boolean, default: false },
});
</script>

<template>
    <Link
        :href="href"
        class="flex min-h-12 w-full items-center border-l-4 ps-4 pe-4 text-start transition"
        :class="[
            subtle ? 'text-sm font-medium' : 'text-base font-semibold',
            active
                ? 'border-accent bg-surface text-fg'
                : subtle
                  ? 'border-transparent text-subtle hover:bg-surface hover:text-muted'
                  : 'border-transparent text-muted hover:bg-surface hover:text-fg',
        ]"
        :aria-current="active ? 'page' : undefined"
    >
        <slot />
    </Link>
</template>
