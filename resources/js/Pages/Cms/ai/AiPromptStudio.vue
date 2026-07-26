<script setup lang="ts">
import { CheckCircle2, FlaskConical, GitCompare, Play, Plus } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';
import StatusBadge from '../shared/StatusBadge.vue';

const props = defineProps<{
    refreshToken: number;
    permissions: string[];
    showError: (error: any, fallback?: string) => void;
    showSuccess: (message: string) => void;
}>();

const prompts = ref<Array<Record<string, any>>>([]);
const selectedId = ref<number | null>(null);
const loading = ref(false);
const running = ref(false);
const result = ref<Record<string, any> | null>(null);
const compareResult = ref<Record<string, any> | null>(null);
const compareIds = reactive({ left: null as number | null, right: null as number | null });
const versionForm = reactive({
    system_prompt: '',
    user_prompt_template: '',
    input_schema: '{}',
    output_schema: '{}',
    temperature: 0.2,
    max_output_tokens: 2048,
    change_reason: '',
});
const testInput = ref('{}');
const selected = computed(() => prompts.value.find((prompt) => prompt.id === selectedId.value) || null);

async function load() {
    loading.value = true;
    try {
        const { data } = await window.axios.get('/cms-api/resources/ai-prompts', {
            params: { per_page: 100, sort: 'key', direction: 'asc' },
        });
        prompts.value = data.items || [];
        selectedId.value ||= prompts.value[0]?.id || null;
    } catch (error) {
        props.showError(error, 'AI prompts could not be loaded.');
    } finally {
        loading.value = false;
    }
}

async function createVersion() {
    if (!selected.value) return;
    try {
        await window.axios.post(`/cms-api/ai/prompts/${selected.value.id}/versions`, {
            ...versionForm,
            input_schema: JSON.parse(versionForm.input_schema || '{}'),
            output_schema: JSON.parse(versionForm.output_schema || '{}'),
        });
        props.showSuccess('Prompt version created.');
        Object.assign(versionForm, {
            system_prompt: '',
            user_prompt_template: '',
            input_schema: '{}',
            output_schema: '{}',
            temperature: 0.2,
            max_output_tokens: 2048,
            change_reason: '',
        });
        await load();
    } catch (error) {
        props.showError(error, 'Prompt version could not be created.');
    }
}

async function activate(version: Record<string, any>) {
    if (!selected.value) return;
    try {
        await window.axios.put(`/cms-api/ai/prompts/${selected.value.id}/versions/${version.id}/activate`, {
            reason: `Activated version ${version.version} from CMS`,
        });
        props.showSuccess('Prompt version activated.');
        await load();
    } catch (error) {
        props.showError(error, 'Prompt version could not be activated.');
    }
}

async function runTest() {
    if (!selected.value) return;
    running.value = true;
    result.value = null;
    try {
        const { data } = await window.axios.post(`/cms-api/ai/prompts/${selected.value.id}/test`, {
            input: JSON.parse(testInput.value || '{}'),
        });
        result.value = data.result;
    } catch (error: any) {
        result.value = error?.response?.data?.result || null;
        props.showError(error, 'AI prompt test failed.');
    } finally {
        running.value = false;
    }
}

async function compare() {
    if (!selected.value || !compareIds.left || !compareIds.right) return;
    const { data } = await window.axios.get(
        `/cms-api/ai/prompts/${selected.value.id}/compare/${compareIds.left}/${compareIds.right}`,
    );
    compareResult.value = data;
}

onMounted(load);
</script>

