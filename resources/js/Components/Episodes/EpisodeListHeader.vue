<script setup>
// Kopfzeile ab Tablet-Breite mit sortierbaren Spalten (gleiches Raster wie EpisodeRow).
import { gridClass, SEEN_COLUMN } from './columns.js';

const props = defineProps({
    sort: { type: String, required: true },
    direction: { type: String, required: true },
    showSeries: { type: Boolean, default: true },
});

defineEmits(['sort']);

const columns = [
    { key: 'number', label: 'episodes.columns.number', sortable: true },
    { key: 'title', label: 'episodes.columns.title', sortable: true },
    { key: 'date', label: 'episodes.columns.first_aired', sortable: true },
    { key: 'broadcaster', label: 'episodes.columns.broadcaster', sortable: false },
    { key: 'team', label: 'episodes.columns.team', sortable: true },
];

const ariaSort = (key) =>
    props.sort === key ? (props.direction === 'asc' ? 'ascending' : 'descending') : 'none';
</script>

<template>
    <div
        class="hidden items-center gap-2 border-b border-line px-3 text-xs font-semibold uppercase tracking-wide text-subtle md:flex"
        role="row"
    >
        <span class="w-11 shrink-0" />
        <button
            type="button"
            class="inline-flex min-h-11 shrink-0 items-center gap-1 rounded-md px-2 text-left uppercase transition hover:text-fg"
            :class="[SEEN_COLUMN, sort === 'seen_at' ? 'text-fg' : '']"
            :aria-sort="ariaSort('seen_at')"
            data-sort="seen_at"
            @click="$emit('sort', 'seen_at')"
        >
            {{ $t('episodes.columns.seen_at') }}
            <svg
                viewBox="0 0 20 20"
                fill="currentColor"
                class="h-3.5 w-3.5 shrink-0 transition"
                :class="[
                    sort === 'seen_at' ? 'text-accent' : 'opacity-0',
                    sort === 'seen_at' && direction === 'desc' ? 'rotate-180' : '',
                ]"
                aria-hidden="true"
            >
                <path
                    fill-rule="evenodd"
                    d="M10 5a1 1 0 0 1 .7.3l4 4a1 1 0 0 1-1.4 1.4L10 7.4l-3.3 3.3a1 1 0 0 1-1.4-1.4l4-4A1 1 0 0 1 10 5Z"
                    clip-rule="evenodd"
                />
            </svg>
        </button>
        <div class="min-w-0 grow items-center gap-3" :class="gridClass(showSeries)">
            <template v-for="column in columns" :key="column.key">
                <button
                    v-if="column.sortable"
                    type="button"
                    class="-mx-1 inline-flex min-h-11 items-center gap-1 rounded-md px-1 text-left uppercase transition hover:text-fg"
                    :class="sort === column.key ? 'text-fg' : ''"
                    :aria-sort="ariaSort(column.key)"
                    :data-sort="column.key"
                    @click="$emit('sort', column.key)"
                >
                    {{ $t(column.label) }}
                    <svg
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        class="h-3.5 w-3.5 transition"
                        :class="[
                            sort === column.key ? 'text-accent' : 'opacity-0',
                            sort === column.key && direction === 'desc' ? 'rotate-180' : '',
                        ]"
                        aria-hidden="true"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 5a1 1 0 0 1 .7.3l4 4a1 1 0 0 1-1.4 1.4L10 7.4l-3.3 3.3a1 1 0 0 1-1.4-1.4l4-4A1 1 0 0 1 10 5Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
                <span v-else class="py-3">{{ $t(column.label) }}</span>
            </template>
        </div>
    </div>
</template>
