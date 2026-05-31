<script setup>
import { Sparkles } from '@lucide/vue';

defineProps({
    selectedSectionLabel: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <section class="rounded-2xl border border-slate-800 bg-slate-900/75 p-6">
        <div class="max-w-xl">
            <p class="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-300">
                <Sparkles class="h-3.5 w-3.5" />
                Coming soon
            </p>
            <h2 class="mt-3 text-2xl font-black text-white">{{ selectedSectionLabel }}</h2>
            <p class="mt-2 text-sm text-slate-400">
                This module is reserved for the next CMS sprint. Core management for users and formulas is live.
            </p>
        </div>
    </section>
</template>
