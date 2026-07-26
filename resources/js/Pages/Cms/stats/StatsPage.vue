<script setup lang="ts">
import {
    Activity,
    Bot,
    BookOpen,
    BrainCircuit,
    CheckCircle2,
    CircleDollarSign,
    FileText,
    Gauge,
    GraduationCap,
    LibraryBig,
    Medal,
    ShieldCheck,
    Sparkles,
    Target,
    UserPlus,
    Users,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';
import BarChart from '../shared/charts/BarChart.vue';
import KpiCard from '../shared/charts/KpiCard.vue';

const props = defineProps<{
    dashboard: Record<string, any>;
    loading: boolean;
    dateFilter: { preset: string; date_from: string; date_to: string };
    t: (key: string) => string;
}>();

const emit = defineEmits<{ reload: [] }>();

const kpis = computed(() => {
    const stats = props.dashboard.summary || {};
    return [
        ['Total users', stats.total_users ?? 0, Users, 'text-emerald-300'],
        ['Students', stats.total_students ?? 0, GraduationCap, 'text-sky-300'],
        ['Lecturers', stats.total_lecturers ?? 0, BookOpen, 'text-amber-300'],
        ['Administrators', stats.total_administrators ?? 0, ShieldCheck, 'text-violet-300'],
        ['Active today', stats.active_today ?? 0, Activity, 'text-emerald-300'],
        ['Active this week', stats.active_week ?? 0, Gauge, 'text-cyan-300'],
        ['Active this month', stats.active_month ?? 0, Target, 'text-teal-300'],
        ['New registrations', stats.new_registrations ?? 0, UserPlus, 'text-sky-300'],
        ['Exercises completed', stats.completed_exercises ?? 0, CheckCircle2, 'text-emerald-300'],
        ['Level attempts', stats.completed_level_attempts ?? 0, BrainCircuit, 'text-cyan-300'],
        ['Average correctness', `${stats.average_correctness ?? 0}%`, Target, 'text-amber-300'],
        ['XP awarded', stats.total_xp_awarded ?? 0, Medal, 'text-amber-300'],
        ['AI generations', stats.ai_generations ?? 0, Bot, 'text-violet-300'],
        ['AI failures', stats.ai_generation_failures ?? 0, XCircle, 'text-rose-300'],
        ['Exercise documents', stats.total_exercise_documents ?? 0, FileText, 'text-sky-300'],
        ['Published formulas', stats.published_formulas ?? 0, LibraryBig, 'text-emerald-300'],
        ['Draft content', stats.draft_content ?? 0, Sparkles, 'text-amber-300'],
    ] as const;
});

const charts = computed(() => {
    const data = props.dashboard.charts || {};
    return [
        ['Daily active users', data.daily_active_users],
        ['Registrations', data.registrations],
        ['Exercises completed', data.exercises_completed],
        ['Correct and incorrect', data.answer_correctness],
        ['AI requests by day', data.ai_requests],
        ['AI success and failure', data.ai_success_failure],
        ['Users by role', data.users_by_role],
        ['Users by country', data.users_by_country],
        ['Users by locale', data.users_by_locale],
        ['Most popular topics', data.popular_topics],
        ['Weakest topics', data.weakest_topics],
        ['Most-used calculators', data.calculators],
        ['Most-viewed formulas', data.formulas],
    ];
});
</script>

<template>
    <section class="space-y-5">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-white">{{ t('dashboard.title') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ t('dashboard.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap items-end gap-2">
                <label class="text-xs font-semibold text-slate-400">
                    Range
                    <select
                        v-model="dateFilter.preset"
                        class="mt-1 block h-9 rounded-lg border border-slate-700 bg-slate-950 px-3 text-sm text-slate-200"
                        @change="emit('reload')"
                    >
                        <option value="today">Today</option>
                        <option value="last_7_days">Last 7 days</option>
                        <option value="last_30_days">Last 30 days</option>
                        <option value="this_month">This month</option>
                        <option value="last_3_months">Last 3 months</option>
                        <option value="this_year">This year</option>
                        <option value="custom">Custom range</option>
                    </select>
                </label>
                <template v-if="dateFilter.preset === 'custom'">
                    <input v-model="dateFilter.date_from" type="date" class="h-9 rounded-lg border border-slate-700 bg-slate-950 text-sm text-slate-200" />
                    <input v-model="dateFilter.date_to" type="date" class="h-9 rounded-lg border border-slate-700 bg-slate-950 text-sm text-slate-200" @change="emit('reload')" />
                </template>
            </div>
        </header>

        <div v-if="loading" class="rounded-lg border border-slate-800 py-16 text-center text-slate-500">
            {{ t('common.loading') }}
        </div>
        <template v-else>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
                <KpiCard
                    v-for="[label, value, icon, tone] in kpis"
                    :key="label"
                    :label="label"
                    :value="value"
                    :icon="icon"
                    :tone="tone"
                />
            </div>

            <section class="grid gap-3 xl:grid-cols-3">
                <article class="rounded-lg border border-emerald-400/20 bg-emerald-400/5 p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-emerald-200">AI success rate</p>
                        <Bot class="h-4 w-4 text-emerald-300" />
                    </div>
                    <p class="mt-2 text-3xl font-bold text-white">{{ dashboard.ai_usage?.success_rate ?? 0 }}%</p>
                </article>
                <article class="rounded-lg border border-amber-400/20 bg-amber-400/5 p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-amber-200">AI tokens</p>
                        <Sparkles class="h-4 w-4 text-amber-300" />
                    </div>
                    <p class="mt-2 text-3xl font-bold text-white">{{ dashboard.ai_usage?.tokens ?? 0 }}</p>
                </article>
                <article class="rounded-lg border border-sky-400/20 bg-sky-400/5 p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-sky-200">Estimated AI cost</p>
                        <CircleDollarSign class="h-4 w-4 text-sky-300" />
                    </div>
                    <p class="mt-2 text-3xl font-bold text-white">${{ dashboard.ai_usage?.estimated_cost ?? 0 }}</p>
                </article>
            </section>

            <div class="grid gap-3 xl:grid-cols-2 2xl:grid-cols-3">
                <article v-for="[title, items] in charts" :key="title" class="min-h-56 rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                    <h3 class="mb-4 text-sm font-bold text-white">{{ title }}</h3>
                    <BarChart :items="items || []" compact />
                </article>
            </div>
        </template>
    </section>
</template>
