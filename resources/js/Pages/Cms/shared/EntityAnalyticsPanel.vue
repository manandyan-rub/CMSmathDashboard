<script setup lang="ts">
import { BarChart3, RefreshCcw } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import BarChart from './charts/BarChart.vue';

const props = defineProps<{
    type: 'levels' | 'games';
    refreshToken: number;
    showError: (error: unknown, fallback?: string) => void;
}>();

const items = ref<Array<Record<string, any>>>([]);
const selectedId = ref<number | null>(null);
const analytics = ref<Record<string, any> | null>(null);
const loading = ref(false);
const dateFrom = ref('');
const dateTo = ref('');
const title = computed(() => props.type === 'levels' ? 'Level performance' : 'Game performance');

function itemLabel(item: Record<string, any>): string {
    return item.translation?.title || item.slug || item.key || `#${item.id}`;
}

async function loadOptions() {
    const { data } = await window.axios.get(`/cms-api/resources/${props.type}`, {
        params: { per_page: 100, sort: props.type === 'levels' ? 'order' : 'key', direction: 'asc' },
    });
    items.value = data.items || [];
    selectedId.value ||= items.value[0]?.id || null;
    await loadAnalytics();
}

async function loadAnalytics() {
    if (!selectedId.value) {
        analytics.value = null;
        return;
    }
    loading.value = true;
    try {
        const segment = props.type === 'levels' ? 'levels' : 'games';
        const { data } = await window.axios.get(`/cms-api/${segment}/${selectedId.value}/analytics`, {
            params: {
                date_from: props.type === 'levels' ? dateFrom.value || undefined : undefined,
                date_to: props.type === 'levels' ? dateTo.value || undefined : undefined,
            },
        });
        analytics.value = data;
    } catch (error) {
        props.showError(error, `${title.value} could not be loaded.`);
    } finally {
        loading.value = false;
    }
}

watch(() => props.refreshToken, loadOptions);
onMounted(loadOptions);
</script>

<template>
    <section class="mt-6 space-y-4 border-t border-slate-800 pt-5">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-bold text-white">
                    <BarChart3 class="h-5 w-5 text-emerald-300" />
                    {{ title }}
                </h2>
                <p class="text-sm text-slate-500">Aggregated from completed platform activity.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <select v-model="selectedId" class="h-9 min-w-56 rounded-lg border-slate-700 bg-slate-950 text-sm text-white" @change="loadAnalytics">
                    <option v-for="item in items" :key="item.id" :value="item.id">{{ itemLabel(item) }}</option>
                </select>
                <template v-if="type === 'levels'">
                    <input v-model="dateFrom" type="date" class="h-9 rounded-lg border-slate-700 bg-slate-950 text-sm text-white" />
                    <input v-model="dateTo" type="date" class="h-9 rounded-lg border-slate-700 bg-slate-950 text-sm text-white" />
                </template>
                <button type="button" class="grid h-9 w-9 place-items-center rounded-lg border border-slate-700 text-slate-300" @click="loadAnalytics">
                    <RefreshCcw class="h-4 w-4" />
                </button>
            </div>
        </header>

        <div v-if="loading" class="py-12 text-center text-slate-500">Loading analytics...</div>
        <template v-else-if="analytics">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <article v-for="(value, key) in analytics.summary" :key="key" class="rounded-lg border border-slate-800 bg-slate-900/60 p-3">
                    <p class="text-[10px] uppercase text-slate-500">{{ String(key).replaceAll('_', ' ') }}</p>
                    <strong class="mt-1 block text-xl text-white">{{ value }}</strong>
                </article>
            </div>
            <div class="grid gap-3 xl:grid-cols-2">
                <article v-if="analytics.hardest_questions" class="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                    <h3 class="mb-3 text-sm font-bold text-white">Hardest questions</h3>
                    <BarChart :items="analytics.hardest_questions.map((item: Record<string, any>) => ({ label: item.question, value: Math.round(100 - item.correctness) }))" />
                </article>
                <article class="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                    <h3 class="mb-3 text-sm font-bold text-white">Common mistakes</h3>
                    <BarChart :items="analytics.common_mistakes || []" />
                </article>
            </div>
        </template>
    </section>
</template>
