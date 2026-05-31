<script setup>
import { CheckCircle2, X } from '@lucide/vue';

defineProps({
    show: {
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
    submitting: {
        type: Boolean,
        required: true,
    },
    isEditing: {
        type: Boolean,
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
        <div class="max-h-[94vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl shadow-black/40">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-white">Edit level: {{ form.title }}</h3>
                <button class="rounded-lg p-1 text-slate-400 hover:bg-slate-800" type="button" @click="emit('close')">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <p class="mb-3 text-xs uppercase tracking-wide text-indigo-300">{{ form.pathTitle }}</p>

            <div class="grid gap-3 sm:grid-cols-2">
                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-slate-400">Difficulty</span>
                    <input v-model="form.difficulty" type="text" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                </label>
                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-slate-400">Passing score %</span>
                    <input v-model.number="form.passing_score" type="number" min="1" max="100" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                </label>
                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-slate-400">XP reward</span>
                    <input v-model.number="form.xp_reward" type="number" min="0" max="10000" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                </label>
                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-slate-400">Time limit (sec)</span>
                    <input v-model="form.time_limit" type="number" min="5" max="7200" placeholder="Leave empty for none" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white placeholder:text-slate-500" />
                </label>
                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-200 sm:col-span-2">
                    <input v-model="form.active" type="checkbox" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-indigo-400" />
                    Active level
                </label>
            </div>

            <hr class="my-4 border-slate-800" />

            <div class="grid gap-3 sm:grid-cols-3">
                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-slate-400">Prompt locale</span>
                    <select v-model="form.prompt_locale" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                        <option v-for="itemLocale in formulaLocales" :key="itemLocale" :value="itemLocale">{{ itemLocale }}</option>
                    </select>
                </label>
                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-slate-400">Question count</span>
                    <input v-model.number="form.question_count" type="number" min="1" max="50" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                </label>
                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase text-slate-400">Options count</span>
                    <input v-model.number="form.options_count" type="number" min="2" max="8" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                </label>
            </div>

            <label class="mt-3 block space-y-1">
                <span class="text-xs font-semibold uppercase text-slate-400">AI Prompt</span>
                <textarea
                    v-model="form.prompt"
                    rows="6"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                ></textarea>
            </label>

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
                    :disabled="submitting || !isEditing"
                    @click="emit('save')"
                >
                    <CheckCircle2 class="h-4 w-4" />
                    {{ submitting ? 'Saving...' : 'Save level' }}
                </button>
            </div>
        </div>
    </div>
</template>
