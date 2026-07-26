<script setup lang="ts">
import { toRef, watch } from 'vue';
import { cacheFilterOptions } from '../data/navData';
import { renderFormulaMarkup } from '../utils/formulaRenderer';
import ExplanationsPage from './ExplanationsPage.vue';
import ExplanationModal from './partials/ExplanationModal.vue';
import { useFormulaExplanations } from './useFormulaExplanations';

const props = defineProps<{
    locale: string;
    locales: string[];
    refreshToken: number;
    canManage: boolean;
    clearAlerts: () => void;
    showError: (error: unknown, fallback?: string) => void;
    showSuccess: (message: string) => void;
}>();

const locale = toRef(props, 'locale');
const explanations = useFormulaExplanations({
    locale,
    clearAlerts: props.clearAlerts,
    showError: props.showError,
    showSuccess: props.showSuccess,
});

watch(
    () => [props.locale, props.refreshToken],
    () => explanations.loadExplanations(1),
    { immediate: true },
);
</script>

<template>
    <ExplanationsPage
        v-model:search="explanations.searchExplanations.value"
        v-model:cache-filter="explanations.cacheFilter.value"
        :explanations-state="explanations.explanationsState"
        :explanations-loading="explanations.explanationsLoading.value"
        :cache-filter-options="cacheFilterOptions"
        :locale="locale"
        :can-manage="canManage"
        :render-formula-markup="renderFormulaMarkup"
        @search="explanations.loadExplanations(1)"
        @reload="explanations.loadExplanations(1)"
        @edit="explanations.openEditExplanation"
        @page="explanations.loadExplanations"
    />

    <ExplanationModal
        :show="explanations.showExplanationModal.value"
        :form="explanations.explanationForm"
        :submitting="explanations.explanationSubmitting.value"
        :locale-loading="explanations.explanationLocaleLoading.value"
        :formula-locales="locales"
        :render-formula-markup="renderFormulaMarkup"
        @close="explanations.showExplanationModal.value = false"
        @save="explanations.saveExplanation"
        @change-locale="explanations.changeExplanationLocale"
        @add-step="explanations.addExplanationStep"
        @remove-step="explanations.removeExplanationStep"
    />
</template>
