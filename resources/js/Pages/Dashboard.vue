<script setup>
/**
 * Statistik-Dashboard: Gesamtfortschritt, Fortschritt nach Ausstrahlungsjahr, gesehene Folgen pro Jahr,
 * Team-Ranglisten und letzter Importlauf. Angekündigte Folgen zählen nirgends mit.
 * Der Reihenfilter oben gilt für alles darunter.
 */
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import ColumnChart from '@/Components/Charts/ColumnChart.vue';
import PageTitle from '@/Components/PageTitle.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import SeriesIcon from '@/Components/SeriesIcon.vue';
import { t } from '@/lang';
import { percent } from '@/lib/format.js';

const props = defineProps({
    series: { type: String, default: null },
    totals: { type: Object, required: true },
    byAirYear: { type: Array, required: true },
    seenPerYear: { type: Array, required: true },
    seenWithoutDate: { type: Number, required: true },
    teams: { type: Object, required: true },
    imports: { type: Array, required: true },
});

const filterOptions = computed(() => [
    { slug: null, name: t('episodes.filters.all_series') },
    ...props.totals.series.map((s) => ({ slug: s.slug, name: s.name })),
]);

function setSeries(slug) {
    router.get(route('dashboard'), slug ? { series: slug } : {}, {
        preserveScroll: true,
        preserveState: true,
    });
}

// Kennzahl für die Auswahl: eine Reihe oder gesamt
const headline = computed(() =>
    props.series ? props.totals.series.find((s) => s.slug === props.series) : props.totals.total,
);
const tiles = computed(() =>
    props.series ? props.totals.series.filter((s) => s.slug === props.series) : props.totals.series,
);

const airYearItems = computed(() =>
    props.byAirYear.map((row) => ({
        key: row.year,
        label: String(row.year),
        value: row.seen,
        context: row.aired,
    })),
);
const seenYearItems = computed(() =>
    props.seenPerYear.map((row) => ({ key: row.year, label: String(row.year), value: row.seen })),
);

