<script setup>
// Vorläufige Darstellung der Folgenliste – die eigentliche Oberfläche entsteht in Schritt 6.
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ $t('episodes.title') }}
            </h2>
        </template>

        <div class="mx-auto max-w-7xl p-4 sm:px-6 lg:px-8">
            <p class="mb-4 text-sm text-gray-600">
                {{ $t('episodes.count', { count: episodes.meta.total }) }}
            </p>
            <ul class="divide-y divide-gray-200 bg-white shadow-sm sm:rounded-lg">
                <li
                    v-for="episode in episodes.data"
                    :key="episode.id"
                    class="flex gap-3 px-4 py-2 text-sm"
                >
                    <span class="w-24 shrink-0 text-gray-500">
                        {{ episode.series.name }} {{ episode.number }}
                    </span>
                    <span class="grow">{{ episode.title }}</span>
                    <span class="text-gray-500">{{ episode.first_aired }}</span>
                </li>
            </ul>
        </div>
    </AuthenticatedLayout>
</template>
