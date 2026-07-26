<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    items?: Array<{ label: string; value: number; secondary?: number }>;
    compact?: boolean;
    secondaryLabel?: string;
}>();

const normalized = computed(() => (props.items || []).slice(props.compact ? -14 : -31));
const maximum = computed(() => Math.max(1, ...normalized.value.map((item) => Number(item.value) || 0)));
</script>

<template>
    <div v-if="normalized.length" class="space-y-2">
        <div v-for="item in normalized" :key="item.label" class="grid grid-cols-[minmax(5rem,8rem)_1fr_auto] items-center gap-2">
            <span class="truncate text-[11px] text-slate-500" :title="item.label">{{ item.label }}</span>
            <div class="h-2 overflow-hidden rounded-full bg-slate-800">
                <div class="h-full rounded-full bg-emerald-400" :style="{ width: `${Math.max(2, (Number(item.value) / maximum) * 100)}%` }"></div>
            </div>
            <span class="min-w-8 text-right text-xs font-bold text-slate-200">{{ item.value }}</span>
        </div>
    </div>
    <p v-else class="py-8 text-center text-sm text-slate-600">No activity in this period.</p>
</template>