<template>
    <section class="mt-5 space-y-4 border-t border-slate-800 pt-5">
        <header>
            <h2 class="text-lg font-bold text-white">Prompt versions and live test</h2>
            <p class="text-sm text-slate-500">Tests call the configured provider and are logged without saving production content.</p>
        </header>

        <select v-model="selectedId" class="w-full max-w-xl rounded-lg border-slate-700 bg-slate-950 text-sm text-white">
            <option v-for="prompt in prompts" :key="prompt.id" :value="prompt.id">{{ prompt.key }} · {{ prompt.feature }}</option>
        </select>

        <template v-if="selected">
            <div class="grid gap-4 xl:grid-cols-2">
                <section class="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-white"><Plus class="h-4 w-4 text-emerald-300" /> New version</h3>
                    <div class="mt-3 space-y-3">
                        <textarea v-model="versionForm.system_prompt" rows="5" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white" placeholder="System prompt"></textarea>
                        <textarea v-model="versionForm.user_prompt_template" rows="6" class="w-full rounded-lg border-slate-700 bg-slate-950 text-sm text-white" placeholder="User prompt template with {{variables}}"></textarea>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <textarea v-model="versionForm.input_schema" rows="5" class="rounded-lg border-slate-700 bg-slate-950 font-mono text-xs text-white" placeholder="Input validation schema"></textarea>
                            <textarea v-model="versionForm.output_schema" rows="5" class="rounded-lg border-slate-700 bg-slate-950 font-mono text-xs text-white" placeholder="Output validation schema"></textarea>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-3">
                            <input v-model.number="versionForm.temperature" type="number" step="0.1" class="rounded-lg border-slate-700 bg-slate-950 text-sm text-white" />
                            <input v-model.number="versionForm.max_output_tokens" type="number" class="rounded-lg border-slate-700 bg-slate-950 text-sm text-white" />
                            <input v-model="versionForm.change_reason" class="rounded-lg border-slate-700 bg-slate-950 text-sm text-white" placeholder="Change reason" />
                        </div>
                        <button v-if="permissions.includes('ai.manage')" type="button" class="rounded-lg bg-emerald-500 px-3 py-2 text-sm font-bold text-slate-950" @click="createVersion">Create version</button>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-white"><FlaskConical class="h-4 w-4 text-amber-300" /> Test active version</h3>
                    <textarea v-model="testInput" rows="10" class="mt-3 w-full rounded-lg border-slate-700 bg-slate-950 font-mono text-xs text-white"></textarea>
                    <button v-if="permissions.includes('ai.manage')" type="button" class="mt-2 inline-flex items-center gap-2 rounded-lg bg-amber-400 px-3 py-2 text-sm font-bold text-slate-950 disabled:opacity-50" :disabled="running" @click="runTest">
                        <Play class="h-4 w-4" /> {{ running ? 'Running…' : 'Run test' }}
                    </button>
                    <div v-if="result" class="mt-4 space-y-2">
                        <div class="flex flex-wrap gap-2"><StatusBadge :status="result.status" /><span class="text-xs text-slate-500">{{ result.latency_ms }} ms · {{ result.input_tokens + result.output_tokens }} tokens · ${{ result.estimated_cost || 0 }}</span></div>
                        <p v-if="result.error" class="rounded-lg border border-rose-400/30 bg-rose-400/10 p-3 text-sm text-rose-200">{{ result.error }}</p>
                        <details class="rounded-lg border border-slate-800 bg-slate-950 p-3">
                            <summary class="cursor-pointer text-xs font-bold text-slate-300">Parsed output</summary>
                            <pre class="mt-3 overflow-x-auto whitespace-pre-wrap text-xs text-slate-400">{{ JSON.stringify(result.parsed_output, null, 2) }}</pre>
                        </details>
                        <details class="rounded-lg border border-slate-800 bg-slate-950 p-3">
                            <summary class="cursor-pointer text-xs font-bold text-slate-300">Raw output</summary>
                            <pre class="mt-3 overflow-x-auto whitespace-pre-wrap text-xs text-slate-400">{{ result.raw_output }}</pre>
                        </details>
                    </div>
                </section>
            </div>

            <section class="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                <h3 class="text-sm font-bold text-white">Version history</h3>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs text-slate-500"><tr><th class="py-2">Version</th><th>Author</th><th>Reason</th><th>Status</th><th></th></tr></thead>
                        <tbody class="divide-y divide-slate-800">
                            <tr v-for="version in selected.versions || []" :key="version.id">
                                <td class="py-3 font-bold text-white">{{ version.version }}</td>
                                <td class="text-slate-300">{{ version.author_id || 'System' }}</td>
                                <td class="text-slate-400">{{ version.change_reason }}</td>
                                <td><StatusBadge :status="version.active ? 'active' : 'inactive'" /></td>
                                <td class="text-right"><button v-if="!version.active && permissions.includes('ai.prompts.publish')" type="button" class="inline-flex items-center gap-1 rounded-lg border border-slate-700 px-2 py-1 text-xs text-slate-300" @click="activate(version)"><CheckCircle2 class="h-3.5 w-3.5" /> Activate</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap items-end gap-2 border-t border-slate-800 pt-4">
                    <label class="text-xs text-slate-400">Left<select v-model="compareIds.left" class="ml-2 rounded-lg border-slate-700 bg-slate-950 text-sm text-white"><option v-for="version in selected.versions || []" :key="version.id" :value="version.id">v{{ version.version }}</option></select></label>
                    <label class="text-xs text-slate-400">Right<select v-model="compareIds.right" class="ml-2 rounded-lg border-slate-700 bg-slate-950 text-sm text-white"><option v-for="version in selected.versions || []" :key="version.id" :value="version.id">v{{ version.version }}</option></select></label>
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-300" @click="compare"><GitCompare class="h-4 w-4" /> Compare</button>
                </div>
                <pre v-if="compareResult" class="mt-3 overflow-x-auto rounded-lg border border-slate-800 bg-slate-950 p-3 text-xs text-slate-400">{{ JSON.stringify(compareResult, null, 2) }}</pre>
            </section>
        </template>
    </section>
</template>
