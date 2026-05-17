<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import {
    Activity,
    BookOpen,
    CheckCircle2,
    ChevronRight,
    GraduationCap,
    LayoutDashboard,
    LibraryBig,
    ListChecks,
    LogOut,
    Plus,
    RefreshCcw,
    Search,
    Settings,
    ShieldCheck,
    Sigma,
    Sparkles,
    SquarePen,
    Target,
    Trash2,
    Trophy,
    UsersRound,
    X,
} from '@lucide/vue';

const sections = [
    { key: 'overview', label: 'Overview', icon: LayoutDashboard },
    { key: 'users', label: 'Users', icon: UsersRound },
    { key: 'formulas', label: 'Formulas', icon: LibraryBig },
    { key: 'levels', label: 'Math Levels', icon: ListChecks },
    { key: 'reports', label: 'Reports', icon: Activity },
    { key: 'settings', label: 'Settings', icon: Settings },
];

const roleOptions = ['admin', 'lecturer', 'student'];
const formulaLocales = ['en', 'hy', 'ru', 'fr'];

const activeSection = ref('overview');
const locale = ref('en');
const searchUsers = ref('');
const searchFormulas = ref('');

const globalLoading = ref(false);
const usersLoading = ref(false);
const formulasLoading = ref(false);
const levelsLoading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const overview = reactive({
    stats: {
        users: 0,
        admins: 0,
        lecturers: 0,
        students: 0,
        formulas: 0,
        paths: 0,
        levels: 0,
    },
    recent_users: [],
    recent_formulas: [],
});

