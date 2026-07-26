<script setup lang="ts">
import { toRef, watch } from 'vue';
import LevelsPage from './LevelsPage.vue';
import LevelModal from './partials/LevelModal.vue';
import { useLevels } from './useLevels';

const props = defineProps<{
    locale: string;
    locales: string[];
    refreshToken: number;
    canManage: boolean;
    clearAlerts: () => void;
    showError: (error: unknown, fallback?: string) => void;
    showSuccess: (message: string) => void;
    reloadDashboard: () => Promise<void>;
}>();

const locale = toRef(props, 'locale');
const levels = useLevels({
    locale,
    clearAlerts: props.clearAlerts,
    showError: props.showError,
    showSuccess: props.showSuccess,
    loadOverview: props.reloadDashboard,
});

watch(
    () => [props.locale, props.refreshToken],
    () => levels.loadLevels(),
    { immediate: true },
);
</script>

<template>
    <LevelsPage
        :levels-state="levels.levelsState"
        :levels-loading="levels.levelsLoading.value"
        :can-manage="canManage"
        @reload="levels.loadLevels"
        @edit="levels.openEditLevel"
    />

    <LevelModal
        :show="levels.showLevelModal.value"
        :form="levels.levelForm"
        :formula-locales="locales"
        :submitting="levels.levelSubmitting.value"
        :preview-loading="levels.levelPreviewLoading.value"
        :preview-result="levels.levelPreview.value"
        :is-editing="levels.isLevelEditing.value"
        @close="levels.showLevelModal.value = false"
        @preview="levels.previewLevelPrompt"
        @save="levels.saveLevel"
    />
</template>
