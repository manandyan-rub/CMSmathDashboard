<script setup lang="ts">
import {
    BadgeCheck,
    Ban,
    Download,
    KeyRound,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    UserCheck,
    X,
} from 'lucide-vue-next';
import { computed, toRef, watch } from 'vue';
import PaginationFooter from '../shared/PaginationFooter.vue';
import StatusBadge from '../shared/StatusBadge.vue';
import UserModal from './partials/UserModal.vue';
import { useUsers } from './useUsers';

const props = defineProps<{
    role: string;
    locale: string;
    countries: Array<{ id: number; name: string; iso_code: string }>;
    locales: string[];
    permissions: Array<{ key: string; label: string; group: string }>;
    grantedPermissions: string[];
    refreshToken: number;
    t: (key: string) => string;
    showError: (error: any, fallback?: string) => void;
    showSuccess: (message: string) => void;
}>();

const role = toRef(props, 'role');
const locale = toRef(props, 'locale');
const users = useUsers({
    role,
    locale,
    permissionDefinitions: props.permissions,
    showError: props.showError,
    showSuccess: props.showSuccess,
});
const title = computed(() => props.role === 'lecturer' ? 'Lecturers' : props.role === 'admin' ? 'Administrators' : 'Students');

watch(() => [props.role, props.refreshToken, props.locale], () => users.load(1), { immediate: true });
</script>

