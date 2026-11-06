<script setup>
/**
 * Eingabefeld mit Vorschlägen: Tippen filtert die Optionen (max. `limit` Treffer).
 * Auswahl per Klick/Tippen, Pfeiltasten + Enter; Esc bricht ab, × leert die Auswahl.
 * Verlassen des Feldes: leere Eingabe → keine Auswahl, exakter Treffer → übernehmen, sonst alte Auswahl.
 *
 * Optionen: [{ value, label, hint? }] – hint erscheint klein rechts (z. B. die Reihe eines Teams).
 */
import { computed, ref, watch } from 'vue';
import { exactMatch, filterOptions } from '@/lib/combobox.js';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    options: { type: Array, required: true },
    id: { type: String, required: true },
    placeholder: { type: String, default: '' },
    limit: { type: Number, default: 8 },
    inputmode: { type: String, default: 'text' },
});

const emit = defineEmits(['update:modelValue']);

const query = ref('');
const open = ref(false);
const active = ref(0);
const input = ref(null);

const selected = computed(() => props.options.find((o) => o.value === props.modelValue) ?? null);
const suggestions = computed(() => filterOptions(props.options, query.value, props.limit));
const listId = computed(() => `${props.id}-list`);

// Anzeige folgt der Auswahl, solange nicht getippt wird
watch(
    selected,
    (option) => {
        if (!open.value) {
            query.value = option?.label ?? '';
        }
    },
    { immediate: true },
);

watch(suggestions, () => (active.value = 0));

function openList() {
    open.value = true;
    query.value = '';
    active.value = 0;
}

function choose(option) {
    emit('update:modelValue', option?.value ?? null);
    open.value = false;
    query.value = option?.label ?? '';
}

function close() {
    open.value = false;
    query.value = selected.value?.label ?? '';
}

function commit() {
    if (!open.value) {
        return;
    }

    if (query.value.trim() === '') {
        choose(null);
        return;
    }

    const match = exactMatch(props.options, query.value);
    match ? choose(match) : close();
}

function onKeydown(event) {
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        if (!open.value) {
            openList();
            return;
        }
        const count = suggestions.value.length;
        if (count > 0) {
            active.value = (active.value + (event.key === 'ArrowDown' ? 1 : -1) + count) % count;
        }
    } else if (event.key === 'Enter' && open.value) {
        event.preventDefault();
        const option = suggestions.value[active.value];
        option ? choose(option) : commit();
        input.value?.blur();
    } else if (event.key === 'Escape' && open.value) {
        event.preventDefault();
        close();
    }
}

function onInput() {
    open.value = true;
}

function clear() {
    choose(null);
    input.value?.focus();
}
</script>

<template>
    <div class="relative">
        <input
            :id="id"
            ref="input"
            v-model="query"
            type="text"
            role="combobox"
            autocomplete="off"
            :inputmode="inputmode"
            :placeholder="selected ? selected.label : placeholder"
            :aria-expanded="open"
            :aria-controls="listId"
            aria-autocomplete="list"
            :aria-activedescendant="
                open && suggestions[active] ? `${id}-option-${active}` : undefined
            "
            class="min-h-11 w-full rounded-lg border-line bg-surface py-2 pl-3 pr-10 text-base text-fg placeholder:text-subtle focus:border-accent focus:ring-1 focus:ring-accent"
            @focus="openList"
            @input="onInput"
            @keydown="onKeydown"
            @blur="commit"
        />

        <button
            v-if="modelValue !== null && modelValue !== undefined"
            type="button"
            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-muted hover:text-fg"
            :aria-label="$t('combobox.clear')"
            data-test="clear"
            @mousedown.prevent
            @click="clear"
        >
            <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                <path
                    d="M5.3 5.3a1 1 0 0 1 1.4 0L10 8.6l3.3-3.3a1 1 0 1 1 1.4 1.4L11.4 10l3.3 3.3a1 1 0 0 1-1.4 1.4L10 11.4l-3.3 3.3a1 1 0 0 1-1.4-1.4L8.6 10 5.3 6.7a1 1 0 0 1 0-1.4Z"
                />
            </svg>
        </button>
        <svg
            v-else
            viewBox="0 0 20 20"
            fill="currentColor"
            class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-subtle"
            aria-hidden="true"
        >
            <path
                fill-rule="evenodd"
                d="M9 3.5a5.5 5.5 0 1 0 3.3 9.9l3.2 3.1a1 1 0 0 0 1.4-1.4l-3.1-3.2A5.5 5.5 0 0 0 9 3.5ZM5.5 9a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0Z"
                clip-rule="evenodd"
            />
        </svg>

        <ul
            v-show="open"
            :id="listId"
            role="listbox"
            class="absolute z-30 mt-1 max-h-80 w-full overflow-y-auto rounded-lg border border-line bg-surface-raised py-1 shadow-xl shadow-black/40"
        >
            <li
                v-for="(option, index) in suggestions"
                :id="`${id}-option-${index}`"
                :key="option.value"
                role="option"
                :aria-selected="option.value === modelValue"
                class="flex min-h-11 cursor-pointer items-center justify-between gap-3 px-3 text-sm"
                :class="[
                    index === active ? 'bg-surface-hover text-fg' : 'text-fg',
                    option.value === modelValue ? 'font-semibold' : '',
                ]"
                @mousedown.prevent
                @mouseenter="active = index"
                @click="choose(option)"
            >
                <span class="min-w-0 truncate">{{ option.label }}</span>
                <span v-if="option.hint" class="shrink-0 text-xs text-subtle">{{
                    option.hint
                }}</span>
            </li>
            <li v-if="suggestions.length === 0" class="px-3 py-3 text-sm text-subtle">
                {{ $t('combobox.no_results') }}
            </li>
        </ul>
    </div>
</template>