const number = (value) => value.toLocaleString('de-DE');
const dateTime = (iso) =>
    iso
        ? new Date(iso).toLocaleString('de-DE', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '–';
const teamUrl = (team) => route('episodes.index', { team: team.id, status: 'unseen' });
</script>

<template>
    <Head :title="$t('dashboard.title')" />

    <AuthenticatedLayout>
        <template #header>
            <PageTitle :title="$t('dashboard.title')" :subtitle="$t('dashboard.subtitle')" />
        </template>

        <div class="space-y-6">
            <!-- Reihenfilter: gilt für alles darunter -->
            <div
                class="flex flex-wrap gap-2"
                role="group"
                :aria-label="$t('episodes.filters.series')"
            >
                <button
                    v-for="option in filterOptions"
                    :key="option.slug ?? 'all'"
                    type="button"
                    class="inline-flex min-h-11 items-center gap-2 rounded-full border px-4 text-sm font-semibold transition"
                    :class="
                        series === option.slug
                            ? 'border-fg bg-fg text-on-accent'
                            : 'border-line-strong text-muted hover:bg-surface-hover hover:text-fg'
                    "
                    :aria-pressed="series === option.slug"
                    @click="setSeries(option.slug)"
                >
                    <SeriesIcon
                        v-if="option.slug"
                        decorative
                        :slug="option.slug"
                        :name="option.name"
                    />
                    {{ option.name }}
                </button>
            </div>

            <!-- Gesamtfortschritt -->
            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-1">
                    <p class="text-sm font-medium text-muted">
                        {{
                            series
                                ? $t('dashboard.progress_series', { name: headline.name })
                                : $t('dashboard.progress_total')
                        }}
                    </p>
                    <p class="mt-1 text-6xl font-bold tracking-tight" data-test="hero">
                        {{ percent(headline.seen, headline.aired) }}&nbsp;%
                    </p>
                    <p class="mt-1 text-muted">
                        {{
                            $t('dashboard.seen_of', {
                                seen: number(headline.seen),
                                aired: number(headline.aired),
                            })
                        }}
                    </p>
                </Card>
                <div
                    class="grid gap-4 sm:grid-cols-2 lg:col-span-2"
                    :class="tiles.length === 1 ? 'sm:grid-cols-1' : ''"
                >
                    <Card v-for="tile in tiles" :key="tile.slug">
                        <p class="flex items-center gap-2 text-sm font-medium text-muted">
                            <SeriesIcon decorative :slug="tile.slug" :name="tile.name" />
                            {{ tile.name }}
                        </p>
                        <p class="mt-1 text-3xl font-semibold">
                            {{ percent(tile.seen, tile.aired) }}&nbsp;%
                        </p>
                        <ProgressBar class="mt-3" :done="tile.seen" :total="tile.aired" />
                    </Card>
                </div>
            </div>

            <!-- Fortschritt nach Ausstrahlungsjahr -->
            <Card :title="$t('dashboard.by_air_year')">
                <p class="-mt-2 mb-4 text-sm text-muted">{{ $t('dashboard.by_air_year_hint') }}</p>
                <ColumnChart
                    v-if="airYearItems.length"
                    :items="airYearItems"
                    :title="$t('dashboard.by_air_year')"
                    :value-label="$t('dashboard.seen')"
                    :context-label="$t('dashboard.aired')"
                />
                <p v-else class="text-sm text-muted">{{ $t('dashboard.no_data') }}</p>
            </Card>

            <!-- Gesehen pro Jahr -->
            <Card :title="$t('dashboard.seen_per_year')">
                <p class="-mt-2 mb-4 text-sm text-muted">
                    {{ $t('dashboard.seen_per_year_hint') }}
                </p>
                <ColumnChart
                    v-if="seenYearItems.length"
                    :items="seenYearItems"
                    :title="$t('dashboard.seen_per_year')"
                    :value-label="$t('dashboard.seen')"
                />
                <p v-else class="text-sm text-muted">{{ $t('dashboard.no_seen_dates') }}</p>
                <p
                    v-if="seenWithoutDate > 0"
                    class="mt-2 text-xs text-subtle"
                    data-test="without-date"
                >
                    {{ $t('dashboard.without_date', { count: number(seenWithoutDate) }) }}
                </p>
            </Card>

            <!-- Teams -->
            <div class="grid gap-4 lg:grid-cols-2">
                <Card
                    v-for="list in ['progress', 'unseen']"
                    :key="list"
                    :title="$t(`dashboard.teams_${list}`)"
                >
                    <p class="-mt-2 mb-4 text-sm text-muted">
                        {{ $t(`dashboard.teams_${list}_hint`) }}
                    </p>
                    <ol v-if="teams[list].length" class="space-y-4">
                        <li v-for="(team, index) in teams[list]" :key="team.id">
                            <Link
                                :href="teamUrl(team)"
                                class="-m-2 block rounded-lg p-2 transition hover:bg-surface-hover"
                            >
                                <div class="mb-1 flex items-center gap-2 text-sm font-semibold">
                                    <span class="w-5 tabular-nums text-subtle"
                                        >{{ index + 1 }}.</span
                                    >
                                    <SeriesIcon
                                        v-if="!series"
                                        :slug="team.series.slug"
                                        :name="team.series.name"
                                    />
                                    <span class="min-w-0 truncate">{{ team.name }}</span>
                                    <span
                                        v-if="list === 'unseen'"
                                        class="ml-auto shrink-0 font-normal text-muted"
                                    >
                                        {{ $t('dashboard.unseen_count', { count: team.unseen }) }}
                                    </span>
                                </div>
                                <ProgressBar :done="team.seen" :total="team.aired" />
                            </Link>
                        </li>
                    </ol>
                    <p v-else class="text-sm text-muted">{{ $t('dashboard.no_data') }}</p>
                </Card>
            </div>

            <!-- Letzter Import -->
            <Card :title="$t('dashboard.last_import')">
                <ul class="grid gap-4 sm:grid-cols-2">
                    <li
                        v-for="entry in imports"
                        :key="entry.series.slug"
                        class="rounded-lg border border-line p-4"
                    >
                        <p class="flex items-center gap-2 font-semibold">
                            <SeriesIcon
                                decorative
                                :slug="entry.series.slug"
                                :name="entry.series.name"
                            />
                            {{ entry.series.name }}
                        </p>
                        <template v-if="entry.run">
                            <p class="mt-1 text-sm text-muted">
                                {{ dateTime(entry.run.started_at) }}
                            </p>
                            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                                <dt class="text-muted">{{ $t('dashboard.import.created') }}</dt>
                                <dd class="text-right tabular-nums">{{ entry.run.created }}</dd>
                                <dt class="text-muted">{{ $t('dashboard.import.updated') }}</dt>
                                <dd class="text-right tabular-nums">{{ entry.run.updated }}</dd>
                                <dt class="text-muted">{{ $t('dashboard.import.skipped') }}</dt>
                                <dd class="text-right tabular-nums">{{ entry.run.skipped }}</dd>
                                <dt class="text-muted">{{ $t('dashboard.import.errors') }}</dt>
                                <dd
                                    class="text-right tabular-nums"
                                    :class="entry.run.errors ? 'text-danger' : ''"
                                >
                                    {{ entry.run.errors }}
                                </dd>
                            </dl>
                            <p
                                v-if="!entry.run.finished_at"
                                class="mt-3 flex items-center gap-1.5 text-sm text-danger"
                                role="alert"
                            >
                                ⚠ {{ $t('dashboard.import.unfinished') }}
                            </p>
                            <details v-if="entry.run.error_text" class="mt-3 text-sm">
                                <summary
                                    class="flex min-h-11 cursor-pointer items-center text-danger"
                                >
                                    ⚠ {{ $t('dashboard.import.show_errors') }}
                                </summary>
                                <pre
                                    class="mt-2 whitespace-pre-wrap rounded-md bg-app p-3 text-xs text-muted"
                                    >{{ entry.run.error_text }}</pre>
                            </details>
                        </template>
                        <p v-else class="mt-1 text-sm text-muted">
                            {{ $t('dashboard.import.never') }}
                        </p>
                    </li>
                </ul>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
