<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ status?: string | null }>();
const tone = computed(() => {
    const status = props.status || 'draft';
    if (['active', 'approved', 'published', 'completed', 'success'].includes(status)) {
        return 'border-emerald-400/30 bg-emerald-400/10 text-emerald-200';
    }
    if (['failed', 'rejected', 'suspended', 'deactivated'].includes(status)) {
        return 'border-rose-400/30 bg-rose-400/10 text-rose-200';
    }
    if (['processing', 'pending', 'in_review', 'scheduled'].includes(status)) {
        return 'border-amber-400/30 bg-amber-400/10 text-amber-200';
    }

    return 'border-slate-600 bg-slate-800 text-slate-300';
});
</script>

<template>
    <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase" :class="tone">
        {{ status || 'draft' }}
    </span>
</template>
