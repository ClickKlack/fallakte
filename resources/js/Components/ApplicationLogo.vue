<script setup>
/**
 * Bildmarke „Amis Fallakte“: Fadenkreuz mit Auge – angelehnt an die Bildsprache
 * eines Krimi-Vorspanns, aber eigenständig (kein Nachbau geschützter Logos).
 */
defineProps({
    title: { type: String, default: null },
});
</script>

<template>
    <svg
        viewBox="0 0 64 64"
        xmlns="http://www.w3.org/2000/svg"
        :role="title ? 'img' : undefined"
        :aria-hidden="title ? undefined : 'true'"
        fill="none"
    >
        <title v-if="title">{{ title }}</title>
        <!-- Fadenkreuz -->
        <circle cx="32" cy="32" r="22" stroke="currentColor" stroke-width="3" />
        <path
            d="M32 2v14M32 48v14M2 32h14M48 32h14"
            stroke="currentColor"
            stroke-width="3"
            stroke-linecap="round"
        />
        <!-- Auge -->
        <path
            d="M17 32c4-6.5 9-9.5 15-9.5s11 3 15 9.5c-4 6.5-9 9.5-15 9.5s-11-3-15-9.5Z"
            stroke="currentColor"
            stroke-width="2.5"
            stroke-linejoin="round"
        />
        <circle cx="32" cy="32" r="5.5" class="fill-accent" />
        <circle cx="32" cy="32" r="2.2" class="fill-bar" />
    </svg>
</template>
