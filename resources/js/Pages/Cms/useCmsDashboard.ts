import { computed, onMounted, ref, watch } from 'vue';
import {
    aiUsage,
    cacheFilterOptions,
    cmsSections,
    formulaLocales,
    roleOptions,
} from './data/navData';
import { useFormulaExplanations } from './formulas/useFormulaExplanations';
import { useFormulaLibrary } from './formulas/useFormulaLibrary';
import { useLevels } from './levels/useLevels';
import { useAlerts } from './shared/useAlerts';
import { useStats } from './stats/useStats';
import { useUsers } from './users/useUsers';
import { renderFormulaMarkup } from './utils/formulaRenderer';

export function useCmsDashboard() {
    const activeSection = ref('overview');
    const locale = ref('en');
    const globalLoading = ref(false);

    const alerts = useAlerts();
    const stats = useStats({
        locale,
        showError: alerts.showError,
    });
    const users = useUsers({
        clearAlerts: alerts.clearAlerts,
        showError: alerts.showError,
        showSuccess: alerts.showSuccess,
        loadOverview: stats.loadOverview,
    });
    const formulas = useFormulaLibrary({
        locale,
        clearAlerts: alerts.clearAlerts,
        showError: alerts.showError,
        showSuccess: alerts.showSuccess,
        loadOverview: stats.loadOverview,
    });
    const explanations = useFormulaExplanations({
        locale,
        clearAlerts: alerts.clearAlerts,
        showError: alerts.showError,
        showSuccess: alerts.showSuccess,
    });
    const levels = useLevels({
        locale,
        clearAlerts: alerts.clearAlerts,
        showError: alerts.showError,
        showSuccess: alerts.showSuccess,
        loadOverview: stats.loadOverview,
    });

    const selectedSectionLabel = computed(() => {
        return cmsSections.find((item) => item.key === activeSection.value)?.label ?? 'Overview';
    });
    const isUsersSection = computed(() => activeSection.value === 'users');
    const isFormulasSection = computed(() => activeSection.value === 'formulas');
    const isLevelsSection = computed(() => activeSection.value === 'levels');
    const isReportsSection = computed(() => activeSection.value === 'reports');
    const isOverviewSection = computed(() => activeSection.value === 'overview');
    const newActionLabel = computed(() => {
        if (isUsersSection.value) return 'New user';
        if (isFormulasSection.value) return 'New formula';
        if (isLevelsSection.value) return 'Reload levels';
        if (isReportsSection.value) return 'Reload explanations';
        return 'New module';
    });

    async function bootstrap() {
        alerts.clearAlerts();
        globalLoading.value = true;
        await Promise.all([
            stats.loadOverview(),
            users.loadUsers(1),
            formulas.loadFormulas(1),
            formulas.loadFormulaCategories(),
            explanations.loadExplanations(1),
            levels.loadLevels(),
        ]);
        globalLoading.value = false;
    }

    function handleNewAction() {
        if (isUsersSection.value) {
            users.openCreateUser();
            return;
        }

        if (isFormulasSection.value) {
            formulas.openCreateFormula();
            return;
        }

        if (isLevelsSection.value) {
            levels.loadLevels();
            return;
        }

        if (isReportsSection.value) {
            explanations.loadExplanations();
        }
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    async function logout() {
        try {
            await window.axios.post('/logout');
        } finally {
            window.location.href = '/login';
        }
    }

    watch(locale, async () => {
        await Promise.all([
            stats.loadOverview(),
            formulas.loadFormulas(1),
            explanations.loadExplanations(1),
            levels.loadLevels(),
        ]);
    });

    onMounted(() => {
        bootstrap();
    });

    return {
        sections: cmsSections,
        roleOptions,
        formulaLocales,
        aiUsage,
        cacheFilterOptions,
        activeSection,
        locale,
        globalLoading,
        selectedSectionLabel,
        newActionLabel,
        isUsersSection,
        isFormulasSection,
        isLevelsSection,
        isReportsSection,
        isOverviewSection,
        formatDate,
        renderFormulaMarkup,
        bootstrap,
        handleNewAction,
        logout,
        ...alerts,
        ...stats,
        ...users,
        ...formulas,
        ...explanations,
        ...levels,
    };
}
