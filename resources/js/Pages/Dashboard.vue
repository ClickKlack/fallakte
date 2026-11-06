<script setup>
// Statistik-Dashboard – wird in Schritt 9 ausgebaut.
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import PageTitle from '@/Components/PageTitle.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head :title="$t('dashboard.title')" />

    <AuthenticatedLayout>
        <template #header>
            <PageTitle :title="$t('dashboard.title')" />
        </template>

        <Card>
            <p class="text-muted">{{ $t('dashboard.coming_soon') }}</p>
        </Card>
    </AuthenticatedLayout>
</template>
