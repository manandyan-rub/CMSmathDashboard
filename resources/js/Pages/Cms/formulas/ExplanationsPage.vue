<script setup>
import { RefreshCcw, Search, SquarePen } from '@lucide/vue';
import PaginationFooter from '../shared/PaginationFooter.vue';

defineProps({
    explanationsState: {
        type: Object,
        required: true,
    },
    explanationsLoading: {
        type: Boolean,
        required: true,
    },
    search: {
        type: String,
        required: true,
    },
    cacheFilter: {
        type: String,
        required: true,
    },
    cacheFilterOptions: {
        type: Array,
        required: true,
    },
    locale: {
        type: String,
        required: true,
    },
    renderFormulaMarkup: {
        type: Function,
        required: true,
    },
    canManage: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits(['update:search', 'update:cacheFilter', 'search', 'reload', 'edit', 'page']);
</script>

<template>
    <section class="space-y-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-white">Formula Explanations</h2>
                    <p class="text-sm text-slate-400">Edit step-by-step formula explanations for each language.</p>
                </div>
                <button
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800"
                    type="button"
                    @click="emit('reload')"
                >
                    <RefreshCcw class="h-4 w-4" />
                    Reload explanations
                </button>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <label class="relative min-w-[240px] flex-1">
                    <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-500" />
                    <input
                        :value="search"
                        type="search"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 py-2 pl-9 pr-3 text-sm text-white placeholder:text-slate-500"
                        placeholder="Search by formula, category, explanation name"
                        @input="emit('update:search', $event.target.value)"
                        @keyup.enter="emit('search')"
                    />
                </label>
                <select
                    :value="cacheFilter"
                    class="min-w-[170px] rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm font-semibold text-white"
                    @change="emit('update:cacheFilter', $event.target.value); emit('search')"
                >
                    <option v-for="option in cacheFilterOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
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
                            <th class="px-4 py-3 text-left font-semibold text-slate-400">Formula</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400">Category</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400">Explanation</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400">Cache</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400">Locale</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="explanationsLoading">
                            <td class="px-4 py-4 text-slate-500" colspan="6">Loading explanations...</td>
                        </tr>
                        <tr v-else-if="explanationsState.items.length === 0">
                            <td class="px-4 py-4 text-slate-500" colspan="6">No formulas found.</td>
                        </tr>
                        <tr
                            v-for="item in explanationsState.items"
                            v-else
                            :key="item.id"
                            class="border-t border-slate-800 align-top"
                        >
                            <td class="max-w-[360px] px-4 py-3">
                                <p class="font-semibold text-white">{{ item.name || 'Untitled formula' }}</p>
                                <div
                                    class="mt-2 max-w-[340px] overflow-x-auto rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2 text-sm text-slate-100"
                                    v-html="renderFormulaMarkup(item.formula)"
                                ></div>
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ item.category }}</td>
                            <td class="px-4 py-3">
                                <div v-if="item.steps_count > 0" class="space-y-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-300">
                                        {{ item.steps_count }} editable step{{ item.steps_count === 1 ? '' : 's' }}
                                    </p>
                                    <p class="line-clamp-2 max-w-[420px] text-xs text-slate-400">
                                        {{ item.steps_json?.[0]?.note || 'Only formula markup has been added so far.' }}
                                    </p>
                                </div>
                                <div v-else class="rounded-lg border border-dashed border-slate-700 px-3 py-2 text-xs text-slate-500">
                                    No explanation yet for this locale.
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase"
                                    :class="item.steps_count > 0 ? 'bg-emerald-500/15 text-emerald-200' : 'bg-amber-500/15 text-amber-200'"
                                >
                                    {{ item.steps_count > 0 ? 'Cached' : 'Missing' }}
                                </span>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ item.locales_count }} locale cache{{ item.locales_count === 1 ? '' : 's' }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-indigo-500/20 px-2.5 py-1 text-xs font-semibold uppercase text-indigo-200">
                                    {{ locale }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    v-if="canManage"
                                    class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-800"
                                    type="button"
                                    @click="emit('edit', item)"
                                >
                                    <SquarePen class="h-3.5 w-3.5" />
                                    Edit explanation
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationFooter :pagination="explanationsState.pagination" label="formulas" @page="emit('page', $event)" />
        </div>
    </section>
</template>
