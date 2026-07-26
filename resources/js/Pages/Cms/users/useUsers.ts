import { reactive, ref, type Ref } from 'vue';
import type { Pagination } from '../shared/cmsTypes';

interface Context {
    role: Ref<string>;
    locale: Ref<string>;
    permissionDefinitions: Array<{ key: string; label: string; group: string }>;
    showError: (error: any, fallback?: string) => void;
    showSuccess: (message: string) => void;
}

export function useUsers(context: Context) {
    const loading = ref(false);
    const detailLoading = ref(false);
    const progressLoading = ref(false);
    const submitting = ref(false);
    const items = ref<Array<Record<string, any>>>([]);
    const pagination = ref<Pagination>({ page: 1, last_page: 1, per_page: 20, total: 0 });
    const detail = ref<Record<string, any> | null>(null);
    const showUserModal = ref(false);
    const filters = reactive({
        search: '',
        status: '',
        country_id: '',
        locale: '',
        verified: '',
        registered_from: '',
        registered_to: '',
        last_active_from: '',
        sort: 'created_at',
        direction: 'desc',
    });
    const form = reactive<Record<string, any>>({
        id: null,
        name: '',
        email: '',
        role: 'student',
        status: 'active',
        locale: 'en',
        country_id: null,
        password: '',
    });
    const xpForm = reactive({ amount: 0, reason: '' });
    const resetForm = reactive({ level_id: null as number | null, reason: '', confirmed: false });
    const lecturerForm = reactive({
        approval_status: 'pending',
        monthly_ai_limit: 100,
        storage_limit_bytes: 104857600,
        moderation_note: '',
    });
    const permissionValues = reactive<Record<string, string>>({});
    const permissionReason = ref('');
    const progress = ref<Record<string, any>>({
        summary: {},
        items: [],
        pagination: { page: 1, last_page: 1, total: 0 },
        streak_history: [],
    });
    const progressFilters = reactive({
        date_from: '',
        date_to: '',
        path_id: '',
        level_id: '',
        topic: '',
        difficulty: '',
    });

    async function load(page = pagination.value.page || 1) {
        loading.value = true;
        try {
            const { data } = await window.axios.get('/cms-api/users', {
                params: {
                    page,
                    ...filters,
                    role: context.role.value || undefined,
                    locale: filters.locale || undefined,
                    verified: filters.verified === '' ? undefined : filters.verified,
                },
            });
            items.value = data.items || [];
            pagination.value = { ...pagination.value, ...(data.pagination || {}) };
        } catch (error) {
            context.showError(error, 'Users could not be loaded.');
        } finally {
            loading.value = false;
        }
    }

    async function openDetail(user: Record<string, any>) {
        detailLoading.value = true;
        try {
            const { data } = await window.axios.get(`/cms-api/users/${user.id}`, {
                params: { locale: context.locale.value },
            });
            detail.value = data;
            Object.assign(lecturerForm, {
                approval_status: data.user.lecturer_profile?.approval_status || 'pending',
                monthly_ai_limit: data.user.lecturer_profile?.monthly_ai_limit || 100,
                storage_limit_bytes: data.user.lecturer_profile?.storage_limit_bytes || 104857600,
                moderation_note: data.user.lecturer_profile?.moderation_note || '',
            });
            context.permissionDefinitions.forEach((permission) => {
                permissionValues[permission.key] = '';
            });
            (data.user.permission_overrides || []).forEach((permission: Record<string, any>) => {
                permissionValues[permission.key] = permission.pivot?.allowed ? 'allow' : 'deny';
            });
            if (data.user.role === 'student') {
                await loadProgress(1, data.user.id);
            }
        } catch (error) {
            context.showError(error, 'User details could not be loaded.');
        } finally {
            detailLoading.value = false;
        }
    }

    async function loadProgress(page = 1, userId = detail.value?.user?.id) {
        if (!userId) return;
        progressLoading.value = true;
        try {
            const { data } = await window.axios.get(`/cms-api/users/${userId}/progress`, {
                params: {
                    page,
                    ...progressFilters,
                    path_id: progressFilters.path_id || undefined,
                    level_id: progressFilters.level_id || undefined,
                    topic: progressFilters.topic || undefined,
                    difficulty: progressFilters.difficulty || undefined,
                },
            });
            progress.value = data;
        } catch (error) {
            context.showError(error, 'Student progress could not be loaded.');
        } finally {
            progressLoading.value = false;
        }
    }

    function openCreate() {
        Object.assign(form, {
            id: null,
            name: '',
            email: '',
            role: context.role.value || 'student',
            status: 'active',
            locale: context.locale.value,
            country_id: null,
            password: '',
        });
        showUserModal.value = true;
    }

    function openEdit(user: Record<string, any>) {
        Object.assign(form, {
            id: user.id,
            name: user.name,
            email: user.email,
            role: user.role,
            status: user.status,
            locale: user.locale,
            country_id: user.country?.id || null,
            password: '',
        });
        showUserModal.value = true;
    }

    async function save() {
        submitting.value = true;
        try {
            const payload = { ...form };
            delete payload.id;
            if (!payload.password) delete payload.password;
            if (form.id) {
                await window.axios.patch(`/cms-api/users/${form.id}`, payload);
            } else {
                await window.axios.post('/cms-api/users', payload);
            }
            showUserModal.value = false;
            context.showSuccess(form.id ? 'User updated.' : 'User created.');
            await load(form.id ? pagination.value.page : 1);
        } catch (error) {
            context.showError(error, 'User could not be saved.');
        } finally {
            submitting.value = false;
        }
    }

    async function action(actionName: string, reason: string, extra: Record<string, any> = {}) {
        if (!detail.value?.user?.id) return;
        submitting.value = true;
        try {
            await window.axios.post(`/cms-api/users/${detail.value.user.id}/actions`, {
                action: actionName,
                reason,
                ...extra,
            });
            context.showSuccess('User action completed.');
            await Promise.all([openDetail(detail.value.user), load()]);
        } catch (error) {
            context.showError(error, 'User action failed.');
        } finally {
            submitting.value = false;
        }
    }

    async function adjustXp() {
        if (!detail.value?.user?.id) return;
        submitting.value = true;
        try {
            await window.axios.post(`/cms-api/users/${detail.value.user.id}/xp-adjustments`, xpForm);
            context.showSuccess('XP adjusted with a ledger entry.');
            xpForm.amount = 0;
            xpForm.reason = '';
            await openDetail(detail.value.user);
        } catch (error) {
            context.showError(error, 'XP could not be adjusted.');
        } finally {
            submitting.value = false;
        }
    }

    async function resetProgress() {
        if (!detail.value?.user?.id) return;
        submitting.value = true;
        try {
            await window.axios.post(`/cms-api/users/${detail.value.user.id}/progress-resets`, resetForm);
            context.showSuccess('Progress reset recorded without deleting history.');
            Object.assign(resetForm, { level_id: null, reason: '', confirmed: false });
            await openDetail(detail.value.user);
        } catch (error) {
            context.showError(error, 'Progress could not be reset.');
        } finally {
            submitting.value = false;
        }
    }

    async function saveLecturer() {
        if (!detail.value?.user?.id) return;
        submitting.value = true;
        try {
            await window.axios.patch(`/cms-api/users/${detail.value.user.id}/lecturer`, lecturerForm);
            context.showSuccess('Lecturer controls updated.');
            await openDetail(detail.value.user);
        } catch (error) {
            context.showError(error, 'Lecturer controls could not be saved.');
        } finally {
            submitting.value = false;
        }
    }

    async function savePermissions() {
        if (!detail.value?.user?.id) return;
        const permissions = Object.entries(permissionValues)
            .map(([key, value]) => ({
                key,
                allowed: value === '' ? null : value === 'allow',
            }));
        submitting.value = true;
        try {
            await window.axios.put(`/cms-api/users/${detail.value.user.id}/permissions`, {
                permissions,
                reason: permissionReason.value || 'Permission overrides updated in CMS',
            });
            permissionReason.value = '';
            context.showSuccess('Permission overrides updated.');
            await openDetail(detail.value.user);
        } catch (error) {
            context.showError(error, 'Permission overrides could not be saved.');
        } finally {
            submitting.value = false;
        }
    }

    function exportUser() {
        if (detail.value?.user?.id) {
            window.location.href = `/cms-api/users/${detail.value.user.id}/export`;
        }
    }

    return {
        loading,
        detailLoading,
        progressLoading,
        submitting,
        items,
        pagination,
        detail,
        filters,
        form,
        showUserModal,
        xpForm,
        resetForm,
        lecturerForm,
        permissionValues,
        permissionReason,
        progress,
        progressFilters,
        load,
        openDetail,
        loadProgress,
        openCreate,
        openEdit,
        save,
        action,
        adjustXp,
        resetProgress,
        saveLecturer,
        savePermissions,
        exportUser,
    };
}
