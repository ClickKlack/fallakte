<script setup>
/**
 * Team-Übersicht je Reihe: Zeitraum und Fortschritt (nur ausgestrahlte Folgen),
 * Klick führt zur gefilterten Folgenliste, Bearbeiten und Zusammenführen per Dialog.
 */
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Combobox from '@/Components/Combobox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import FilterPanel from '@/Components/FilterPanel.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import MergeSuggestions from '@/Components/Teams/MergeSuggestions.vue';
import Modal from '@/Components/Modal.vue';
import PageTitle from '@/Components/PageTitle.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import SeriesIcon from '@/Components/SeriesIcon.vue';
import TextInput from '@/Components/TextInput.vue';
import { t } from '@/lang';
import { groupTeams, mergeTargets, periodLabel, TEAM_SORTS } from '@/lib/teams.js';

const props = defineProps({
    teams: { type: Array, required: true },
    series: { type: Array, required: true },
});

const query = ref('');
const seriesFilter = ref(null);
const sort = ref('name');

const groups = computed(() =>
    groupTeams(props.teams, props.series, {
        query: query.value,
        series: seriesFilter.value,
        sort: sort.value,
    }),
);
const shownCount = computed(() => groups.value.reduce((sum, g) => sum + g.teams.length, 0));
const activeFilters = computed(() => (query.value ? 1 : 0) + (seriesFilter.value ? 1 : 0));

const seriesOptions = computed(() => props.series.map((s) => ({ value: s.slug, label: s.name })));
const sortOptions = TEAM_SORTS.map((s) => ({ value: s, label: t(`teams.sort.${s}`) }));

/** Einzahl/Mehrzahl: Schlüssel mit .one und .other */
const plural = (key, count) => t(`${key}.${count === 1 ? 'one' : 'other'}`, { count });

const episodesUrl = (team) => route('episodes.index', { team: team.id });

// Bearbeiten
const editing = ref(null);
const editForm = useForm({ name: '', city: '', investigators: '' });

function openEdit(team) {
    editing.value = team;
    editForm.defaults({
        name: team.name,
        city: team.city ?? '',
        investigators: team.investigators ?? '',
    });
    editForm.reset();
    editForm.clearErrors();
}

