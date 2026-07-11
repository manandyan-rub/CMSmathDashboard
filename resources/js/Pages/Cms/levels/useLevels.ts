import { computed, reactive, ref } from 'vue';

export function useLevels({ locale, clearAlerts, showError, showSuccess, loadOverview }) {
    const levelsLoading = ref(false);
    const showLevelModal = ref(false);
    const levelSubmitting = ref(false);
    const levelPreviewLoading = ref(false);

    const levelsState = reactive({
        items: [],
    });

    const levelForm = reactive({
        id: null,
        title: '',
        pathTitle: '',
        difficulty: '',
        passing_score: 70,
        xp_reward: 50,
        time_limit: '',
        active: true,
        prompt: '',
        question_count: 10,
        options_count: 4,
        prompt_locale: 'en',
    });
    const levelPreview = ref(null);

    const isLevelEditing = computed(() => levelForm.id !== null);

    async function loadLevels() {
        levelsLoading.value = true;

        try {
            const { data } = await window.axios.get('/cms-api/levels', {
                params: { locale: locale.value },
            });

            levelsState.items = data?.items ?? [];
        } catch (error) {
            showError(error, 'Failed to load levels.');
        } finally {
            levelsLoading.value = false;
        }
    }

    function openEditLevel(pathItem, level) {
        clearAlerts();

        levelForm.id = level.id;
        levelForm.title = level.title ?? '';
        levelForm.pathTitle = pathItem?.title ?? '';
        levelForm.difficulty = level.difficulty ?? '';
        levelForm.passing_score = Number(level.passing_score ?? 70);
        levelForm.xp_reward = Number(level.xp_reward ?? 50);
        levelForm.time_limit = level.time_limit === null || level.time_limit === undefined ? '' : String(level.time_limit);
        levelForm.active = !!level.active;
        levelForm.prompt = level?.prompt?.prompt ?? '';
        levelForm.question_count = Number(level?.prompt?.question_count ?? 10);
        levelForm.options_count = Number(level?.prompt?.options_count ?? 4);
        levelForm.prompt_locale = level?.prompt?.locale ?? locale.value;
        levelPreview.value = null;

        showLevelModal.value = true;
    }

    async function previewLevelPrompt() {
        if (!isLevelEditing.value) return;

        levelPreviewLoading.value = true;
        clearAlerts();

        try {
            const { data } = await window.axios.post(`/cms-api/levels/${levelForm.id}/prompt-preview`, {
                prompt: levelForm.prompt,
                question_count: Number(levelForm.question_count),
                options_count: Number(levelForm.options_count),
                locale: levelForm.prompt_locale,
            });

            levelPreview.value = data?.item ?? null;
            showSuccess('Mock preview generated and logged.');
            await loadOverview();
        } catch (error) {
            levelPreview.value = null;
            showError(error, 'Failed to preview level prompt.');
        } finally {
            levelPreviewLoading.value = false;
        }
    }

    async function saveLevel() {
        if (!isLevelEditing.value) return;

        levelSubmitting.value = true;
        clearAlerts();

        try {
            await window.axios.patch(`/cms-api/levels/${levelForm.id}`, {
                difficulty: levelForm.difficulty,
                passing_score: Number(levelForm.passing_score),
                xp_reward: Number(levelForm.xp_reward),
                time_limit: levelForm.time_limit === '' ? null : Number(levelForm.time_limit),
                active: !!levelForm.active,
            });

            await window.axios.patch(`/cms-api/levels/${levelForm.id}/prompt`, {
                prompt: levelForm.prompt,
                question_count: Number(levelForm.question_count),
                options_count: Number(levelForm.options_count),
                locale: levelForm.prompt_locale,
            });

            showSuccess('Level updated.');
            showLevelModal.value = false;
            levelPreview.value = null;
            await Promise.all([loadLevels(), loadOverview()]);
        } catch (error) {
            showError(error, 'Failed to save level settings.');
        } finally {
            levelSubmitting.value = false;
        }
    }

    return {
        levelsLoading,
        levelsState,
        showLevelModal,
        levelSubmitting,
        levelPreviewLoading,
        levelForm,
        levelPreview,
        isLevelEditing,
        loadLevels,
        openEditLevel,
        previewLevelPrompt,
        saveLevel,
    };
}
