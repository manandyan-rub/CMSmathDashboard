<script setup lang="ts">
import { AlertTriangle, X } from 'lucide-vue-next';

defineProps<{
    show: boolean;
    title: string;
    message: string;
    confirmLabel: string;
    reason: string;
    busy?: boolean;
}>();

const emit = defineEmits<{
    'update:reason': [value: string];
    close: [];
    confirm: [];
}>();
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[80] grid place-items-center bg-slate-950/75 p-4">
        <section class="w-full max-w-md rounded-lg border border-slate-700 bg-slate-900 p-5 shadow-2xl">
            <div class="flex items-start justify-between gap-3">
                <div class="flex gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-rose-400/10 text-rose-300">
                        <AlertTriangle class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="font-bold text-white">{{ title }}</h2>
                        <p class="mt-1 text-sm text-slate-400">{{ message }}</p>
                    </div>
                </div>
                <button type="button" class="text-slate-500 hover:text-white" @click="emit('close')">
                    <X class="h-4 w-4" />
                </button>
            </div>
            <label class="mt-4 block text-xs font-semibold text-slate-300">
                Reason
                <textarea
                    :value="reason"
                    rows="3"
                    class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 text-sm text-white focus:border-emerald-400 focus:ring-0"
                    @input="emit('update:reason', ($event.target as HTMLTextAreaElement).value)"
                ></textarea>
            </label>
            <div class="mt-4 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-slate-700 px-3 py-2 text-sm font-semibold text-slate-300"
                    @click="emit('close')"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-rose-500 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50"
                    :disabled="busy || reason.trim().length < 3"
                    @click="emit('confirm')"
                >
                    {{ confirmLabel }}
                </button>
            </div>
        </section>
    </div>
</template>
