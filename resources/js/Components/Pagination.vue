<script setup>
/**
 * Seitennavigation für Laravel-Paginierung (meta + links aus einer Resource-Collection).
 * Auf dem Smartphone nur „Zurück / Seite x von y / Weiter“.
 */
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    meta: { type: Object, required: true },
    links: { type: Object, required: true },
});

// Nummerierte Links ohne „Zurück/Weiter“ (die ersten und letzten Einträge von meta.links).
const pages = computed(() => (props.meta.links ?? []).slice(1, -1));
</script>

<template>
    <nav
        v-if="meta.last_page > 1"
        class="flex items-center justify-between gap-2"
        :aria-label="$t('pagination.label')"
    >
        <component
            :is="links.prev ? Link : 'span'"
            :href="links.prev ?? undefined"
            preserve-scroll
            class="inline-flex min-h-11 items-center rounded-lg border border-line px-4 text-sm font-semibold"
            :class="links.prev ? 'text-fg hover:bg-surface-hover' : 'text-subtle opacity-50'"
        >
            {{ $t('pagination.previous') }}
        </component>

        <span class="text-sm text-muted sm:hidden">
            {{
                $t('pagination.page_of', {
                    page: meta.current_page,
                    pages: meta.last_page,
                })
            }}
        </span>

        <div class="hidden flex-wrap justify-center gap-1 sm:flex">
            <template v-for="(page, index) in pages" :key="index">
                <span v-if="!page.url" class="px-2 py-2 text-subtle">…</span>
                <Link
                    v-else
                    :href="page.url"
                    class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-sm font-semibold tabular-nums"
                    :class="
                        page.active
                            ? 'bg-fg text-on-accent'
                            : 'text-muted hover:bg-surface-hover hover:text-fg'
                    "
                    :aria-current="page.active ? 'page' : undefined"
                >
                    {{ page.label }}
                </Link>
            </template>
        </div>

        <component
            :is="links.next ? Link : 'span'"
            :href="links.next ?? undefined"
            class="inline-flex min-h-11 items-center rounded-lg border border-line px-4 text-sm font-semibold"
            :class="links.next ? 'text-fg hover:bg-surface-hover' : 'text-subtle opacity-50'"
        >
            {{ $t('pagination.next') }}
        </component>
    </nav>
</template>
