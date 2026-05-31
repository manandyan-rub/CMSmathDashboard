<script setup>
import { CheckCircle2, X } from '@lucide/vue';

defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    isEditing: {
        type: Boolean,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    formulaLocales: {
        type: Array,
        required: true,
    },
    formulaCategoryOptions: {
        type: Array,
        required: true,
    },
    submitting: {
        type: Boolean,
        required: true,
    },
    renderFormulaMarkup: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(['close', 'save']);
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
        @click.self="emit('close')"
    >
        <div class="max-h-[94vh] w-full max-w-4xl overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl shadow-black/40">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-white">{{ isEditing ? 'Edit formula' : 'Create formula' }}</h3>
                <button class="rounded-lg p-1 text-slate-400 hover:bg-slate-800" type="button" @click="emit('close')">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-slate-400">Category</span>
                    <select
                        v-model="form.category"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                    >
                        <option value="" disabled>Select category</option>
                        <option v-for="category in formulaCategoryOptions" :key="category" :value="category">
                            {{ category }}
                        </option>
                    </select>
                </label>
                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-slate-400">Example (optional)</span>
                    <input
                        v-model="form.example"
                        type="text"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                    />
                </label>
            </div>

            <label class="mt-3 block space-y-1">
                <span class="text-xs font-semibold uppercase text-slate-400">Formula</span>
                <textarea
                    v-model="form.formula"
                    rows="3"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 font-mono text-sm text-white"
                ></textarea>
            </label>

            <div class="mt-3 rounded-xl border border-indigo-400/20 bg-slate-950/60 p-4">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <span class="text-xs font-semibold uppercase tracking-wide text-indigo-300">Formula preview</span>
                    <span class="text-[11px] font-semibold text-slate-500">Rendered from LaTeX</span>
                </div>
                <div
                    class="overflow-x-auto rounded-lg border border-slate-800 bg-slate-900 px-4 py-4 text-center text-lg text-white"
                    v-html="renderFormulaMarkup(form.formula, true)"
                ></div>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div v-for="itemLocale in formulaLocales" :key="itemLocale" class="rounded-xl border border-slate-800 p-3">
                    <p class="mb-2 text-xs font-black uppercase tracking-wide text-indigo-300">{{ itemLocale }}</p>
                    <label class="mb-2 block space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">Name</span>
                        <input
                            v-model="form.translations[itemLocale].name"
                            type="text"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                        />
                    </label>
                    <label class="block space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">Description</span>
                        <textarea
                            v-model="form.translations[itemLocale].description"
                            rows="2"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                        ></textarea>
                    </label>
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button
                    class="rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-300 hover:bg-slate-800"
                    type="button"
                    @click="emit('close')"
                >
                    Cancel
                </button>
                <button
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-400 disabled:opacity-50"
                    type="button"
                    :disabled="submitting"
                    @click="emit('save')"
                >
                    <CheckCircle2 class="h-4 w-4" />
                    {{ submitting ? 'Saving...' : 'Save formula' }}
                </button>
            </div>
        </div>
    </div>
</template>
