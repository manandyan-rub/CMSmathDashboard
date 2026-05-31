import { reactive, ref } from 'vue';

export function useFormulaExplanations({ locale, clearAlerts, showError, showSuccess }) {
    const searchExplanations = ref('');
    const cacheFilter = ref('all');
    const explanationsLoading = ref(false);
    const showExplanationModal = ref(false);
    const explanationSubmitting = ref(false);
    const explanationLocaleLoading = ref(false);

    const explanationsState = reactive({
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

    const explanationForm = reactive({
        formulaId: null,
        name: '',
        category: '',
        formula: '',
        locale: 'en',
        steps: [],
    });

    async function loadExplanations(page = explanationsState.pagination.page || 1) {
        explanationsLoading.value = true;
        try {
            const { data } = await window.axios.get('/cms-api/formula-explanations', {
                params: {
                    page,
                    search: searchExplanations.value,
                    locale: locale.value,
                    cache: cacheFilter.value,
                },
            });

            explanationsState.items = data?.items ?? [];
            explanationsState.pagination = {
                ...explanationsState.pagination,
                ...(data?.pagination ?? {}),
            };
        } catch (error) {
            showError(error, 'Failed to load formula explanations.');
        } finally {
            explanationsLoading.value = false;
        }
    }

    function applyExplanationItem(item) {
        explanationForm.formulaId = item.id;
        explanationForm.name = item.name || item.category || 'Formula';
        explanationForm.category = item.category || '';
        explanationForm.formula = item.formula || '';
        explanationForm.locale = item.locale || locale.value;
        explanationForm.steps = Array.isArray(item.steps_json) && item.steps_json.length > 0
            ? item.steps_json.map((step) => ({
                latex: step?.latex ?? '',
                note: step?.note ?? '',
            }))
            : [{ latex: '', note: '' }];
    }

    function openEditExplanation(item) {
        clearAlerts();
        applyExplanationItem({
            ...item,
            locale: item.locale || locale.value,
        });

        showExplanationModal.value = true;
    }

    async function changeExplanationLocale(nextLocale) {
        if (!explanationForm.formulaId || nextLocale === explanationForm.locale) return;

        explanationLocaleLoading.value = true;
        clearAlerts();

        try {
            const { data } = await window.axios.get(`/cms-api/formula-explanations/${explanationForm.formulaId}`, {
                params: {
                    locale: nextLocale,
                },
            });

            applyExplanationItem(data?.item ?? {
                id: explanationForm.formulaId,
                name: explanationForm.name,
                category: explanationForm.category,
                formula: explanationForm.formula,
                locale: nextLocale,
                steps_json: [],
            });
        } catch (error) {
            showError(error, 'Failed to load explanation language.');
        } finally {
            explanationLocaleLoading.value = false;
        }
    }

    function addExplanationStep() {
        explanationForm.steps.push({ latex: '', note: '' });
    }

    function removeExplanationStep(index) {
        explanationForm.steps.splice(index, 1);

        if (explanationForm.steps.length === 0) {
            addExplanationStep();
        }
    }

    async function saveExplanation() {
        if (!explanationForm.formulaId) return;

        explanationSubmitting.value = true;
        clearAlerts();

        try {
            await window.axios.patch(`/cms-api/formula-explanations/${explanationForm.formulaId}/steps`, {
                locale: explanationForm.locale,
                steps_json: explanationForm.steps,
            });

            showExplanationModal.value = false;
            showSuccess('Formula explanation saved.');
            await loadExplanations(explanationsState.pagination.page);
        } catch (error) {
            showError(error, 'Failed to save formula explanation.');
        } finally {
            explanationSubmitting.value = false;
        }
    }

    return {
        searchExplanations,
        cacheFilter,
        explanationsLoading,
        explanationsState,
        showExplanationModal,
        explanationSubmitting,
        explanationLocaleLoading,
        explanationForm,
        loadExplanations,
        openEditExplanation,
        changeExplanationLocale,
        addExplanationStep,
        removeExplanationStep,
        saveExplanation,
    };
}
