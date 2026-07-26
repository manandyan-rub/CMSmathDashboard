import { reactive, ref } from 'vue';
import type { Pagination } from '../shared/cmsTypes';

export function useExerciseStudio(
    showError: (error: any, fallback?: string) => void,
    showSuccess: (message: string) => void,
) {
    const loading = ref(false);
    const detailLoading = ref(false);
    const submitting = ref(false);
    const items = ref<Array<Record<string, any>>>([]);
    const detail = ref<Record<string, any> | null>(null);
    const pagination = ref<Pagination>({ page: 1, last_page: 1, total: 0, per_page: 20 });
    const filters = reactive({
        search: '',
        status: '',
        source: '',
        locale: '',
        publication_status: '',
        moderation_status: '',
    });

    async function load(page = pagination.value.page || 1) {
        loading.value = true;
        try {
            const { data } = await window.axios.get('/cms-api/exercise-documents', {
                params: { page, ...filters },
            });
            items.value = data.items || [];
            pagination.value = { ...pagination.value, ...(data.pagination || {}) };
        } catch (error) {
            showError(error, 'Documents could not be loaded.');
        } finally {
            loading.value = false;
        }
    }

    async function open(item: Record<string, any>) {
        detailLoading.value = true;
        try {
            const { data } = await window.axios.get(`/cms-api/exercise-documents/${item.id}`);
            detail.value = data.document;
        } catch (error) {
            showError(error, 'Document details could not be loaded.');
        } finally {
            detailLoading.value = false;
        }
    }

    async function action(actionName: string, reason: string, targetUserId?: number) {
        if (!detail.value) return;
        submitting.value = true;
        try {
            const { data } = await window.axios.post(`/cms-api/exercise-documents/${detail.value.id}/actions`, {
                action: actionName,
                reason,
                target_user_id: targetUserId,
                confirmed: actionName === 'delete' ? true : undefined,
            });
            detail.value = data.document;
            showSuccess(data.message);
            await load();
        } catch (error) {
            showError(error, 'Document action failed.');
        } finally {
            submitting.value = false;
        }
    }

    function exportDocument(format: 'json' | 'html') {
        if (detail.value) {
            window.location.href = `/cms-api/exercise-documents/${detail.value.id}/export?format=${format}`;
        }
    }

    return {
        loading,
        detailLoading,
        submitting,
        items,
        detail,
        pagination,
        filters,
        load,
        open,
        action,
        exportDocument,
    };
}
