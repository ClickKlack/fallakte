<script setup>
// Vorläufige Darstellung der Folgenliste – die eigentliche Oberfläche entsteht in Schritt 6.
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageTitle from '@/Components/PageTitle.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/lib/format.js';
import { Head } from '@inertiajs/vue3';

defineProps({
    episodes: { type: Object, required: true },
    filters: { type: Object, required: true },
    options: { type: Object, required: true },
});
</script>

<template>
    <Head :title="$t('episodes.title')" />

    <AuthenticatedLayout>
        <template #header>
            <PageTitle
                :title="$t('episodes.title')"
                :subtitle="$t('episodes.count', { count: episodes.meta.total })"
            />
        </template>

        <ul class="divide-y divide-line overflow-hidden rounded-xl border border-line bg-surface">
            <li
                v-for="episode in episodes.data"
                :key="episode.id"
                class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-4 py-3"
            >
                <span class="w-14 shrink-0 font-semibold tabular-nums text-muted">{{
                    episode.number
                }}</span>
                <span class="min-w-0 grow font-medium">{{ episode.title }}</span>
                <StatusBadge :status="episode.status" />
                <span class="text-sm tabular-nums text-subtle">{{
                    formatDate(episode.first_aired)
                }}</span>
            </li>
        </ul>

        <Pagination class="mt-6" :meta="episodes.meta" :links="episodes.links" />
    </AuthenticatedLayout>
</template>