<template>
    <section class="space-y-4">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-white">{{ title }}</h2>
                <p class="text-sm text-slate-500">{{ users.pagination.value.total }} accounts</p>
            </div>
            <button v-if="grantedPermissions.includes('users.manage')" type="button" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-3 py-2 text-sm font-bold text-slate-950" @click="users.openCreate">
                <Plus class="h-4 w-4" /> {{ t('common.create') }}
            </button>
        </header>

        <div class="grid gap-2 border-y border-slate-800 py-3 md:grid-cols-3 xl:grid-cols-6">
            <label class="relative md:col-span-2">
                <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-500" />
                <input v-model="users.filters.search" class="h-10 w-full rounded-lg border-slate-700 bg-slate-950 pl-9 text-sm text-white" placeholder="Name or email" @keyup.enter="users.load(1)" />
            </label>
            <select v-model="users.filters.status" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" @change="users.load(1)">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="deactivated">Deactivated</option>
            </select>
            <select v-model="users.filters.country_id" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" @change="users.load(1)">
                <option value="">All countries</option>
                <option v-for="country in countries" :key="country.id" :value="country.id">{{ country.name }}</option>
            </select>
            <select v-model="users.filters.locale" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" @change="users.load(1)">
                <option value="">All locales</option>
                <option v-for="item in locales" :key="item" :value="item">{{ item.toUpperCase() }}</option>
            </select>
            <select v-model="users.filters.verified" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" @change="users.load(1)">
                <option value="">Any verification</option>
                <option value="1">Verified</option>
                <option value="0">Unverified</option>
            </select>
            <input v-model="users.filters.registered_from" type="date" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" @change="users.load(1)" />
            <input v-model="users.filters.registered_to" type="date" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" @change="users.load(1)" />
            <input v-model="users.filters.last_active_from" type="date" class="h-10 rounded-lg border-slate-700 bg-slate-950 text-sm text-slate-300" @change="users.load(1)" />
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-800 bg-slate-900/60">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-800 bg-slate-950/60 text-left text-xs text-slate-500">
                        <tr>
                            <th class="px-3 py-3">User</th><th class="px-3 py-3">Country</th><th class="px-3 py-3">Locale</th>
                            <th class="px-3 py-3">XP / Level</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Last active</th>
                            <th class="px-3 py-3 text-right">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <tr v-if="users.loading.value"><td colspan="7" class="px-4 py-10 text-center text-slate-500">{{ t('common.loading') }}</td></tr>
                        <tr v-else-if="users.items.value.length === 0"><td colspan="7" class="px-4 py-10 text-center text-slate-500">{{ t('common.empty') }}</td></tr>
                        <tr v-for="user in users.items.value" v-else :key="user.id" class="hover:bg-slate-800/35">
                            <td class="px-3 py-3">
                                <button type="button" class="flex items-center gap-2 text-left" @click="users.openDetail(user)">
                                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-teal-400/10 font-bold text-teal-200">{{ user.name.charAt(0) }}</span>
                                    <span><strong class="block text-white">{{ user.name }}</strong><small class="text-slate-500">{{ user.email }}</small></span>
                                </button>
                            </td>
                            <td class="px-3 py-3 text-slate-300">{{ user.country?.name || '—' }}</td>
                            <td class="px-3 py-3 uppercase text-slate-400">{{ user.locale }}</td>
                            <td class="px-3 py-3"><strong class="text-white">{{ user.xp }}</strong><small class="ml-1 text-slate-500">{{ user.level || '—' }}</small></td>
                            <td class="px-3 py-3"><StatusBadge :status="user.status" /></td>
                            <td class="px-3 py-3 text-slate-400">{{ user.last_active_at ? new Date(user.last_active_at).toLocaleString() : '—' }}</td>
                            <td class="px-3 py-3 text-right">
                                <button v-if="grantedPermissions.includes('users.manage')" type="button" class="rounded-lg border border-slate-700 p-2 text-slate-300 hover:bg-slate-800" :title="t('common.edit')" @click="users.openEdit(user)"><Pencil class="h-4 w-4" /></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <PaginationFooter :pagination="users.pagination.value" label="users" @page="users.load" />
        </div>

        <aside v-if="users.detail.value || users.detailLoading.value" class="fixed inset-y-0 right-0 z-50 w-full max-w-3xl overflow-y-auto border-l border-slate-700 bg-slate-950 p-5 shadow-2xl">
            <div v-if="users.detailLoading.value" class="py-20 text-center text-slate-500">{{ t('common.loading') }}</div>
            <template v-else-if="users.detail.value">
                <header class="mb-5 flex items-start justify-between gap-3 border-b border-slate-800 pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-white">{{ users.detail.value.user.name }}</h2>
                        <p class="text-sm text-slate-500">{{ users.detail.value.user.email }}</p>
                        <div class="mt-2 flex gap-2"><StatusBadge :status="users.detail.value.user.status" /><StatusBadge :status="users.detail.value.user.role" /></div>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-white" @click="users.detail.value = null"><X class="h-5 w-5" /></button>
                </header>

                <div class="mb-5 grid gap-2 sm:grid-cols-3">
                    <article v-for="(value, key) in users.detail.value.statistics" :key="key" class="rounded-lg border border-slate-800 bg-slate-900 p-3">
                        <p class="text-[10px] uppercase text-slate-500">{{ String(key).replaceAll('_', ' ') }}</p>
                        <p class="mt-1 text-xl font-bold text-white">{{ value }}</p>
                    </article>
                </div>

                <section v-if="grantedPermissions.includes('users.manage')" class="mb-5">
                    <h3 class="mb-2 text-xs font-bold uppercase text-slate-500">Account actions</h3>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="action-button" @click="users.action('activate', 'Account reactivated by administrator')"><UserCheck class="h-4 w-4" /> Activate</button>
                        <button type="button" class="action-button" @click="users.action('suspend', 'Account suspended by administrator')"><Ban class="h-4 w-4" /> Suspend</button>
                        <button type="button" class="action-button" @click="users.action('verify_email', 'Email verified by administrator')"><BadgeCheck class="h-4 w-4" /> Verify email</button>
                        <button type="button" class="action-button" @click="users.action('send_password_reset', '')"><KeyRound class="h-4 w-4" /> Reset password</button>
                        <button type="button" class="action-button" @click="users.exportUser"><Download class="h-4 w-4" /> Export</button>
                    </div>
                </section>

                <section v-if="grantedPermissions.includes('users.xp.adjust') || grantedPermissions.includes('users.progress.reset')" class="mb-5 grid gap-3 md:grid-cols-2">
                    <form v-if="grantedPermissions.includes('users.xp.adjust')" class="rounded-lg border border-slate-800 bg-slate-900 p-4" @submit.prevent="users.adjustXp">
                        <h3 class="text-sm font-bold text-white">Adjust XP</h3>
                        <input v-model.number="users.xpForm.amount" type="number" class="mt-3 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white" placeholder="Amount" />
                        <textarea v-model="users.xpForm.reason" rows="2" class="mt-2 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white" placeholder="Required reason"></textarea>
                        <button type="submit" class="mt-2 rounded-lg bg-amber-400 px-3 py-2 text-xs font-bold text-slate-950">Record adjustment</button>
                    </form>
                    <form v-if="grantedPermissions.includes('users.progress.reset')" class="rounded-lg border border-slate-800 bg-slate-900 p-4" @submit.prevent="users.resetProgress">
                        <h3 class="text-sm font-bold text-white">Reset progress</h3>
                        <input v-model.number="users.resetForm.level_id" type="number" class="mt-3 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white" placeholder="Level ID (blank for all)" />
                        <textarea v-model="users.resetForm.reason" rows="2" class="mt-2 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white" placeholder="Required reason"></textarea>
                        <label class="mt-2 flex items-center gap-2 text-xs text-slate-400"><input v-model="users.resetForm.confirmed" type="checkbox" /> Confirm reset</label>
                        <button type="submit" class="mt-2 inline-flex items-center gap-2 rounded-lg bg-rose-500 px-3 py-2 text-xs font-bold text-white"><RotateCcw class="h-4 w-4" /> Reset safely</button>
                    </form>
                </section>

                <section v-if="users.detail.value.user.role === 'lecturer' && grantedPermissions.includes('lecturers.manage')" class="mb-5 rounded-lg border border-slate-800 bg-slate-900 p-4">
                    <h3 class="text-sm font-bold text-white">Lecturer controls</h3>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <select v-model="users.lecturerForm.approval_status" class="rounded-lg border-slate-700 bg-slate-950 text-sm text-white">
                            <option value="pending">Pending</option><option value="approved">Approved</option><option value="suspended">Suspended</option><option value="rejected">Rejected</option>
                        </select>
                        <input v-model.number="users.lecturerForm.monthly_ai_limit" type="number" class="rounded-lg border-slate-700 bg-slate-950 text-sm text-white" />
                        <input v-model.number="users.lecturerForm.storage_limit_bytes" type="number" class="rounded-lg border-slate-700 bg-slate-950 text-sm text-white" />
                    </div>
                    <textarea v-model="users.lecturerForm.moderation_note" rows="2" class="mt-3 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white" placeholder="Moderation note"></textarea>
                    <button type="button" class="mt-2 rounded-lg bg-emerald-500 px-3 py-2 text-xs font-bold text-slate-950" @click="users.saveLecturer">Save lecturer settings</button>
                </section>

                <section v-if="users.detail.value.user.role === 'admin' && grantedPermissions.includes('users.roles.manage')" class="mb-5 rounded-lg border border-slate-800 bg-slate-900 p-4">
                    <h3 class="text-sm font-bold text-white">Permission overrides</h3>
                    <p class="mt-1 text-xs text-slate-500">Blank values inherit the role. Explicit values override it.</p>
                    <div class="mt-3 grid max-h-72 gap-2 overflow-y-auto sm:grid-cols-2">
                        <label v-for="permission in permissions" :key="permission.key" class="grid grid-cols-[1fr_8rem] items-center gap-2 rounded-lg border border-slate-800 px-3 py-2">
                            <span class="min-w-0">
                                <strong class="block truncate text-xs text-white">{{ permission.label }}</strong>
                                <small class="text-[10px] text-slate-500">{{ permission.key }}</small>
                            </span>
                            <select v-model="users.permissionValues[permission.key]" class="h-8 rounded-lg border-slate-700 bg-slate-950 text-xs text-white">
                                <option value="">Inherit</option>
                                <option value="allow">Allow</option>
                                <option value="deny">Deny</option>
                            </select>
                        </label>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <input v-model="users.permissionReason.value" class="h-9 min-w-0 flex-1 rounded-lg border-slate-700 bg-slate-950 text-xs text-white" placeholder="Reason for permission change" />
                        <button type="button" class="rounded-lg bg-emerald-500 px-3 text-xs font-bold text-slate-950" @click="users.savePermissions">Save overrides</button>
                    </div>
                </section>

                <section v-if="users.detail.value.user.role === 'student'" class="mb-5 space-y-3 border-y border-slate-800 py-4">
                    <div>
                        <h3 class="text-sm font-bold text-white">Progress inspector</h3>
                        <p class="text-xs text-slate-500">Historical attempts remain intact when progress is reset.</p>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-6">
                        <input v-model="users.progressFilters.date_from" type="date" class="h-9 rounded-lg border-slate-700 bg-slate-900 text-xs text-white" />
                        <input v-model="users.progressFilters.date_to" type="date" class="h-9 rounded-lg border-slate-700 bg-slate-900 text-xs text-white" />
                        <input v-model="users.progressFilters.path_id" type="number" min="1" class="h-9 rounded-lg border-slate-700 bg-slate-900 text-xs text-white" placeholder="Path ID" />
                        <input v-model="users.progressFilters.level_id" type="number" min="1" class="h-9 rounded-lg border-slate-700 bg-slate-900 text-xs text-white" placeholder="Level ID" />
                        <input v-model="users.progressFilters.topic" class="h-9 rounded-lg border-slate-700 bg-slate-900 text-xs text-white" placeholder="Topic" />
                        <select v-model="users.progressFilters.difficulty" class="h-9 rounded-lg border-slate-700 bg-slate-900 text-xs text-white" @change="users.loadProgress(1)">
                            <option value="">All difficulties</option>
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                            <option value="expert">Expert</option>
                        </select>
                    </div>
                    <button type="button" class="rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-300" @click="users.loadProgress(1)">Apply progress filters</button>
                    <div class="grid gap-2 sm:grid-cols-5">
                        <article v-for="(value, key) in users.progress.value.summary" :key="key" class="rounded-lg border border-slate-800 bg-slate-900 p-3">
                            <p class="text-[10px] uppercase text-slate-500">{{ String(key).replaceAll('_', ' ') }}</p>
                            <strong class="text-lg text-white">{{ value }}</strong>
                        </article>
                    </div>
                    <div class="max-h-64 overflow-auto rounded-lg border border-slate-800">
                        <table class="min-w-full text-xs">
                            <thead class="sticky top-0 bg-slate-950 text-left text-slate-500">
                                <tr><th class="px-3 py-2">Attempt</th><th class="px-3 py-2">Level</th><th class="px-3 py-2">Score</th><th class="px-3 py-2">Hints</th><th class="px-3 py-2">Finished</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <tr v-if="users.progressLoading.value"><td colspan="5" class="px-3 py-8 text-center text-slate-500">{{ t('common.loading') }}</td></tr>
                                <tr v-else-if="users.progress.value.items.length === 0"><td colspan="5" class="px-3 py-8 text-center text-slate-500">{{ t('common.empty') }}</td></tr>
                                <tr v-for="attempt in users.progress.value.items" v-else :key="attempt.id">
                                    <td class="px-3 py-2 text-slate-400">#{{ attempt.id }}</td>
                                    <td class="px-3 py-2 font-semibold text-white">{{ attempt.level?.translations?.[0]?.title || attempt.level?.slug || attempt.level_id }}</td>
                                    <td class="px-3 py-2" :class="attempt.passed ? 'text-emerald-300' : 'text-rose-300'">{{ attempt.score }}%</td>
                                    <td class="px-3 py-2 text-slate-300">{{ attempt.hints_used }}</td>
                                    <td class="px-3 py-2 text-slate-500">{{ new Date(attempt.finished_at).toLocaleString() }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <PaginationFooter :pagination="users.progress.value.pagination" label="attempts" @page="users.loadProgress" />
                </section>

                <section class="grid gap-4 xl:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-xs font-bold uppercase text-slate-500">Weak topics</h3>
                        <div class="space-y-2">
                            <div v-for="topic in users.detail.value.weak_topics" :key="topic.topic" class="flex justify-between rounded-lg border border-slate-800 px-3 py-2 text-sm">
                                <span class="text-slate-300">{{ topic.topic }}</span><strong class="text-amber-300">{{ Number(topic.average_score).toFixed(1) }}%</strong>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="mb-2 text-xs font-bold uppercase text-slate-500">Recent attempts</h3>
                        <div class="space-y-2">
                            <div v-for="attempt in users.detail.value.recent_attempts" :key="attempt.id" class="flex justify-between rounded-lg border border-slate-800 px-3 py-2 text-sm">
                                <span class="text-slate-300">Attempt #{{ attempt.id }}</span><strong :class="attempt.passed ? 'text-emerald-300' : 'text-rose-300'">{{ attempt.score }}%</strong>
                            </div>
                        </div>
                    </div>
                </section>
            </template>
        </aside>

        <UserModal
            :show="users.showUserModal.value"
            :form="users.form"
            :countries="countries"
            :locales="locales"
            :submitting="users.submitting.value"
            :t="t"
            @close="users.showUserModal.value = false"
            @save="users.save"
        />
    </section>
</template>

<style scoped>
.action-button {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    border: 1px solid rgb(51 65 85);
    border-radius: 0.5rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: rgb(203 213 225);
}
.action-button:hover {
    background: rgb(30 41 59);
}
</style>
