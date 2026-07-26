<script setup lang="ts">
import { ArrowUpRight, Search } from 'lucide-vue-next';

defineProps<{
    groups: Record<string, Array<Record<string, any>>>;
    loading: boolean;
    query: string;
    t: (key: string) => string;
}>();

const emit = defineEmits<{
    select: [item: Record<string, any>];
}>();
</script>

<template>
    <div
        v-if="query.length >= 2"
        class="absolute left-0 right-0 top-[calc(100%+0.5rem)] z-50 max-h-[70vh] overflow-y-auto rounded-lg border border-slate-700 bg-slate-950 p-2 shadow-2xl shadow-black/40"
    >
        <p v-if="loading" class="px-3 py-5 text-center text-sm text-slate-400">
            {{ t('common.loading') }}
        </p>
        <template v-else>
            <section v-for="(items, group) in groups" :key="group" class="mb-2 last:mb-0">
                <p class="px-2 py-1 text-[10px] font-bold uppercase text-slate-500">
                    {{ String(group).replaceAll('_', ' ') }}
                </p>
                <button
                    v-for="item in items"
                    :key="`${group}-${item.id}`"
                    type="button"
                    class="flex w-full items-center justify-between gap-3 rounded-lg px-2 py-2 text-left hover:bg-slate-800"
                    @click="emit('select', item)"
                >
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold text-slate-100">{{ item.title }}</span>
                        <span class="block truncate text-xs text-slate-500">{{ item.subtitle }}</span>
                    </span>
                    <ArrowUpRight class="h-4 w-4 shrink-0 text-emerald-300" />
                </button>
            </section>
            <div v-if="Object.values(groups).every((items) => items.length === 0)" class="px-3 py-5 text-center">
                <Search class="mx-auto h-5 w-5 text-slate-600" />
                <p class="mt-2 text-sm text-slate-500">{{ t('common.empty') }}</p>
            </div>
        </template>
    </div>
</template>
