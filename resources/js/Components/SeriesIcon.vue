<script setup>
/**
 * Symbol einer Reihe (eigene Gestaltung, keine Nachbildung von Sender-Logos):
 * Tatort = Fingerabdruck, Polizeiruf 110 = Notruf-Plakette „110“.
 */
defineProps({
    slug: { type: String, required: true },
    name: { type: String, required: true },
    // Steht der Name schon als Text daneben, ist das Symbol rein dekorativ (sonst doppelt vorgelesen)
    decorative: { type: Boolean, default: false },
});
</script>

<template>
    <span
        class="inline-flex shrink-0 align-middle"
        :title="decorative ? undefined : name"
        :role="decorative ? undefined : 'img'"
        :aria-label="decorative ? undefined : name"
        :aria-hidden="decorative ? 'true' : undefined"
        :data-series="slug"
    >
        <!-- Tatort: Fingerabdruck -->
        <svg
            v-if="slug === 'tatort'"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
            class="h-5 w-5 text-accent"
            aria-hidden="true"
        >
            <path d="M6.2 6.6A8 8 0 0 1 19.6 9" />
            <path d="M4.3 10.4A8 8 0 0 1 4.9 8.6" />
            <path d="M20 12.5c0 2.6-.4 4.7-1 6.5" />
            <path d="M7.5 18.8c.8-1.9 1.2-4 1.2-6.3a3.3 3.3 0 0 1 6.6 0c0 1.2-.1 2.4-.3 3.5" />
            <path d="M4.5 14.5c.6-1.2.9-2.6.9-4" />
            <path d="M12 12.5c0 3.4-.8 6.4-2.3 8.7" />
            <path d="M14.5 18.6c-.3 1-.7 1.9-1.1 2.7" />
            <path d="M17.2 15.3c0 1.3-.2 2.6-.5 3.8" />
            <path d="M5.8 17.6c-.3.6-.6 1.2-1 1.7" />
        </svg>
        <!-- Polizeiruf 110: Notruf-Plakette -->
        <svg
            v-else-if="slug === 'polizeiruf-110'"
            viewBox="0 0 24 24"
            class="h-5 w-5"
            aria-hidden="true"
        >
            <rect x="1.5" y="5" width="21" height="14" rx="4" class="fill-fg" />
            <text
                x="12"
                y="15.6"
                text-anchor="middle"
                font-size="9.5"
                font-weight="800"
                font-family="ui-sans-serif, system-ui, sans-serif"
                letter-spacing="-0.3"
                class="fill-app"
            >
                110
            </text>
        </svg>
        <span v-else class="text-xs text-subtle">{{ name }}</span>
    </span>
</template>
