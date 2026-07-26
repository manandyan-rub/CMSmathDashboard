<script setup lang="ts">
import { Bot, CircleDollarSign, Clock, RefreshCcw, Sparkles, TriangleAlert } from 'lucide-vue-next';
import { onMounted, reactive, ref, watch } from 'vue';
import BarChart from '../shared/charts/BarChart.vue';
import StatusBadge from '../shared/StatusBadge.vue';

const props = defineProps<{
    failuresOnly: boolean;
    permissions: string[];
    refreshToken: number;
    t: (key: string) => string;
    showError: (error: any, fallback?: string) => void;
    showSuccess: (message: string) => void;
}>();

const loading = ref(false);
const analytics = ref<Record<string, any>>({ summary: {}, by_feature: [], by_model: [], by_user: [] });
const failures = ref<Array<Record<string, any>>>([]);
const filters = reactive({ date_from: '', date_to: '', feature: '', model_id: '' });

async function load() {
    loading.value = true;
    try {
        const [analyticsResponse, failuresResponse] = await Promise.all([
            window.axios.get('/cms-api/ai/analytics', { params: filters }),
            window.axios.get('/cms-api/generation-logs', { params: { status: 'failed', per_page: 50 } }),
        ]);
        analytics.value = analyticsResponse.data;
        failures.value = failuresResponse.data.items || [];
    } catch (error) {
        props.showError(error, 'AI analytics could not be loaded.');
    } finally {
        loading.value = false;
    }
}

async function retry(log: Record<string, any>) {
    try {
        await window.axios.post(`/cms-api/ai/generations/${log.id}/retry`);
        props.showSuccess('Generation retry was submitted.');
        await load();
    } catch (error) {
        props.showError(error, 'This generation cannot be retried from the CMS.');
    }
}

watch(() => props.refreshToken, load);
onMounted(load);
</script>

<template>
    <section class="space-y-4">
        <header>
            <h2 class="text-lg font-bold text-white">{{ failuresOnly ? 'AI failures' : 'AI usage' }}</h2>
            <p class="text-sm text-slate-500">Provider-backed generation operations and cost telemetry.</p>
        </header>
        <div class="flex flex-wrap items-end gap-2 border-y border-slate-800 py-3">
            <input v-model="filters.date_from" type="date" class="h-9 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" />
            <input v-model="filters.date_to" type="date" class="h-9 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" />
            <input v-model="filters.feature" class="h-9 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" placeholder="Feature" />
            <button type="button" class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-700 px-3 text-xs font-semibold text-slate-300" @click="load"><RefreshCcw class="h-4 w-4" /> {{ t('common.refresh') }}</button>
        </div>
        <div v-if="loading" class="py-20 text-center text-slate-500">{{ t('common.loading') }}</div>
        <template v-else>
            <div v-if="!failuresOnly" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <article v-for="[label, value, icon] in [
                    ['Requests', analytics.summary?.requests || 0, Bot],
                    ['Failures', analytics.summary?.failures || 0, TriangleAlert],
                    ['Tokens', analytics.summary?.tokens || 0, Sparkles],
                    ['Cost', `$${Number(analytics.summary?.estimated_cost || 0).toFixed(4)}`, CircleDollarSign],
                    ['Avg latency', `${Math.round(analytics.summary?.average_latency_ms || 0)} ms`, Clock],
                ]" :key="label" class="rounded-lg border border-slate-800 bg-slate-900 p-3">
                    <component :is="icon" class="h-4 w-4 text-emerald-300" /><p class="mt-2 text-xs text-slate-500">{{ label }}</p><strong class="text-xl text-white">{{ value }}</strong>
                </article>
            </div>
            <div v-if="!failuresOnly" class="grid gap-3 xl:grid-cols-3">
                <article v-for="[title, items] in [['By feature', analytics.by_feature], ['By model', analytics.by_model], ['By user', analytics.by_user]]" :key="title" class="rounded-lg border border-slate-800 bg-slate-900 p-4">
                    <h3 class="mb-3 text-sm font-bold text-white">{{ title }}</h3><BarChart :items="items || []" compact />
                </article>
            </div>
            <section>
                <h3 class="mb-2 text-sm font-bold text-white">Recent failures</h3>
                <div class="overflow-hidden rounded-lg border border-slate-800">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-950/60 text-left text-xs text-slate-500"><tr><th class="px-3 py-3">Log</th><th class="px-3 py-3">User</th><th class="px-3 py-3">Feature</th><th class="px-3 py-3">Error</th><th class="px-3 py-3">Time</th><th></th></tr></thead>
                        <tbody class="divide-y divide-slate-800">
                            <tr v-if="failures.length === 0"><td colspan="6" class="px-4 py-10 text-center text-slate-500">{{ t('common.empty') }}</td></tr>
                            <tr v-for="log in failures" :key="log.id">
                                <td class="px-3 py-3 font-bold text-white">#{{ log.id }} <StatusBadge :status="log.source" /></td>
                                <td class="px-3 py-3 text-slate-300">{{ log.user?.name || '—' }}</td>
                                <td class="px-3 py-3 text-slate-300">{{ log.feature || '—' }}</td>
                                <td class="max-w-md truncate px-3 py-3 text-rose-300">{{ log.error_excerpt }}</td>
                                <td class="px-3 py-3 text-slate-500">{{ log.started_at ? new Date(log.started_at).toLocaleString() : '—' }}</td>
                                <td class="px-3 py-3 text-right"><button v-if="permissions.includes('ai.manage')" type="button" class="rounded-lg border border-slate-700 px-2 py-1 text-xs text-slate-300" @click="retry(log)">{{ t('common.retry') }}</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>
    </section>
</template>
