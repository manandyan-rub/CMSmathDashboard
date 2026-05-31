import { computed, reactive, ref } from 'vue';

export function useUsers({ clearAlerts, showError, showSuccess, loadOverview }) {
    const searchUsers = ref('');
    const usersLoading = ref(false);
    const showUserModal = ref(false);
    const userSubmitting = ref(false);

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

    const userForm = reactive({
        id: null,
        name: '',
        email: '',
        role: 'student',
        password: '',
    });

    const isUserEditing = computed(() => userForm.id !== null);

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

    return {
        searchUsers,
        usersLoading,
        usersState,
        showUserModal,
        userSubmitting,
        userForm,
        isUserEditing,
        loadUsers,
        openCreateUser,
        openEditUser,
        saveUser,
        deleteUser,
    };
}
