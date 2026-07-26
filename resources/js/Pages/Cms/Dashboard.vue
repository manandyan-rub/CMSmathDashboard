<script setup lang="ts">
import { computed } from 'vue';
import AiPromptStudio from './ai/AiPromptStudio.vue';
import AiUsagePage from './ai/AiUsagePage.vue';
import ExerciseStudioPage from './exercise-studio/ExerciseStudioPage.vue';
import FormulaExplanationsManager from './formulas/FormulaExplanationsManager.vue';
import HallOfFamePage from './gamification/HallOfFamePage.vue';
import GenerationLogsManager from './generation-logs/GenerationLogsManager.vue';
import CmsSidebar from './layout/CmsSidebar.vue';
import CmsTopbar from './layout/CmsTopbar.vue';
import LevelPromptsPage from './levels/LevelPromptsPage.vue';
import LocalizationPage from './localization/LocalizationPage.vue';
import AuditLogPage from './platform/AuditLogPage.vue';
import PlatformSettingsPage from './platform/PlatformSettingsPage.vue';
import ExportsPage from './platform/ExportsPage.vue';
import ResourceManagerPage from './resources/ResourceManagerPage.vue';
import AccountSettingsPage from './settings/AccountSettingsPage.vue';
import EntityAnalyticsPanel from './shared/EntityAnalyticsPanel.vue';
import StatsPage from './stats/StatsPage.vue';
import UsersPage from './users/UsersPage.vue';
import { useCmsDashboard } from './useCmsDashboard';

const {
    activeSection,
    locale,
    theme,
    themeClass,
    meta,
    loading,
    dashboardLoading,
    dashboard,
    dateFilter,
    errorMessage,
    successMessage,
    refreshToken,
    searchQuery,
    searchGroups,
    searchLoading,
    navigationGroups,
    groupLabel,
    sectionLabel,
    resourceKey,
    t,
    showError,
    showSuccess,
    clearAlerts,
    bootstrap,
    loadDashboard,
    refreshCurrentSection,
    selectSearchResult,
    toggleTheme,
    logout,
} = useCmsDashboard();

const userRole = computed(() => {
    if (activeSection.value === 'lecturers') return 'lecturer';
    if (activeSection.value === 'administrators') return 'admin';
    return 'student';
});

const aiUsage = computed(() => ({
    generations: dashboard.value.ai_usage?.generations ?? dashboard.value.summary?.ai_generations ?? 0,
    failed: dashboard.value.ai_usage?.failed ?? dashboard.value.summary?.ai_generation_failures ?? 0,
    success_rate: dashboard.value.ai_usage?.success_rate ?? 0,
}));

function setTheme(value: string) {
    theme.value = value === 'light' ? 'light' : 'dark';
}
</script>

