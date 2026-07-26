<script setup lang="ts">
import { Medal, Trophy } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

const props = defineProps<{ refreshToken: number; t: (key: string) => string; showError: (error: any, fallback?: string) => void }>();
const items = ref<Array<Record<string, any>>>([]);
const loading = ref(false);
async function load() {
    loading.value = true;
    try {
        const { data } = await window.axios.get('/cms-api/gamification/hall-of-fame');
        items.value = data.items || [];
    } catch (error) {
        props.showError(error, 'Hall of Fame could not be loaded.');
    } finally {
        loading.value = false;
    }
}
watch(() => props.refreshToken, load);
onMounted(load);
</script>

<template>
    <section class="space-y-4">
        <header><h2 class="flex items-center gap-2 text-lg font-bold text-white"><Trophy class="h-5 w-5 text-amber-300" /> Hall of Fame</h2><p class="text-sm text-slate-500">Current ranking from real XP profiles.</p></header>
        <div v-if="loading" class="py-20 text-center text-slate-500">{{ t('common.loading') }}</div>
        <div v-else class="overflow-hidden rounded-lg border border-slate-800">
            <table class="min-w-full text-sm"><thead class="bg-slate-950/60 text-left text-xs text-slate-500"><tr><th class="px-3 py-3">Rank</th><th class="px-3 py-3">Learner</th><th class="px-3 py-3">XP</th><th class="px-3 py-3">Level</th><th class="px-3 py-3">Streak</th><th class="px-3 py-3">Stars</th></tr></thead>
                <tbody class="divide-y divide-slate-800"><tr v-if="items.length === 0"><td colspan="6" class="px-4 py-10 text-center text-slate-500">{{ t('common.empty') }}</td></tr><tr v-for="(item, index) in items" :key="item.id"><td class="px-3 py-3"><span class="inline-flex items-center gap-1 font-bold" :class="index < 3 ? 'text-amber-300' : 'text-slate-500'"><Medal v-if="index < 3" class="h-4 w-4" />{{ index + 1 }}</span></td><td class="px-3 py-3 font-semibold text-white">{{ item.name }}</td><td class="px-3 py-3 font-bold text-emerald-300">{{ item.total_xp }}</td><td class="px-3 py-3 text-slate-300">{{ item.current_rank }}</td><td class="px-3 py-3 text-slate-300">{{ item.streak_days }}</td><td class="px-3 py-3 text-slate-300">{{ item.total_stars }}</td></tr></tbody>
            </table>
        </div>
    </section>
</template>
