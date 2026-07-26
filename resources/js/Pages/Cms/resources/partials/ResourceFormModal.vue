<script setup lang="ts">
import { Save, X } from 'lucide-vue-next';
import type { ResourceSchema } from '../../shared/cmsTypes';
import StructuredListEditor from './StructuredListEditor.vue';

defineProps<{
    show: boolean;
    schema: ResourceSchema | null;
    form: Record<string, any>;
    activeLocale: string;
    submitting: boolean;
    t: (key: string) => string;
}>();

const emit = defineEmits<{
    'update:activeLocale': [value: string];
    close: [];
    save: [];
}>();
</script>

<template>
    <div v-if="show && schema" class="fixed inset-0 z-[70] grid place-items-center bg-slate-950/75 p-3">
        <section class="max-h-[94vh] w-full max-w-5xl overflow-y-auto rounded-lg border border-slate-700 bg-slate-900 shadow-2xl">
            <header class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-700 bg-slate-900 px-5 py-4">
                <div>
                    <p class="text-xs font-semibold text-emerald-300">{{ schema.title }}</p>
                    <h2 class="font-bold text-white">{{ form.id ? t('common.edit') : t('common.create') }}</h2>
                </div>
                <button type="button" class="text-slate-400 hover:text-white" @click="emit('close')">
                    <X class="h-5 w-5" />
                </button>
            </header>

            <div class="grid gap-6 p-5 lg:grid-cols-2">
                <section>
                    <h3 class="mb-3 text-xs font-bold uppercase text-slate-500">Configuration</h3>
                    <div class="space-y-4">
                        <label v-for="field in schema.fields" :key="field.key" class="block text-xs font-semibold text-slate-300">
                            {{ field.label }}
                            <span v-if="field.required" class="text-rose-300">*</span>

                            <input
                                v-if="['text', 'number', 'datetime'].includes(field.type)"
                                v-model="form[field.key]"
                                :type="field.type === 'datetime' ? 'datetime-local' : field.type"
                                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 text-sm text-white focus:border-emerald-400 focus:ring-0"
                            />
                            <textarea
                                v-else-if="['textarea', 'json'].includes(field.type)"
                                v-model="form[field.key]"
                                :rows="field.type === 'json' ? 7 : 4"
                                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 font-mono text-sm text-white focus:border-emerald-400 focus:ring-0"
                            ></textarea>
                            <StructuredListEditor
                                v-else-if="['options', 'calculator_fields'].includes(field.type)"
                                v-model="form[field.key]"
                                :type="field.type"
                                :locales="schema.locales"
                            />
                            <select
                                v-else-if="['select', 'relation'].includes(field.type)"
                                v-model="form[field.key]"
                                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 text-sm text-white focus:border-emerald-400 focus:ring-0"
                            >
                                <option :value="null">—</option>
                                <option v-for="option in field.options" :key="String(option.value)" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <select
                                v-else-if="field.type === 'relation_multiple'"
                                v-model="form[field.key]"
                                multiple
                                class="mt-1 min-h-32 w-full rounded-lg border border-slate-700 bg-slate-950 text-sm text-white focus:border-emerald-400 focus:ring-0"
                            >
                                <option v-for="option in field.options" :key="String(option.value)" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <label v-else-if="field.type === 'boolean'" class="mt-2 flex items-center gap-2">
                                <input v-model="form[field.key]" type="checkbox" class="rounded border-slate-600 bg-slate-950 text-emerald-500 focus:ring-emerald-400" />
                                <span class="font-normal text-slate-400">{{ form[field.key] ? 'Enabled' : 'Disabled' }}</span>
                            </label>
                        </label>
                    </div>
                </section>

                <section v-if="schema.translation_fields.length">
                    <h3 class="mb-3 text-xs font-bold uppercase text-slate-500">{{ t('resource.translation') }}</h3>
                    <div class="mb-4 flex flex-wrap gap-1">
                        <button
                            v-for="locale in schema.locales"
                            :key="locale"
                            type="button"
                            class="rounded-lg border px-3 py-1.5 text-xs font-bold"
                            :class="activeLocale === locale ? 'border-emerald-400 bg-emerald-400/10 text-emerald-200' : 'border-slate-700 text-slate-400'"
                            @click="emit('update:activeLocale', locale)"
                        >
                            {{ locale.toUpperCase() }}
                        </button>
                    </div>
                    <div class="space-y-4">
                        <label v-for="field in schema.translation_fields" :key="field.key" class="block text-xs font-semibold text-slate-300">
                            {{ field.label }}
                            <textarea
                                v-if="['description', 'explanation', 'help_text', 'educational_notes', 'warning_text', 'question_text', 'hint_text', 'instructions'].includes(field.key)"
                                v-model="form.translations[activeLocale][field.key]"
                                rows="4"
                                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 text-sm text-white focus:border-emerald-400 focus:ring-0"
                            ></textarea>
                            <input
                                v-else
                                v-model="form.translations[activeLocale][field.key]"
                                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 text-sm text-white focus:border-emerald-400 focus:ring-0"
                            />
                        </label>
                        <label class="flex items-center gap-2 text-xs text-slate-400">
                            <input v-model="form.translations[activeLocale].reviewed" type="checkbox" class="rounded border-slate-600 bg-slate-950 text-emerald-500" />
                            Translation reviewed
                        </label>
                    </div>
                </section>
            </div>

            <footer class="sticky bottom-0 flex flex-wrap items-end justify-between gap-3 border-t border-slate-700 bg-slate-900 px-5 py-4">
                <label class="min-w-[16rem] flex-1 text-xs font-semibold text-slate-300">
                    {{ t('common.reason') }}
                    <input
                        v-model="form.reason"
                        class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 text-sm text-white focus:border-emerald-400 focus:ring-0"
                        placeholder="Why is this content changing?"
                    />
                </label>
                <div class="flex gap-2">
                    <button type="button" class="rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-300" @click="emit('close')">
                        {{ t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-3 py-2 text-sm font-semibold text-slate-950 disabled:opacity-50"
                        :disabled="submitting"
                        @click="emit('save')"
                    >
                        <Save class="h-4 w-4" />
                        {{ t('common.save') }}
                    </button>
                </div>
            </footer>
        </section>
    </div>
</template>
