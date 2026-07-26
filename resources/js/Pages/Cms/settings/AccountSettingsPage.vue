<script setup lang="ts">
import SettingsPage from './SettingsPage.vue';
import { useSettings } from './useSettings';

const props = defineProps<{
    theme: string;
    clearAlerts: () => void;
    showError: (error: unknown, fallback?: string) => void;
    showSuccess: (message: string) => void;
    setTheme: (theme: string) => void;
}>();

const settings = useSettings({
    clearAlerts: props.clearAlerts,
    showError: props.showError,
    showSuccess: props.showSuccess,
});

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleDateString() : '-';
}
</script>

<template>
    <SettingsPage
        :current-user="settings.currentUser"
        :profile-form="settings.profileForm"
        :profile-errors="settings.profileErrors"
        :profile-submitting="settings.profileSubmitting.value"
        :password-form="settings.passwordForm"
        :password-errors="settings.passwordErrors"
        :password-submitting="settings.passwordSubmitting.value"
        :theme="theme"
        :format-date="formatDate"
        @save-profile="settings.saveProfile"
        @save-password="settings.savePassword"
        @set-theme="setTheme"
    />
</template>
