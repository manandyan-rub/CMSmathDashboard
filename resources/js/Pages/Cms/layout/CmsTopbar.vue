<script setup>
import { Plus, RefreshCcw, Search } from '@lucide/vue';

defineProps({
    selectedSectionLabel: {
        type: String,
        required: true,
    },
    newActionLabel: {
        type: String,
        required: true,
    },
    locale: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['update:locale', 'refresh', 'newAction']);
</script>

<template>
    <header class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-800 bg-slate-900/75 px-4 py-3 sm:px-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-300">CMS workspace</p>
            <h1 class="text-xl font-extrabold tracking-tight text-white sm:text-2xl">{{ selectedSectionLabel }}</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <label class="relative">
                <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                <input
                    type="text"
                    class="rounded-lg border border-slate-700 bg-slate-900 py-2 pl-9 pr-3 text-sm text-white placeholder:text-slate-500 focus:border-indigo-400 focus:outline-none"
                    placeholder="Search lessons, cohorts, owners"
                />
            </label>
            <select
                :value="locale"
                class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm font-medium text-slate-200"
                @change="emit('update:locale', $event.target.value)"
            >
                <option value="en">English</option>
                <option value="hy">Armenian</option>
                <option value="ru">Russian</option>
                <option value="fr">French</option>
            </select>
            <button
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-300 hover:bg-slate-800"
                type="button"
                @click="emit('refresh')"
            >
                <RefreshCcw class="h-4 w-4" />
                Refresh
            </button>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-400"
                @click="emit('newAction')"
            >
                <Plus class="h-4 w-4" />
                {{ newActionLabel }}
            </button>
        </div>
    </header>
</template>
