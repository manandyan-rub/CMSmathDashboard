<script setup>
import { CheckCircle2, Plus, Trash2, X } from '@lucide/vue';

defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    submitting: {
        type: Boolean,
        required: true,
    },
    localeLoading: {
        type: Boolean,
        required: true,
    },
    formulaLocales: {
        type: Array,
        required: true,
    },
    renderFormulaMarkup: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(['close', 'save', 'addStep', 'removeStep', 'changeLocale']);
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
        @click.self="emit('close')"
    >
        <div class="max-h-[94vh] w-full max-w-5xl overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl shadow-black/40">
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-indigo-300">Formula explanation</p>
                    <h3 class="mt-1 text-lg font-bold text-white">{{ form.name }}</h3>
                    <p class="mt-1 text-sm text-slate-400">{{ form.category }} - {{ form.locale.toUpperCase() }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <label class="space-y-1">
                        <span class="block text-xs font-semibold uppercase text-slate-400">Language</span>
                        <select
                            :value="form.locale"
                            class="min-w-[140px] rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm font-semibold uppercase text-white"
                            :disabled="localeLoading || submitting"
                            @change="emit('changeLocale', $event.target.value)"
                        >
                            <option v-for="itemLocale in formulaLocales" :key="itemLocale" :value="itemLocale">
                                {{ itemLocale }}
                            </option>
                        </select>
                    </label>
                    <button class="rounded-lg p-1 text-slate-400 hover:bg-slate-800" type="button" @click="emit('close')">
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div class="rounded-xl border border-indigo-400/20 bg-slate-950/60 p-4">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <span class="text-xs font-semibold uppercase tracking-wide text-indigo-300">Formula preview</span>
                    <div class="flex items-center gap-2">
                        <span v-if="localeLoading" class="text-xs font-semibold text-slate-400">Loading language...</span>
                        <span class="rounded-full bg-indigo-500/20 px-2.5 py-1 text-xs font-semibold uppercase text-indigo-200">
                            {{ form.locale }}
                        </span>
                    </div>
                </div>
                <div
                    class="overflow-x-auto rounded-lg border border-slate-800 bg-slate-900 px-4 py-4 text-center text-lg text-white"
                    v-html="renderFormulaMarkup(form.formula, true)"
                ></div>
            </div>

            <div class="mt-4 space-y-3">
                <article
                    v-for="(step, index) in form.steps"
                    :key="index"
                    class="rounded-xl border border-slate-800 bg-slate-950/60 p-4"
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h4 class="text-sm font-bold text-white">Step {{ index + 1 }}</h4>
                        <button
                            class="inline-flex items-center gap-1 rounded-lg border border-rose-400/40 px-2 py-1 text-xs font-semibold text-rose-300 hover:bg-rose-500/10"
                            type="button"
                            @click="emit('removeStep', index)"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            Remove
                        </button>
                    </div>

                    <div class="grid gap-3 lg:grid-cols-[1fr_1fr]">
                        <label class="space-y-1">
                            <span class="text-xs font-semibold uppercase text-slate-400">LaTeX or formula line</span>
                            <textarea
                                v-model="step.latex"
                                rows="3"
                                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 font-mono text-sm text-white placeholder:text-slate-500"
                                placeholder="\frac{x}{2} = 4"
                            ></textarea>
                        </label>
                        <div class="space-y-1">
                            <span class="text-xs font-semibold uppercase text-slate-400">Rendered preview</span>
                            <div
                                class="min-h-[86px] overflow-x-auto rounded-lg border border-slate-800 bg-slate-900 px-4 py-4 text-center text-base text-white"
                                v-html="renderFormulaMarkup(step.latex, true)"
                            ></div>
                        </div>
                    </div>

                    <label class="mt-3 block space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">Explanation text</span>
                        <textarea
                            v-model="step.note"
                            rows="3"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white placeholder:text-slate-500"
                            placeholder="Explain what happens in this step..."
                        ></textarea>
                    </label>
                </article>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <button
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800"
                    type="button"
                    @click="emit('addStep')"
                >
                    <Plus class="h-4 w-4" />
                    Add step
                </button>

                <div class="flex justify-end gap-2">
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
                        :disabled="submitting || localeLoading"
                        @click="emit('save')"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        {{ submitting ? 'Saving...' : 'Save explanation' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
