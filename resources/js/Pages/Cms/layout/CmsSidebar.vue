<script setup>
import { LogOut, Sigma, Sparkles } from '@lucide/vue';

defineProps({
    sections: {
        type: Array,
        required: true,
    },
    activeSection: {
        type: String,
        required: true,
    },
    aiUsage: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['update:activeSection', 'logout']);
</script>

<template>
    <aside class="hidden w-72 shrink-0 border-r border-indigo-500/20 bg-gradient-to-b from-slate-950 via-indigo-950 to-slate-950 p-5 lg:flex lg:flex-col">
        <div class="mb-8 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 text-white shadow-lg shadow-indigo-500/30">
                <Sigma class="h-5 w-5" />
            </div>
            <div>
                <p class="text-lg font-extrabold tracking-tight text-white">MathDashboard CMS</p>
                <p class="text-xs text-indigo-200/75">Content command</p>
            </div>
        </div>

        <nav class="space-y-1.5">
            <button
                v-for="section in sections"
                :key="section.key"
                class="flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition"
                :class="
                    activeSection === section.key
                        ? 'bg-indigo-500/20 text-indigo-100 ring-1 ring-indigo-400/40'
                        : 'text-slate-300 hover:bg-indigo-500/10 hover:text-white'
                "
                type="button"
                @click="emit('update:activeSection', section.key)"
            >
                <component :is="section.icon" class="h-4 w-4" />
                {{ section.label }}
            </button>
        </nav>

        <div class="mt-6 rounded-2xl border border-indigo-400/25 bg-indigo-500/10 p-4">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-400/20 text-violet-200">
                        <Sparkles class="h-4 w-4" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-white">AI usage</p>
                        <p class="text-xs text-indigo-200/75">This month</p>
                    </div>
                </div>
                <span class="text-xs font-bold text-indigo-100">{{ aiUsage.percent }}%</span>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="rounded-xl border border-slate-700/70 bg-slate-950/35 p-2">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Generations</p>
                    <p class="mt-1 text-lg font-extrabold text-white">{{ aiUsage.generations }}</p>
                </div>
                <div class="rounded-xl border border-slate-700/70 bg-slate-950/35 p-2">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Credits</p>
                    <p class="mt-1 text-lg font-extrabold text-white">{{ aiUsage.creditsUsed }}</p>
                </div>
            </div>

            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-900/80">
                <div
                    class="h-full rounded-full bg-gradient-to-r from-indigo-400 via-violet-400 to-sky-400"
                    :style="{ width: `${aiUsage.percent}%` }"
                ></div>
            </div>

            <div class="mt-3 space-y-1.5">
                <div v-for="item in aiUsage.items" :key="item.label" class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">{{ item.label }}</span>
                    <span class="font-semibold text-indigo-100">{{ item.value }}</span>
                </div>
            </div>

            <p class="mt-3 text-xs text-slate-400">
                {{ aiUsage.creditsUsed }} of {{ aiUsage.creditLimit }} AI credits used.
            </p>
        </div>

        <div class="mt-auto rounded-2xl border border-slate-800 bg-slate-900/80 p-3">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-400/20 font-bold text-indigo-200">M</div>
                <div>
                    <p class="text-sm font-semibold text-white">Math admin</p>
                    <p class="text-xs text-slate-400">Owner workspace</p>
                </div>
            </div>
            <button
                class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-300 transition hover:border-slate-500 hover:bg-slate-800"
                type="button"
                @click="emit('logout')"
            >
                <LogOut class="h-4 w-4" />
                Logout
            </button>
        </div>
    </aside>
</template>
