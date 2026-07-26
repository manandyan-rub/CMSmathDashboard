import { reactive, ref } from 'vue';
import type { Pagination } from '../shared/cmsTypes';

export function useLocalization(
    showError: (error: any, fallback?: string) => void,
    showSuccess: (message: string) => void,
) {
    const loading = ref(false);
    const submitting = ref(false);
    const overview = ref<Record<string, any>>({ locales: [], source_locale: 'en', entities: [] });
    const items = ref<Array<Record<string, any>>>([]);
    const pagination = ref<Pagination>({ page: 1, last_page: 1, total: 0, per_page: 20 });
    const selected = ref<Record<string, any> | null>(null);
    const filters = reactive({
        entity_type: 'formulas',
        locale: 'hy',
        missing: false,
        outdated: false,
        search: '',
    });
    const editValues = reactive<Record<string, any>>({});
    const editReviewed = ref(false);
    const reason = ref('');

    async function loadOverview() {
        const { data } = await window.axios.get('/cms-api/localization/overview');
        overview.value = data;
        if (!data.locales.includes(filters.locale)) {
            filters.locale = data.locales.find((item: string) => item !== data.source_locale) || data.source_locale;
        }
    }

    async function load(page = pagination.value.page || 1) {
        loading.value = true;
        try {
            if (!overview.value.entities?.length) await loadOverview();
            const { data } = await window.axios.get('/cms-api/localization/items', {
                params: { page, ...filters },
            });
            items.value = data.items || [];
            pagination.value = { ...pagination.value, ...(data.pagination || {}) };
        } catch (error) {
            showError(error, 'Translations could not be loaded.');
        } finally {
            loading.value = false;
        }
    }

    function edit(item: Record<string, any>) {
        selected.value = item;
        Object.keys(editValues).forEach((key) => delete editValues[key]);
        const existing = item.translations?.[filters.locale] || {};
        item.fields.forEach((field: string) => {
            editValues[field] = Array.isArray(existing[field])
                ? JSON.stringify(existing[field], null, 2)
                : existing[field] || '';
        });
        editReviewed.value = Boolean(existing.reviewed);
        reason.value = '';
    }

    async function save() {
        if (!selected.value) return;
        submitting.value = true;
        try {
            const values = { ...editValues };
            selected.value.fields.forEach((field: string) => {
                if (field === 'hints' && typeof values[field] === 'string') {
                    values[field] = values[field].trim() ? JSON.parse(values[field]) : [];
                }
            });
            await window.axios.put(
                `/cms-api/localization/${filters.entity_type}/${selected.value.id}`,
                {
                    locale: filters.locale,
                    values,
                    reviewed: editReviewed.value,
                    machine_draft: false,
                    reason: reason.value || 'Translation updated in CMS',
                },
            );
            selected.value = null;
            showSuccess('Translation updated.');
            await Promise.all([load(), loadOverview()]);
        } catch (error) {
            showError(error, 'Translation could not be saved.');
        } finally {
            submitting.value = false;
        }
    }

    async function copySource(item: Record<string, any>) {
        await window.axios.post(
            `/cms-api/localization/${filters.entity_type}/${item.id}/copy-source`,
            { target_locale: filters.locale, reason: 'Source locale copied as draft' },
        );
        showSuccess('Source locale copied as an unreviewed draft.');
        await load();
    }

    async function machineDraft(item: Record<string, any>) {
        submitting.value = true;
        try {
            await window.axios.post(
                `/cms-api/localization/${filters.entity_type}/${item.id}/machine-draft`,
                { target_locale: filters.locale },
            );
            showSuccess('Machine translation saved as an unreviewed draft.');
            await load();
        } catch (error) {
            showError(error, 'Machine translation could not be generated.');
        } finally {
            submitting.value = false;
        }
    }

    async function importFile(file: File) {
        const text = await file.text();
        const parsed = JSON.parse(text);
        const rows = Array.isArray(parsed) ? parsed : parsed.items;
        await window.axios.post('/cms-api/localization/import', {
            items: rows,
            reason: `Imported from ${file.name}`,
        });
        showSuccess(`${rows.length} translations imported.`);
        await Promise.all([load(), loadOverview()]);
    }

    function exportTranslations() {
        window.location.href = `/cms-api/localization/export?entity_type=${encodeURIComponent(filters.entity_type)}`;
    }

    return {
        loading,
        submitting,
        overview,
        items,
        pagination,
        selected,
        filters,
        editValues,
        editReviewed,
        reason,
        load,
        loadOverview,
        edit,
        save,
        copySource,
        machineDraft,
        importFile,
        exportTranslations,
    };
}
