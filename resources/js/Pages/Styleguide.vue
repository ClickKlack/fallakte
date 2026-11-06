<script setup>
// Übersicht aller UI-Bausteine – nur in der lokalen Entwicklung erreichbar (Route /styleguide).
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import AppBrand from '@/Components/AppBrand.vue';
import Card from '@/Components/Card.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import DangerButton from '@/Components/DangerButton.vue';
import FilterPanel from '@/Components/FilterPanel.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PageTitle from '@/Components/PageTitle.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SeenDate from '@/Components/SeenDate.vue';
import SeenToggle from '@/Components/SeenToggle.vue';
import SelectInput from '@/Components/SelectInput.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head } from '@inertiajs/vue3';

const rows = ref([
    {
        number: '1342',
        title: 'Unter Freunden',
        date: '11.10.2026',
        team: 'Şahin und Krieger',
        seen: true,
        seen_at: '2026-10-12',
        status: 'aired',
    },
    {
        number: '1343',
        title: 'Unter Feinden',
        date: '18.10.2026',
        team: 'Şahin und Krieger',
        seen: true,
        seen_at: null,
        status: 'aired',
    },
    {
        number: '1344',
        title: 'Gottesgarten',
        date: '25.10.2026',
        team: 'Voss und Rathgeber',
        seen: false,
        seen_at: null,
        status: 'aired',
    },
    {
        number: '1345',
        title: 'Frei wie ein Adler',
        date: '08.11.2026',
        team: 'Grandjean und Ott',
        seen: false,
        seen_at: null,
        status: 'announced',
    },
]);
const confirmOpen = ref(false);
const team = ref(null);
const search = ref('');
</script>

<template>
    <Head title="Styleguide" />

    <AuthenticatedLayout>
        <template #header>
            <PageTitle title="Styleguide" subtitle="Bausteine im Überblick (nur lokal sichtbar)" />
        </template>

        <div class="space-y-6">
            <Card title="Logo">
                <div class="flex flex-wrap items-center gap-8">
                    <AppBrand size="lg" />
                    <AppBrand />
                    <ApplicationLogo class="h-16 w-16 text-fg" />
                    <ApplicationLogo class="h-8 w-8 text-fg" />
                </div>
            </Card>

            <Card title="Folgen mit Haken">
                <ul class="-mx-3 divide-y divide-line sm:mx-0">
                    <li v-for="row in rows" :key="row.number" class="flex items-center gap-2 py-1">
                        <SeenToggle
                            :seen="row.seen"
                            :disabled="row.status === 'announced'"
                            :label="row.title"
                            @change="(v) => Object.assign(row, v)"
                        />
                        <!-- Feste Spalte „gesehen am“ direkt neben dem Haken (kurze Mauswege);
                             auch ohne Haken reserviert, damit alle Titel bündig beginnen -->
                        <div class="flex w-[5.5rem] shrink-0 sm:w-32">
                            <SeenDate
                                v-if="row.seen"
                                :seen-at="row.seen_at"
                                @change="(v) => Object.assign(row, v)"
                            />
                        </div>
                        <div class="min-w-0 grow">
                            <!-- Fließtext statt Flexbox: Nummer und Titel bleiben zusammen, nur der Titel bricht um -->
                            <p class="font-medium">
                                <span class="mr-1.5 font-semibold tabular-nums text-muted">{{
                                    row.number
                                }}</span>
                                {{ row.title }}
                                <StatusBadge class="ml-1 align-text-bottom" :status="row.status" />
                            </p>
                            <div class="text-sm text-subtle">{{ row.date }} · {{ row.team }}</div>
                        </div>
                    </li>
                </ul>
            </Card>

            <Card title="Fortschritt">
                <div class="space-y-4">
                    <ProgressBar :done="512" :total="1344" />
                    <ProgressBar :done="0" :total="12" />
                    <ProgressBar :done="46" :total="46" />
                </div>
            </Card>

            <FilterPanel :active-count="2">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel for="sg-team" value="Team" />
                        <SelectInput
                            id="sg-team"
                            v-model="team"
                            class="mt-1 w-full"
                            placeholder="Alle Teams"
                            :options="[
                                { value: 1, label: 'Thiel und Boerne' },
                                { value: 2, label: 'Batic und Leitmayr' },
                            ]"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="sg-search" value="Titel" />
                        <TextInput
                            id="sg-search"
                            v-model="search"
                            type="search"
                            class="mt-1 w-full"
                            placeholder="z. B. Taxi"
                        />
                        <InputError class="mt-2" message="Beispiel für eine Fehlermeldung." />
                    </div>
                </div>
            </FilterPanel>

            <Card title="Buttons">
                <div class="flex flex-wrap gap-3">
                    <PrimaryButton type="button">Speichern</PrimaryButton>
                    <SecondaryButton>Abbrechen</SecondaryButton>
                    <DangerButton @click="confirmOpen = true">Teams zusammenführen</DangerButton>
                    <PrimaryButton type="button" disabled>Deaktiviert</PrimaryButton>
                </div>
            </Card>
        </div>

        <ConfirmDialog
            :show="confirmOpen"
            title="Teams zusammenführen?"
            confirm-label="Zusammenführen"
            danger
            @confirm="confirmOpen = false"
            @cancel="confirmOpen = false"
        >
            Alle Folgen und Schreibweisen von „Borowski und Sahin“ werden „Borowski und Şahin“
            zugeordnet.
        </ConfirmDialog>
    </AuthenticatedLayout>
</template>
