<script setup lang="ts">
import { watch } from 'vue';
import GenerationLogsPage from './GenerationLogsPage.vue';
import { useGenerationLogs } from './useGenerationLogs';

const props = defineProps<{
    refreshToken: number;
    clearAlerts: () => void;
    showError: (error: unknown, fallback?: string) => void;
}>();

const logs = useGenerationLogs({
    clearAlerts: props.clearAlerts,
    showError: props.showError,
});

function formatTimestamp(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '-';
}

function formatDuration(seconds: number | null): string {
    if (seconds === null || seconds === undefined) return '-';
    return seconds < 1 ? `${Math.round(seconds * 1000)} ms` : `${seconds.toFixed(2)} s`;
}

function statusTone(status: string): string {
    if (['completed', 'success'].includes(status)) return 'bg-emerald-500/15 text-emerald-200';
    if (status === 'failed') return 'bg-rose-500/15 text-rose-200';
    return 'bg-amber-500/15 text-amber-200';
}

watch(
    () => props.refreshToken,
    () => logs.loadGenerationLogs(1),
    { immediate: true },
);
</script>

<template>
    <GenerationLogsPage
        :generation-logs-state="logs.generationLogsState"
        :generation-logs-loading="logs.generationLogsLoading.value"
        :generation-log-detail-loading="logs.generationLogDetailLoading.value"
        :generation-log-filters="logs.generationLogFilters"
        :generation-log-status-options="logs.generationLogStatusOptions"
        :generation-log-detail="logs.generationLogDetail.value"
        :selected-generation-log-id="logs.selectedGenerationLogId.value"
        :format-timestamp="formatTimestamp"
        :format-duration="formatDuration"
        :status-tone="statusTone"
        @reload="logs.loadGenerationLogs(1)"
        @open="logs.openGenerationLog"
        @page="logs.loadGenerationLogs"
    />
</template>
