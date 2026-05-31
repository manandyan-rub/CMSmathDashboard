<script setup>
import { Plus, Search, SquarePen, Trash2 } from '@lucide/vue';
import PaginationFooter from '../shared/PaginationFooter.vue';

defineProps({
    formulasState: {
        type: Object,
        required: true,
    },
    formulasLoading: {
        type: Boolean,
        required: true,
    },
    search: {
        type: String,
        required: true,
    },
    locale: {
        type: String,
        required: true,
    },
    formatDate: {
        type: Function,
        required: true,
    },
    renderFormulaMarkup: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(['update:search', 'search', 'create', 'edit', 'delete', 'page']);
</script>

<template>
    <section class="space-y-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-white">Formulas Management</h2>
                    <p class="text-sm text-slate-400">Manage formula records and locale translations.</p>
                </div>
                <button
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-500 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-400"
                    type="button"
                    @click="emit('create')"
                >
                    <Plus class="h-4 w-4" />
                    Add formula
                </button>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <label class="relative min-w-[220px] flex-1">
                    <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-500" />
                    <input
                        :value="search"
                        type="search"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 py-2 pl-9 pr-3 text-sm text-white placeholder:text-slate-500"
                        placeholder="Search by formula, category, name"
                        @input="emit('update:search', $event.target.value)"
                        @keyup.enter="emit('search')"
                    />
                </label>
                <button
                    class="rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800"
                    type="button"
                    @click="emit('search')"
                >
                    Search
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/75">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400">Name ({{ locale.toUpperCase() }})</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400">Category</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400">Formula</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400">Updated</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="formulasLoading">
                            <td class="px-4 py-4 text-slate-500" colspan="5">Loading formulas...</td>
                        </tr>
                        <tr v-else-if="formulasState.items.length === 0">
                            <td class="px-4 py-4 text-slate-500" colspan="5">No formulas found.</td>
                        </tr>
                        <tr
                            v-for="formula in formulasState.items"
                            v-else
                            :key="formula.id"
                            class="border-t border-slate-800"
                        >
                            <td class="px-4 py-3 font-medium text-white">{{ formula.name || '-' }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ formula.category }}</td>
                            <td class="max-w-[460px] px-4 py-3">
                                <div
                                    class="max-w-[440px] overflow-x-auto rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2 text-sm text-slate-100"
                                    v-html="renderFormulaMarkup(formula.formula)"
                                ></div>
                            </td>
                            <td class="px-4 py-3 text-slate-400">{{ formatDate(formula.updated_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        class="rounded-lg border border-slate-700 px-2 py-1 text-xs font-semibold text-slate-200 hover:bg-slate-800"
                                        type="button"
                                        @click="emit('edit', formula)"
                                    >
                                        <SquarePen class="h-3.5 w-3.5" />
                                    </button>
                                    <button
                                        class="rounded-lg border border-rose-400/40 px-2 py-1 text-xs font-semibold text-rose-300 hover:bg-rose-500/10"
                                        type="button"
                                        @click="emit('delete', formula)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationFooter :pagination="formulasState.pagination" label="formulas" @page="emit('page', $event)" />
        </div>
    </section>
</template>