function saveEdit() {
    editForm.put(route('teams.update', editing.value.id), {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
}

// Zusammenführen
const merging = ref(null);
const mergeForm = useForm({ target_id: null });
const targets = computed(() => (merging.value ? mergeTargets(props.teams, merging.value) : []));
const mergeTarget = computed(
    () => props.teams.find((team) => team.id === mergeForm.target_id) ?? null,
);

function openMerge(team, target = null) {
    merging.value = team;
    mergeForm.reset();
    mergeForm.clearErrors();
    mergeForm.target_id = target?.id ?? null;
}

function confirmMerge() {
    mergeForm.post(route('teams.merge', merging.value.id), {
        preserveScroll: true,
        onSuccess: () => (merging.value = null),
    });
}
</script>

<template>
    <Head :title="$t('teams.title')" />

    <AuthenticatedLayout>
        <template #header>
            <PageTitle
                :title="$t('teams.title')"
                :subtitle="$t('teams.count', { shown: shownCount, total: teams.length })"
            />
        </template>

        <div class="space-y-6">
            <FilterPanel :active-count="activeFilters">
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div class="col-span-2">
                        <InputLabel for="team-search" :value="$t('teams.search')" />
                        <div class="relative mt-1">
                            <TextInput
                                id="team-search"
                                v-model="query"
                                type="search"
                                class="w-full pr-11"
                                :placeholder="$t('teams.search_placeholder')"
                                autocomplete="off"
                            />
                            <button
                                v-if="query"
                                type="button"
                                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-muted hover:text-fg"
                                :aria-label="$t('episodes.filters.clear_search')"
                                @click="query = ''"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                    <div>
                        <InputLabel for="team-series" :value="$t('episodes.filters.series')" />
                        <SelectInput
                            id="team-series"
                            v-model="seriesFilter"
                            class="mt-1 w-full"
                            :options="seriesOptions"
                            :placeholder="$t('episodes.filters.all_series')"
                        />
                    </div>
                    <div>
                        <InputLabel for="team-sort" :value="$t('episodes.filters.sort')" />
                        <SelectInput
                            id="team-sort"
                            v-model="sort"
                            class="mt-1 w-full"
                            :options="sortOptions"
                        />
                    </div>
                </div>
            </FilterPanel>

            <MergeSuggestions
                :teams="teams"
                :series="series"
                :query="query"
                :series-filter="seriesFilter"
                @merge="openMerge"
            />

            <p
                v-if="groups.length === 0"
                class="rounded-xl border border-line bg-surface px-4 py-10 text-center text-muted"
            >
                {{ $t('teams.empty') }}
            </p>

            <section v-for="group in groups" :key="group.series.slug" class="space-y-3">
                <h2 class="flex items-center gap-2 text-lg font-semibold">
                    <SeriesIcon decorative :slug="group.series.slug" :name="group.series.name" />
                    {{ group.series.name }}
                    <span class="text-sm font-normal text-subtle">({{ group.teams.length }})</span>
                </h2>

                <ul class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <li
                        v-for="team in group.teams"
                        :key="team.id"
                        class="flex flex-col rounded-xl border border-line bg-surface p-4"
                        :data-team="team.id"
                    >
                        <Link
                            :href="episodesUrl(team)"
                            class="-m-1 block rounded-lg p-1 transition hover:bg-surface-hover"
                            data-test="team-link"
                        >
                            <h3 class="font-semibold leading-snug">{{ team.name }}</h3>
                            <p class="mt-0.5 text-sm text-subtle">
                                <span v-if="team.city">{{ team.city }} · </span>
                                <span v-if="team.investigators">{{ team.investigators }} · </span>
                                {{ periodLabel(team) ?? $t('teams.not_aired') }}
                            </p>
                            <ProgressBar
                                v-if="team.aired_count > 0"
                                class="mt-3"
                                :done="team.seen_count"
                                :total="team.aired_count"
                            />
                        </Link>

                        <div class="mt-3 flex flex-wrap gap-2 border-t border-line pt-3">
                            <button
                                type="button"
                                class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-muted transition hover:bg-surface-hover hover:text-fg"
                                data-test="edit-team"
                                @click="openEdit(team)"
                            >
                                {{ $t('teams.edit') }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-muted transition hover:bg-surface-hover hover:text-fg"
                                data-test="merge-team"
                                @click="openMerge(team)"
                            >
                                {{ $t('teams.merge') }}
                            </button>
                        </div>
                    </li>
                </ul>
            </section>
        </div>

        <!-- Team bearbeiten -->
        <Modal :show="editing !== null" max-width="md" @close="editing = null">
            <form v-if="editing" class="space-y-4 p-6" @submit.prevent="saveEdit">
                <h2 class="text-lg font-semibold">{{ $t('teams.edit_title') }}</h2>
                <div>
                    <InputLabel for="team-name" :value="$t('teams.attributes.name')" />
                    <TextInput
                        id="team-name"
                        v-model="editForm.name"
                        class="mt-1 w-full"
                        required
                    />
                    <InputError class="mt-2" :message="editForm.errors.name" />
                </div>
                <div>
                    <InputLabel for="team-city" :value="$t('teams.attributes.city')" />
                    <TextInput id="team-city" v-model="editForm.city" class="mt-1 w-full" />
                    <InputError class="mt-2" :message="editForm.errors.city" />
                </div>
                <div>
                    <InputLabel
                        for="team-investigators"
                        :value="$t('teams.attributes.investigators')"
                    />
                    <TextInput
                        id="team-investigators"
                        v-model="editForm.investigators"
                        class="mt-1 w-full"
                    />
                    <InputError class="mt-2" :message="editForm.errors.investigators" />
                </div>
                <div v-if="editing.aliases.length">
                    <p class="text-sm font-medium text-muted">{{ $t('teams.aliases') }}</p>
                    <ul class="mt-1 flex flex-wrap gap-1.5">
                        <li
                            v-for="alias in editing.aliases"
                            :key="alias"
                            class="rounded-full bg-surface px-2.5 py-0.5 text-xs text-muted"
                        >
                            {{ alias }}
                        </li>
                    </ul>
                </div>
                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <SecondaryButton @click="editing = null">{{
                        $t('common.cancel')
                    }}</SecondaryButton>
                    <PrimaryButton :disabled="editForm.processing" data-test="save-team">
                        {{ $t('common.save') }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Teams zusammenführen -->
        <Modal :show="merging !== null" max-width="md" @close="merging = null">
            <div v-if="merging" class="space-y-4 p-6">
                <h2 class="text-lg font-semibold">
                    {{ $t('teams.merge_title', { name: merging.name }) }}
                </h2>
                <p class="text-sm text-muted">
                    {{ $t('teams.merge_hint', { name: merging.name }) }}
                </p>

                <div>
                    <InputLabel for="merge-target" :value="$t('teams.merge_target')" />
                    <Combobox
                        id="merge-target"
                        v-model="mergeForm.target_id"
                        class="mt-1"
                        :options="targets"
                        :placeholder="$t('teams.merge_target_placeholder')"
                    />
                    <InputError class="mt-2" :message="mergeForm.errors.target_id" />
                </div>

                <p
                    v-if="mergeTarget"
                    class="rounded-lg border border-danger/40 bg-danger-soft/60 p-3 text-sm"
                    data-test="merge-summary"
                >
                    {{
                        $t('teams.merge_summary', {
                            episodes: plural('teams.episodes_count', merging.episodes_count),
                            aliases: plural('teams.aliases_count', merging.aliases.length),
                            source: merging.name,
                            target: mergeTarget.name,
                        })
                    }}
                </p>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <SecondaryButton @click="merging = null">{{
                        $t('common.cancel')
                    }}</SecondaryButton>
                    <DangerButton
                        :disabled="!mergeTarget || mergeForm.processing"
                        data-test="confirm-merge"
                        @click="confirmMerge"
                    >
                        {{ $t('teams.merge_confirm') }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
