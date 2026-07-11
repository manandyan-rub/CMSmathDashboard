<script setup>
import { RefreshCcw } from '@lucide/vue';
import PaginationFooter from '../shared/PaginationFooter.vue';
import GenerationLogDetailPanel from './partials/GenerationLogDetailPanel.vue';

defineProps({
    generationLogsState: {
        type: Object,
        required: true,
    },
    generationLogsLoading: {
        type: Boolean,
        required: true,
    },
    generationLogDetailLoading: {
        type: Boolean,
        required: true,
    },
    generationLogFilters: {
        type: Object,
        required: true,
    },
    generationLogStatusOptions: {
        type: Array,
        required: true,
    },
    generationLogDetail: {
        type: Object,
        default: null,
    },
    selectedGenerationLogId: {
        type: Number,
        default: null,
    },
    formatTimestamp: {
        type: Function,
        required: true,
    },
    formatDuration: {
        type: Function,
        required: true,
    },
    statusTone: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(['reload', 'open', 'page']);
</script>

<template>
    <section class="space-y-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-white">Generation Logs</h2>
                    <p class="text-sm text-slate-400">Review AI generation runs, failures, and raw payloads.</p>
                </div>
                <button
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800"
                    type="button"
                    @click="emit('reload')"
                >
                    <RefreshCcw class="h-4 w-4" />
                    Reload logs
                </button>
            </div>

            <div class="mt-4 grid gap-2 md:grid-cols-4">
                <select
                    v-model="generationLogFilters.status"
                    class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm font-semibold text-white"
                >
                    <option v-for="option in generationLogStatusOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <input
                    v-model="generationLogFilters.user_id"
                    type="number"
                    min="1"
                    class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white placeholder:text-slate-500"
                    placeholder="User ID"
                />
                <input
                    v-model="generationLogFilters.date_from"
                    type="date"
                    class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                />
                <input
                    v-model="generationLogFilters.date_to"
                    type="date"
                    class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                />
            </div>

            <div class="mt-3 flex justify-end">
                <button
                    class="rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800"
                    type="button"
                    @click="emit('reload')"
                >
                    Apply filters
                </button>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.45fr_0.95fr]">
            <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/75">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-800 text-sm">
                        <thead class="bg-slate-900">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-400">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-400">Source</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-400">User</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-400">Document</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-400">Started</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-400">Finished</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-400">Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="generationLogsLoading">
                                <td class="px-4 py-4 text-slate-500" colspan="7">Loading generation logs...</td>
                            </tr>
                            <tr v-else-if="generationLogsState.items.length === 0">
                                <td class="px-4 py-4 text-slate-500" colspan="7">No generation logs found.</td>
                            </tr>
                            <tr
                                v-for="item in generationLogsState.items"
                                v-else
                                :key="item.id"
                                class="cursor-pointer border-t border-slate-800 transition hover:bg-slate-950/40"
                                :class="selectedGenerationLogId === item.id ? 'bg-indigo-500/10' : ''"
                                @click="emit('open', item)"
                            >
                                <td class="px-4 py-3">
                                    <div class="space-y-2">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase" :class="statusTone(item.status)">
                                            {{ item.status }}
                                        </span>
                                        <p v-if="item.error_excerpt" class="max-w-[240px] text-xs text-slate-400">
                                            {{ item.error_excerpt }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase"
                                        :class="item.source === 'preview' ? 'bg-amber-500/15 text-amber-200' : 'bg-slate-700 text-slate-300'"
                                    >
                                        {{ item.source }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-white">{{ item.user?.name || 'Unknown user' }}</p>
                                    <p class="text-xs text-slate-400">{{ item.user?.email || '-' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-white">{{ item.document?.title || 'No document' }}</p>
                                    <p class="text-xs uppercase text-slate-400">{{ item.document?.language || '-' }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-300">{{ formatTimestamp(item.started_at) }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ formatTimestamp(item.finished_at) }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ formatDuration(item.duration_seconds) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <PaginationFooter :pagination="generationLogsState.pagination" label="logs" @page="emit('page', $event)" />
            </div>

            <GenerationLogDetailPanel
                :item="generationLogDetail"
                :loading="generationLogDetailLoading"
                :format-timestamp="formatTimestamp"
                :format-duration="formatDuration"
                :status-tone="statusTone"
            />
        </div>
    </section>
</template>
