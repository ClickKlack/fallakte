<script setup>
/**
 * Eine Folge in der Liste. Smartphone: zweizeilige Karte. Ab Tablet: Spalten wie in der Kopfzeile.
 * Links stehen immer Haken und „gesehen am“ (feste Breite, auch ohne Haken reserviert).
 */
import SeenDate from '@/Components/SeenDate.vue';
import SeenToggle from '@/Components/SeenToggle.vue';
import SeriesIcon from '@/Components/SeriesIcon.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/lib/format.js';
import { gridClass, SEEN_COLUMN } from './columns.js';

defineProps({
    episode: { type: Object, required: true },
    showSeries: { type: Boolean, default: true },
    busy: { type: Boolean, default: false },
});

defineEmits(['seen']);
</script>

<template>
    <li
        class="flex items-center gap-2 px-1 py-1 sm:px-3"
        :class="episode.status === 'announced' ? 'bg-app/40' : ''"
        :data-episode="episode.id"
    >
        <SeenToggle
            :seen="episode.seen"
            :disabled="!episode.can_mark_seen"
            :busy="busy"
            :label="episode.title"
            @change="$emit('seen', $event)"
        />
        <div class="flex shrink-0" :class="SEEN_COLUMN">
            <SeenDate
                v-if="episode.seen"
                :seen-at="episode.seen_at"
                :disabled="busy"
                @change="$emit('seen', $event)"
            />
        </div>

        <!-- Smartphone: zweizeilig -->
        <div class="min-w-0 grow py-1.5 md:hidden">
            <p class="font-medium">
                <SeriesIcon
                    v-if="showSeries"
                    class="mr-1.5"
                    :slug="episode.series.slug"
                    :name="episode.series.name"
                />
                <span class="mr-1.5 font-semibold tabular-nums text-muted">{{
                    episode.number
                }}</span>
                {{ episode.title }}
                <StatusBadge class="ml-1 align-text-bottom" :status="episode.status" />
            </p>
            <p class="text-sm text-subtle">
                {{ formatDate(episode.first_aired) || $t('episodes.no_date') }}
                <span v-if="episode.broadcaster"> · {{ episode.broadcaster }}</span>
                <span v-if="episode.team"> · {{ episode.team.name }}</span>
            </p>
        </div>

        <!-- Ab Tablet: Spalten -->
        <div class="hidden min-w-0 grow items-center gap-3 py-2" :class="gridClass(showSeries)">
            <span class="flex items-center gap-2 font-semibold tabular-nums text-muted">
                <SeriesIcon
                    v-if="showSeries"
                    :slug="episode.series.slug"
                    :name="episode.series.name"
                />
                {{ episode.number }}
            </span>
            <span class="min-w-0 font-medium">
                {{ episode.title }}
                <StatusBadge class="ml-1 align-text-bottom" :status="episode.status" />
            </span>
            <span class="text-sm tabular-nums text-muted">
                {{ formatDate(episode.first_aired) || '–' }}
            </span>
            <span class="truncate text-sm text-muted" :title="episode.broadcaster">
                {{ episode.broadcaster ?? '–' }}
            </span>
            <span class="truncate text-sm text-muted" :title="episode.team?.name">
                {{ episode.team?.name ?? '–' }}
            </span>
        </div>
    </li>
</template>
