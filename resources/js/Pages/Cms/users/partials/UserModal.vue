<script setup lang="ts">
import { Save, X } from 'lucide-vue-next';

defineProps<{
    show: boolean;
    form: Record<string, any>;
    countries: Array<{ id: number; name: string; iso_code: string }>;
    locales: string[];
    submitting: boolean;
    t: (key: string) => string;
}>();

const emit = defineEmits<{ close: []; save: [] }>();
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[70] grid place-items-center bg-slate-950/75 p-4">
        <section class="w-full max-w-2xl rounded-lg border border-slate-700 bg-slate-900 shadow-2xl">
            <header class="flex items-center justify-between border-b border-slate-700 px-5 py-4">
                <h2 class="font-bold text-white">{{ form.id ? t('common.edit') : t('common.create') }}</h2>
                <button type="button" class="text-slate-400 hover:text-white" @click="emit('close')"><X class="h-5 w-5" /></button>
            </header>
            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <label class="text-xs font-semibold text-slate-300">
                    Name
                    <input v-model="form.name" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white" />
                </label>
                <label class="text-xs font-semibold text-slate-300">
                    Email
                    <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white" />
                </label>
                <label class="text-xs font-semibold text-slate-300">
                    Role
                    <select v-model="form.role" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white">
                        <option value="student">Student</option>
                        <option value="lecturer">Lecturer</option>
                        <option value="admin">Administrator</option>
                    </select>
                </label>
                <label class="text-xs font-semibold text-slate-300">
                    Status
                    <select v-model="form.status" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="deactivated">Deactivated</option>
                    </select>
                </label>
                <label class="text-xs font-semibold text-slate-300">
                    Country
                    <select v-model="form.country_id" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white">
                        <option :value="null">Unassigned</option>
                        <option v-for="country in countries" :key="country.id" :value="country.id">{{ country.name }}</option>
                    </select>
                </label>
                <label class="text-xs font-semibold text-slate-300">
                    Locale
                    <select v-model="form.locale" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white">
                        <option v-for="locale in locales" :key="locale" :value="locale">{{ locale.toUpperCase() }}</option>
                    </select>
                </label>
                <label v-if="!form.id" class="text-xs font-semibold text-slate-300 sm:col-span-2">
                    Temporary password
                    <input v-model="form.password" type="password" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white" />
                </label>
            </div>
            <footer class="flex justify-end gap-2 border-t border-slate-700 px-5 py-4">
                <button type="button" class="rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-300" @click="emit('close')">{{ t('common.cancel') }}</button>
                <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-3 py-2 text-sm font-bold text-slate-950" :disabled="submitting" @click="emit('save')">
                    <Save class="h-4 w-4" /> {{ t('common.save') }}
                </button>
            </footer>
        </section>
    </div>
</template>
