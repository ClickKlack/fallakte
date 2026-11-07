<script setup>
/**
 * Über-Seite: Widmung, Zweck, Datenquelle (Quellenangabe nach CC BY-SA), Datenstand, Technik und Version.
 */
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppBrand from '@/Components/AppBrand.vue';
import Card from '@/Components/Card.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SeriesIcon from '@/Components/SeriesIcon.vue';

defineProps({
    version: { type: String, required: true },
    laravel: { type: String, required: true },
    repository: { type: String, required: true },
    series: { type: Array, required: true },
    sqliteBackup: { type: Boolean, default: true },
});

// Backup-Formate (SQLite nur bei SQLite-Datenbank)
const backups = [
    { format: 'xlsx', icon: 'table' },
    { format: 'json', icon: 'code' },
    { format: 'sqlite', icon: 'database' },
];

// Manueller Import beider Reihen
const confirming = ref(false);
const importing = ref(false);

function runImport() {
    confirming.value = false;
    router.post(
        route('import.run'),
        {},
        {
            preserveScroll: true,
            onStart: () => (importing.value = true),
            onFinish: () => (importing.value = false),
        },
    );
}

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
        : null;
</script>

<template>
    <Head :title="$t('about.title')" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <div class="flex flex-col items-center gap-3 py-4 text-center">
                <AppBrand size="lg" />
                <p class="text-muted">{{ $t('about.tagline') }}</p>
                <p
                    class="rounded-full border border-line px-3 py-1 text-xs font-semibold text-subtle"
                    data-test="version"
                >
                    {{ $t('about.version', { version }) }}
                </p>
            </div>

            <!-- Widmung -->
            <Card>
                <blockquote
                    class="space-y-3 text-center text-lg leading-relaxed"
                    data-test="dedication"
                >
                    <p v-for="(line, index) in $t('about.dedication').split('\n')" :key="index">
                        {{ line }}
                    </p>
                </blockquote>
            </Card>

            <Card :title="$t('about.purpose_title')">
                <p class="text-muted">{{ $t('about.purpose') }}</p>
            </Card>

            <Card :title="$t('about.data_title')">
                <ul class="space-y-4">
                    <li
                        v-for="entry in series"
                        :key="entry.slug"
                        class="flex flex-wrap items-start gap-x-4 gap-y-1"
                    >
                        <span class="flex min-w-40 items-center gap-2 font-semibold">
                            <SeriesIcon decorative :slug="entry.slug" :name="entry.name" />
                            {{ entry.name }}
                        </span>
                        <span class="text-sm text-muted">
                            {{
                                $t('about.counts', {
                                    episodes: number(entry.episodes),
                                    teams: number(entry.teams),
                                })
                            }}<br />
                            <template v-if="entry.last_import">
                                {{ $t('about.last_import', { date: dateTime(entry.last_import) }) }}
                            </template>
                            <template v-else>{{ $t('about.never_imported') }}</template>
                        </span>
                    </li>
                </ul>
                <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-line pt-4">
                    <SecondaryButton
                        :disabled="importing"
                        data-test="import"
                        @click="confirming = true"
                    >
                        <svg
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            class="h-4 w-4"
                            :class="importing ? 'animate-spin' : ''"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M15.3 4.7A7 7 0 0 0 3.1 9a1 1 0 1 0 2 .2 5 5 0 0 1 8.7-3l-1.5 1.5H16V4l-.7.7ZM4.7 15.3A7 7 0 0 0 16.9 11a1 1 0 1 0-2-.2 5 5 0 0 1-8.7 3l1.5-1.5H4V16l.7-.7Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        {{ importing ? $t('about.importing') : $t('about.import') }}
                    </SecondaryButton>
                    <p class="text-xs text-subtle">{{ $t('about.import_hint') }}</p>
                </div>
            </Card>
            <Card :title="$t('about.backup_title')" data-test="backup">
                <p class="-mt-2 mb-4 text-sm text-muted">{{ $t('about.backup_hint') }}</p>
                <ul class="grid gap-3 sm:grid-cols-3">
                    <li
                        v-for="item in backups.filter((b) => b.format !== 'sqlite' || sqliteBackup)"
                        :key="item.format"
                    >
                        <a
                            :href="route('backup.download', item.format)"
                            class="flex h-full flex-col gap-1 rounded-lg border border-line p-4 transition hover:border-accent hover:bg-surface-hover"
                            :data-test="`backup-${item.format}`"
                            download
                        >
                            <span class="flex items-center gap-2 font-semibold">
                                <svg
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    class="h-4 w-4 text-accent"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M10 3a1 1 0 0 1 1 1v7.6l2.3-2.3a1 1 0 1 1 1.4 1.4l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 1.4-1.4L9 11.6V4a1 1 0 0 1 1-1ZM4 15a1 1 0 0 1 1 1v.5h10V16a1 1 0 1 1 2 0v1.5a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V16a1 1 0 0 1 1-1Z"
                                    />
                                </svg>
                                {{ $t(`about.backup.${item.format}.label`) }}
                            </span>
                            <span class="text-xs text-muted">{{
                                $t(`about.backup.${item.format}.hint`)
                            }}</span>
                        </a>
                    </li>
                </ul>
                <p v-if="sqliteBackup" class="mt-3 text-xs text-subtle">
                    {{ $t('about.backup_sqlite_note') }}
                </p>
            </Card>

            <Card :title="$t('about.source_title')">
                <div class="space-y-3 text-sm text-muted">
                    <p>{{ $t('about.source') }}</p>
                    <ul class="space-y-1">
                        <li v-for="entry in series" :key="entry.slug">
                            <a
                                :href="entry.source_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex min-h-11 items-center font-semibold text-accent hover:underline"
                                >{{ entry.source_title }} ↗</a
                            >
                        </li>
                    </ul>
                    <p>
                        {{ $t('about.license_prefix') }}
                        <a
                            href="https://creativecommons.org/licenses/by-sa/4.0/deed.de"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="font-semibold text-accent hover:underline"
                            >CC BY-SA 4.0</a
                        >.
                        {{ $t('about.license_suffix') }}
                    </p>
                    <p>{{ $t('about.trademark') }}</p>
                </div>
            </Card>

            <Card :title="$t('about.tech_title')">
                <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
                    <dt class="text-muted">{{ $t('about.tech.version') }}</dt>
                    <dd class="font-semibold tabular-nums">{{ version }}</dd>
                    <dt class="text-muted">{{ $t('about.tech.stack') }}</dt>
                    <dd>Laravel {{ laravel }} · Vue 3 · Inertia.js · Tailwind CSS · SQLite</dd>
                    <dt class="text-muted">{{ $t('about.tech.code') }}</dt>
                    <dd>
                        <a
                            :href="repository"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="font-semibold text-accent hover:underline"
                            >{{ $t('about.tech.repository') }} ↗</a
                        >
                    </dd>
                </dl>
            </Card>
        </div>
        <ConfirmDialog
            :show="confirming"
            :title="$t('about.import_confirm_title')"
            :confirm-label="$t('about.import')"
            @confirm="runImport"
            @cancel="confirming = false"
        >
            {{ $t('about.import_confirm') }}
        </ConfirmDialog>
    </AuthenticatedLayout>
</template>
