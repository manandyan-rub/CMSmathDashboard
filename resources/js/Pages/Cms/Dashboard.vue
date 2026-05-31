<script setup>
import ExplanationsPage from './formulas/ExplanationsPage.vue';
import ExplanationModal from './formulas/partials/ExplanationModal.vue';
import FormulaModal from './formulas/partials/FormulaModal.vue';
import FormulasPage from './formulas/FormulasPage.vue';
import CmsSidebar from './layout/CmsSidebar.vue';
import CmsTopbar from './layout/CmsTopbar.vue';
import LevelModal from './levels/partials/LevelModal.vue';
import LevelsPage from './levels/LevelsPage.vue';
import ComingSoonSection from './shared/ComingSoonSection.vue';
import StatsPage from './stats/StatsPage.vue';
import UserModal from './users/partials/UserModal.vue';
import UsersPage from './users/UsersPage.vue';
import { useCmsDashboard } from './useCmsDashboard';

const {
    sections,
    roleOptions,
    formulaLocales,
    aiUsage,
    activeSection,
    locale,
    searchUsers,
    searchFormulas,
    searchExplanations,
    cacheFilter,
    globalLoading,
    usersLoading,
    formulasLoading,
    levelsLoading,
    explanationsLoading,
    errorMessage,
    successMessage,
    overview,
    usersState,
    formulasState,
    levelsState,
    explanationsState,
    showUserModal,
    userSubmitting,
    userForm,
    showFormulaModal,
    formulaSubmitting,
    formulaForm,
    showLevelModal,
    levelSubmitting,
    levelForm,
    showExplanationModal,
    explanationSubmitting,
    explanationLocaleLoading,
    explanationForm,
    selectedSectionLabel,
    newActionLabel,
    isUserEditing,
    isFormulaEditing,
    isLevelEditing,
    isUsersSection,
    isFormulasSection,
    isLevelsSection,
    isReportsSection,
    isOverviewSection,
    cacheFilterOptions,
    formulaCategoryOptions,
    topKpis,
    bootstrap,
    loadUsers,
    loadFormulas,
    loadExplanations,
    loadLevels,
    openCreateUser,
    openEditUser,
    saveUser,
    deleteUser,
    openCreateFormula,
    openEditFormula,
    saveFormula,
    deleteFormula,
    handleNewAction,
    openEditExplanation,
    changeExplanationLocale,
    addExplanationStep,
    removeExplanationStep,
    saveExplanation,
    openEditLevel,
    saveLevel,
    formatDate,
    renderFormulaMarkup,
    logout,
} = useCmsDashboard();
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-slate-950 text-slate-100">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_0%_0%,rgba(99,102,241,0.22),transparent_38%),radial-gradient(circle_at_100%_0%,rgba(16,185,129,0.18),transparent_28%),radial-gradient(circle_at_50%_100%,rgba(14,165,233,0.12),transparent_32%)]"></div>

        <div class="relative z-10 mx-auto flex min-h-screen max-w-[1900px]">
            <CmsSidebar
                v-model:active-section="activeSection"
                :sections="sections"
                :ai-usage="aiUsage"
                @logout="logout"
            />

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <CmsTopbar
                    v-model:locale="locale"
                    :selected-section-label="selectedSectionLabel"
                    :new-action-label="newActionLabel"
                    @refresh="bootstrap"
                    @new-action="handleNewAction"
                />

                <div v-if="errorMessage" class="mb-4 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                    {{ errorMessage }}
                </div>

                <div
                    v-if="successMessage"
                    class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200"
                >
                    {{ successMessage }}
                </div>

                <StatsPage
                    v-if="isOverviewSection"
                    :overview="overview"
                    :top-kpis="topKpis"
                    :render-formula-markup="renderFormulaMarkup"
                    @open-formulas="activeSection = 'formulas'"
                />

                <UsersPage
                    v-else-if="isUsersSection"
                    v-model:search="searchUsers"
                    :users-state="usersState"
                    :users-loading="usersLoading"
                    :format-date="formatDate"
                    @search="loadUsers(1)"
                    @create="openCreateUser"
                    @edit="openEditUser"
                    @delete="deleteUser"
                    @page="loadUsers"
                />

                <FormulasPage
                    v-else-if="isFormulasSection"
                    v-model:search="searchFormulas"
                    :formulas-state="formulasState"
                    :formulas-loading="formulasLoading"
                    :locale="locale"
                    :format-date="formatDate"
                    :render-formula-markup="renderFormulaMarkup"
                    @search="loadFormulas(1)"
                    @create="openCreateFormula"
                    @edit="openEditFormula"
                    @delete="deleteFormula"
                    @page="loadFormulas"
                />

                <ExplanationsPage
                    v-else-if="isReportsSection"
                    v-model:search="searchExplanations"
                    v-model:cache-filter="cacheFilter"
                    :explanations-state="explanationsState"
                    :explanations-loading="explanationsLoading"
                    :cache-filter-options="cacheFilterOptions"
                    :locale="locale"
                    :render-formula-markup="renderFormulaMarkup"
                    @search="loadExplanations(1)"
                    @reload="loadExplanations(1)"
                    @edit="openEditExplanation"
                    @page="loadExplanations"
                />

                <LevelsPage
                    v-else-if="isLevelsSection"
                    :levels-state="levelsState"
                    :levels-loading="levelsLoading"
                    @reload="loadLevels"
                    @edit="openEditLevel"
                />

                <ComingSoonSection v-else :selected-section-label="selectedSectionLabel" />

                <div v-if="globalLoading" class="mt-4 text-sm text-slate-400">Syncing CMS data...</div>
            </main>
        </div>

        <UserModal
            :show="showUserModal"
            :is-editing="isUserEditing"
            :form="userForm"
            :role-options="roleOptions"
            :submitting="userSubmitting"
            @close="showUserModal = false"
            @save="saveUser"
        />

        <FormulaModal
            :show="showFormulaModal"
            :is-editing="isFormulaEditing"
            :form="formulaForm"
            :formula-locales="formulaLocales"
            :formula-category-options="formulaCategoryOptions"
            :submitting="formulaSubmitting"
            :render-formula-markup="renderFormulaMarkup"
            @close="showFormulaModal = false"
            @save="saveFormula"
        />

        <ExplanationModal
            :show="showExplanationModal"
            :form="explanationForm"
            :submitting="explanationSubmitting"
            :locale-loading="explanationLocaleLoading"
            :formula-locales="formulaLocales"
            :render-formula-markup="renderFormulaMarkup"
            @close="showExplanationModal = false"
            @save="saveExplanation"
            @change-locale="changeExplanationLocale"
            @add-step="addExplanationStep"
            @remove-step="removeExplanationStep"
        />

        <LevelModal
            :show="showLevelModal"
            :form="levelForm"
            :formula-locales="formulaLocales"
            :submitting="levelSubmitting"
            :is-editing="isLevelEditing"
            @close="showLevelModal = false"
            @save="saveLevel"
        />
    </div>
</template>

