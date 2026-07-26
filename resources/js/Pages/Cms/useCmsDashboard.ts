import { computed, onMounted, ref, watch } from 'vue';
import { cmsNavigation } from './data/navData';
import { useCmsI18n } from './i18n/cmsMessages';
import type { CmsMeta } from './shared/cmsTypes';

const emptyMeta: CmsMeta = {
    user: {
        id: 0,
        name: '',
        email: '',
        role: '',
        status: '',
        locale: 'en',
        permissions: [],
    },
    locales: ['en'],
    source_locale: 'en',
    countries: [],
    permissions: [],
    resources: [],
};

export function useCmsDashboard() {
    const storedSection = typeof window !== 'undefined' ? window.localStorage.getItem('cms-section') : null;
    const storedLocale = typeof window !== 'undefined' ? window.localStorage.getItem('cms-locale') : null;
    const storedTheme = typeof window !== 'undefined' ? window.localStorage.getItem('cms-theme') : null;
    const activeSection = ref(storedSection || 'dashboard');
    const locale = ref(storedLocale || 'en');
    const theme = ref(storedTheme === 'light' ? 'light' : 'dark');
    const meta = ref<CmsMeta>(emptyMeta);
    const loading = ref(false);
    const dashboardLoading = ref(false);
    const errorMessage = ref('');
    const successMessage = ref('');
    const refreshToken = ref(0);
    const searchQuery = ref('');
    const searchGroups = ref<Record<string, Array<Record<string, any>>>>({});
    const searchLoading = ref(false);
    const selectedEntity = ref<Record<string, any> | null>(null);
    const dashboard = ref<Record<string, any>>({
        range: { preset: 'last_30_days', from: '', to: '' },
        summary: {},
        charts: {},
        ai_usage: {},
    });
    const dateFilter = ref({ preset: 'last_30_days', date_from: '', date_to: '' });
    let searchTimer: ReturnType<typeof setTimeout> | null = null;

    const { t } = useCmsI18n(locale);
    const permissions = computed(() => new Set(meta.value.user.permissions || []));
    const navigationGroups = computed(() => cmsNavigation
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => permissions.value.has(item.permission)),
        }))
        .filter((group) => group.items.length > 0));
    const activeItem = computed(() => navigationGroups.value
        .flatMap((group) => group.items)
        .find((item) => item.key === activeSection.value)
        ?? navigationGroups.value[0]?.items[0]);
    const activeGroup = computed(() => navigationGroups.value
        .find((group) => group.items.some((item) => item.key === activeItem.value?.key)));
    const sectionLabel = computed(() => activeItem.value ? t(activeItem.value.labelKey) : t('nav.dashboard'));
    const groupLabel = computed(() => activeGroup.value ? t(activeGroup.value.labelKey) : t('nav.dashboard'));
    const resourceKey = computed(() => activeItem.value?.resource ?? null);
    const themeClass = computed(() => theme.value === 'light' ? 'cms-theme-light' : 'cms-theme-dark');

    function showError(error: any, fallback = 'Request failed.') {
        errorMessage.value = error?.response?.data?.message
            || Object.values(error?.response?.data?.errors || {})?.flat()?.[0]
            || fallback;
        successMessage.value = '';
    }

    function showSuccess(message: string) {
        successMessage.value = message;
        errorMessage.value = '';
    }

    function clearAlerts() {
        errorMessage.value = '';
        successMessage.value = '';
    }

    async function loadMeta() {
        const { data } = await window.axios.get('/cms-api/meta');
        meta.value = data;
        if (!storedLocale && data.user?.locale) {
            locale.value = data.user.locale;
        }
    }

    async function loadDashboard() {
        dashboardLoading.value = true;
        try {
            const { data } = await window.axios.get('/cms-api/dashboard-analytics', {
                params: {
                    ...dateFilter.value,
                    locale: locale.value,
                },
            });
            dashboard.value = data;
        } catch (error) {
            showError(error, 'Dashboard data could not be loaded.');
        } finally {
            dashboardLoading.value = false;
        }
    }

    async function bootstrap() {
        clearAlerts();
        loading.value = true;
        try {
            await loadMeta();
            if (!activeItem.value) {
                activeSection.value = navigationGroups.value[0]?.items[0]?.key || 'dashboard';
            }
            await loadDashboard();
            refreshToken.value += 1;
        } catch (error) {
            showError(error, 'The CMS could not be initialized.');
        } finally {
            loading.value = false;
        }
    }

    async function runGlobalSearch(query: string) {
        if (query.trim().length < 2) {
            searchGroups.value = {};
            return;
        }
        searchLoading.value = true;
        try {
            const { data } = await window.axios.get('/cms-api/search', {
                params: { q: query.trim(), locale: locale.value, limit: 5 },
            });
            searchGroups.value = data.groups || {};
        } catch (error) {
            showError(error, 'Search failed.');
        } finally {
            searchLoading.value = false;
        }
    }

    function selectSearchResult(item: Record<string, any>) {
        selectedEntity.value = item;
        activeSection.value = item.section;
        searchQuery.value = '';
        searchGroups.value = {};
    }

    function refreshCurrentSection() {
        clearAlerts();
        refreshToken.value += 1;
        if (activeSection.value === 'dashboard') {
            loadDashboard();
        }
    }

    function toggleTheme() {
        theme.value = theme.value === 'light' ? 'dark' : 'light';
    }

    async function logout() {
        try {
            await window.axios.post('/logout');
        } finally {
            window.location.href = '/login';
        }
    }

    watch(searchQuery, (query) => {
        if (searchTimer) {
            clearTimeout(searchTimer);
        }
        searchTimer = setTimeout(() => runGlobalSearch(query), 300);
    });
    watch(activeSection, (value) => {
        window.localStorage.setItem('cms-section', value);
        clearAlerts();
    });
    watch(locale, () => {
        window.localStorage.setItem('cms-locale', locale.value);
        loadDashboard();
        refreshToken.value += 1;
    });
    watch(theme, (value) => {
        window.localStorage.setItem('cms-theme', value);
        document.documentElement.dataset.cmsTheme = value;
    }, { immediate: true });

    onMounted(bootstrap);

    return {
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
        selectedEntity,
        navigationGroups,
        activeItem,
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
    };
}
