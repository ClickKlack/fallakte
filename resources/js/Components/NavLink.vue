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
        class="inline-flex min-h-11 items-center border-b-2 px-1 transition"
        :class="[
            subtle ? 'text-xs font-medium' : 'text-sm font-semibold',
            active
                ? 'border-accent text-fg'
                : subtle
                  ? 'border-transparent text-subtle hover:text-muted'
                  : 'border-transparent text-muted hover:border-line-strong hover:text-fg',
        ]"
        :aria-current="active ? 'page' : undefined"
    >
        <slot />
    </Link>
</template>
