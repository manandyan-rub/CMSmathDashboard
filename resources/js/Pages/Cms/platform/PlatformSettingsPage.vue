<script setup lang="ts">
import { Save, Settings2 } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    refreshToken: number;
    canManage: boolean;
    t: (key: string) => string;
    showError: (error: any, fallback?: string) => void;
    showSuccess: (message: string) => void;
}>();

const loading = ref(false);
const items = ref<Array<Record<string, any>>>([]);
const reasons = ref<Record<number, string>>({});
const values = ref<Record<number, any>>({});
const grouped = computed(() => items.value.reduce<Record<string, Array<Record<string, any>>>>(
    (groups, item) => {
        const group = String(item.group || 'general');
        (groups[group] ||= []).push(item);

        return groups;
    },
    {},
));

async function load() {
    loading.value = true;
    try {
        const { data } = await window.axios.get('/cms-api/platform-settings');
        items.value = data.items || [];
        values.value = Object.fromEntries(items.value.map((item) => [item.id, item.value ?? item.default_value]));
    } catch (error) {
        props.showError(error, 'Platform settings could not be loaded.');
    } finally {
        loading.value = false;
    }
}

async function save(setting: Record<string, any>) {
    try {
        await window.axios.patch(`/cms-api/platform-settings/${setting.id}`, {
            value: values.value[setting.id],
            reason: reasons.value[setting.id] || 'Platform setting updated in CMS',
        });
        props.showSuccess(`${setting.label} updated.`);
        reasons.value[setting.id] = '';
        await load();
    } catch (error) {
        props.showError(error, 'Setting could not be saved.');
    }
}

watch(() => props.refreshToken, load);
onMounted(load);
</script>

<template>
    <section class="space-y-4">
        <header>
            <h2 class="flex items-center gap-2 text-lg font-bold text-white"><Settings2 class="h-5 w-5 text-emerald-300" /> Platform settings</h2>
            <p class="text-sm text-slate-500">Typed runtime configuration. Secrets remain in environment variables.</p>
        </header>
        <p v-if="loading" class="py-20 text-center text-slate-500">{{ t('common.loading') }}</p>
        <div v-else class="space-y-6">
            <section v-for="(settings, group) in grouped" :key="group">
                <h3 class="mb-2 text-xs font-bold uppercase text-slate-500">{{ group }}</h3>
                <div class="overflow-hidden rounded-lg border border-slate-800">
                    <div v-for="setting in settings" :key="setting.id" class="grid gap-3 border-b border-slate-800 p-4 last:border-0 lg:grid-cols-[minmax(15rem,1fr)_minmax(14rem,1fr)_minmax(12rem,.7fr)_auto] lg:items-end">
                        <div>
                            <p class="text-sm font-semibold text-white">{{ setting.label }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ setting.description }}</p>
                            <code class="mt-1 block text-[10px] text-emerald-300">{{ setting.key }}</code>
                        </div>
                        <label class="text-xs text-slate-400">
                            Value
                            <input v-if="['string', 'integer', 'decimal'].includes(setting.type)" v-model="values[setting.id]" :type="setting.type === 'string' ? 'text' : 'number'" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white" />
                            <select v-else-if="setting.type === 'boolean'" v-model="values[setting.id]" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white"><option :value="true">Enabled</option><option :value="false">Disabled</option></select>
                            <textarea v-else v-model="values[setting.id]" rows="3" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 font-mono text-xs text-white"></textarea>
                        </label>
                        <label class="text-xs text-slate-400">Reason<input v-model="reasons[setting.id]" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white" /></label>
                        <button v-if="canManage" type="button" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-500 px-3 text-xs font-bold text-slate-950 disabled:opacity-50" :disabled="!setting.editable" @click="save(setting)"><Save class="h-4 w-4" /> {{ t('common.save') }}</button>
                    </div>
                </div>
            </section>
        </div>
    </section>
</template>