<template>
    <div class="cms-theme-shell min-h-screen bg-[#06111d] text-slate-100" :class="themeClass">
        <div class="mx-auto flex min-h-screen max-w-[1920px]">
            <CmsSidebar
                v-model:active-section="activeSection"
                :groups="navigationGroups"
                :ai-usage="aiUsage"
                :current-user="meta.user"
                :t="t"
                @logout="logout"
            />

            <main class="min-w-0 flex-1 px-4 py-4 sm:px-6 lg:px-8">
                <CmsTopbar
                    v-model:locale="locale"
                    v-model:search-query="searchQuery"
                    :group-label="groupLabel"
                    :section-label="sectionLabel"
                    :locales="meta.locales"
                    :theme="theme"
                    :search-groups="searchGroups"
                    :search-loading="searchLoading"
                    :t="t"
                    @refresh="refreshCurrentSection"
                    @toggle-theme="toggleTheme"
                    @select-search="selectSearchResult"
                />

                <div v-if="errorMessage" class="mb-4 rounded-lg border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                    {{ errorMessage }}
                </div>
                <div v-if="successMessage" class="mb-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                    {{ successMessage }}
                </div>

                <div v-if="loading" class="grid min-h-[60vh] place-items-center text-sm text-slate-500">
                    {{ t('common.loading') }}
                </div>

                <StatsPage
                    v-else-if="activeSection === 'dashboard'"
                    :dashboard="dashboard"
                    :loading="dashboardLoading"
                    :date-filter="dateFilter"
                    :t="t"
                    @reload="loadDashboard"
                />

                <UsersPage
                    v-else-if="['students', 'lecturers', 'administrators'].includes(activeSection)"
                    :role="userRole"
                    :locale="locale"
                    :countries="meta.countries"
                    :locales="meta.locales"
                    :permissions="meta.permissions"
                    :granted-permissions="meta.user.permissions"
                    :refresh-token="refreshToken"
                    :t="t"
                    :show-error="showError"
                    :show-success="showSuccess"
                />

                <LevelPromptsPage
                    v-else-if="activeSection === 'level-prompts'"
                    :locale="locale"
                    :locales="meta.locales"
                    :refresh-token="refreshToken"
                    :can-manage="meta.user.permissions.includes('levels.manage')"
                    :clear-alerts="clearAlerts"
                    :show-error="showError"
                    :show-success="showSuccess"
                    :reload-dashboard="loadDashboard"
                />

                <FormulaExplanationsManager
                    v-else-if="activeSection === 'formula-explanations'"
                    :locale="locale"
                    :locales="meta.locales"
                    :refresh-token="refreshToken"
                    :can-manage="meta.user.permissions.includes('formulas.manage')"
                    :clear-alerts="clearAlerts"
                    :show-error="showError"
                    :show-success="showSuccess"
                />

                <ExerciseStudioPage
                    v-else-if="['documents', 'moderation'].includes(activeSection)"
                    :mode="activeSection === 'moderation' ? 'moderation' : 'documents'"
                    :refresh-token="refreshToken"
                    :can-moderate="meta.user.permissions.includes('exercise_documents.moderate')"
                    :t="t"
                    :show-error="showError"
                    :show-success="showSuccess"
                />

                <GenerationLogsManager
                    v-else-if="activeSection === 'generation-logs'"
                    :refresh-token="refreshToken"
                    :clear-alerts="clearAlerts"
                    :show-error="showError"
                />

                <template v-else-if="activeSection === 'ai-prompts'">
                    <ResourceManagerPage
                        resource-key="ai-prompts"
                        :locale="locale"
                        :refresh-token="refreshToken"
                        :permissions="meta.user.permissions"
                        :t="t"
                        :show-error="showError"
                        :show-success="showSuccess"
                    />
                    <AiPromptStudio
                        :refresh-token="refreshToken"
                        :permissions="meta.user.permissions"
                        :show-error="showError"
                        :show-success="showSuccess"
                    />
                </template>

                <template v-else-if="activeSection === 'levels'">
                    <ResourceManagerPage
                        resource-key="levels"
                        :locale="locale"
                        :refresh-token="refreshToken"
                        :permissions="meta.user.permissions"
                        :t="t"
                        :show-error="showError"
                        :show-success="showSuccess"
                    />
                    <EntityAnalyticsPanel type="levels" :refresh-token="refreshToken" :show-error="showError" />
                </template>

                <AiUsagePage
                    v-else-if="['ai-usage', 'ai-failures'].includes(activeSection)"
                    :failures-only="activeSection === 'ai-failures'"
                    :refresh-token="refreshToken"
                    :permissions="meta.user.permissions"
                    :t="t"
                    :show-error="showError"
                    :show-success="showSuccess"
                />

                <LocalizationPage
                    v-else-if="['translations', 'missing-translations'].includes(activeSection)"
                    :missing-only="activeSection === 'missing-translations'"
                    :refresh-token="refreshToken"
                    :can-manage="meta.user.permissions.includes('translations.manage')"
                    :t="t"
                    :show-error="showError"
                    :show-success="showSuccess"
                />

                <HallOfFamePage
                    v-else-if="activeSection === 'hall-of-fame'"
                    :refresh-token="refreshToken"
                    :t="t"
                    :show-error="showError"
                />

                <template v-else-if="activeSection === 'games'">
                    <ResourceManagerPage
                        resource-key="games"
                        :locale="locale"
                        :refresh-token="refreshToken"
                        :permissions="meta.user.permissions"
                        :t="t"
                        :show-error="showError"
                        :show-success="showSuccess"
                    />
                    <EntityAnalyticsPanel type="games" :refresh-token="refreshToken" :show-error="showError" />
                </template>

                <template v-else-if="activeSection === 'settings'">
                    <AccountSettingsPage
                        :theme="theme"
                        :clear-alerts="clearAlerts"
                        :show-error="showError"
                        :show-success="showSuccess"
                        :set-theme="setTheme"
                    />
                    <div class="my-6 border-t border-slate-800"></div>
                    <PlatformSettingsPage
                        :refresh-token="refreshToken"
                        :can-manage="meta.user.permissions.includes('settings.manage')"
                        :t="t"
                        :show-error="showError"
                        :show-success="showSuccess"
                    />
                </template>

                <AuditLogPage
                    v-else-if="activeSection === 'audit-log'"
                    :refresh-token="refreshToken"
                    :t="t"
                    :show-error="showError"
                />

                <ExportsPage
                    v-else-if="activeSection === 'exports'"
                    :permissions="meta.user.permissions"
                    :show-error="showError"
                />

                <ResourceManagerPage
                    v-else-if="resourceKey"
                    :resource-key="resourceKey"
                    :locale="locale"
                    :refresh-token="refreshToken"
                    :permissions="meta.user.permissions"
                    :t="t"
                    :show-error="showError"
                    :show-success="showSuccess"
                />

                <div v-else class="rounded-lg border border-slate-800 p-8 text-center text-slate-500">
                    {{ t('common.empty') }}
                </div>
            </main>
        </div>
    </div>
</template>
