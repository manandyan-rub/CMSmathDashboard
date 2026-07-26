<script setup lang="ts">
import { Bot, Copy, Download, Languages, Pencil, Search, Upload, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import PaginationFooter from '../shared/PaginationFooter.vue';
import StatusBadge from '../shared/StatusBadge.vue';
import { useLocalization } from './useLocalization';

const props = defineProps<{
    missingOnly: boolean;
    canManage: boolean;
    refreshToken: number;
    t: (key: string) => string;
    showError: (error: any, fallback?: string) => void;
    showSuccess: (message: string) => void;
}>();

const localization = useLocalization(props.showError, props.showSuccess);
const fileInput = ref<HTMLInputElement | null>(null);

async function handleFile(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) await localization.importFile(file);
}

watch(() => [props.missingOnly, props.refreshToken], () => {
    localization.filters.missing = props.missingOnly;
    localization.load(1);
}, { immediate: true });
</script>

<template>
    <section class="space-y-4">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-bold text-white"><Languages class="h-5 w-5 text-emerald-300" /> {{ missingOnly ? 'Missing translations' : 'Translation overview' }}</h2>
                <p class="text-sm text-slate-500">Side-by-side locale editing with review status and draft provenance.</p>
            </div>
            <div class="flex gap-2">
                <input ref="fileInput" type="file" accept="application/json" class="hidden" @change="handleFile" />
                <button v-if="canManage" type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-300" @click="fileInput?.click()"><Upload class="h-4 w-4" /> Import</button>
                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-300" @click="localization.exportTranslations"><Download class="h-4 w-4" /> Export</button>
            </div>
        </header>

        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
            <article v-for="entity in localization.overview.value.entities || []" :key="entity.type" class="rounded-lg border border-slate-800 bg-slate-900 p-3">
                <div class="flex justify-between"><strong class="text-xs text-white">{{ entity.type.replaceAll('_', ' ') }}</strong><span class="text-xs font-bold" :class="entity.completeness === 100 ? 'text-emerald-300' : 'text-amber-300'">{{ entity.completeness }}%</span></div>
                <div class="mt-2 h-1.5 rounded-full bg-slate-800"><div class="h-full rounded-full bg-emerald-400" :style="{ width: `${entity.completeness}%` }"></div></div>
            </article>
        </div>

        <div class="flex flex-wrap gap-2 border-y border-slate-800 py-3">
            <select v-model="localization.filters.entity_type" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" @change="localization.load(1)">
                <option v-for="entity in localization.overview.value.entities || []" :key="entity.type" :value="entity.type">{{ entity.type.replaceAll('_', ' ') }}</option>
            </select>
            <select v-model="localization.filters.locale" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" @change="localization.load(1)">
                <option v-for="locale in localization.overview.value.locales || []" :key="locale" :value="locale">{{ locale.toUpperCase() }}</option>
            </select>
            <label class="relative min-w-56 flex-1"><Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-500" /><input v-model="localization.filters.search" class="h-10 w-full rounded-lg border-slate-700 bg-slate-950 pl-9 text-sm text-white" @keyup.enter="localization.load(1)" /></label>
            <label class="flex items-center gap-2 rounded-lg border border-slate-700 px-3 text-xs text-slate-300"><input v-model="localization.filters.outdated" type="checkbox" @change="localization.load(1)" /> Outdated</label>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-800">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-950/60 text-left text-xs text-slate-500"><tr><th class="px-3 py-3">Content</th><th class="px-3 py-3">Source {{ localization.overview.value.source_locale?.toUpperCase() }}</th><th class="px-3 py-3">Target {{ localization.filters.locale.toUpperCase() }}</th><th class="px-3 py-3">Status</th><th></th></tr></thead>
                    <tbody class="divide-y divide-slate-800">
                        <tr v-if="localization.loading.value"><td colspan="5" class="px-4 py-10 text-center text-slate-500">{{ t('common.loading') }}</td></tr>
                        <tr v-else-if="localization.items.value.length === 0"><td colspan="5" class="px-4 py-10 text-center text-slate-500">{{ t('common.empty') }}</td></tr>
                        <tr v-for="item in localization.items.value" v-else :key="item.id">
                            <td class="px-3 py-3 font-semibold text-white">{{ item.identity }}</td>
                            <td class="max-w-sm px-3 py-3 text-slate-400"><p v-for="field in item.fields" :key="field" class="line-clamp-2"><span class="text-slate-600">{{ field }}:</span> {{ item.translations?.[localization.overview.value.source_locale]?.[field] || '—' }}</p></td>
                            <td class="max-w-sm px-3 py-3 text-slate-300"><p v-for="field in item.fields" :key="field" class="line-clamp-2"><span class="text-slate-600">{{ field }}:</span> {{ item.translations?.[localization.filters.locale]?.[field] || '—' }}</p></td>
                            <td class="px-3 py-3"><StatusBadge :status="item.translations?.[localization.filters.locale]?.reviewed ? 'reviewed' : item.translations?.[localization.filters.locale]?.machine_draft ? 'machine_draft' : item.translations?.[localization.filters.locale] ? 'draft' : 'missing'" /></td>
                            <td class="px-3 py-3"><div class="flex justify-end gap-1">
                                <button v-if="canManage" type="button" class="icon-button" title="Copy source" @click="localization.copySource(item)"><Copy class="h-4 w-4" /></button>
                                <button v-if="canManage" type="button" class="icon-button" title="Machine draft" @click="localization.machineDraft(item)"><Bot class="h-4 w-4" /></button>
                                <button v-if="canManage" type="button" class="icon-button" :title="t('common.edit')" @click="localization.edit(item)"><Pencil class="h-4 w-4" /></button>
                            </div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <PaginationFooter :pagination="localization.pagination.value" label="translations" @page="localization.load" />
        </div>

        <aside v-if="localization.selected.value" class="fixed inset-y-0 right-0 z-50 w-full max-w-2xl overflow-y-auto border-l border-slate-700 bg-slate-950 p-5 shadow-2xl">
            <header class="mb-5 flex justify-between"><div><h2 class="font-bold text-white">{{ localization.selected.value.identity }}</h2><p class="text-xs text-slate-500">{{ localization.filters.locale.toUpperCase() }}</p></div><button type="button" class="text-slate-400" @click="localization.selected.value = null"><X class="h-5 w-5" /></button></header>
            <div class="space-y-4">
                <label v-for="field in localization.selected.value.fields" :key="field" class="block text-xs font-semibold text-slate-300">
                    {{ field.replaceAll('_', ' ') }}
                    <textarea v-model="localization.editValues[field]" rows="5" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-900 text-sm text-white"></textarea>
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-300"><input v-model="localization.editReviewed.value" type="checkbox" /> Mark reviewed</label>
                <label class="block text-xs text-slate-300">Reason<input v-model="localization.reason.value" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-900 text-sm text-white" /></label>
                <button type="button" class="rounded-lg bg-emerald-500 px-3 py-2 text-sm font-bold text-slate-950" :disabled="localization.submitting.value" @click="localization.save">{{ t('common.save') }}</button>
            </div>
        </aside>
    </section>
</template>

<style scoped>
.icon-button { display: inline-grid; height: 2rem; width: 2rem; place-items: center; border-radius: .5rem; border: 1px solid rgb(51 65 85); color: rgb(203 213 225); }
.icon-button:hover { background: rgb(30 41 59); color: white; }
</style>
