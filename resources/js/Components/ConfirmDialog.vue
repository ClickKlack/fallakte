<script setup>
// Bestätigungsdialog für folgenreiche Aktionen (z. B. Teams zusammenführen).
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
    show: { type: Boolean, required: true },
    title: { type: String, required: true },
    confirmLabel: { type: String, required: true },
    danger: { type: Boolean, default: false },
    busy: { type: Boolean, default: false },
});

defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Modal :show="show" max-width="md" @close="$emit('cancel')">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-fg">{{ title }}</h2>
            <div class="mt-2 text-sm text-muted"><slot /></div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <SecondaryButton @click="$emit('cancel')">{{
                    $t('common.cancel')
                }}</SecondaryButton>
                <component
                    :is="danger ? DangerButton : PrimaryButton"
                    type="button"
                    :disabled="busy"
                    @click="$emit('confirm')"
                >
                    {{ confirmLabel }}
                </component>
            </div>
        </div>
    </Modal>
</template>
