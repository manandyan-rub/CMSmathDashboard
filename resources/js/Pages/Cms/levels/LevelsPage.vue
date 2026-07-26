<script setup>
import { RefreshCcw, SquarePen } from '@lucide/vue';

defineProps({
    levelsState: {
        type: Object,
        required: true,
    },
    levelsLoading: {
        type: Boolean,
        required: true,
    },
    canManage: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits(['reload', 'edit']);
</script>

<template>
    <section class="space-y-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-white">Math Levels Control</h2>
                    <p class="text-sm text-slate-400">Manage level pass score, XP, active status, and AI prompts.</p>
                </div>
                <button
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800"
                    type="button"
                    @click="emit('reload')"
                >
                    <RefreshCcw class="h-4 w-4" />
                    Reload levels
                </button>
            </div>
        </div>

        <div v-if="levelsLoading" class="rounded-xl border border-slate-800 bg-slate-900/75 px-4 py-3 text-sm text-slate-400">
            Loading levels...
        </div>

        <div v-else class="space-y-4">
            <article
                v-for="pathItem in levelsState.items"
                :key="pathItem.id"
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/75"
            >
                <header class="border-b border-slate-800 px-4 py-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-lg font-bold text-white">{{ pathItem.title }}</h3>
                            <p class="text-sm text-slate-400">{{ pathItem.description || 'No description' }}</p>
                        </div>
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase"
                            :class="pathItem.active ? 'bg-indigo-500/20 text-indigo-200' : 'bg-slate-700 text-slate-300'"
                        >
                            {{ pathItem.active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </header>

                <div class="grid gap-3 p-3 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="level in pathItem.levels"
                        :key="level.id"
                        class="rounded-xl border border-slate-800 bg-slate-950/70 p-3"
                    >
                        <div class="mb-2 flex items-start justify-between gap-2">
                            <div>
                                <h4 class="font-semibold text-white">{{ level.title }}</h4>
                                <p class="text-xs text-slate-400">
                                    {{ level.topic || '-' }} • {{ level.difficulty }}
                                </p>
                            </div>
                            <span
                                class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="level.active ? 'bg-indigo-500/20 text-indigo-200' : 'bg-slate-700 text-slate-300'"
                            >
                                {{ level.active ? 'Live' : 'Off' }}
                            </span>
                        </div>

                        <div class="space-y-1 text-xs text-slate-300">
                            <p>Pass: <span class="font-semibold text-white">{{ level.passing_score }}%</span></p>
                            <p>XP: <span class="font-semibold text-white">{{ level.xp_reward }}</span></p>
                            <p>Attempts: <span class="font-semibold text-white">{{ level.attempts_count }}</span></p>
                            <p>Questions: <span class="font-semibold text-white">{{ level?.prompt?.question_count ?? 0 }}</span></p>
                        </div>

                        <button
                            v-if="canManage"
                            type="button"
                            class="mt-3 inline-flex items-center gap-1 rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-800"
                            @click="emit('edit', pathItem, level)"
                        >
                            <SquarePen class="h-3.5 w-3.5" />
                            Edit config
                        </button>
                    </article>
                </div>
            </article>
        </div>
    </section>
</template>
