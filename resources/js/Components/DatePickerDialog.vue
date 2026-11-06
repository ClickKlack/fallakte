<script setup>
// Datumsauswahl als Dialog (auf Smartphone und Desktop gleich gut bedienbar).
import CalendarPanel from '@/Components/CalendarPanel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { todayIso } from '@/lib/format.js';

defineProps({
    show: { type: Boolean, required: true },
    modelValue: { type: String, default: null },
    title: { type: String, required: true },
    min: { type: String, default: '1970-01-01' },
    max: { type: String, default: null },
    allowEmpty: { type: Boolean, default: true },
});

const emit = defineEmits(['select', 'close']);
const today = todayIso();

function choose(value) {
    emit('select', value);
    emit('close');
}
</script>

<template>
    <Modal :show="show" max-width="sm" @close="$emit('close')">
        <div class="p-4 sm:p-5">
            <h2 class="mb-3 text-lg font-semibold">{{ title }}</h2>

            <CalendarPanel
                v-if="show"
                :model-value="modelValue"
                :min="min"
                :max="max ?? today"
                :today="today"
                @select="choose"
            />

            <div class="mt-4 flex flex-wrap gap-2">
                <SecondaryButton class="grow" @click="choose(today)">
                    {{ $t('datepicker.today') }}
                </SecondaryButton>
                <SecondaryButton v-if="allowEmpty" class="grow" @click="choose(null)">
                    {{ $t('datepicker.no_date') }}
                </SecondaryButton>
                <SecondaryButton class="grow" @click="$emit('close')">
                    {{ $t('common.cancel') }}
                </SecondaryButton>
            </div>
        </div>
    </Modal>
</template>
