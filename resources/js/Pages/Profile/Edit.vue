<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import PageTitle from '@/Components/PageTitle.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    status: { type: String },
});
</script>

<template>
    <Head :title="$t('profile.title')" />

    <AuthenticatedLayout>
        <template #header>
            <PageTitle :title="$t('profile.title')" />
        </template>

        <div class="max-w-xl space-y-6">
            <Card>
                <UpdateProfileInformationForm :status="status" />
            </Card>
            <Card>
                <UpdatePasswordForm />
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