const usersState = reactive({
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

const levelsState = reactive({
    items: [],
});

const showUserModal = ref(false);
const userSubmitting = ref(false);
const userForm = reactive({
    id: null,
    name: '',
    email: '',
    role: 'student',
    password: '',
});

const showFormulaModal = ref(false);
const formulaSubmitting = ref(false);
const formulaForm = reactive({
    id: null,
    category: '',
    formula: '',
    example: '',
    translations: buildEmptyTranslations(),
});

const showLevelModal = ref(false);
const levelSubmitting = ref(false);
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

const selectedSectionLabel = computed(() => {
    return sections.find((item) => item.key === activeSection.value)?.label ?? 'Overview';
});

const newActionLabel = computed(() => {
    if (isUsersSection.value) return 'New user';
    if (isFormulasSection.value) return 'New formula';
    if (isLevelsSection.value) return 'Reload levels';
    return 'New module';
});

const isUserEditing = computed(() => userForm.id !== null);
const isFormulaEditing = computed(() => formulaForm.id !== null);
const isLevelEditing = computed(() => levelForm.id !== null);
const isUsersSection = computed(() => activeSection.value === 'users');
const isFormulasSection = computed(() => activeSection.value === 'formulas');
const isLevelsSection = computed(() => activeSection.value === 'levels');
const isOverviewSection = computed(() => activeSection.value === 'overview');

const topKpis = computed(() => [
    {
        title: 'Users',
        value: overview.stats.users,
        hint: `${overview.stats.admins} admin • ${overview.stats.lecturers} lecturer`,
        tone: 'from-emerald-500/20 to-emerald-500/5 border-emerald-400/20',
        icon: UsersRound,
    },
    {
        title: 'Formulas',
        value: overview.stats.formulas,
        hint: `${overview.stats.paths} paths • ${overview.stats.levels} levels`,
        tone: 'from-blue-500/20 to-blue-500/5 border-blue-400/20',
        icon: Sigma,
    },
    {
        title: 'Student Ratio',
        value:
            overview.stats.users > 0
                ? `${Math.round((overview.stats.students / overview.stats.users) * 100)}%`
                : '0%',
        hint: `${overview.stats.students} students`,
        tone: 'from-amber-500/20 to-amber-500/5 border-amber-400/20',
        icon: GraduationCap,
    },
    {
        title: 'Health',
        value:
            overview.stats.formulas > 0
                ? `${Math.min(100, Math.round((overview.stats.formulas / Math.max(1, overview.stats.levels)) * 10))}%`
                : '0%',
        hint: 'Content coverage',
        tone: 'from-violet-500/20 to-violet-500/5 border-violet-400/20',
        icon: Target,
    },
]);

function buildEmptyTranslations() {
    return {
        en: { name: '', description: '' },
        hy: { name: '', description: '' },
        ru: { name: '', description: '' },
        fr: { name: '', description: '' },
    };
}

function clearAlerts() {
    errorMessage.value = '';
    successMessage.value = '';
}

function showError(error, fallback) {
    errorMessage.value =
        error?.response?.data?.message ||
        error?.response?.data?.error ||
        error?.message ||
        fallback;
}

function showSuccess(text) {
    successMessage.value = text;
    setTimeout(() => {
        if (successMessage.value === text) {
            successMessage.value = '';
        }
    }, 2400);
}

async function loadOverview() {
    try {
        const { data } = await window.axios.get('/cms-api/overview', {
            params: { locale: locale.value },
        });

        overview.stats = {
            ...overview.stats,
            ...(data?.stats ?? {}),
        };
        overview.recent_users = data?.recent_users ?? [];
        overview.recent_formulas = data?.recent_formulas ?? [];
    } catch (error) {
        showError(error, 'Failed to load overview data.');
    }
}

async function loadUsers(page = usersState.pagination.page || 1) {
    usersLoading.value = true;
    try {
        const { data } = await window.axios.get('/cms-api/users', {
            params: {
                page,
                search: searchUsers.value,
            },
        });

        usersState.items = data?.items ?? [];
        usersState.pagination = {
            ...usersState.pagination,
            ...(data?.pagination ?? {}),
        };
    } catch (error) {
        showError(error, 'Failed to load users.');
    } finally {
        usersLoading.value = false;
    }
}

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

async function bootstrap() {
    clearAlerts();
    globalLoading.value = true;
    await Promise.all([loadOverview(), loadUsers(1), loadFormulas(1), loadLevels()]);
    globalLoading.value = false;
}

function openCreateUser() {
    clearAlerts();
    userForm.id = null;
    userForm.name = '';
    userForm.email = '';
    userForm.role = 'student';
    userForm.password = '';
    showUserModal.value = true;
}

function openEditUser(user) {
    clearAlerts();
    userForm.id = user.id;
    userForm.name = user.name;
    userForm.email = user.email;
    userForm.role = user.role;
    userForm.password = '';
    showUserModal.value = true;
}

async function saveUser() {
    userSubmitting.value = true;
    clearAlerts();

    const payload = {
        name: userForm.name,
        email: userForm.email,
        role: userForm.role,
        password: userForm.password,
    };

    try {
        if (isUserEditing.value) {
            await window.axios.patch(`/cms-api/users/${userForm.id}`, payload);
            showSuccess('User updated.');
        } else {
            await window.axios.post('/cms-api/users', payload);
            showSuccess('User created.');
        }

        showUserModal.value = false;
        await Promise.all([loadUsers(usersState.pagination.page), loadOverview()]);
    } catch (error) {
        showError(error, 'Failed to save user.');
    } finally {
        userSubmitting.value = false;
    }
}

async function deleteUser(user) {
    clearAlerts();
    const ok = window.confirm(`Delete user "${user.name}"?`);
    if (!ok) return;

    try {
        await window.axios.delete(`/cms-api/users/${user.id}`);
        showSuccess('User deleted.');
        await Promise.all([loadUsers(usersState.pagination.page), loadOverview()]);
    } catch (error) {
        showError(error, 'Failed to delete user.');
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
        await Promise.all([loadFormulas(formulasState.pagination.page), loadOverview()]);
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
        await Promise.all([loadFormulas(formulasState.pagination.page), loadOverview()]);
    } catch (error) {
        showError(error, 'Failed to delete formula.');
    }
}

function handleNewAction() {
    if (isUsersSection.value) {
        openCreateUser();
        return;
    }

    if (isFormulasSection.value) {
        openCreateFormula();
        return;
    }

    if (isLevelsSection.value) {
        loadLevels();
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

    showLevelModal.value = true;
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
        await Promise.all([loadLevels(), loadOverview()]);
    } catch (error) {
        showError(error, 'Failed to save level settings.');
    } finally {
        levelSubmitting.value = false;
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
    await Promise.all([loadOverview(), loadFormulas(1), loadLevels()]);
});

onMounted(() => {
    bootstrap();
});
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-slate-950 text-slate-100">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_0%_0%,rgba(99,102,241,0.22),transparent_38%),radial-gradient(circle_at_100%_0%,rgba(16,185,129,0.18),transparent_28%),radial-gradient(circle_at_50%_100%,rgba(14,165,233,0.12),transparent_32%)]"></div>

        <div class="relative z-10 mx-auto flex min-h-screen max-w-[1900px]">
            <aside class="hidden w-72 shrink-0 border-r border-slate-800 bg-gradient-to-b from-slate-950 via-[#07251f] to-slate-950 p-5 lg:flex lg:flex-col">
                <div class="mb-8 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-400 text-slate-950 shadow-lg shadow-amber-500/30">
                        <Sigma class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-lg font-extrabold tracking-tight text-white">MathDashboard CMS</p>
                        <p class="text-xs text-emerald-200/70">Content command</p>
                    </div>
                </div>

                <nav class="space-y-1.5">
                    <button
                        v-for="section in sections"
                        :key="section.key"
                        class="flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition"
                        :class="
                            activeSection === section.key
                                ? 'bg-emerald-500/20 text-emerald-100 ring-1 ring-emerald-400/40'
                                : 'text-slate-300 hover:bg-slate-800/50 hover:text-white'
                        "
                        type="button"
                        @click="activeSection = section.key"
                    >
                        <component :is="section.icon" class="h-4 w-4" />
                        {{ section.label }}
                    </button>
                </nav>

                <div class="mt-6 rounded-2xl border border-emerald-400/25 bg-emerald-500/10 p-4">
                    <div class="mb-2 flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-emerald-100">
                        <span>My sprint</span>
                        <span>78%</span>
                    </div>
                    <p class="text-sm font-semibold text-white">Curriculum release</p>
                    <p class="mt-1 text-xs text-emerald-200/80">12 lessons, 4 quizzes, 2 diagnostics queued.</p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-900/80">
                        <div class="h-full w-[78%] rounded-full bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
                    </div>
                </div>

                <div class="mt-auto rounded-2xl border border-slate-800 bg-slate-900/80 p-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-300/20 font-bold text-emerald-200">M</div>
                        <div>
                            <p class="text-sm font-semibold text-white">Math admin</p>
                            <p class="text-xs text-slate-400">Owner workspace</p>
                        </div>
                    </div>
                    <button
                        class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-300 transition hover:border-slate-500 hover:bg-slate-800"
                        type="button"
                        @click="logout"
                    >
                        <LogOut class="h-4 w-4" />
                        Logout
                    </button>
                </div>
            </aside>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <header class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-800 bg-slate-900/75 px-4 py-3 sm:px-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-300">CMS workspace</p>
                        <h1 class="text-xl font-extrabold tracking-tight text-white sm:text-2xl">{{ selectedSectionLabel }}</h1>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="relative">
                            <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                            <input
                                type="text"
                                class="rounded-lg border border-slate-700 bg-slate-900 py-2 pl-9 pr-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none"
                                placeholder="Search lessons, cohorts, owners"
                            />
                        </label>
                        <select
                            v-model="locale"
                            class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm font-medium text-slate-200"
                        >
                            <option value="en">English</option>
                            <option value="hy">Armenian</option>
                            <option value="ru">Russian</option>
                            <option value="fr">French</option>
                        </select>
                        <button
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-300 hover:bg-slate-800"
                            type="button"
                            @click="bootstrap"
                        >
                            <RefreshCcw class="h-4 w-4" />
                            Refresh
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400"
                            @click="handleNewAction"
                        >
                            <Plus class="h-4 w-4" />
                            {{ newActionLabel }}
                        </button>
                    </div>
                </header>

                <div v-if="errorMessage" class="mb-4 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                    {{ errorMessage }}
                </div>

                <div
                    v-if="successMessage"
                    class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200"
                >
                    {{ successMessage }}
                </div>

                <section v-if="isOverviewSection" class="space-y-5">
                    <div class="grid gap-4 xl:grid-cols-[2fr_1fr]">
                        <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-emerald-300">Math content operations</p>
                            <h2 class="mt-2 max-w-3xl text-3xl font-black leading-tight text-white">
                                Plan, review, and publish learning paths behind MathDashboard.
                            </h2>
                            <p class="mt-3 max-w-3xl text-sm text-slate-400">
                                Manage users, formula libraries, and learning tracks from one editorial command center.
                            </p>
                            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                <article
                                    v-for="card in topKpis"
                                    :key="card.title"
                                    class="rounded-xl border bg-gradient-to-br p-4"
                                    :class="card.tone"
                                >
                                    <div class="mb-2 flex items-center justify-between">
                                        <p class="text-xs font-semibold uppercase text-slate-300">{{ card.title }}</p>
                                        <component :is="card.icon" class="h-4 w-4 text-slate-200" />
                                    </div>
                                    <p class="text-3xl font-black text-white">{{ card.value }}</p>
                                    <p class="mt-1 text-xs text-slate-300">{{ card.hint }}</p>
                                </article>
                            </div>
                        </article>

                        <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5">
                            <p class="text-xs font-bold uppercase tracking-wide text-emerald-300">Formula Focus</p>
                            <div class="mt-3 space-y-2">
                                <div class="rounded-lg border border-slate-700 bg-slate-900 p-3">
                                    <p class="text-xs font-semibold uppercase text-slate-400">Algebra</p>
                                    <p class="mt-1 font-mono text-sm text-blue-300">ax + b = c</p>
                                </div>
                                <div class="rounded-lg border border-slate-700 bg-slate-900 p-3">
                                    <p class="text-xs font-semibold uppercase text-slate-400">Geometry</p>
                                    <p class="mt-1 font-mono text-sm text-emerald-300">A = pi r^2</p>
                                </div>
                                <div class="rounded-lg border border-slate-700 bg-slate-900 p-3">
                                    <p class="text-xs font-semibold uppercase text-slate-400">Probability</p>
                                    <p class="mt-1 font-mono text-sm text-amber-300">P(A | B)</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="mb-1 flex items-center justify-between text-xs text-slate-400">
                                    <span>Content health mix</span>
                                    <span class="font-semibold text-amber-300">94%</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-800">
                                    <div class="h-full w-[38%] bg-emerald-500"></div>
                                    <div class="-mt-2 h-2 w-[28%] translate-x-[38%] bg-blue-500"></div>
                                    <div class="-mt-2 h-2 w-[20%] translate-x-[66%] bg-amber-500"></div>
                                    <div class="-mt-2 h-2 w-[14%] translate-x-[86%] bg-rose-500"></div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-[2fr_1fr]">
                        <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-300">Recent users</p>
                                    <h3 class="text-lg font-bold text-white">Learner and staff activity</h3>
                                </div>
                                <UsersRound class="h-5 w-5 text-emerald-300" />
                            </div>
                            <div class="space-y-2">
                                <div
                                    v-for="item in overview.recent_users"
                                    :key="item.id"
                                    class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2"
                                >
                                    <div>
                                        <p class="text-sm font-semibold text-white">{{ item.name }}</p>
                                        <p class="text-xs text-slate-400">{{ item.email }}</p>
                                    </div>
                                    <span class="rounded-full bg-slate-800 px-2 py-1 text-xs uppercase text-slate-300">{{ item.role }}</span>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-300">Recent formulas</p>
                                    <h3 class="text-lg font-bold text-white">Curriculum updates</h3>
                                </div>
                                <BookOpen class="h-5 w-5 text-emerald-300" />
                            </div>
                            <div class="space-y-2">
                                <div
                                    v-for="item in overview.recent_formulas"
                                    :key="item.id"
                                    class="rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2"
                                >
                                    <p class="text-sm font-semibold text-white">{{ item.name || item.category }}</p>
                                    <p class="line-clamp-2 text-xs text-slate-400">{{ item.formula }}</p>
                                    <button
                                        type="button"
                                        class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-emerald-300 hover:text-emerald-200"
                                        @click="activeSection = 'formulas'"
                                    >
                                        Open in curriculum
                                        <ChevronRight class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <section v-else-if="isUsersSection" class="space-y-4">
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-white">Users Management</h2>
                                <p class="text-sm text-slate-400">Create, edit, and remove platform members.</p>
                            </div>
                            <button
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400"
                                type="button"
                                @click="openCreateUser"
                            >
                                <Plus class="h-4 w-4" />
                                Add user
                            </button>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <label class="relative min-w-[220px] flex-1">
                                <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-500" />
                                <input
                                    v-model="searchUsers"
                                    type="search"
                                    class="w-full rounded-lg border border-slate-700 bg-slate-950 py-2 pl-9 pr-3 text-sm text-white placeholder:text-slate-500"
                                    placeholder="Search users by name/email/role"
                                    @keyup.enter="loadUsers(1)"
                                />
                            </label>
                            <button
                                class="rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800"
                                type="button"
                                @click="loadUsers(1)"
                            >
                                Search
                            </button>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/75">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-800 text-sm">
                                <thead class="bg-slate-900">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-400">Name</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-400">Email</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-400">Role</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-400">Created</th>
                                        <th class="px-4 py-3 text-right font-semibold text-slate-400">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="usersLoading">
                                        <td class="px-4 py-4 text-slate-500" colspan="5">Loading users...</td>
                                    </tr>
                                    <tr v-else-if="usersState.items.length === 0">
                                        <td class="px-4 py-4 text-slate-500" colspan="5">No users found.</td>
                                    </tr>
                                    <tr v-for="user in usersState.items" v-else :key="user.id" class="border-t border-slate-800">
                                        <td class="px-4 py-3 font-medium text-white">{{ user.name }}</td>
                                        <td class="px-4 py-3 text-slate-300">{{ user.email }}</td>
                                        <td class="px-4 py-3">
                                            <span class="rounded-full bg-slate-800 px-2.5 py-1 text-xs font-semibold uppercase text-slate-300">
                                                {{ user.role }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-400">{{ formatDate(user.created_at) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="inline-flex items-center gap-1.5">
                                                <button
                                                    class="rounded-lg border border-slate-700 px-2 py-1 text-xs font-semibold text-slate-200 hover:bg-slate-800"
                                                    type="button"
                                                    @click="openEditUser(user)"
                                                >
                                                    <SquarePen class="h-3.5 w-3.5" />
                                                </button>
                                                <button
                                                    class="rounded-lg border border-rose-400/40 px-2 py-1 text-xs font-semibold text-rose-300 hover:bg-rose-500/10"
                                                    type="button"
                                                    @click="deleteUser(user)"
                                                >
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-800 px-4 py-3 text-sm">
                            <p class="text-slate-400">
                                Showing {{ usersState.pagination.from || 0 }}-{{ usersState.pagination.to || 0 }} of
                                {{ usersState.pagination.total }} users
                            </p>
                            <div class="inline-flex gap-2">
                                <button
                                    class="rounded-lg border border-slate-700 px-3 py-1.5 font-semibold text-slate-200 disabled:cursor-not-allowed disabled:opacity-40"
                                    type="button"
                                    :disabled="usersState.pagination.page <= 1"
                                    @click="loadUsers(usersState.pagination.page - 1)"
                                >
                                    Prev
                                </button>
                                <button
                                    class="rounded-lg border border-slate-700 px-3 py-1.5 font-semibold text-slate-200 disabled:cursor-not-allowed disabled:opacity-40"
                                    type="button"
                                    :disabled="usersState.pagination.page >= usersState.pagination.last_page"
                                    @click="loadUsers(usersState.pagination.page + 1)"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <section v-else-if="isFormulasSection" class="space-y-4">
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-white">Formulas Management</h2>
                                <p class="text-sm text-slate-400">Manage formula records and locale translations.</p>
                            </div>
                            <button
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400"
                                type="button"
                                @click="openCreateFormula"
                            >
                                <Plus class="h-4 w-4" />
                                Add formula
                            </button>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <label class="relative min-w-[220px] flex-1">
                                <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-500" />
                                <input
                                    v-model="searchFormulas"
                                    type="search"
                                    class="w-full rounded-lg border border-slate-700 bg-slate-950 py-2 pl-9 pr-3 text-sm text-white placeholder:text-slate-500"
                                    placeholder="Search by formula, category, name"
                                    @keyup.enter="loadFormulas(1)"
                                />
                            </label>
                            <button
                                class="rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800"
                                type="button"
                                @click="loadFormulas(1)"
                            >
                                Search
                            </button>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/75">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-800 text-sm">
                                <thead class="bg-slate-900">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-400">Name ({{ locale.toUpperCase() }})</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-400">Category</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-400">Formula</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-400">Updated</th>
                                        <th class="px-4 py-3 text-right font-semibold text-slate-400">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="formulasLoading">
                                        <td class="px-4 py-4 text-slate-500" colspan="5">Loading formulas...</td>
                                    </tr>
                                    <tr v-else-if="formulasState.items.length === 0">
                                        <td class="px-4 py-4 text-slate-500" colspan="5">No formulas found.</td>
                                    </tr>
                                    <tr
                                        v-for="formula in formulasState.items"
                                        v-else
                                        :key="formula.id"
                                        class="border-t border-slate-800"
                                    >
                                        <td class="px-4 py-3 font-medium text-white">{{ formula.name || '-' }}</td>
                                        <td class="px-4 py-3 text-slate-300">{{ formula.category }}</td>
                                        <td class="max-w-[420px] truncate px-4 py-3 font-mono text-xs text-slate-300">{{ formula.formula }}</td>
                                        <td class="px-4 py-3 text-slate-400">{{ formatDate(formula.updated_at) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="inline-flex items-center gap-1.5">
                                                <button
                                                    class="rounded-lg border border-slate-700 px-2 py-1 text-xs font-semibold text-slate-200 hover:bg-slate-800"
                                                    type="button"
                                                    @click="openEditFormula(formula)"
                                                >
                                                    <SquarePen class="h-3.5 w-3.5" />
                                                </button>
                                                <button
                                                    class="rounded-lg border border-rose-400/40 px-2 py-1 text-xs font-semibold text-rose-300 hover:bg-rose-500/10"
                                                    type="button"
                                                    @click="deleteFormula(formula)"
                                                >
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-800 px-4 py-3 text-sm">
                            <p class="text-slate-400">
                                Showing {{ formulasState.pagination.from || 0 }}-{{ formulasState.pagination.to || 0 }} of
                                {{ formulasState.pagination.total }} formulas
                            </p>
                            <div class="inline-flex gap-2">
                                <button
                                    class="rounded-lg border border-slate-700 px-3 py-1.5 font-semibold text-slate-200 disabled:cursor-not-allowed disabled:opacity-40"
                                    type="button"
                                    :disabled="formulasState.pagination.page <= 1"
                                    @click="loadFormulas(formulasState.pagination.page - 1)"
                                >
                                    Prev
                                </button>
                                <button
                                    class="rounded-lg border border-slate-700 px-3 py-1.5 font-semibold text-slate-200 disabled:cursor-not-allowed disabled:opacity-40"
                                    type="button"
                                    :disabled="formulasState.pagination.page >= formulasState.pagination.last_page"
                                    @click="loadFormulas(formulasState.pagination.page + 1)"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <section v-else-if="isLevelsSection" class="space-y-4">
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-white">Math Levels Control</h2>
                                <p class="text-sm text-slate-400">Manage level pass score, XP, active status, and AI prompts.</p>
                            </div>
                            <button
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800"
                                type="button"
                                @click="loadLevels"
                            >
                                <RefreshCcw class="h-4 w-4" />
                                Reload levels
                            </button>
                        </div>
                    </div>

                    <div v-if="levelsLoading" class="rounded-xl border border-slate-800 bg-slate-900/75 px-4 py-3 text-sm text-slate-400">
                        Loading levels...
                    </div>

                    <div v-else class="space-y-4">
                        <article
                            v-for="pathItem in levelsState.items"
                            :key="pathItem.id"
                            class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/75"
                        >
                            <header class="border-b border-slate-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">{{ pathItem.title }}</h3>
                                        <p class="text-sm text-slate-400">{{ pathItem.description || 'No description' }}</p>
                                    </div>
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase"
                                        :class="pathItem.active ? 'bg-emerald-500/20 text-emerald-200' : 'bg-slate-700 text-slate-300'"
                                    >
                                        {{ pathItem.active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </header>

                            <div class="grid gap-3 p-3 md:grid-cols-2 xl:grid-cols-3">
                                <article
                                    v-for="level in pathItem.levels"
                                    :key="level.id"
                                    class="rounded-xl border border-slate-800 bg-slate-950/70 p-3"
                                >
                                    <div class="mb-2 flex items-start justify-between gap-2">
                                        <div>
                                            <h4 class="font-semibold text-white">{{ level.title }}</h4>
                                            <p class="text-xs text-slate-400">
                                                {{ level.topic || '-' }} • {{ level.difficulty }}
                                            </p>
                                        </div>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                            :class="level.active ? 'bg-emerald-500/20 text-emerald-200' : 'bg-slate-700 text-slate-300'"
                                        >
                                            {{ level.active ? 'Live' : 'Off' }}
                                        </span>
                                    </div>

                                    <div class="space-y-1 text-xs text-slate-300">
                                        <p>Pass: <span class="font-semibold text-white">{{ level.passing_score }}%</span></p>
                                        <p>XP: <span class="font-semibold text-white">{{ level.xp_reward }}</span></p>
                                        <p>Attempts: <span class="font-semibold text-white">{{ level.attempts_count }}</span></p>
                                        <p>Questions: <span class="font-semibold text-white">{{ level?.prompt?.question_count ?? 0 }}</span></p>
                                    </div>

                                    <button
                                        type="button"
                                        class="mt-3 inline-flex items-center gap-1 rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-800"
                                        @click="openEditLevel(pathItem, level)"
                                    >
                                        <SquarePen class="h-3.5 w-3.5" />
                                        Edit config
                                    </button>
                                </article>
                            </div>
                        </article>
                    </div>
                </section>

                <section v-else class="rounded-2xl border border-slate-800 bg-slate-900/75 p-6">
                    <div class="max-w-xl">
                        <p class="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-300">
                            <Sparkles class="h-3.5 w-3.5" />
                            Coming soon
                        </p>
                        <h2 class="mt-3 text-2xl font-black text-white">{{ selectedSectionLabel }}</h2>
                        <p class="mt-2 text-sm text-slate-400">
                            This module is reserved for the next CMS sprint. Core management for users and formulas is live.
                        </p>
                    </div>
                </section>

                <div v-if="globalLoading" class="mt-4 text-sm text-slate-400">Syncing CMS data...</div>
            </main>
        </div>

        <div
            v-if="showUserModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
            @click.self="showUserModal = false"
        >
            <div class="w-full max-w-xl rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl shadow-black/40">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">{{ isUserEditing ? 'Edit user' : 'Create user' }}</h3>
                    <button class="rounded-lg p-1 text-slate-400 hover:bg-slate-800" type="button" @click="showUserModal = false">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">Name</span>
                        <input v-model="userForm.name" type="text" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">Email</span>
                        <input v-model="userForm.email" type="email" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">Role</span>
                        <select v-model="userForm.role" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                            <option v-for="item in roleOptions" :key="item" :value="item">{{ item }}</option>
                        </select>
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">
                            Password {{ isUserEditing ? '(optional)' : '' }}
                        </span>
                        <input
                            v-model="userForm.password"
                            type="password"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                            autocomplete="new-password"
                        />
                    </label>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        class="rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-300 hover:bg-slate-800"
                        type="button"
                        @click="showUserModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-50"
                        type="button"
                        :disabled="userSubmitting"
                        @click="saveUser"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        {{ userSubmitting ? 'Saving...' : 'Save user' }}
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="showFormulaModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
            @click.self="showFormulaModal = false"
        >
            <div class="max-h-[94vh] w-full max-w-4xl overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl shadow-black/40">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">{{ isFormulaEditing ? 'Edit formula' : 'Create formula' }}</h3>
                    <button
                        class="rounded-lg p-1 text-slate-400 hover:bg-slate-800"
                        type="button"
                        @click="showFormulaModal = false"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">Category</span>
                        <input
                            v-model="formulaForm.category"
                            type="text"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                        />
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">Example (optional)</span>
                        <input
                            v-model="formulaForm.example"
                            type="text"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                        />
                    </label>
                </div>

                <label class="mt-3 block space-y-1">
                    <span class="text-xs font-semibold uppercase text-slate-400">Formula</span>
                    <textarea
                        v-model="formulaForm.formula"
                        rows="3"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 font-mono text-sm text-white"
                    ></textarea>
                </label>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div v-for="itemLocale in formulaLocales" :key="itemLocale" class="rounded-xl border border-slate-800 p-3">
                        <p class="mb-2 text-xs font-black uppercase tracking-wide text-emerald-300">{{ itemLocale }}</p>
                        <label class="mb-2 block space-y-1">
                            <span class="text-xs font-semibold uppercase text-slate-400">Name</span>
                            <input
                                v-model="formulaForm.translations[itemLocale].name"
                                type="text"
                                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                            />
                        </label>
                        <label class="block space-y-1">
                            <span class="text-xs font-semibold uppercase text-slate-400">Description</span>
                            <textarea
                                v-model="formulaForm.translations[itemLocale].description"
                                rows="2"
                                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                            ></textarea>
                        </label>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        class="rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-300 hover:bg-slate-800"
                        type="button"
                        @click="showFormulaModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-50"
                        type="button"
                        :disabled="formulaSubmitting"
                        @click="saveFormula"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        {{ formulaSubmitting ? 'Saving...' : 'Save formula' }}
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="showLevelModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
            @click.self="showLevelModal = false"
        >
            <div class="max-h-[94vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl shadow-black/40">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">Edit level: {{ levelForm.title }}</h3>
                    <button class="rounded-lg p-1 text-slate-400 hover:bg-slate-800" type="button" @click="showLevelModal = false">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <p class="mb-3 text-xs uppercase tracking-wide text-emerald-300">{{ levelForm.pathTitle }}</p>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">Difficulty</span>
                        <input v-model="levelForm.difficulty" type="text" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">Passing score %</span>
                        <input v-model.number="levelForm.passing_score" type="number" min="1" max="100" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">XP reward</span>
                        <input v-model.number="levelForm.xp_reward" type="number" min="0" max="10000" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">Time limit (sec)</span>
                        <input v-model="levelForm.time_limit" type="number" min="5" max="7200" placeholder="Leave empty for none" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white placeholder:text-slate-500" />
                    </label>
                    <label class="inline-flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-200 sm:col-span-2">
                        <input v-model="levelForm.active" type="checkbox" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-emerald-400" />
                        Active level
                    </label>
                </div>

                <hr class="my-4 border-slate-800" />

                <div class="grid gap-3 sm:grid-cols-3">
                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">Prompt locale</span>
                        <select v-model="levelForm.prompt_locale" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                            <option v-for="itemLocale in formulaLocales" :key="itemLocale" :value="itemLocale">{{ itemLocale }}</option>
                        </select>
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">Question count</span>
                        <input v-model.number="levelForm.question_count" type="number" min="1" max="50" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                    </label>
                    <label class="space-y-1">
                        <span class="text-xs font-semibold uppercase text-slate-400">Options count</span>
                        <input v-model.number="levelForm.options_count" type="number" min="2" max="8" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                    </label>
                </div>

                <label class="mt-3 block space-y-1">
                    <span class="text-xs font-semibold uppercase text-slate-400">AI Prompt</span>
                    <textarea
                        v-model="levelForm.prompt"
                        rows="6"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                    ></textarea>
                </label>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        class="rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-300 hover:bg-slate-800"
                        type="button"
                        @click="showLevelModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-50"
                        type="button"
                        :disabled="levelSubmitting || !isLevelEditing"
                        @click="saveLevel"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        {{ levelSubmitting ? 'Saving...' : 'Save level' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

