<script setup>
/**
 * Einklappbare Liste mit Vorschlägen für zusammengehörige Teams (gemeinsamer Ermittler-Nachname).
 * „Zusammenführen“ öffnet den bekannten Dialog mit vorausgewähltem Ziel; Gruppen lassen sich
 * ausblenden (gemerkt im Browser – reine Bequemlichkeit, keine gespeicherten Daten).
 */
import { computed, ref } from 'vue';
import SeriesIcon from '@/Components/SeriesIcon.vue';
import { mergeSuggestions } from '@/lib/mergeSuggestions.js';
import { matchesTeam } from '@/lib/teams.js';

const props = defineProps({
    teams: { type: Array, required: true },
    series: { type: Array, required: true },
    query: { type: String, default: '' },
    seriesFilter: { type: String, default: null },
});

defineEmits(['merge']);

const STORAGE_KEY = 'fallakte.dismissedMergeSuggestions';

function loadDismissed() {
    try {
        return new Set(JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]'));
    } catch {
        return new Set();
    }
}

const dismissed = ref(loadDismissed());
const open = ref(false);
const showDismissed = ref(false);

const seriesName = (slug) => props.series.find((s) => s.slug === slug)?.name ?? slug;

const all = computed(() =>
    mergeSuggestions(props.teams).filter(
        (group) =>
            (!props.seriesFilter || group.series === props.seriesFilter) &&
            (!props.query || group.teams.some((team) => matchesTeam(team, props.query))),
    ),
);
const visible = computed(() =>
    all.value.filter((g) => showDismissed.value || !dismissed.value.has(g.signature)),
);
const hiddenCount = computed(
    () => all.value.filter((g) => dismissed.value.has(g.signature)).length,
);
const openCount = computed(() => all.value.length - hiddenCount.value);

function toggleDismiss(group) {
    const next = new Set(dismissed.value);
    next.has(group.signature) ? next.delete(group.signature) : next.add(group.signature);
    dismissed.value = next;

    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify([...next]));
    } catch {
        // Speicher nicht verfügbar (z. B. privates Fenster) – Ausblenden gilt dann nur bis zum Neuladen
    }
}
</script>

<template>
    <section
        v-if="all.length"
        class="rounded-xl border border-line bg-surface"
        data-test="merge-suggestions"
    >
        <button
            type="button"
            class="flex min-h-12 w-full items-center justify-between gap-3 px-4 text-left font-semibold"
            :aria-expanded="open"
            @click="open = !open"
        >
            <span class="flex items-center gap-2">
                <svg
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    class="h-5 w-5 text-accent"
                    aria-hidden="true"
                >
                    <path
                        d="M10 2a6 6 0 0 0-3.6 10.8c.4.3.6.7.6 1.2v.5c0 .3.2.5.5.5h5c.3 0 .5-.2.5-.5V14c0-.5.2-.9.6-1.2A6 6 0 0 0 10 2Zm-2 15a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-.5H8v.5Z"
                    />
                </svg>
                {{ $t('teams.suggestions.title') }}
                <span
                    v-if="openCount"
                    class="rounded-full bg-accent px-2 py-0.5 text-xs font-bold text-on-accent"
                    >{{ openCount }}</span
                >
            </span>
            <svg
                viewBox="0 0 20 20"
                fill="currentColor"
                class="h-5 w-5 text-muted transition"
                :class="open ? 'rotate-180' : ''"
                aria-hidden="true"
            >
                <path
                    fill-rule="evenodd"
                    d="M5.3 7.3a1 1 0 0 1 1.4 0L10 10.6l3.3-3.3a1 1 0 1 1 1.4 1.4l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 0-1.4Z"
                    clip-rule="evenodd"
                />
            </svg>
        </button>

        <div v-show="open" class="space-y-3 border-t border-line p-4">
            <p class="text-sm text-muted">{{ $t('teams.suggestions.hint') }}</p>

            <article
                v-for="group in visible"
                :key="group.key"
                class="rounded-lg border border-line p-3"
                :class="dismissed.has(group.signature) ? 'opacity-50' : ''"
                :data-group="group.label"
            >
                <header class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="flex items-center gap-2 font-semibold">
                        <SeriesIcon :slug="group.series" :name="seriesName(group.series)" />
                        {{ group.label }}
                        <span class="text-sm font-normal text-subtle">
                            ({{
                                $t('teams.suggestions.team_count', { count: group.teams.length })
                            }})
                        </span>
                    </h3>
                    <button
                        type="button"
                        class="-my-2 inline-flex min-h-11 items-center text-xs font-semibold text-muted hover:text-fg"
                        data-test="dismiss"
                        @click="toggleDismiss(group)"
                    >
                        {{
                            dismissed.has(group.signature)
                                ? $t('teams.suggestions.restore')
                                : $t('teams.suggestions.dismiss')
                        }}
                    </button>
                </header>

                <ul class="mt-2 divide-y divide-line">
                    <li
                        v-for="team in group.teams"
                        :key="team.id"
                        class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-1.5 text-sm"
                    >
                        <span class="min-w-0">
                            {{ team.name }}
                            <span class="text-subtle"
                                >·
                                {{
                                    $t('teams.suggestions.episodes', { count: team.episodes_count })
                                }}</span
                            >
                            <span
                                v-if="team.id === group.target.id"
                                class="ml-1 rounded-full bg-accent-soft px-2 py-0.5 text-xs font-semibold text-accent-strong"
                                >{{ $t('teams.suggestions.target') }}</span
                            >
                        </span>
                        <button
                            v-if="team.id !== group.target.id"
                            type="button"
                            class="inline-flex min-h-11 items-center gap-1 rounded-lg px-2 text-xs font-semibold text-accent hover:bg-surface-hover"
                            :data-test="`suggest-merge-${team.id}`"
                            @click="$emit('merge', team, group.target)"
                        >
                            {{ $t('teams.suggestions.merge_into', { target: group.target.name }) }}
                        </button>
                    </li>
                </ul>
            </article>

            <button
                v-if="hiddenCount"
                type="button"
                class="inline-flex min-h-11 items-center text-sm font-semibold text-muted hover:text-fg"
                @click="showDismissed = !showDismissed"
            >
                {{
                    showDismissed
                        ? $t('teams.suggestions.hide_dismissed')
                        : $t('teams.suggestions.show_dismissed', { count: hiddenCount })
                }}
            </button>
        </div>
    </section>
</template>
