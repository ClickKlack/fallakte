<script setup>
/**
 * Folgenliste (Startseite): Filter im URL-Query, Sortierung, Paginierung und Abhaken direkt in der Liste.
 * Abhaken läuft ohne Seitenwechsel über den JSON-Endpunkt; die Zeile ändert sich sofort und wird
 * bei einem Fehler zurückgesetzt. So bleibt eine Folge auch im Filter „ungesehen“ stehen, bis neu gefiltert wird.
 */
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EpisodeFilterBar from '@/Components/Episodes/EpisodeFilterBar.vue';
import EpisodeListHeader from '@/Components/Episodes/EpisodeListHeader.vue';
import EpisodeRow from '@/Components/Episodes/EpisodeRow.vue';
import PageTitle from '@/Components/PageTitle.vue';
import Pagination from '@/Components/Pagination.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Toast from '@/Components/Toast.vue';
import { useFilterSync } from '@/composables/useFilterSync.js';
import { t } from '@/lang';
import { sendJson } from '@/lib/api.js';
import { nextSort, resetFilters } from '@/lib/filters.js';

const props = defineProps({
    episodes: { type: Object, required: true },
    filters: { type: Object, required: true },
    options: { type: Object, required: true },
});

const loading = ref(false);

const { form, setFromServer, update } = useFilterSync(
    props.filters,
    (query) =>
        router.get(route('episodes.index'), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['episodes', 'filters'],
            onStart: () => (loading.value = true),
            onFinish: () => (loading.value = false),
        }),
    { teams: props.options.teams },
);

// Zurück/Vor im Browser oder Paginierung: Formular an die Server-Filter angleichen
watch(() => props.filters, setFromServer);

// Lokale Kopie der Zeilen für sofortige Rückmeldung beim Abhaken
const rows = ref([...props.episodes.data]);
watch(
    () => props.episodes.data,
    (data) => (rows.value = [...data]),
);

const busy = reactive({});
const toast = ref(null);
let toastTimer = null;

function showToast(message) {
    toast.value = message;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => (toast.value = null), 6000);
}

async function changeSeen(episode, payload) {
    const index = rows.value.findIndex((row) => row.id === episode.id);
    const previous = { seen: episode.seen, seen_at: episode.seen_at };

    rows.value[index] = { ...episode, ...payload };
    busy[episode.id] = true;

    try {
        const result = await sendJson('PATCH', route('episodes.seen', episode.id), payload);
        rows.value[index] = { ...rows.value[index], ...result };
    } catch (error) {
        rows.value[index] = { ...rows.value[index], ...previous };
        showToast(
            error.status === 419 || error.status === 401
                ? t('episodes.session_expired')
                : error.message,
        );
    } finally {
        delete busy[episode.id];
    }
}

const showSeries = computed(() => !form.series);

// Aktueller Listenzustand (Filter, Sortierung, Seite) für die Rückkehr nach dem Bearbeiten
const page = usePage();
const returnTo = computed(() => {
    const index = page.url.indexOf('?');
    return index === -1 ? '' : page.url.slice(index);
});
const editUrl = (episode) =>
    route('episodes.edit', { episode: episode.id, return_to: returnTo.value || undefined });
const createUrl = computed(() =>
    route('episodes.create', { return_to: returnTo.value || undefined }),
);
const subtitle = computed(() =>
    props.episodes.meta.total === 1
        ? t('episodes.count_one')
        : t('episodes.count', { count: props.episodes.meta.total.toLocaleString('de-DE') }),
);
</script>

<template>
    <Head :title="$t('episodes.title')" />

    <AuthenticatedLayout>
        <template #header>
            <PageTitle :title="$t('episodes.title')" :subtitle="subtitle">
                <template #actions>
                    <Link
                        :href="createUrl"
                        class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-line-strong px-4 text-sm font-semibold text-fg transition hover:bg-surface-hover"
                        data-test="create"
                    >
                        <svg
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            class="h-4 w-4"
                            aria-hidden="true"
                        >
                            <path
                                d="M10 4a1 1 0 0 1 1 1v4h4a1 1 0 1 1 0 2h-4v4a1 1 0 1 1-2 0v-4H5a1 1 0 1 1 0-2h4V5a1 1 0 0 1 1-1Z"
                            />
                        </svg>
                        {{ $t('episodes.create') }}
                    </Link>
                </template>
            </PageTitle>
        </template>

        <div class="space-y-4">
            <EpisodeFilterBar
                :form="form"
                :options="options"
                @reset="update(resetFilters(form))"
                @clear-search="update({ q: null })"
            />

            <section
                class="overflow-hidden rounded-xl border border-line bg-surface transition-opacity"
                :class="loading ? 'opacity-60' : ''"
                :aria-busy="loading"
            >
                <EpisodeListHeader
                    :sort="form.sort"
                    :direction="form.direction"
                    :show-series="showSeries"
                    @sort="(column) => update(nextSort(form, column))"
                />

                <ul v-if="rows.length" class="divide-y divide-line">
                    <EpisodeRow
                        v-for="episode in rows"
                        :key="episode.id"
                        :episode="episode"
                        :show-series="showSeries"
                        :busy="!!busy[episode.id]"
                        :edit-url="editUrl(episode)"
                        @seen="(payload) => changeSeen(episode, payload)"
                    />
                </ul>

                <div v-else class="flex flex-col items-center gap-4 px-4 py-12 text-center">
                    <p class="text-muted">{{ $t('episodes.empty') }}</p>
                    <SecondaryButton @click="update(resetFilters(form))">
                        {{ $t('episodes.filters.reset') }}
                    </SecondaryButton>
                </div>
            </section>

            <Pagination :meta="episodes.meta" :links="episodes.links" />
        </div>

        <Toast :message="toast" @close="toast = null" />
    </AuthenticatedLayout>
</template>
