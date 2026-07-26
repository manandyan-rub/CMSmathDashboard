import { computed, reactive, ref, type Ref } from 'vue';
import type { Pagination, ResourceField, ResourceSchema } from '../shared/cmsTypes';

interface ResourceContext {
    locale: Ref<string>;
    permissions: string[];
    showError: (error: any, fallback?: string) => void;
    showSuccess: (message: string) => void;
}

const emptyPagination: Pagination = {
    page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: null,
    to: null,
};

export function useCmsResource(resourceKey: Ref<string>, context: ResourceContext) {
    const schema = ref<ResourceSchema | null>(null);
    const loading = ref(false);
    const submitting = ref(false);
    const items = ref<Array<Record<string, any>>>([]);
    const pagination = ref<Pagination>({ ...emptyPagination });
    const search = ref('');
    const workflowState = ref('');
    const sort = ref('');
    const direction = ref<'asc' | 'desc'>('asc');
    const selectedIds = ref<number[]>([]);
    const showForm = ref(false);
    const form = reactive<Record<string, any>>({ id: null, translations: {}, reason: '' });
    const showConfirm = ref(false);
    const confirmTarget = ref<Record<string, any> | null>(null);
    const confirmReason = ref('');
    const preview = ref<any>(null);
    const versions = ref<any[]>([]);
    const detailMode = ref<'preview' | 'versions' | null>(null);
    const activeTranslationLocale = ref(context.locale.value);
    const dragId = ref<number | null>(null);

    const canManage = computed(() => schema.value
        ? context.permissions.includes(schema.value.permissions.manage)
        : false);
    const visibleFields = computed(() => (schema.value?.fields || [])
        .filter((field) => !['options', 'calculator_fields', 'json', 'textarea'].includes(field.type))
        .slice(0, 5));

    async function loadSchema() {
        const { data } = await window.axios.get(`/cms-api/resources/${resourceKey.value}/schema`);
        schema.value = data.resource;
        sort.value ||= data.resource.sorts?.[0] || '';
    }

    async function load(page = pagination.value.page || 1) {
        loading.value = true;
        try {
            if (!schema.value || schema.value.key !== resourceKey.value) {
                await loadSchema();
            }
            const { data } = await window.axios.get(`/cms-api/resources/${resourceKey.value}`, {
                params: {
                    page,
                    search: search.value || undefined,
                    workflow_state: workflowState.value || undefined,
                    sort: sort.value || undefined,
                    direction: direction.value,
                    locale: context.locale.value,
                },
            });
            items.value = data.items || [];
            pagination.value = { ...emptyPagination, ...(data.pagination || {}) };
            selectedIds.value = selectedIds.value.filter((id) => items.value.some((item) => item.id === id));
        } catch (error) {
            context.showError(error, 'Content could not be loaded.');
        } finally {
            loading.value = false;
        }
    }

    function blankValue(field: ResourceField): any {
        if (field.type === 'boolean') return false;
        if (field.type === 'number' || field.type === 'relation') return null;
        if (field.type === 'relation_multiple') return [];
        if (['json', 'options', 'calculator_fields'].includes(field.type)) return '[]';
        return '';
    }

    function resetForm() {
        Object.keys(form).forEach((key) => delete form[key]);
        form.id = null;
        form.reason = '';
        form.translations = {};
        (schema.value?.fields || []).forEach((field) => {
            form[field.key] = blankValue(field);
        });
        (schema.value?.locales || []).forEach((locale) => {
            form.translations[locale] = {};
            (schema.value?.translation_fields || []).forEach((field) => {
                form.translations[locale][field.key] = '';
            });
            form.translations[locale].reviewed = false;
        });
        activeTranslationLocale.value = context.locale.value;
    }

    function openCreate() {
        resetForm();
        showForm.value = true;
    }

    function openEdit(item: Record<string, any>) {
        resetForm();
        form.id = item.id;
        (schema.value?.fields || []).forEach((field) => {
            const value = item[field.key];
            form[field.key] = ['json', 'options', 'calculator_fields'].includes(field.type)
                ? JSON.stringify(value ?? [], null, 2)
                : value ?? blankValue(field);
        });
        const translations = structuredClone(item.translations || {});
        (schema.value?.locales || []).forEach((locale) => {
            form.translations[locale] = {
                ...form.translations[locale],
                ...(translations[locale] || {}),
            };
        });
        form.reason = '';
        showForm.value = true;
    }

    function payload() {
        const result: Record<string, any> = {
            translations: form.translations,
            reason: form.reason || (form.id ? 'Content updated in CMS' : 'Content created in CMS'),
        };
        (schema.value?.fields || []).forEach((field) => {
            let value = form[field.key];
            if (['json', 'options', 'calculator_fields'].includes(field.type)) {
                value = value?.trim() ? JSON.parse(value) : null;
            }
            if (field.type === 'number' && value !== '' && value !== null) {
                value = Number(value);
            }
            result[field.key] = value === '' ? null : value;
        });

        return result;
    }

    async function save() {
        submitting.value = true;
        try {
            const data = payload();
            if (form.id) {
                await window.axios.patch(`/cms-api/resources/${resourceKey.value}/${form.id}`, data);
            } else {
                await window.axios.post(`/cms-api/resources/${resourceKey.value}`, data);
            }
            showForm.value = false;
            context.showSuccess('Content saved.');
            await load(form.id ? pagination.value.page : 1);
        } catch (error) {
            context.showError(error, 'Content could not be saved.');
        } finally {
            submitting.value = false;
        }
    }

    function requestArchive(item: Record<string, any>) {
        confirmTarget.value = item;
        confirmReason.value = '';
        showConfirm.value = true;
    }

    async function confirmArchive() {
        if (!confirmTarget.value) return;
        submitting.value = true;
        try {
            await window.axios.delete(
                `/cms-api/resources/${resourceKey.value}/${confirmTarget.value.id}`,
                { data: { mode: 'archive', reason: confirmReason.value } },
            );
            showConfirm.value = false;
            context.showSuccess('Content archived.');
            await load();
        } catch (error) {
            context.showError(error, 'Content could not be archived.');
        } finally {
            submitting.value = false;
        }
    }

    async function duplicate(item: Record<string, any>) {
        try {
            await window.axios.post(`/cms-api/resources/${resourceKey.value}/${item.id}/duplicate`);
            context.showSuccess('Draft copy created.');
            await load(1);
        } catch (error) {
            context.showError(error, 'Content could not be duplicated.');
        }
    }

    async function loadPreview(item: Record<string, any>) {
        detailMode.value = 'preview';
        preview.value = null;
        const { data } = await window.axios.get(
            `/cms-api/resources/${resourceKey.value}/${item.id}/preview`,
            { params: { locale: context.locale.value } },
        );
        preview.value = data.preview;
    }

    async function loadVersions(item: Record<string, any>) {
        detailMode.value = 'versions';
        versions.value = [];
        const { data } = await window.axios.get(
            `/cms-api/resources/${resourceKey.value}/${item.id}/versions`,
        );
        versions.value = data.items || [];
    }

    async function publish(
        item: Record<string, any>,
        state: string,
        comment: string,
        scheduledAt?: string,
    ) {
        try {
            await window.axios.put(`/cms-api/resources/${resourceKey.value}/${item.id}/publishing`, {
                state,
                comment,
                scheduled_at: scheduledAt || null,
            });
            context.showSuccess(`Publishing state changed to ${state}.`);
            await load();
        } catch (error) {
            context.showError(error, 'Publishing state could not be changed.');
        }
    }

    async function restoreVersion(version: Record<string, any>, reason: string) {
        try {
            await window.axios.post(`/cms-api/versions/${version.id}/restore`, {
                confirmed: true,
                reason,
            });
            detailMode.value = null;
            context.showSuccess(`Version ${version.version_number} restored.`);
            await load();
        } catch (error) {
            context.showError(error, 'The selected version could not be restored.');
        }
    }

    function toggleSelection(id: number) {
        selectedIds.value = selectedIds.value.includes(id)
            ? selectedIds.value.filter((selected) => selected !== id)
            : [...selectedIds.value, id];
    }

    async function bulk(action: 'activate' | 'deactivate' | 'archive') {
        if (!selectedIds.value.length) return;
        try {
            await window.axios.post(`/cms-api/resources/${resourceKey.value}/bulk`, {
                ids: selectedIds.value,
                action,
                reason: `Bulk ${action} from CMS`,
            });
            selectedIds.value = [];
            context.showSuccess('Bulk action completed.');
            await load();
        } catch (error) {
            context.showError(error, 'Bulk action failed.');
        }
    }

    function startDrag(id: number) {
        dragId.value = id;
    }

    async function dropOn(targetId: number) {
        if (!dragId.value || dragId.value === targetId) return;
        const sourceIndex = items.value.findIndex((item) => item.id === dragId.value);
        const targetIndex = items.value.findIndex((item) => item.id === targetId);
        const reordered = [...items.value];
        const [moved] = reordered.splice(sourceIndex, 1);
        reordered.splice(targetIndex, 0, moved);
        items.value = reordered;
        dragId.value = null;
        try {
            await window.axios.post(`/cms-api/resources/${resourceKey.value}/reorder`, {
                items: reordered.map((item, index) => ({ id: item.id, order: index })),
            });
        } catch (error) {
            context.showError(error, 'Order could not be saved.');
            await load();
        }
    }

    return {
        schema,
        loading,
        submitting,
        items,
        pagination,
        search,
        workflowState,
        sort,
        direction,
        selectedIds,
        showForm,
        form,
        showConfirm,
        confirmReason,
        preview,
        versions,
        detailMode,
        activeTranslationLocale,
        visibleFields,
        canManage,
        load,
        openCreate,
        openEdit,
        save,
        requestArchive,
        confirmArchive,
        duplicate,
        loadPreview,
        loadVersions,
        publish,
        restoreVersion,
        toggleSelection,
        bulk,
        startDrag,
        dropOn,
    };
}
