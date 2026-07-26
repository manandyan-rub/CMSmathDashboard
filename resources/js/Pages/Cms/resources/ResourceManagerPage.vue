<script setup lang="ts">
import {
    Archive,
    Copy,
    Eye,
    FileClock,
    GripVertical,
    Pencil,
    Plus,
    Search,
    X,
} from 'lucide-vue-next';
import { computed, ref, toRef, watch } from 'vue';
import ConfirmDialog from '../shared/ConfirmDialog.vue';
import PaginationFooter from '../shared/PaginationFooter.vue';
import StatusBadge from '../shared/StatusBadge.vue';
import ResourceFormModal from './partials/ResourceFormModal.vue';
import { useCmsResource } from './useCmsResource';

const props = defineProps<{
    resourceKey: string;
    locale: string;
    refreshToken: number;
    permissions: string[];
    t: (key: string, replacements?: Record<string, string | number>) => string;
    showError: (error: any, fallback?: string) => void;
    showSuccess: (message: string) => void;
}>();

const resourceKey = toRef(props, 'resourceKey');
const locale = toRef(props, 'locale');
const resource = useCmsResource(resourceKey, {
    locale,
    permissions: props.permissions,
    showError: props.showError,
    showSuccess: props.showSuccess,
});
const allSelected = computed(() => resource.items.value.length > 0
    && resource.items.value.every((item) => resource.selectedIds.value.includes(item.id)));
const importInput = ref<HTMLInputElement | null>(null);
const publishTarget = ref<Record<string, any> | null>(null);
const publishState = ref('');
const publishComment = ref('');
const publishScheduledAt = ref('');
const restoreReason = ref('');

function displayValue(item: Record<string, any>, key: string) {
    const value = item[key];
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return Array.isArray(value) ? `${value.length} items` : 'Configured';
    return String(value);
}

function toggleAll() {
    resource.selectedIds.value = allSelected.value ? [] : resource.items.value.map((item) => item.id);
}

async function importQuestions(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    try {
        const parsed = JSON.parse(await file.text());
        const items = Array.isArray(parsed) ? parsed : parsed.items;
        await window.axios.post('/cms-api/questions/import', {
            items,
            reason: 'Question import from CMS JSON file',
        });
        props.showSuccess(`${items.length} questions imported.`);
        await resource.load(1);
    } catch (error) {
        props.showError(error, 'Questions could not be imported.');
    } finally {
        if (importInput.value) importInput.value.value = '';
    }
}

function requestPublishing(item: Record<string, any>, state: string) {
    publishTarget.value = item;
    publishState.value = state;
    publishComment.value = `Move content from ${item.publishing?.state || 'draft'} to ${state}`;
    publishScheduledAt.value = '';
}

async function confirmPublishing() {
    if (!publishTarget.value || publishComment.value.trim().length < 3) return;
    await resource.publish(
        publishTarget.value,
        publishState.value,
        publishComment.value,
        publishState.value === 'published' ? publishScheduledAt.value : undefined,
    );
    publishTarget.value = null;
}

watch(() => [props.resourceKey, props.refreshToken, props.locale], () => resource.load(1), { immediate: true });
</script>

