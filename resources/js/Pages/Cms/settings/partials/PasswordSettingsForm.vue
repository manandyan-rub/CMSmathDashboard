<script setup>
import { KeyRound, Save } from '@lucide/vue';

defineProps({
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
});

const emit = defineEmits(['save']);
</script>

<template>
    <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-300">Security</p>
                <h2 class="mt-1 text-lg font-bold text-white">Change password</h2>
                <p class="mt-1 text-sm text-slate-400">Use a strong password for the admin workspace.</p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-200">
                <KeyRound class="h-5 w-5" />
            </span>
        </div>

        <form class="mt-5 space-y-4" @submit.prevent="emit('save')">
            <label class="block space-y-1.5">
                <span class="text-xs font-semibold uppercase text-slate-400">Current password</span>
                <input
                    v-model="form.current_password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                />
                <span v-if="errors.current_password" class="block text-xs font-semibold text-rose-300">
                    {{ errors.current_password }}
                </span>
            </label>

            <label class="block space-y-1.5">
                <span class="text-xs font-semibold uppercase text-slate-400">New password</span>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                />
                <span v-if="errors.password" class="block text-xs font-semibold text-rose-300">{{ errors.password }}</span>
            </label>

            <label class="block space-y-1.5">
                <span class="text-xs font-semibold uppercase text-slate-400">Confirm password</span>
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                />
                <span v-if="errors.password_confirmation" class="block text-xs font-semibold text-rose-300">
                    {{ errors.password_confirmation }}
                </span>
            </label>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-400 disabled:opacity-50"
                    :disabled="submitting"
                >
                    <Save class="h-4 w-4" />
                    {{ submitting ? 'Saving...' : 'Update password' }}
                </button>
            </div>
        </form>
    </article>
</template>
