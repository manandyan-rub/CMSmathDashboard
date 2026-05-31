<script setup>
import { MonitorCog, Moon, Sun } from '@lucide/vue';

defineProps({
    theme: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['setTheme']);
</script>

<template>
    <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-300">Appearance</p>
                <h2 class="mt-1 text-lg font-bold text-white">CMS theme</h2>
                <p class="mt-1 text-sm text-slate-400">Switch the control panel between dark and light mode.</p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/15 text-indigo-200">
                <MonitorCog class="h-5 w-5" />
            </span>
        </div>

        <div class="mt-5 grid gap-2 rounded-xl border border-slate-800 bg-slate-950/60 p-1 sm:grid-cols-2">
            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition"
                :class="theme === 'dark' ? 'bg-indigo-500 text-white' : 'text-slate-300 hover:bg-slate-800'"
                @click="emit('setTheme', 'dark')"
            >
                <Moon class="h-4 w-4" />
                Dark
            </button>
            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition"
                :class="theme === 'light' ? 'bg-indigo-500 text-white' : 'text-slate-300 hover:bg-slate-800'"
                @click="emit('setTheme', 'light')"
            >
                <Sun class="h-4 w-4" />
                Light
            </button>
        </div>
    </article>
</template>