<template>
    <section class="space-y-4">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-white">{{ resource.schema.value?.title || resourceKey }}</h2>
                <p class="text-sm text-slate-500">Filtered, localized, versioned, and publication-aware content.</p>
            </div>
            <button
                v-if="resource.canManage.value"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-3 py-2 text-sm font-bold text-slate-950"
                @click="resource.openCreate"
            >
                <Plus class="h-4 w-4" />
                {{ t('common.create') }}
            </button>
            <template v-if="resourceKey === 'questions' && resource.canManage.value">
                <input ref="importInput" type="file" accept="application/json" class="hidden" @change="importQuestions" />
                <button type="button" class="rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-300" @click="importInput?.click()">
                    Import JSON
                </button>
            </template>
        </header>

        <div class="flex flex-wrap items-center gap-2 border-y border-slate-800 py-3">
            <label class="relative min-w-[16rem] flex-1">
                <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-500" />
                <input
                    v-model="resource.search.value"
                    class="h-10 w-full rounded-lg border border-slate-700 bg-slate-950 pl-9 text-sm text-white focus:border-emerald-400 focus:ring-0"
                    :placeholder="t('common.search')"
                    @keyup.enter="resource.load(1)"
                />
            </label>
            <select
                v-model="resource.workflowState.value"
                class="h-10 rounded-lg border border-slate-700 bg-slate-950 px-3 text-sm text-slate-300 focus:border-emerald-400 focus:ring-0"
                @change="resource.load(1)"
            >
                <option value="">{{ t('common.all') }} publishing states</option>
                <option value="draft">Draft</option>
                <option value="in_review">In review</option>
                <option value="approved">Approved</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
            </select>
            <select
                v-model="resource.sort.value"
                class="h-10 rounded-lg border border-slate-700 bg-slate-950 px-3 text-sm text-slate-300 focus:border-emerald-400 focus:ring-0"
                @change="resource.load(1)"
            >
                <option v-for="sort in resource.schema.value?.sorts || []" :key="sort" :value="sort">
                    {{ sort.replaceAll('_', ' ') }}
                </option>
            </select>
            <button type="button" class="h-10 rounded-lg border border-slate-700 px-3 text-sm font-semibold text-slate-300" @click="resource.load(1)">
                {{ t('common.search') }}
            </button>
        </div>

        <div v-if="resource.canManage.value && resource.selectedIds.value.length" class="flex flex-wrap items-center gap-2 rounded-lg border border-amber-400/25 bg-amber-400/5 px-3 py-2">
            <span class="mr-auto text-sm font-semibold text-amber-200">
                {{ t('resource.selected', { count: resource.selectedIds.value.length }) }}
            </span>
            <button type="button" class="rounded-lg border border-slate-700 px-2.5 py-1.5 text-xs text-slate-300" @click="resource.bulk('activate')">Activate</button>
            <button type="button" class="rounded-lg border border-slate-700 px-2.5 py-1.5 text-xs text-slate-300" @click="resource.bulk('deactivate')">Deactivate</button>
            <button type="button" class="rounded-lg border border-rose-400/30 px-2.5 py-1.5 text-xs text-rose-300" @click="resource.bulk('archive')">Archive</button>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-800 bg-slate-900/60">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-800 bg-slate-950/60 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="w-10 px-3 py-3"><input type="checkbox" :checked="allSelected" @change="toggleAll" /></th>
                            <th class="w-8 px-1 py-3"></th>
                            <th class="px-3 py-3 text-left">Content</th>
                            <th v-for="field in resource.visibleFields.value" :key="field.key" class="px-3 py-3 text-left">
                                {{ field.label }}
                            </th>
                            <th class="px-3 py-3 text-left">Publishing</th>
                            <th class="px-3 py-3 text-left">Translation</th>
                            <th class="px-3 py-3 text-right">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <tr v-if="resource.loading.value">
                            <td :colspan="resource.visibleFields.value.length + 7" class="px-4 py-10 text-center text-slate-500">{{ t('common.loading') }}</td>
                        </tr>
                        <tr v-else-if="resource.items.value.length === 0">
                            <td :colspan="resource.visibleFields.value.length + 7" class="px-4 py-10 text-center text-slate-500">{{ t('common.empty') }}</td>
                        </tr>
                        <tr
                            v-for="item in resource.items.value"
                            v-else
                            :key="item.id"
                            :draggable="resource.canManage.value"
                            class="hover:bg-slate-800/35"
                            @dragstart="resource.startDrag(item.id)"
                            @dragover.prevent
                            @drop="resource.dropOn(item.id)"
                        >
                            <td class="px-3 py-3"><input v-if="resource.canManage.value" type="checkbox" :checked="resource.selectedIds.value.includes(item.id)" @change="resource.toggleSelection(item.id)" /></td>
                            <td class="px-1 py-3 text-slate-600"><GripVertical class="h-4 w-4" /></td>
                            <td class="max-w-[18rem] px-3 py-3">
                                <p class="truncate font-semibold text-white">{{ item.translation?.title || item.translation?.name || item.translation?.question || item.slug || item.key || `#${item.id}` }}</p>
                                <p class="truncate text-xs text-slate-500">#{{ item.id }}</p>
                            </td>
                            <td v-for="field in resource.visibleFields.value" :key="field.key" class="max-w-[14rem] truncate px-3 py-3 text-slate-300">
                                {{ displayValue(item, field.key) }}
                            </td>
                            <td class="px-3 py-3">
                                <select
                                    v-if="permissions.includes('content.review') || permissions.includes('content.publish')"
                                    :value="item.publishing?.state || 'draft'"
                                    class="h-8 rounded-lg border border-slate-700 bg-slate-950 px-2 text-xs text-slate-300"
                                    @change="requestPublishing(item, ($event.target as HTMLSelectElement).value)"
                                >
                                    <option value="draft">Draft</option>
                                    <option value="in_review">In review</option>
                                    <option value="approved">Approved</option>
                                    <option value="published">Published</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </td>
                            <td class="px-3 py-3">
                                <span class="text-xs font-bold" :class="item.translation_completeness === 100 ? 'text-emerald-300' : 'text-amber-300'">
                                    {{ item.translation_completeness }}%
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex justify-end gap-1">
                                    <button type="button" class="icon-button" :title="t('common.preview')" @click="resource.loadPreview(item)"><Eye class="h-4 w-4" /></button>
                                    <button type="button" class="icon-button" :title="t('resource.history')" @click="resource.loadVersions(item)"><FileClock class="h-4 w-4" /></button>
                                    <button v-if="resource.canManage.value" type="button" class="icon-button" :title="t('common.duplicate')" @click="resource.duplicate(item)"><Copy class="h-4 w-4" /></button>
                                    <button v-if="resource.canManage.value" type="button" class="icon-button" :title="t('common.edit')" @click="resource.openEdit(item)"><Pencil class="h-4 w-4" /></button>
                                    <button v-if="resource.canManage.value" type="button" class="icon-button text-rose-300" :title="t('common.archive')" @click="resource.requestArchive(item)"><Archive class="h-4 w-4" /></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <PaginationFooter :pagination="resource.pagination.value" :label="resource.schema.value?.title || 'items'" @page="resource.load" />
        </div>

        <aside v-if="resource.detailMode.value" class="fixed inset-y-0 right-0 z-50 w-full max-w-2xl overflow-y-auto border-l border-slate-700 bg-slate-950 p-5 shadow-2xl">
            <header class="mb-4 flex items-center justify-between">
                <h2 class="font-bold text-white">{{ resource.detailMode.value === 'preview' ? t('common.preview') : t('resource.history') }}</h2>
                <button type="button" class="text-slate-400 hover:text-white" @click="resource.detailMode.value = null"><X class="h-5 w-5" /></button>
            </header>
            <pre v-if="resource.detailMode.value === 'preview'" class="overflow-x-auto whitespace-pre-wrap rounded-lg border border-slate-800 bg-slate-900 p-4 text-xs text-slate-300">{{ JSON.stringify(resource.preview.value, null, 2) }}</pre>
            <div v-else class="space-y-2">
                <article v-for="version in resource.versions.value" :key="version.id" class="rounded-lg border border-slate-800 bg-slate-900 p-3">
                    <div class="flex justify-between gap-3">
                        <strong class="text-sm text-white">Version {{ version.version_number }}</strong>
                        <time class="text-xs text-slate-500">{{ new Date(version.created_at).toLocaleString() }}</time>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">{{ version.author?.name || 'System' }} · {{ version.reason || 'No reason provided' }}</p>
                    <p class="mt-2 text-xs text-emerald-300">{{ (version.changed_fields || []).join(', ') }}</p>
                    <details class="mt-3 rounded-lg border border-slate-800 bg-slate-950 p-2">
                        <summary class="cursor-pointer text-xs font-semibold text-slate-300">Preview version data</summary>
                        <div class="mt-2 grid gap-2">
                            <pre class="max-h-56 overflow-auto whitespace-pre-wrap text-[10px] text-rose-200">{{ JSON.stringify(version.previous_data, null, 2) }}</pre>
                            <pre class="max-h-56 overflow-auto whitespace-pre-wrap border-t border-slate-800 pt-2 text-[10px] text-emerald-200">{{ JSON.stringify(version.new_data, null, 2) }}</pre>
                        </div>
                    </details>
                    <button
                        type="button"
                        class="mt-3 rounded-lg border border-amber-400/30 px-2.5 py-1.5 text-xs font-semibold text-amber-200 disabled:opacity-40"
                        :disabled="restoreReason.trim().length < 3"
                        @click="resource.restoreVersion(version, restoreReason)"
                    >
                        Restore this version
                    </button>
                </article>
                <label class="block text-xs font-semibold text-slate-300">
                    Required restoration reason
                    <textarea v-model="restoreReason" rows="2" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-900 text-sm text-white"></textarea>
                </label>
            </div>
        </aside>

        <ResourceFormModal
            :show="resource.showForm.value"
            :schema="resource.schema.value"
            :form="resource.form"
            :active-locale="resource.activeTranslationLocale.value"
            :submitting="resource.submitting.value"
            :t="t"
            @update:active-locale="resource.activeTranslationLocale.value = $event"
            @close="resource.showForm.value = false"
            @save="resource.save"
        />
        <ConfirmDialog
            :show="resource.showConfirm.value"
            title="Archive content"
            message="This removes the item from active use while preserving history and references."
            confirm-label="Archive"
            :reason="resource.confirmReason.value"
            :busy="resource.submitting.value"
            @update:reason="resource.confirmReason.value = $event"
            @close="resource.showConfirm.value = false"
            @confirm="resource.confirmArchive"
        />

        <div v-if="publishTarget" class="fixed inset-0 z-[70] grid place-items-center bg-slate-950/75 p-4">
            <section class="w-full max-w-lg rounded-lg border border-slate-700 bg-slate-900 shadow-2xl">
                <header class="flex items-center justify-between border-b border-slate-700 px-5 py-4">
                    <div>
                        <h2 class="font-bold text-white">Publishing transition</h2>
                        <p class="text-xs text-slate-500">{{ publishTarget.publishing?.state || 'draft' }} to {{ publishState }}</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-white" @click="publishTarget = null"><X class="h-5 w-5" /></button>
                </header>
                <div class="space-y-4 p-5">
                    <label class="block text-xs font-semibold text-slate-300">
                        Reviewer comment or reason
                        <textarea v-model="publishComment" rows="3" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white"></textarea>
                    </label>
                    <label v-if="publishState === 'published'" class="block text-xs font-semibold text-slate-300">
                        Schedule for later (optional)
                        <input v-model="publishScheduledAt" type="datetime-local" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white" />
                    </label>
                </div>
                <footer class="flex justify-end gap-2 border-t border-slate-700 px-5 py-4">
                    <button type="button" class="rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-300" @click="publishTarget = null">{{ t('common.cancel') }}</button>
                    <button type="button" class="rounded-lg bg-emerald-500 px-3 py-2 text-sm font-bold text-slate-950 disabled:opacity-40" :disabled="publishComment.trim().length < 3" @click="confirmPublishing">{{ t('common.confirm') }}</button>
                </footer>
            </section>
        </div>
    </section>
</template>

<style scoped>
.icon-button {
    display: inline-grid;
    height: 2rem;
    width: 2rem;
    place-items: center;
    border-radius: 0.5rem;
    border: 1px solid rgb(51 65 85);
    color: rgb(203 213 225);
}
.icon-button:hover {
    background: rgb(30 41 59);
    color: white;
}
</style>
