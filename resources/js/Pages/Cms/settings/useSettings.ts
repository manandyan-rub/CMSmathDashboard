import { usePage } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

function resetErrors(target) {
    Object.keys(target).forEach((key) => {
        delete target[key];
    });
}

function fillErrors(target, errors = {}) {
    resetErrors(target);
    Object.entries(errors).forEach(([key, value]) => {
        target[key] = Array.isArray(value) ? value[0] : value;
    });
}

function userPayload(user = {}) {
    return {
        id: user.id ?? null,
        name: user.name ?? '',
        email: user.email ?? '',
        role: user.role ?? '',
        email_verified_at: user.email_verified_at ?? null,
        created_at: user.created_at ?? null,
        updated_at: user.updated_at ?? null,
    };
}

export function useSettings({ clearAlerts, showError, showSuccess }) {
    const page = usePage();
    const currentUser = reactive(userPayload(page.props.auth?.user));
    const profileSubmitting = ref(false);
    const passwordSubmitting = ref(false);

    const profileForm = reactive({
        name: currentUser.name,
        email: currentUser.email,
    });

    const passwordForm = reactive({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const profileErrors = reactive({});
    const passwordErrors = reactive({});

    function syncCurrentUser(user) {
        Object.assign(currentUser, userPayload(user));
        profileForm.name = currentUser.name;
        profileForm.email = currentUser.email;
    }

    async function saveProfile() {
        clearAlerts();
        resetErrors(profileErrors);
        profileSubmitting.value = true;

        try {
            const { data } = await window.axios.patch('/cms-api/profile', {
                name: profileForm.name,
                email: profileForm.email,
            });

            syncCurrentUser(data.item);
            showSuccess('Profile updated.');
        } catch (error) {
            fillErrors(profileErrors, error?.response?.data?.errors);
            showError(error, 'Failed to update profile.');
        } finally {
            profileSubmitting.value = false;
        }
    }

    async function savePassword() {
        clearAlerts();
        resetErrors(passwordErrors);
        passwordSubmitting.value = true;

        try {
            await window.axios.put('/cms-api/profile/password', {
                current_password: passwordForm.current_password,
                password: passwordForm.password,
                password_confirmation: passwordForm.password_confirmation,
            });

            passwordForm.current_password = '';
            passwordForm.password = '';
            passwordForm.password_confirmation = '';
            showSuccess('Password updated.');
        } catch (error) {
            fillErrors(passwordErrors, error?.response?.data?.errors);
            showError(error, 'Failed to update password.');
        } finally {
            passwordSubmitting.value = false;
        }
    }

    return {
        currentUser,
        profileForm,
        profileErrors,
        profileSubmitting,
        passwordForm,
        passwordErrors,
        passwordSubmitting,
        saveProfile,
        savePassword,
    };
}
