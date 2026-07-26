<script setup lang="ts">
import {
    Archive,
    CheckCircle2,
    Copy,
    Download,
    Eye,
    FileText,
    Send,
    Star,
    Trash2,
    X,
    XCircle,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import ConfirmDialog from '../shared/ConfirmDialog.vue';
import PaginationFooter from '../shared/PaginationFooter.vue';
import StatusBadge from '../shared/StatusBadge.vue';
import { useExerciseStudio } from './useExerciseStudio';

const props = defineProps<{
    mode: 'documents' | 'moderation';
    canModerate: boolean;
    refreshToken: number;
    t: (key: string) => string;
    showError: (error: any, fallback?: string) => void;
    showSuccess: (message: string) => void;
}>();

const studio = useExerciseStudio(props.showError, props.showSuccess);
const actionReason = ref('');
const transferUserId = ref<number | undefined>();
const showConfirm = ref(false);
const destructiveAction = ref<'archive' | 'delete'>('archive');

function requestDestructive(action: 'archive' | 'delete') {
    destructiveAction.value = action;
    actionReason.value = '';
    showConfirm.value = true;
}

async function confirmDestructive() {
    await studio.action(destructiveAction.value, actionReason.value);
    showConfirm.value = false;
}

watch(() => [props.mode, props.refreshToken], () => {
    studio.filters.moderation_status = props.mode === 'moderation' ? 'pending' : '';
    studio.load(1);
}, { immediate: true });
</script>

<template>
    <section class="space-y-4">
        <header>
            <h2 class="text-lg font-bold text-white">{{ mode === 'moderation' ? 'Moderation queue' : 'Exercise documents' }}</h2>
            <p class="text-sm text-slate-500">{{ studio.pagination.value.total }} documents</p>
        </header>

        <div class="grid gap-2 border-y border-slate-800 py-3 md:grid-cols-3 xl:grid-cols-6">
            <input v-model="studio.filters.search" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-white md:col-span-2" placeholder="Document title" @keyup.enter="studio.load(1)" />
            <select v-model="studio.filters.publication_status" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" @change="studio.load(1)">
                <option value="">All publishing states</option><option value="draft">Draft</option><option value="in_review">In review</option><option value="approved">Approved</option><option value="published">Published</option><option value="archived">Archived</option>
            </select>
            <select v-model="studio.filters.source" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" @change="studio.load(1)">
                <option value="">All sources</option><option value="manual">Manual</option><option value="ai">AI</option><option value="import">Import</option>
            </select>
            <input v-model="studio.filters.locale" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" placeholder="Locale" @keyup.enter="studio.load(1)" />
            <button type="button" class="h-10 rounded-lg border border-slate-700 text-sm font-semibold text-slate-300" @click="studio.load(1)">{{ t('common.search') }}</button>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-800 bg-slate-900/60">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-800 bg-slate-950/60 text-left text-xs text-slate-500">
                        <tr><th class="px-3 py-3">Document</th><th class="px-3 py-3">Owner</th><th class="px-3 py-3">Source</th><th class="px-3 py-3">Locale / Pages</th><th class="px-3 py-3">Publishing</th><th class="px-3 py-3">Updated</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <tr v-if="studio.loading.value"><td colspan="7" class="px-4 py-10 text-center text-slate-500">{{ t('common.loading') }}</td></tr>
                        <tr v-else-if="studio.items.value.length === 0"><td colspan="7" class="px-4 py-10 text-center text-slate-500">{{ t('common.empty') }}</td></tr>
                        <tr v-for="item in studio.items.value" v-else :key="item.id" class="hover:bg-slate-800/35">
                            <td class="px-3 py-3"><strong class="block text-white">{{ item.title }}</strong><small class="text-slate-500">{{ item.items_count }} blocks</small></td>
                            <td class="px-3 py-3 text-slate-300">{{ item.user?.name || '—' }}</td>
                            <td class="px-3 py-3"><StatusBadge :status="item.ai_generated ? 'ai' : item.creation_source" /></td>
                            <td class="px-3 py-3 text-slate-300">{{ item.language.toUpperCase() }} · {{ item.page_count }}</td>
                            <td class="px-3 py-3"><StatusBadge :status="item.publication_status" /></td>
                            <td class="px-3 py-3 text-slate-400">{{ new Date(item.updated_at).toLocaleString() }}</td>
                            <td class="px-3 py-3 text-right"><button type="button" class="rounded-lg border border-slate-700 p-2 text-slate-300" @click="studio.open(item)"><Eye class="h-4 w-4" /></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <PaginationFooter :pagination="studio.pagination.value" label="documents" @page="studio.load" />
        </div>

        <aside v-if="studio.detail.value || studio.detailLoading.value" class="fixed inset-y-0 right-0 z-50 w-full max-w-4xl overflow-y-auto border-l border-slate-700 bg-slate-950 p-5 shadow-2xl">
            <p v-if="studio.detailLoading.value" class="py-20 text-center text-slate-500">{{ t('common.loading') }}</p>
            <template v-else-if="studio.detail.value">
                <header class="mb-4 flex items-start justify-between border-b border-slate-800 pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-white">{{ studio.detail.value.title }}</h2>
                        <p class="text-sm text-slate-500">{{ studio.detail.value.user?.name }} · {{ studio.detail.value.language.toUpperCase() }}</p>
                        <div class="mt-2 flex gap-2"><StatusBadge :status="studio.detail.value.publication_status" /><StatusBadge :status="studio.detail.value.moderation_status" /></div>
                    </div>
                    <button type="button" class="text-slate-400" @click="studio.detail.value = null"><X class="h-5 w-5" /></button>
                </header>

                <div v-if="canModerate" class="mb-4 flex flex-wrap gap-2">
                    <button class="action-button" type="button" @click="studio.action('submit_review', actionReason || 'Submitted for review')"><Send class="h-4 w-4" /> Submit</button>
                    <button class="action-button" type="button" @click="studio.action('approve', actionReason || 'Approved by moderator')"><CheckCircle2 class="h-4 w-4" /> Approve</button>
                    <button class="action-button" type="button" @click="studio.action('reject', actionReason || 'Rejected by moderator')"><XCircle class="h-4 w-4" /> Reject</button>
                    <button class="action-button" type="button" @click="studio.action('publish', actionReason || 'Published by moderator')"><Eye class="h-4 w-4" /> Publish</button>
                    <button class="action-button" type="button" @click="studio.action('duplicate', actionReason || 'Duplicated for editing')"><Copy class="h-4 w-4" /> Duplicate</button>
                    <button class="action-button" type="button" @click="studio.action('feature', actionReason || 'Featured by moderator')"><Star class="h-4 w-4" /> Feature</button>
                    <button class="action-button" type="button" @click="studio.exportDocument('json')"><Download class="h-4 w-4" /> JSON</button>
                    <button class="action-button" type="button" @click="studio.exportDocument('html')"><Download class="h-4 w-4" /> HTML</button>
                    <button class="action-button text-rose-300" type="button" @click="requestDestructive('archive')"><Archive class="h-4 w-4" /> Archive</button>
                    <button class="action-button text-rose-300" type="button" @click="requestDestructive('delete')"><Trash2 class="h-4 w-4" /> Delete</button>
                </div>
                <div class="mb-5 grid gap-2 sm:grid-cols-[1fr_auto_auto]">
                    <input v-model="actionReason" class="rounded-lg border-slate-700 bg-slate-900 text-sm text-white" placeholder="Moderation reason / note" />
                    <input v-model.number="transferUserId" type="number" class="w-32 rounded-lg border-slate-700 bg-slate-900 text-sm text-white" placeholder="Owner ID" />
                    <button type="button" class="rounded-lg border border-slate-700 px-3 text-xs font-semibold text-slate-300" @click="studio.action('transfer', actionReason || 'Ownership transferred', transferUserId)">Transfer</button>
                </div>

                <section class="mb-5">
                    <h3 class="mb-2 text-xs font-bold uppercase text-slate-500">Blocks and provenance</h3>
                    <div class="space-y-2">
                        <article v-for="block in studio.detail.value.items" :key="block.id" class="rounded-lg border border-slate-800 bg-slate-900 p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <strong class="text-sm text-white">#{{ block.item_order + 1 }} · {{ block.type }}</strong>
                                <div class="flex gap-2"><StatusBadge :status="block.source" /><StatusBadge v-if="block.edited_after_ai" status="edited" /></div>
                            </div>
                            <p class="mt-2 whitespace-pre-wrap text-sm text-slate-300">{{ block.question }}</p>
                            <p v-if="block.explanation" class="mt-2 text-xs text-slate-500">{{ block.explanation }}</p>
                        </article>
                    </div>
                </section>

                <section class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-xs font-bold uppercase text-slate-500">Moderation history</h3>
                        <div class="space-y-2">
                            <article v-for="note in studio.detail.value.moderation_notes" :key="note.id" class="rounded-lg border border-slate-800 p-3">
                                <div class="flex justify-between"><StatusBadge :status="note.status" /><time class="text-xs text-slate-600">{{ new Date(note.created_at).toLocaleString() }}</time></div>
                                <p class="mt-2 text-sm text-slate-300">{{ note.note }}</p>
                            </article>
                        </div>
                    </div>
                    <div>
                        <h3 class="mb-2 text-xs font-bold uppercase text-slate-500">Revision history</h3>
                        <div class="space-y-2">
                            <article v-for="version in studio.detail.value.versions" :key="version.id" class="rounded-lg border border-slate-800 p-3">
                                <strong class="text-sm text-white">Version {{ version.version_number }}</strong>
                                <p class="text-xs text-slate-500">{{ version.author?.name || 'System' }} · {{ version.reason || 'No reason' }}</p>
                            </article>
                        </div>
                    </div>
                </section>
            </template>
        </aside>

        <ConfirmDialog
            :show="showConfirm"
            :title="destructiveAction === 'delete' ? 'Delete document' : 'Archive document'"
            message="This action is recorded in the audit log and requires a reason."
            :confirm-label="destructiveAction === 'delete' ? 'Delete' : 'Archive'"
            :reason="actionReason"
            :busy="studio.submitting.value"
            @update:reason="actionReason = $event"
            @close="showConfirm = false"
            @confirm="confirmDestructive"
        />
    </section>
</template>

<style scoped>
.action-button {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    border: 1px solid rgb(51 65 85);
    border-radius: 0.5rem;
    padding: 0.45rem 0.6rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: rgb(203 213 225);
}
.action-button:hover { background: rgb(30 41 59); }
</style>
