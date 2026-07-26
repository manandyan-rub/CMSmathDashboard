<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    label: string;
    value: string | number;
    icon: Component;
    tone?: string;
}>();
</script>

<template>
    <article class="rounded-lg border border-slate-800 bg-slate-900/65 p-3">
        <div class="flex items-center justify-between gap-2">
            <p class="text-[11px] font-semibold text-slate-500">{{ label }}</p>
            <component :is="icon" class="h-4 w-4" :class="tone || 'text-emerald-300'" />
        </div>
        <p class="mt-2 text-2xl font-bold text-white">{{ value }}</p>
    </article>
</template>
