<script setup>
/**
 * Folge bearbeiten bzw. manuell anlegen (episode = null).
 * Manuell geänderte Felder sind vor dem Import geschützt (Schloss); der Schutz lässt sich je Feld aufheben.
 */
import { computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Combobox from '@/Components/Combobox.vue';
import DateField from '@/Components/DateField.vue';
import FieldLabel from '@/Components/FieldLabel.vue';
import InputError from '@/Components/InputError.vue';
import PageTitle from '@/Components/PageTitle.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import SeriesIcon from '@/Components/SeriesIcon.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TextInput from '@/Components/TextInput.vue';
import { t } from '@/lang';
import { todayIso } from '@/lib/format.js';

const props = defineProps({
    episode: { type: Object, default: null },
    options: { type: Object, required: true },
    returnTo: { type: String, default: '' },
});

const isNew = computed(() => props.episode === null);

const form = useForm({
    series_id: props.episode?.series.id ?? props.options.series[0]?.id ?? null,
    number: props.episode?.number ?? null,
    title: props.episode?.title ?? '',
    first_aired: props.episode?.first_aired ?? null,
    broadcaster: props.episode?.broadcaster ?? '',
    team_id: props.episode?.team_id ?? null,
    seen: props.episode?.seen ?? false,
    seen_at: props.episode?.seen_at ?? null,
    return_to: props.returnTo,
});

const seriesOptions = computed(() =>
    props.options.series.map((s) => ({ value: s.id, label: s.name })),
);
const teamOptions = computed(() =>
    props.options.teams
        .filter((team) => team.series_id === form.series_id)
        .map((team) => ({ value: team.id, label: team.name })),
);

// Team passt nicht mehr zur gewählten Reihe → entfernen
watch(
    () => form.series_id,
    () => {
        if (form.team_id && !teamOptions.value.some((o) => o.value === form.team_id)) {
            form.team_id = null;
        }
    },
);

// Beim Abhaken das heutige Datum vorschlagen, beim Entfernen das Datum leeren
watch(
    () => form.seen,
    (seen) => {
        form.seen_at = seen ? (form.seen_at ?? todayIso()) : null;
    },
);

const isProtected = (field) => !isNew.value && (props.episode.manual_fields ?? []).includes(field);
const canReset = computed(() => !isNew.value && props.episode.from_wikipedia);
const listUrl = computed(() => route('episodes.index') + props.returnTo);

function submit() {
    form.transform((data) => ({ ...data, series_id: isNew.value ? data.series_id : undefined }));

    if (isNew.value) {
        form.post(route('episodes.store'));
    } else {
        form.put(route('episodes.update', props.episode.id));
    }
}

function resetProtection(field) {
    router.delete(route('episodes.protection.destroy', [props.episode.id, field]), {
        preserveScroll: true,
        preserveState: true,
        only: ['episode', 'flash'],
    });
}

const pageTitle = computed(() =>
    isNew.value ? t('episodes.form.create_title') : t('episodes.form.edit_title'),
);
</script>

<template>
    <Head :title="pageTitle" />

    <AuthenticatedLayout>
        <template #header>
            <Link
                :href="listUrl"
                class="-ml-1 mb-2 inline-flex min-h-11 items-center gap-1 rounded-lg px-1 text-sm font-semibold text-muted hover:text-fg"
            >
                <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                    <path
                        fill-rule="evenodd"
                        d="M12.7 5.3a1 1 0 0 1 0 1.4L9.4 10l3.3 3.3a1 1 0 1 1-1.4 1.4l-4-4a1 1 0 0 1 0-1.4l4-4a1 1 0 0 1 1.4 0Z"
                        clip-rule="evenodd"
                    />
                </svg>
                {{ $t('episodes.form.back') }}
            </Link>
            <PageTitle :title="pageTitle" />
        </template>

        <form class="max-w-2xl space-y-6" novalidate @submit.prevent="submit">
            <Card>
                <div
                    v-if="!isNew"
                    class="mb-5 flex flex-wrap items-center gap-2 text-sm text-muted"
                >
                    <SeriesIcon :slug="episode.series.slug" :name="episode.series.name" />
                    <span>{{ episode.series.name }}</span>
                    <span class="font-semibold tabular-nums">· {{ episode.display_number }}</span>
                    <StatusBadge :status="episode.status" />
                </div>

                <p v-if="isNew" class="mb-5 text-sm text-muted">
                    {{ $t('episodes.form.create_hint') }}
                </p>
                <p v-else-if="episode.from_wikipedia" class="mb-5 text-sm text-muted">
                    {{ $t('episodes.form.edit_hint') }}
                </p>

                <div class="grid gap-5 sm:grid-cols-6">
                    <div v-if="isNew" class="sm:col-span-6">
                        <FieldLabel for="series" :label="$t('episodes.attributes.series_id')" />
                        <SelectInput
                            id="series"
                            v-model="form.series_id"
                            class="mt-1 w-full"
                            :options="seriesOptions"
                        />
                        <InputError class="mt-2" :message="form.errors.series_id" />
                    </div>

                    <div class="sm:col-span-2">
                        <FieldLabel
                            for="number"
                            :label="$t('episodes.attributes.number')"
                            :protected="isProtected('number')"
                            :resettable="canReset"
                            @reset="resetProtection('number')"
                        />
                        <TextInput
                            id="number"
                            v-model.number="form.number"
                            type="number"
                            inputmode="numeric"
                            min="1"
                            class="mt-1 w-full tabular-nums"
                            :placeholder="episode?.number === null ? episode.display_number : ''"
                        />
                        <InputError class="mt-2" :message="form.errors.number" />
                    </div>

                    <div class="sm:col-span-4">
                        <FieldLabel
                            for="first_aired"
                            :label="$t('episodes.attributes.first_aired')"
                            :protected="isProtected('first_aired')"
                            :resettable="canReset"
                            @reset="resetProtection('first_aired')"
                        />
                        <DateField
                            id="first_aired"
                            v-model="form.first_aired"
                            class="mt-1"
                            max="2100-12-31"
                            :dialog-title="$t('episodes.attributes.first_aired')"
                            :invalid="!!form.errors.first_aired"
                        />
                        <InputError class="mt-2" :message="form.errors.first_aired" />
                    </div>

                    <div class="sm:col-span-6">
                        <FieldLabel
                            for="title"
                            :label="$t('episodes.attributes.title')"
                            :protected="isProtected('title')"
                            :resettable="canReset"
                            @reset="resetProtection('title')"
                        />
                        <TextInput
                            id="title"
                            v-model="form.title"
                            type="text"
                            class="mt-1 w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>

                    <div class="sm:col-span-2">
                        <FieldLabel
                            for="broadcaster"
                            :label="$t('episodes.attributes.broadcaster')"
                            :protected="isProtected('broadcaster')"
                            :resettable="canReset"
                            @reset="resetProtection('broadcaster')"
                        />
                        <TextInput
                            id="broadcaster"
                            v-model="form.broadcaster"
                            type="text"
                            list="broadcaster-suggestions"
                            autocomplete="off"
                            class="mt-1 w-full"
                        />
                        <datalist id="broadcaster-suggestions">
                            <option
                                v-for="name in options.broadcasters"
                                :key="name"
                                :value="name"
                            />
                        </datalist>
                        <InputError class="mt-2" :message="form.errors.broadcaster" />
                    </div>

                    <div class="sm:col-span-4">
                        <FieldLabel
                            for="team"
                            :label="$t('episodes.attributes.team_id')"
                            :protected="isProtected('team_id')"
                            :resettable="canReset"
                            @reset="resetProtection('team_id')"
                        />
                        <Combobox
                            id="team"
                            v-model="form.team_id"
                            class="mt-1"
                            :options="teamOptions"
                            :placeholder="$t('episodes.form.no_team')"
                        />
                        <InputError class="mt-2" :message="form.errors.team_id" />
                    </div>
                </div>
            </Card>

            <Card>
                <div class="grid gap-5 sm:grid-cols-6">
                    <div class="sm:col-span-2">
                        <span class="block text-sm font-medium text-muted">{{
                            $t('episodes.attributes.seen')
                        }}</span>
                        <label class="mt-1 flex min-h-11 items-center gap-3">
                            <Checkbox id="seen" v-model:checked="form.seen" />
                            <span>{{ $t('episodes.form.seen_label') }}</span>
                        </label>
                        <InputError class="mt-2" :message="form.errors.seen" />
                    </div>
                    <div class="sm:col-span-4">
                        <FieldLabel for="seen_at" :label="$t('episodes.attributes.seen_at')" />
                        <DateField
                            id="seen_at"
                            v-model="form.seen_at"
                            class="mt-1"
                            :disabled="!form.seen"
                            :dialog-title="$t('episodes.attributes.seen_at')"
                            :invalid="!!form.errors.seen_at"
                        />
                        <InputError class="mt-2" :message="form.errors.seen_at" />
                    </div>
                </div>
            </Card>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link
                    :href="listUrl"
                    class="inline-flex min-h-11 items-center justify-center rounded-lg border border-line-strong px-4 text-sm font-semibold text-fg transition hover:bg-surface-hover"
                >
                    {{ $t('common.cancel') }}
                </Link>
                <PrimaryButton :disabled="form.processing" data-test="save">
                    {{ isNew ? $t('episodes.form.create') : $t('common.save') }}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
