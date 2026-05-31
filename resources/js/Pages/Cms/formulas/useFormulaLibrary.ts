import { computed, reactive, ref } from 'vue';
import { formulaLocales } from '../data/navData';

function buildEmptyTranslations() {
    return {
        en: { name: '', description: '' },
        hy: { name: '', description: '' },
        ru: { name: '', description: '' },
        fr: { name: '', description: '' },
    };
}

export function useFormulaLibrary({ locale, clearAlerts, showError, showSuccess, loadOverview }) {
    const searchFormulas = ref('');
    const formulaCategories = ref([]);
    const formulasLoading = ref(false);
    const showFormulaModal = ref(false);
    const formulaSubmitting = ref(false);

    const formulasState = reactive({
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

    const formulaForm = reactive({
        id: null,
        category: '',
        formula: '',
        example: '',
        translations: buildEmptyTranslations(),
    });

    const isFormulaEditing = computed(() => formulaForm.id !== null);
    const formulaCategoryOptions = computed(() => {
        const categories = new Set(formulaCategories.value);

        if (formulaForm.category) {
            categories.add(formulaForm.category);
        }

        return Array.from(categories).sort((a, b) => String(a).localeCompare(String(b)));
    });

    async function loadFormulas(page = formulasState.pagination.page || 1) {
        formulasLoading.value = true;
        try {
            const { data } = await window.axios.get('/cms-api/formulas', {
                params: {
                    page,
                    search: searchFormulas.value,
                    locale: locale.value,
                },
            });

            formulasState.items = data?.items ?? [];
            formulasState.pagination = {
                ...formulasState.pagination,
                ...(data?.pagination ?? {}),
            };
        } catch (error) {
            showError(error, 'Failed to load formulas.');
        } finally {
            formulasLoading.value = false;
        }
    }

    async function loadFormulaCategories() {
        try {
            const { data } = await window.axios.get('/cms-api/formula-categories');
            formulaCategories.value = data?.items ?? [];
        } catch (error) {
            showError(error, 'Failed to load formula categories.');
        }
    }

    function openCreateFormula() {
        clearAlerts();
        formulaForm.id = null;
        formulaForm.category = '';
        formulaForm.formula = '';
        formulaForm.example = '';
        formulaForm.translations = buildEmptyTranslations();
        showFormulaModal.value = true;
    }

    function openEditFormula(formula) {
        clearAlerts();
        formulaForm.id = formula.id;
        formulaForm.category = formula.category ?? '';
        formulaForm.formula = formula.formula ?? '';
        formulaForm.example = formula.example ?? '';
        formulaForm.translations = buildEmptyTranslations();

        for (const itemLocale of formulaLocales) {
            formulaForm.translations[itemLocale] = {
                name: formula?.translations?.[itemLocale]?.name ?? '',
                description: formula?.translations?.[itemLocale]?.description ?? '',
            };
        }

        showFormulaModal.value = true;
    }

    async function saveFormula() {
        formulaSubmitting.value = true;
        clearAlerts();

        const payload = {
            category: formulaForm.category,
            formula: formulaForm.formula,
            example: formulaForm.example,
            translations: formulaForm.translations,
        };

        try {
            if (isFormulaEditing.value) {
                await window.axios.patch(`/cms-api/formulas/${formulaForm.id}`, payload, {
                    params: { locale: locale.value },
                });
                showSuccess('Formula updated.');
            } else {
                await window.axios.post('/cms-api/formulas', payload, {
                    params: { locale: locale.value },
                });
                showSuccess('Formula created.');
            }

            showFormulaModal.value = false;
            await Promise.all([loadFormulas(formulasState.pagination.page), loadFormulaCategories(), loadOverview()]);
        } catch (error) {
            showError(error, 'Failed to save formula.');
        } finally {
            formulaSubmitting.value = false;
        }
    }

    async function deleteFormula(formula) {
        clearAlerts();
        const ok = window.confirm(`Delete formula "${formula.name || formula.category}"?`);
        if (!ok) return;

        try {
            await window.axios.delete(`/cms-api/formulas/${formula.id}`);
            showSuccess('Formula deleted.');
            await Promise.all([loadFormulas(formulasState.pagination.page), loadFormulaCategories(), loadOverview()]);
        } catch (error) {
            showError(error, 'Failed to delete formula.');
        }
    }

    return {
        searchFormulas,
        formulasLoading,
        formulasState,
        showFormulaModal,
        formulaSubmitting,
        formulaForm,
        isFormulaEditing,
        formulaCategoryOptions,
        loadFormulas,
        loadFormulaCategories,
        openCreateFormula,
        openEditFormula,
        saveFormula,
        deleteFormula,
    };
}
