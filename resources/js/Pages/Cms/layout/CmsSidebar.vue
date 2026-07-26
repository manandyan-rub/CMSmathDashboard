<script setup lang="ts">
import { LogOut, Menu, Sigma, Sparkles, X } from 'lucide-vue-next';
import { ref } from 'vue';
import type { CmsUser, NavigationGroup } from '../shared/cmsTypes';

defineProps<{
    groups: NavigationGroup[];
    activeSection: string;
    currentUser: CmsUser | null;
    aiUsage: Record<string, number>;
    t: (key: string, replacements?: Record<string, string | number>) => string;
}>();

const emit = defineEmits<{
    'update:activeSection': [value: string];
    logout: [];
}>();
const mobileOpen = ref(false);

function selectSection(key: string) {
    emit('update:activeSection', key);
    mobileOpen.value = false;
}
</script>

<template>
    <button
        type="button"
        class="fixed left-3 top-3 z-50 grid h-10 w-10 place-items-center rounded-lg border border-slate-700 bg-slate-950 text-slate-100 lg:hidden"
        aria-label="Open navigation"
        @click="mobileOpen = true"
    >
        <Menu class="h-5 w-5" />
    </button>

    <div
        v-if="mobileOpen"
        class="fixed inset-0 z-40 bg-slate-950/70 lg:hidden"
        @click.self="mobileOpen = false"
    ></div>

    <aside
        class="cms-sidebar fixed inset-y-0 left-0 z-50 flex w-[min(19rem,88vw)] -translate-x-full flex-col border-r border-slate-800 bg-[#071420] p-4 transition-transform lg:sticky lg:top-0 lg:h-screen lg:w-72 lg:translate-x-0"
        :class="{ 'translate-x-0': mobileOpen }"
    >
        <div class="mb-5 flex items-center gap-3 border-b border-slate-800 pb-4">
            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-amber-400 text-slate-950">
                <Sigma class="h-5 w-5" />
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-bold text-white">{{ t('app.name') }}</p>
                <p class="truncate text-[11px] text-emerald-300">{{ t('app.subtitle') }}</p>
            </div>
            <button
                type="button"
                class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden"
                aria-label="Close navigation"
                @click="mobileOpen = false"
            >
                <X class="h-4 w-4" />
            </button>
        </div>

        <nav class="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
            <section v-for="group in groups" :key="group.key">
                <p class="mb-1.5 px-2 text-[10px] font-bold uppercase text-slate-500">
                    {{ t(group.labelKey) }}
                </p>
                <div class="space-y-1">
                    <button
                        v-for="item in group.items"
                        :key="item.key"
                        type="button"
                        class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm transition"
                        :class="
                            activeSection === item.key
                                ? 'bg-emerald-400/15 font-semibold text-emerald-200 ring-1 ring-emerald-400/30'
                                : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'
                        "
                        @click="selectSection(item.key)"
                    >
                        <component :is="item.icon" class="h-4 w-4 shrink-0" />
                        <span class="truncate">{{ t(item.labelKey) }}</span>
                    </button>
                </div>
            </section>
        </nav>

        <div class="mt-4 rounded-lg border border-slate-700 bg-slate-900/70 p-3">
            <div class="flex items-center justify-between gap-2">
                <span class="flex items-center gap-2 text-xs font-semibold text-slate-200">
                    <Sparkles class="h-4 w-4 text-amber-300" />
                    {{ t('nav.usage') }}
                </span>
                <strong class="text-sm text-emerald-300">{{ aiUsage.success_rate ?? 0 }}%</strong>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                <div>
                    <p class="text-slate-500">{{ t('nav.generationLogs') }}</p>
                    <p class="font-bold text-white">{{ aiUsage.generations ?? 0 }}</p>
                </div>
                <div>
                    <p class="text-slate-500">{{ t('nav.failures') }}</p>
                    <p class="font-bold text-rose-300">{{ aiUsage.failed ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="mt-3 border-t border-slate-800 pt-3">
            <div class="mb-2 flex items-center gap-2">
                <div class="grid h-8 w-8 place-items-center rounded-lg bg-teal-400/15 text-sm font-bold text-teal-200">
                    {{ (currentUser?.name || 'M').charAt(0).toUpperCase() }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-white">{{ currentUser?.name || 'Math admin' }}</p>
                    <p class="truncate text-xs capitalize text-slate-500">{{ currentUser?.role || 'admin' }}</p>
                </div>
            </div>
            <button
                type="button"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-800"
                @click="emit('logout')"
            >
                <LogOut class="h-4 w-4" />
                {{ t('common.logout') }}
            </button>
        </div>
    </aside>
</template>
