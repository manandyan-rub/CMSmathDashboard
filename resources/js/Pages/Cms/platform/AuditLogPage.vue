<script setup lang="ts">
import { Eye, Search, ShieldCheck, X } from 'lucide-vue-next';
import { onMounted, reactive, ref, watch } from 'vue';
import PaginationFooter from '../shared/PaginationFooter.vue';

const props = defineProps<{
    refreshToken: number;
    t: (key: string) => string;
    showError: (error: any, fallback?: string) => void;
}>();

const loading = ref(false);
const items = ref<Array<Record<string, any>>>([]);
const detail = ref<Record<string, any> | null>(null);
const pagination = ref({ page: 1, last_page: 1, total: 0 });
const filters = reactive({ administrator_id: '', action: '', entity_type: '', entity_id: '', date_from: '', date_to: '' });

async function load(page = pagination.value.page || 1) {
    loading.value = true;
    try {
        const { data } = await window.axios.get('/cms-api/audit-logs', { params: { page, ...filters } });
        items.value = data.items || [];
        pagination.value = { ...pagination.value, ...(data.pagination || {}) };
    } catch (error) {
        props.showError(error, 'Audit records could not be loaded.');
    } finally {
        loading.value = false;
    }
}

async function open(item: Record<string, any>) {
    const { data } = await window.axios.get(`/cms-api/audit-logs/${item.id}`);
    detail.value = data.item;
}

watch(() => props.refreshToken, () => load(1));
onMounted(() => load(1));
</script>

<template>
    <section class="space-y-4">
        <header><h2 class="flex items-center gap-2 text-lg font-bold text-white"><ShieldCheck class="h-5 w-5 text-emerald-300" /> Audit log</h2><p class="text-sm text-slate-500">Administrator actions, reasons, request IDs, and redacted changes.</p></header>
        <div class="grid gap-2 border-y border-slate-800 py-3 sm:grid-cols-2 xl:grid-cols-6">
            <label class="relative xl:col-span-2"><Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-500" /><input v-model="filters.action" class="h-10 w-full rounded-lg border-slate-700 bg-slate-950 pl-9 text-sm text-white" placeholder="Action" @keyup.enter="load(1)" /></label>
            <input v-model="filters.administrator_id" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-white" placeholder="Admin ID" />
            <input v-model="filters.entity_type" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-white" placeholder="Entity type" />
            <input v-model="filters.date_from" type="date" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-white" />
            <input v-model="filters.date_to" type="date" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-white" @change="load(1)" />
        </div>
        <div class="overflow-hidden rounded-lg border border-slate-800">
            <div class="overflow-x-auto"><table class="min-w-full text-sm">
                <thead class="bg-slate-950/60 text-left text-xs text-slate-500"><tr><th class="px-3 py-3">Time</th><th class="px-3 py-3">Administrator</th><th class="px-3 py-3">Action</th><th class="px-3 py-3">Entity</th><th class="px-3 py-3">Reason</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-800">
                    <tr v-if="loading"><td colspan="6" class="px-4 py-10 text-center text-slate-500">{{ t('common.loading') }}</td></tr>
                    <tr v-else-if="items.length === 0"><td colspan="6" class="px-4 py-10 text-center text-slate-500">{{ t('common.empty') }}</td></tr>
                    <tr v-for="item in items" v-else :key="item.id">
                        <td class="px-3 py-3 text-slate-400">{{ new Date(item.created_at).toLocaleString() }}</td>
                        <td class="px-3 py-3 text-slate-300">{{ item.administrator?.name || 'System' }}</td>
                        <td class="px-3 py-3 font-semibold text-emerald-300">{{ item.action }}</td>
                        <td class="px-3 py-3 text-slate-400">{{ item.entity_type || '—' }} #{{ item.entity_id || '—' }}</td>
                        <td class="max-w-xs truncate px-3 py-3 text-slate-400">{{ item.reason || '—' }}</td>
                        <td class="px-3 py-3 text-right"><button type="button" class="rounded-lg border border-slate-700 p-2 text-slate-300" @click="open(item)"><Eye class="h-4 w-4" /></button></td>
                    </tr>
                </tbody>
            </table></div>
            <PaginationFooter :pagination="pagination" label="audit records" @page="load" />
        </div>
        <aside v-if="detail" class="fixed inset-y-0 right-0 z-50 w-full max-w-2xl overflow-y-auto border-l border-slate-700 bg-slate-950 p-5 shadow-2xl">
            <header class="mb-4 flex justify-between"><div><h2 class="font-bold text-white">{{ detail.action }}</h2><p class="text-xs text-slate-500">{{ detail.request_id }}</p></div><button type="button" class="text-slate-400" @click="detail = null"><X class="h-5 w-5" /></button></header>
            <dl class="mb-4 grid gap-2 text-sm sm:grid-cols-2"><div><dt class="text-xs text-slate-500">Administrator</dt><dd class="text-white">{{ detail.administrator?.name || 'System' }}</dd></div><div><dt class="text-xs text-slate-500">IP</dt><dd class="text-white">{{ detail.ip_address || '—' }}</dd></div><div><dt class="text-xs text-slate-500">Entity</dt><dd class="text-white">{{ detail.entity_type }} #{{ detail.entity_id }}</dd></div><div><dt class="text-xs text-slate-500">Reason</dt><dd class="text-white">{{ detail.reason || '—' }}</dd></div></dl>
            <div class="grid gap-3"><section><h3 class="mb-1 text-xs font-bold text-slate-500">Previous</h3><pre class="overflow-x-auto rounded-lg border border-slate-800 bg-slate-900 p-3 text-xs text-rose-200">{{ JSON.stringify(detail.old_values, null, 2) }}</pre></section><section><h3 class="mb-1 text-xs font-bold text-slate-500">New</h3><pre class="overflow-x-auto rounded-lg border border-slate-800 bg-slate-900 p-3 text-xs text-emerald-200">{{ JSON.stringify(detail.new_values, null, 2) }}</pre></section></div>
        </aside>
    </section>
</template>
