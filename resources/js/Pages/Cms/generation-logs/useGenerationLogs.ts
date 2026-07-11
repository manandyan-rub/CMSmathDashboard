import { computed, reactive, ref } from 'vue';

function prettyPrintPayload(value) {
    if (!value) return '';

    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2);
    }

    try {
        return JSON.stringify(JSON.parse(value), null, 2);
    } catch {
        return String(value);
    }
}

export function useGenerationLogs({ clearAlerts, showError }) {
    const generationLogsLoading = ref(false);
    const generationLogDetailLoading = ref(false);
    const selectedGenerationLogId = ref(null);

    const generationLogFilters = reactive({
        status: 'all',
        user_id: '',
        date_from: '',
        date_to: '',
    });

    const generationLogsState = reactive({
        items: [],
        pagination: {
            page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
            from: null,
            to: null,
        },
    });

    const generationLogDetail = ref(null);

    const generationLogStatusOptions = [
        { value: 'all', label: 'All statuses' },
        { value: 'completed', label: 'Completed' },
        { value: 'failed', label: 'Failed' },
        { value: 'processing', label: 'Processing' },
    ];

    const selectedGenerationLogSummary = computed(() => {
        return generationLogsState.items.find((item) => item.id === selectedGenerationLogId.value) ?? null;
    });

    async function loadGenerationLogs(page = generationLogsState.pagination.page || 1) {
        generationLogsLoading.value = true;

        try {
            const params = {
                page,
                status: generationLogFilters.status === 'all' ? undefined : generationLogFilters.status,
                user_id: generationLogFilters.user_id === '' ? undefined : Number(generationLogFilters.user_id),
                date_from: generationLogFilters.date_from || undefined,
                date_to: generationLogFilters.date_to || undefined,
            };

            const { data } = await window.axios.get('/cms-api/generation-logs', { params });

            generationLogsState.items = data?.items ?? [];
            generationLogsState.pagination = {
                ...generationLogsState.pagination,
                ...(data?.pagination ?? {}),
            };

            if (selectedGenerationLogId.value) {
                const stillVisible = generationLogsState.items.some((item) => item.id === selectedGenerationLogId.value);
                if (!stillVisible) {
                    generationLogDetail.value = null;
                    selectedGenerationLogId.value = null;
                }
            }
        } catch (error) {
            showError(error, 'Failed to load generation logs.');
        } finally {
            generationLogsLoading.value = false;
        }
    }

    async function openGenerationLog(log) {
        if (!log?.id) return;

        clearAlerts();
        selectedGenerationLogId.value = log.id;
        generationLogDetailLoading.value = true;

        try {
            const { data } = await window.axios.get(`/cms-api/generation-logs/${log.id}`);
            const item = data?.item ?? null;

            generationLogDetail.value = item
                ? {
                      ...item,
                      request_json_pretty: prettyPrintPayload(item.request_json),
                      response_raw_pretty: prettyPrintPayload(item.response_raw),
                      parsed_json_pretty: prettyPrintPayload(item.parsed_json),
                  }
                : null;
        } catch (error) {
            showError(error, 'Failed to load generation log detail.');
        } finally {
            generationLogDetailLoading.value = false;
        }
    }

    return {
        generationLogsLoading,
        generationLogDetailLoading,
        generationLogFilters,
        generationLogStatusOptions,
        generationLogsState,
        generationLogDetail,
        selectedGenerationLogId,
        selectedGenerationLogSummary,
        loadGenerationLogs,
        openGenerationLog,
    };
}
