<script setup>
import { Save, UserRound } from '@lucide/vue';

defineProps({
    currentUser: {
        type: Object,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        required: true,
    },
    submitting: {
        type: Boolean,
        required: true,
    },
    formatDate: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(['save']);
</script>

<template>
    <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-300">Profile</p>
                <h2 class="mt-1 text-lg font-bold text-white">Account information</h2>
                <p class="mt-1 text-sm text-slate-400">Update your CMS identity and login email.</p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/15 text-indigo-200">
                <UserRound class="h-5 w-5" />
            </span>
        </div>

        <form class="mt-5 space-y-4" @submit.prevent="emit('save')">
            <label class="block space-y-1.5">
                <span class="text-xs font-semibold uppercase text-slate-400">Name</span>
                <input
                    v-model="form.name"
                    type="text"
                    autocomplete="name"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                />
                <span v-if="errors.name" class="block text-xs font-semibold text-rose-300">{{ errors.name }}</span>
            </label>

            <label class="block space-y-1.5">
                <span class="text-xs font-semibold uppercase text-slate-400">Email</span>
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                />
                <span v-if="errors.email" class="block text-xs font-semibold text-rose-300">{{ errors.email }}</span>
            </label>

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                    <p class="text-[11px] font-semibold uppercase text-slate-400">Role</p>
                    <p class="mt-1 text-sm font-bold capitalize text-white">{{ currentUser.role || 'admin' }}</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                    <p class="text-[11px] font-semibold uppercase text-slate-400">Joined</p>
                    <p class="mt-1 text-sm font-bold text-white">{{ formatDate(currentUser.created_at) }}</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                    <p class="text-[11px] font-semibold uppercase text-slate-400">Email status</p>
                    <p class="mt-1 text-sm font-bold text-white">
                        {{ currentUser.email_verified_at ? 'Verified' : 'Unverified' }}
                    </p>
                </div>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-400 disabled:opacity-50"
                    :disabled="submitting"
                >
                    <Save class="h-4 w-4" />
                    {{ submitting ? 'Saving...' : 'Save profile' }}
                </button>
            </div>
        </form>
    </article>
</template>
