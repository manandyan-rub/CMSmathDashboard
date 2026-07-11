<script setup>
defineProps({
    item: {
        type: Object,
        default: null,
    },
    loading: {
        type: Boolean,
        required: true,
    },
    formatTimestamp: {
        type: Function,
        required: true,
    },
    formatDuration: {
        type: Function,
        required: true,
    },
    statusTone: {
        type: Function,
        required: true,
    },
});
</script>

<template>
    <aside class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4">
        <div v-if="loading" class="text-sm text-slate-400">Loading generation details...</div>

        <div v-else-if="!item" class="text-sm text-slate-400">Select a generation log row to inspect the full prompt and payload.</div>

        <div v-else class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-indigo-300">Generation detail</p>
                    <h3 class="mt-1 text-lg font-bold text-white">Log #{{ item.id }}</h3>
                    <p class="mt-1 text-sm text-slate-400">
                        {{ item.user?.name || 'Unknown user' }}
                        <span v-if="item.document"> - {{ item.document.title }}</span>
                    </p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase" :class="statusTone(item.status)">
                    {{ item.status }}
                </span>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                    <p class="text-[11px] font-semibold uppercase text-slate-400">Source</p>
                    <p class="mt-1 text-sm font-semibold uppercase text-white">{{ item.source || 'runtime' }}</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                    <p class="text-[11px] font-semibold uppercase text-slate-400">Started</p>
                    <p class="mt-1 text-sm font-semibold text-white">{{ formatTimestamp(item.started_at) }}</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                    <p class="text-[11px] font-semibold uppercase text-slate-400">Finished</p>
                    <p class="mt-1 text-sm font-semibold text-white">{{ formatTimestamp(item.finished_at) }}</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                    <p class="text-[11px] font-semibold uppercase text-slate-400">Duration</p>
                    <p class="mt-1 text-sm font-semibold text-white">{{ formatDuration(item.duration_seconds) }}</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                    <p class="text-[11px] font-semibold uppercase text-slate-400">Document language</p>
                    <p class="mt-1 text-sm font-semibold uppercase text-white">{{ item.document?.language || '-' }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                <p class="text-[11px] font-semibold uppercase text-slate-400">Prompt</p>
                <pre class="mt-2 whitespace-pre-wrap break-words text-xs text-slate-200">{{ item.prompt || '-' }}</pre>
            </div>

            <div v-if="item.error" class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-3">
                <p class="text-[11px] font-semibold uppercase text-rose-300">Error</p>
                <pre class="mt-2 whitespace-pre-wrap break-words text-xs text-rose-100">{{ item.error }}</pre>
            </div>

            <details class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                <summary class="cursor-pointer text-sm font-semibold text-white">Request JSON</summary>
                <pre class="mt-3 overflow-x-auto text-xs text-slate-200">{{ item.request_json_pretty || '-' }}</pre>
            </details>

            <details class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                <summary class="cursor-pointer text-sm font-semibold text-white">Response raw</summary>
                <pre class="mt-3 overflow-x-auto text-xs text-slate-200">{{ item.response_raw_pretty || '-' }}</pre>
            </details>

            <details class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                <summary class="cursor-pointer text-sm font-semibold text-white">Parsed JSON</summary>
                <pre class="mt-3 overflow-x-auto text-xs text-slate-200">{{ item.parsed_json_pretty || '-' }}</pre>
            </details>
        </div>
    </aside>
</template>
