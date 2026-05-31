<script setup>
import { Plus, Search, SquarePen, Trash2 } from '@lucide/vue';
import PaginationFooter from '../shared/PaginationFooter.vue';

defineProps({
    usersState: {
        type: Object,
        required: true,
    },
    usersLoading: {
        type: Boolean,
        required: true,
    },
    search: {
        type: String,
        required: true,
    },
    formatDate: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(['update:search', 'search', 'create', 'edit', 'delete', 'page']);
</script>

<template>
    <section class="space-y-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-white">Users Management</h2>
                    <p class="text-sm text-slate-400">Create, edit, and remove platform members.</p>
                </div>
                <button
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-500 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-400"
                    type="button"
                    @click="emit('create')"
                >
                    <Plus class="h-4 w-4" />
                    Add user
                </button>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <label class="relative min-w-[220px] flex-1">
                    <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-500" />
                    <input
                        :value="search"
                        type="search"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 py-2 pl-9 pr-3 text-sm text-white placeholder:text-slate-500"
                        placeholder="Search users by name/email/role"
                        @input="emit('update:search', $event.target.value)"
                        @keyup.enter="emit('search')"
                    />
                </label>
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
                            <th class="px-4 py-3 text-left font-semibold text-slate-400">Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400">Email</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400">Role</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-400">Created</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="usersLoading">
                            <td class="px-4 py-4 text-slate-500" colspan="5">Loading users...</td>
                        </tr>
                        <tr v-else-if="usersState.items.length === 0">
                            <td class="px-4 py-4 text-slate-500" colspan="5">No users found.</td>
                        </tr>
                        <tr v-for="user in usersState.items" v-else :key="user.id" class="border-t border-slate-800">
                            <td class="px-4 py-3 font-medium text-white">{{ user.name }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-slate-800 px-2.5 py-1 text-xs font-semibold uppercase text-slate-300">
                                    {{ user.role }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-400">{{ formatDate(user.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        class="rounded-lg border border-slate-700 px-2 py-1 text-xs font-semibold text-slate-200 hover:bg-slate-800"
                                        type="button"
                                        @click="emit('edit', user)"
                                    >
                                        <SquarePen class="h-3.5 w-3.5" />
                                    </button>
                                    <button
                                        class="rounded-lg border border-rose-400/40 px-2 py-1 text-xs font-semibold text-rose-300 hover:bg-rose-500/10"
                                        type="button"
                                        @click="emit('delete', user)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationFooter :pagination="usersState.pagination" label="users" @page="emit('page', $event)" />
        </div>
    </section>
</template>
