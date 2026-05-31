<script setup>
import AppearancePanel from './partials/AppearancePanel.vue';
import PasswordSettingsForm from './partials/PasswordSettingsForm.vue';
import ProfileSettingsForm from './partials/ProfileSettingsForm.vue';

defineProps({
    currentUser: {
        type: Object,
        required: true,
    },
    profileForm: {
        type: Object,
        required: true,
    },
    profileErrors: {
        type: Object,
        required: true,
    },
    profileSubmitting: {
        type: Boolean,
        required: true,
    },
    passwordForm: {
        type: Object,
        required: true,
    },
    passwordErrors: {
        type: Object,
        required: true,
    },
    passwordSubmitting: {
        type: Boolean,
        required: true,
    },
    theme: {
        type: String,
        required: true,
    },
    formatDate: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(['saveProfile', 'savePassword', 'setTheme']);
</script>

<template>
    <section class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
        <div class="space-y-4">
            <ProfileSettingsForm
                :current-user="currentUser"
                :form="profileForm"
                :errors="profileErrors"
                :submitting="profileSubmitting"
                :format-date="formatDate"
                @save="emit('saveProfile')"
            />
            <PasswordSettingsForm
                :form="passwordForm"
                :errors="passwordErrors"
                :submitting="passwordSubmitting"
                @save="emit('savePassword')"
            />
        </div>

        <div class="space-y-4">
            <AppearancePanel :theme="theme" @set-theme="emit('setTheme', $event)" />

            <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-300">Workspace</p>
                <h2 class="mt-1 text-lg font-bold text-white">Admin session</h2>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                        <span class="text-slate-400">Signed in as</span>
                        <span class="font-semibold text-white">{{ currentUser.email }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                        <span class="text-slate-400">Access level</span>
                        <span class="font-semibold capitalize text-white">{{ currentUser.role || 'admin' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                        <span class="text-slate-400">Last profile update</span>
                        <span class="font-semibold text-white">{{ formatDate(currentUser.updated_at) }}</span>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>
