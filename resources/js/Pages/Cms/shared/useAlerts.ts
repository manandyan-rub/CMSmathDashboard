import { ref } from 'vue';

export function useAlerts() {
    const errorMessage = ref('');
    const successMessage = ref('');

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

    return {
        errorMessage,
        successMessage,
        clearAlerts,
        showError,
        showSuccess,
    };
}
