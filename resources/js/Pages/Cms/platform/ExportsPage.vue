<script setup lang="ts">
import { Download, FileSpreadsheet } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{
    permissions: string[];
    showError: (error: unknown, fallback?: string) => void;
}>();

const loading = ref('');
const filters = reactive({ date_from: '', date_to: '' });
const definitions = [
    ['users', 'Users', 'users.view'],
    ['progress', 'Student progress', 'users.view'],
    ['attempts', 'Level attempts', 'users.view'],
    ['level-analytics', 'Level analytics', 'levels.view'],
    ['questions', 'Questions', 'questions.view'],
    ['ai-usage', 'AI usage', 'ai.view'],
    ['exercise-documents', 'Exercise documents', 'exercise_documents.view'],
    ['xp-ledger', 'XP ledger', 'gamification.view'],
    ['audit-logs', 'Audit logs', 'audit.view'],
] as const;
const available = computed(() => definitions.filter(([, , permission]) => props.permissions.includes(permission)));

async function download(resource: string) {
    loading.value = resource;
    try {
        const response = await window.axios.get(`/cms-api/exports/${resource}`, {
            params: {
                date_from: filters.date_from || undefined,
                date_to: filters.date_to || undefined,
            },
            responseType: 'blob',
        });
        const url = URL.createObjectURL(response.data);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = `${resource}-${new Date().toISOString().slice(0, 10)}.csv`;
        anchor.click();
        URL.revokeObjectURL(url);
    } catch (error) {
        props.showError(error, 'The export could not be generated.');
    } finally {
        loading.value = '';
    }
}
</script>

<template>
    <section class="space-y-4">
        <header>
            <h2 class="flex items-center gap-2 text-lg font-bold text-white">
                <FileSpreadsheet class="h-5 w-5 text-emerald-300" />
                Data exports
            </h2>
            <p class="text-sm text-slate-500">Permission-scoped CSV exports generated from server-side queries.</p>
        </header>

        <div class="flex flex-wrap gap-2 border-y border-slate-800 py-3">
            <label class="text-xs text-slate-400">
                From
                <input v-model="filters.date_from" type="date" class="ml-2 h-9 rounded-lg border-slate-700 bg-slate-950 text-sm text-white" />
            </label>
            <label class="text-xs text-slate-400">
                To
                <input v-model="filters.date_to" type="date" class="ml-2 h-9 rounded-lg border-slate-700 bg-slate-950 text-sm text-white" />
            </label>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <article v-for="[resource, label] in available" :key="resource" class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                <div class="grid h-9 w-9 place-items-center rounded-lg bg-emerald-400/10 text-emerald-300">
                    <FileSpreadsheet class="h-4 w-4" />
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-semibold text-white">{{ label }}</h3>
                    <p class="text-xs text-slate-500">CSV with authorized fields</p>
                </div>
                <button
                    type="button"
                    class="grid h-9 w-9 place-items-center rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 disabled:opacity-50"
                    :title="`Export ${label}`"
                    :disabled="loading === resource"
                    @click="download(resource)"
                >
                    <Download class="h-4 w-4" />
                </button>
            </article>
        </div>
    </section>
</template>
