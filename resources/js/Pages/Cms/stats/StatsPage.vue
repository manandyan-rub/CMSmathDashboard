<script setup>
import { BookOpen, ChevronRight, UsersRound } from '@lucide/vue';

defineProps({
    overview: {
        type: Object,
        required: true,
    },
    topKpis: {
        type: Array,
        required: true,
    },
    renderFormulaMarkup: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(['openFormulas']);
</script>

<template>
    <section class="space-y-5">
        <div class="grid gap-4 xl:grid-cols-[2fr_1fr]">
            <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-300">Math content operations</p>
                <h2 class="mt-2 max-w-3xl text-3xl font-black leading-tight text-white">
                    Plan, review, and publish learning paths behind MathDashboard.
                </h2>
                <p class="mt-3 max-w-3xl text-sm text-slate-400">
                    Manage users, formula libraries, and learning tracks from one editorial command center.
                </p>
                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="card in topKpis"
                        :key="card.title"
                        class="rounded-xl border bg-gradient-to-br p-4"
                        :class="card.tone"
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase text-slate-300">{{ card.title }}</p>
                            <component :is="card.icon" class="h-4 w-4 text-slate-200" />
                        </div>
                        <p class="text-3xl font-black text-white">{{ card.value }}</p>
                        <p class="mt-1 text-xs text-slate-300">{{ card.hint }}</p>
                    </article>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-300">Content footprint</p>
                <h3 class="mt-2 text-lg font-bold text-white">Live inventory</h3>
                <p class="mt-1 text-sm text-slate-400">Current totals pulled from the CMS database.</p>

                <div class="mt-4 space-y-3">
                    <div class="rounded-lg border border-slate-700 bg-slate-900 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold uppercase text-slate-400">Formulas</p>
                            <p class="text-lg font-black text-white">{{ overview.stats.formulas }}</p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-700 bg-slate-900 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold uppercase text-slate-400">Learning paths</p>
                            <p class="text-lg font-black text-white">{{ overview.stats.paths }}</p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-700 bg-slate-900 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold uppercase text-slate-400">Math levels</p>
                            <p class="text-lg font-black text-white">{{ overview.stats.levels }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-slate-700 bg-slate-900 p-3">
                    <div class="flex items-center justify-between gap-3 text-xs text-slate-400">
                        <span>Formula coverage</span>
                        <span class="font-semibold text-indigo-300">
                            {{ overview.stats.levels > 0 ? (overview.stats.formulas / overview.stats.levels).toFixed(1) : '0.0' }}
                            per level
                        </span>
                    </div>
                </div>
            </article>
        </div>

        <div class="grid gap-4 xl:grid-cols-[2fr_1fr]">
            <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-indigo-300">Recent users</p>
                        <h3 class="text-lg font-bold text-white">Learner and staff activity</h3>
                    </div>
                    <UsersRound class="h-5 w-5 text-indigo-300" />
                </div>
                <div class="space-y-2">
                    <div
                        v-for="item in overview.recent_users"
                        :key="item.id"
                        class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2"
                    >
                        <div>
                            <p class="text-sm font-semibold text-white">{{ item.name }}</p>
                            <p class="text-xs text-slate-400">{{ item.email }}</p>
                        </div>
                        <span class="rounded-full bg-slate-800 px-2 py-1 text-xs uppercase text-slate-300">{{ item.role }}</span>
                    </div>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-indigo-300">Recent formulas</p>
                        <h3 class="text-lg font-bold text-white">Curriculum updates</h3>
                    </div>
                    <BookOpen class="h-5 w-5 text-indigo-300" />
                </div>
                <div class="space-y-2">
                    <div
                        v-for="item in overview.recent_formulas"
                        :key="item.id"
                        class="rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2"
                    >
                        <p class="text-sm font-semibold text-white">{{ item.name || item.category }}</p>
                        <div
                            class="mt-1 overflow-x-auto rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2 text-sm text-slate-100"
                            v-html="renderFormulaMarkup(item.formula)"
                        ></div>
                        <button
                            type="button"
                            class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-indigo-300 hover:text-indigo-200"
                            @click="emit('openFormulas')"
                        >
                            Open in curriculum
                            <ChevronRight class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>
