<script setup>
defineProps({
    pagination: {
        type: Object,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['page']);
</script>

<template>
    <div class="flex items-center justify-between border-t border-slate-800 px-4 py-3 text-sm">
        <p class="text-slate-400">
            Showing {{ pagination.from || 0 }}-{{ pagination.to || 0 }} of {{ pagination.total }} {{ label }}
        </p>
        <div class="inline-flex gap-2">
            <button
                class="rounded-lg border border-slate-700 px-3 py-1.5 font-semibold text-slate-200 disabled:cursor-not-allowed disabled:opacity-40"
                type="button"
                :disabled="pagination.page <= 1"
                @click="emit('page', pagination.page - 1)"
            >
                Prev
            </button>
            <button
                class="rounded-lg border border-slate-700 px-3 py-1.5 font-semibold text-slate-200 disabled:cursor-not-allowed disabled:opacity-40"
                type="button"
                :disabled="pagination.page >= pagination.last_page"
                @click="emit('page', pagination.page + 1)"
            >
                Next
            </button>
        </div>
    </div>
</template>
