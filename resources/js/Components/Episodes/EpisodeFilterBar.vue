<script setup>
/**
 * Filterleiste der Folgenliste: Reihe, Team, Status, Jahre, Titelsuche, Sortierung, Seitengröße.
 * Arbeitet direkt auf dem reaktiven Filterformular (useFilterSync).
 */
import { computed } from 'vue';
import Combobox from '@/Components/Combobox.vue';
import DateField from '@/Components/DateField.vue';
import FilterPanel from '@/Components/FilterPanel.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import { t } from '@/lang';
import { activeFilterCount, teamOptions } from '@/lib/filters.js';

const props = defineProps({
    form: { type: Object, required: true },
    options: { type: Object, required: true },
});

defineEmits(['reset', 'clear-search']);

const seriesOptions = computed(() =>
    props.options.series.map((s) => ({ value: s.slug, label: s.name })),
);
const teams = computed(() =>
    teamOptions(props.options.teams, props.options.series, props.form.series),
);
const broadcasters = computed(() =>
    props.options.broadcasters.map((b) => ({ value: b, label: b })),
);
const statuses = computed(() =>
    props.options.statuses.map((s) => ({ value: s, label: t(`episodes.status.${s}`) })),
);
const sorts = computed(() =>
    props.options.sorts.map((s) => ({ value: s, label: t(`episodes.sort.${s}`) })),
);
const pageSizes = computed(() =>
    props.options.per_page.map((n) => ({ value: n, label: String(n) })),
);
const years = computed(() => {
    const list = [];
    for (let y = props.options.years.max; y >= props.options.years.min; y--) {
        list.push({ value: y, label: String(y) });
    }
    return list;
});
const active = computed(() => activeFilterCount(props.form));
</script>

<template>
    <FilterPanel :active-count="active">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-6">
            <div class="col-span-2">
                <InputLabel for="filter-q" :value="$t('episodes.filters.search')" />
                <div class="relative mt-1">
                    <TextInput
                        id="filter-q"
                        v-model="form.q"
                        type="search"
                        class="w-full pr-11"
                        :placeholder="$t('episodes.filters.search_placeholder')"
                        autocomplete="off"
                        enterkeyhint="search"
                    />
                    <!-- Eigenes ×, da nicht jeder Browser (z. B. Firefox) eines anbietet -->
                    <button
                        v-if="form.q"
                        type="button"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-muted hover:text-fg"
                        :aria-label="$t('episodes.filters.clear_search')"
                        data-test="clear-search"
                        @click="$emit('clear-search')"
                    >
                        <svg
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            class="h-4 w-4"
                            aria-hidden="true"
                        >
                            <path
                                d="M5.3 5.3a1 1 0 0 1 1.4 0L10 8.6l3.3-3.3a1 1 0 1 1 1.4 1.4L11.4 10l3.3 3.3a1 1 0 0 1-1.4 1.4L10 11.4l-3.3 3.3a1 1 0 0 1-1.4-1.4L8.6 10 5.3 6.7a1 1 0 0 1 0-1.4Z"
                            />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="col-span-2 lg:col-span-1">
                <InputLabel for="filter-series" :value="$t('episodes.filters.series')" />
                <SelectInput
                    id="filter-series"
                    v-model="form.series"
                    class="mt-1 w-full"
                    :options="seriesOptions"
                    :placeholder="$t('episodes.filters.all_series')"
                />
            </div>
            <div class="col-span-2">
                <InputLabel for="filter-team" :value="$t('episodes.filters.team')" />
                <Combobox
                    id="filter-team"
                    v-model="form.team"
                    class="mt-1"
                    :options="teams"
                    :placeholder="$t('episodes.filters.all_teams')"
                />
            </div>
            <div>
                <InputLabel for="filter-status" :value="$t('episodes.filters.status')" />
                <SelectInput
                    id="filter-status"
                    v-model="form.status"
                    class="mt-1 w-full"
                    :options="statuses"
                />
            </div>
            <div class="lg:col-span-2">
                <InputLabel for="filter-broadcaster" :value="$t('episodes.filters.broadcaster')" />
                <SelectInput
                    id="filter-broadcaster"
                    v-model="form.broadcaster"
                    class="mt-1 w-full"
                    :options="broadcasters"
                    :placeholder="$t('episodes.filters.all_broadcasters')"
                />
            </div>
            <div>
                <InputLabel for="filter-year-from" :value="$t('episodes.filters.year_from')" />
                <Combobox
                    id="filter-year-from"
                    v-model="form.year_from"
                    class="mt-1"
                    :options="years"
                    :limit="12"
                    inputmode="numeric"
                    :placeholder="$t('episodes.filters.any_year')"
                />
            </div>
            <div>
                <InputLabel for="filter-year-to" :value="$t('episodes.filters.year_to')" />
                <Combobox
                    id="filter-year-to"
                    v-model="form.year_to"
                    class="mt-1"
                    :options="years"
                    :limit="12"
                    inputmode="numeric"
                    :placeholder="$t('episodes.filters.any_year')"
                />
            </div>

            <div class="col-span-2 sm:col-span-1">
                <InputLabel for="filter-seen-from" :value="$t('episodes.filters.seen_from')" />
                <DateField
                    id="filter-seen-from"
                    v-model="form.seen_from"
                    class="mt-1"
                    :dialog-title="$t('episodes.filters.seen_from')"
                />
            </div>
            <div class="col-span-2 sm:col-span-1">
                <InputLabel for="filter-seen-to" :value="$t('episodes.filters.seen_to')" />
                <DateField
                    id="filter-seen-to"
                    v-model="form.seen_to"
                    class="mt-1"
                    :dialog-title="$t('episodes.filters.seen_to')"
                />
            </div>

            <div class="col-span-2">
                <InputLabel for="filter-sort" :value="$t('episodes.filters.sort')" />
                <div class="mt-1 flex gap-2">
                    <SelectInput
                        id="filter-sort"
                        v-model="form.sort"
                        class="grow"
                        :options="sorts"
                    />
                    <button
                        type="button"
                        class="inline-flex min-h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-line bg-surface text-muted transition hover:text-fg"
                        :aria-label="
                            form.direction === 'asc'
                                ? $t('episodes.sort.ascending')
                                : $t('episodes.sort.descending')
                        "
                        :title="
                            form.direction === 'asc'
                                ? $t('episodes.sort.ascending')
                                : $t('episodes.sort.descending')
                        "
                        data-test="direction"
                        @click="form.direction = form.direction === 'asc' ? 'desc' : 'asc'"
                    >
                        <svg
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            class="h-5 w-5 transition"
                            :class="form.direction === 'desc' ? 'rotate-180' : ''"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 3a1 1 0 0 1 .7.3l4 4a1 1 0 0 1-1.4 1.4L11 6.4V16a1 1 0 1 1-2 0V6.4L6.7 8.7a1 1 0 0 1-1.4-1.4l4-4A1 1 0 0 1 10 3Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </div>
            </div>
            <div>
                <InputLabel for="filter-per-page" :value="$t('episodes.filters.per_page')" />
                <SelectInput
                    id="filter-per-page"
                    v-model="form.per_page"
                    class="mt-1 w-full"
                    :options="pageSizes"
                />
            </div>
            <div class="flex items-end lg:col-span-3 lg:justify-end">
                <SecondaryButton
                    class="w-full lg:w-auto"
                    :disabled="active === 0"
                    @click="$emit('reset')"
                >
                    {{ $t('episodes.filters.reset_short') }}
                </SecondaryButton>
            </div>
        </div>
    </FilterPanel>
</template>
