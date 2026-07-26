<script setup lang="ts">
import { Plus, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    modelValue: string;
    type: 'options' | 'calculator_fields';
    locales: string[];
}>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const items = ref<Array<Record<string, any>>>([]);
let syncing = false;

function translations(fields: string[]) {
    return Object.fromEntries(props.locales.map((locale) => [
        locale,
        Object.fromEntries(fields.map((field) => [field, ''])),
    ]));
}

function blankItem() {
    if (props.type === 'options') {
        return {
            option_text: '',
            explanation: '',
            is_correct: false,
            order: items.value.length,
            translations: translations(['label', 'explanation']),
        };
    }

    return {
        key: '',
        input_type: 'number',
        example_values: [],
        supported_units: [],
        display_order: items.value.length,
        active: true,
        translations: translations(['label', 'description']),
    };
}

function normalize(item: Record<string, any>) {
    const normalized = { ...blankItem(), ...item };
    props.locales.forEach((locale) => {
        normalized.translations[locale] = {
            ...blankItem().translations[locale],
            ...(item.translations?.[locale] || {}),
        };
    });

    return normalized;
}

function parse(value: string) {
    try {
        const parsed = JSON.parse(value || '[]');
        return Array.isArray(parsed) ? parsed.map(normalize) : [];
    } catch {
        return [];
    }
}

function add() {
    items.value.push(blankItem());
}

function remove(index: number) {
    items.value.splice(index, 1);
}

function csvToArray(value: string): string[] {
    return value.split(',').map((item) => item.trim()).filter(Boolean);
}

watch(
    () => props.modelValue,
    (value) => {
        const current = JSON.stringify(items.value);
        const incoming = JSON.stringify(parse(value));
        if (current !== incoming) {
            syncing = true;
            items.value = parse(value);
            syncing = false;
        }
    },
    { immediate: true },
);

watch(items, (value) => {
    if (!syncing) {
        emit('update:modelValue', JSON.stringify(value, null, 2));
    }
}, { deep: true });
</script>

<template>
    <div class="mt-2 space-y-3">
        <div v-for="(item, index) in items" :key="item.id || index" class="rounded-lg border border-slate-700 p-3">
            <div class="mb-3 flex items-center justify-between">
                <strong class="text-xs text-white">{{ type === 'options' ? `Option ${index + 1}` : `Field ${index + 1}` }}</strong>
                <button type="button" class="grid h-7 w-7 place-items-center rounded-lg text-rose-300 hover:bg-rose-400/10" title="Remove" @click="remove(index)">
                    <Trash2 class="h-4 w-4" />
                </button>
            </div>

            <template v-if="type === 'options'">
                <div class="grid gap-2 sm:grid-cols-[1fr_7rem_5rem]">
                    <input v-model="item.option_text" class="rounded-lg border-slate-700 bg-slate-950 text-sm text-white" placeholder="Source option text" />
                    <label class="flex items-center gap-2 rounded-lg border border-slate-700 px-2 text-xs text-slate-300">
                        <input v-model="item.is_correct" type="checkbox" />
                        Correct
                    </label>
                    <input v-model.number="item.order" type="number" min="0" class="rounded-lg border-slate-700 bg-slate-950 text-sm text-white" title="Display order" />
                </div>
                <textarea v-model="item.explanation" rows="2" class="mt-2 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white" placeholder="Source explanation"></textarea>
            </template>

            <template v-else>
                <div class="grid gap-2 sm:grid-cols-2">
                    <input v-model="item.key" class="rounded-lg border-slate-700 bg-slate-950 text-sm text-white" placeholder="Stable field key" />
                    <select v-model="item.input_type" class="rounded-lg border-slate-700 bg-slate-950 text-sm text-white">
                        <option value="number">Number</option>
                        <option value="select">Select</option>
                        <option value="text">Text</option>
                    </select>
                    <input
                        :value="(item.example_values || []).join(', ')"
                        class="rounded-lg border-slate-700 bg-slate-950 text-sm text-white"
                        placeholder="Example values, comma-separated"
                        @input="item.example_values = csvToArray(($event.target as HTMLInputElement).value)"
                    />
                    <input
                        :value="(item.supported_units || []).join(', ')"
                        class="rounded-lg border-slate-700 bg-slate-950 text-sm text-white"
                        placeholder="Supported units, comma-separated"
                        @input="item.supported_units = csvToArray(($event.target as HTMLInputElement).value)"
                    />
                    <input v-model.number="item.display_order" type="number" min="0" class="rounded-lg border-slate-700 bg-slate-950 text-sm text-white" title="Display order" />
                    <label class="flex items-center gap-2 rounded-lg border border-slate-700 px-2 text-xs text-slate-300">
                        <input v-model="item.active" type="checkbox" />
                        Active
                    </label>
                </div>
            </template>

            <details class="mt-3 border-t border-slate-800 pt-2">
                <summary class="cursor-pointer text-xs font-semibold text-emerald-300">Localized labels</summary>
                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                    <fieldset v-for="locale in locales" :key="locale" class="rounded-lg border border-slate-800 p-2">
                        <legend class="px-1 text-[10px] font-bold text-slate-500">{{ locale.toUpperCase() }}</legend>
                        <input v-model="item.translations[locale].label" class="w-full rounded-lg border-slate-700 bg-slate-950 text-xs text-white" placeholder="Label" />
                        <textarea v-model="item.translations[locale][type === 'options' ? 'explanation' : 'description']" rows="2" class="mt-2 w-full rounded-lg border-slate-700 bg-slate-950 text-xs text-white" :placeholder="type === 'options' ? 'Explanation' : 'Description'"></textarea>
                    </fieldset>
                </div>
            </details>
        </div>

        <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-800" @click="add">
            <Plus class="h-4 w-4" />
            Add {{ type === 'options' ? 'option' : 'field' }}
        </button>
    </div>
</template>
