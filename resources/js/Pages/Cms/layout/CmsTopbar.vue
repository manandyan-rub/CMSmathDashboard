<script setup lang="ts">
import { Moon, RefreshCcw, Search, Sun } from 'lucide-vue-next';
import GlobalSearchPanel from '../search/GlobalSearchPanel.vue';

defineProps<{
    groupLabel: string;
    sectionLabel: string;
    locale: string;
    locales: string[];
    theme: string;
    searchQuery: string;
    searchGroups: Record<string, Array<Record<string, any>>>;
    searchLoading: boolean;
    t: (key: string) => string;
}>();

const emit = defineEmits<{
    'update:locale': [value: string];
    'update:searchQuery': [value: string];
    refresh: [];
    toggleTheme: [];
    selectSearch: [item: Record<string, any>];
}>();
</script>

<template>
    <header class="mb-5 flex flex-col gap-3 border-b border-slate-800 pb-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="pl-12 lg:pl-0">
            <p class="text-xs font-semibold text-emerald-300">{{ groupLabel }}</p>
            <h1 class="text-xl font-bold text-white">{{ sectionLabel }}</h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative min-w-[16rem] flex-1 sm:min-w-[22rem]">
                <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-500" />
                <input
                    :value="searchQuery"
                    type="search"
                    class="h-10 w-full rounded-lg border border-slate-700 bg-slate-950 pl-9 pr-3 text-sm text-white placeholder:text-slate-600 focus:border-emerald-400 focus:ring-0"
                    :placeholder="t('topbar.search')"
                    @input="emit('update:searchQuery', ($event.target as HTMLInputElement).value)"
                />
                <GlobalSearchPanel
                    :groups="searchGroups"
                    :loading="searchLoading"
                    :query="searchQuery"
                    :t="t"
                    @select="emit('selectSearch', $event)"
                />
            </div>

            <select
                :value="locale"
                class="h-10 rounded-lg border border-slate-700 bg-slate-950 px-3 text-sm font-medium text-slate-200 focus:border-emerald-400 focus:ring-0"
                @change="emit('update:locale', ($event.target as HTMLSelectElement).value)"
            >
                <option v-for="item in locales" :key="item" :value="item">{{ item.toUpperCase() }}</option>
            </select>

            <button
                type="button"
                class="grid h-10 w-10 place-items-center rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800"
                :title="t('common.refresh')"
                @click="emit('refresh')"
            >
                <RefreshCcw class="h-4 w-4" />
            </button>
            <button
                type="button"
                class="grid h-10 w-10 place-items-center rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800"
                :title="theme === 'light' ? t('common.dark') : t('common.light')"
                @click="emit('toggleTheme')"
            >
                <Moon v-if="theme === 'light'" class="h-4 w-4" />
                <Sun v-else class="h-4 w-4" />
            </button>
        </div>
    </header>
</template>
