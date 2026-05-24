<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowRight,
    LockKeyhole,
    Mail,
    ShieldCheck,
} from '@lucide/vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="CMS Login" />

    <main class="min-h-screen bg-[#f7f9ff] text-[#0f172a]">
        <div class="grid min-h-screen lg:grid-cols-[430px_minmax(0,1fr)]">
            <section class="relative flex min-h-[36vh] flex-col justify-between overflow-hidden bg-[#0b0f19] px-6 py-8 text-white sm:px-10 lg:min-h-screen">
                <div class="pointer-events-none absolute inset-0 opacity-[0.07]"
                    style="background-image: linear-gradient(to right, #dbe3f3 1px, transparent 1px), linear-gradient(to bottom, #dbe3f3 1px, transparent 1px); background-size: 38px 38px;"
                ></div>
                <div class="pointer-events-none absolute -left-24 top-20 h-56 w-56 rounded-full bg-[#4f46e5]/25 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 right-0 h-52 w-52 rounded-full bg-[#7c3aed]/20 blur-3xl"></div>

                <div class="flex items-center justify-between gap-4">
                    <div class="relative z-10 flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-lg bg-gradient-to-br from-[#4f46e5] to-[#7c3aed]">
                            <span class="font-serif text-xl italic text-white">fx</span>
                        </span>
                        <div>
                            <p class="text-base font-black leading-tight">MathDash CMS</p>
                            <p class="text-xs font-bold uppercase text-[#a5b4fc]">Private admin access</p>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 my-10 max-w-[340px] lg:my-0">
                    <p class="mb-3 text-sm font-bold uppercase text-[#818cf8]">CMS workspace</p>
                    <h1 class="text-4xl font-black leading-tight">
                        Manage MathDash content from one secure place.
                    </h1>
                    <p class="mt-5 text-base leading-7 text-[#cbd5e1]">
                        Sign in to update formulas, lessons, math levels, users, and publishing settings.
                    </p>
                </div>

                <div class="relative z-10 text-sm font-semibold text-[#94a3b8]">
                    MathDash<span class="text-[#818cf8]">.</span> Admin portal
                </div>
            </section>

            <section class="flex items-center justify-center px-5 py-8 sm:px-8 lg:px-12">
                <div class="w-full max-w-md rounded-lg border border-[#dbe3f3] bg-white p-6 shadow-xl shadow-[#4f46e5]/10 sm:p-8">
                    <div class="mb-7">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-lg bg-[#eef2ff] text-[#4f46e5]">
                            <ShieldCheck class="h-6 w-6" aria-hidden="true" />
                        </div>
                        <p class="text-sm font-black uppercase text-[#4f46e5]">Secure CMS login</p>
                        <h2 class="mt-2 text-2xl font-black leading-tight">Welcome back</h2>
                        <p class="mt-2 text-sm leading-6 text-[#64748b]">
                            Use your administrator account to open the MathDash control panel.
                        </p>
                    </div>

                    <div v-if="status" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                        {{ status }}
                    </div>

                    <form class="grid gap-5" @submit.prevent="submit">
                        <label class="grid gap-2">
                            <span class="text-sm font-black text-[#1e293b]">Email address</span>
                            <span class="relative">
                                <Mail class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-[#64748b]" aria-hidden="true" />
                                <input
                                    v-model="form.email"
                                    class="h-12 w-full rounded-lg border-[#dbe3f3] bg-white pl-11 pr-3 text-sm font-semibold text-[#0f172a] shadow-sm focus:border-[#6366f1] focus:ring-[#6366f1]"
                                    type="email"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="admin@mathdashboard.com"
                                />
                            </span>
                            <InputError :message="form.errors.email" />
                        </label>

                        <label class="grid gap-2">
                            <span class="text-sm font-black text-[#1e293b]">Password</span>
                            <span class="relative">
                                <LockKeyhole class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-[#64748b]" aria-hidden="true" />
                                <input
                                    v-model="form.password"
                                    class="h-12 w-full rounded-lg border-[#dbe3f3] bg-white pl-11 pr-3 text-sm font-semibold text-[#0f172a] shadow-sm focus:border-[#6366f1] focus:ring-[#6366f1]"
                                    type="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Enter your password"
                                />
                            </span>
                            <InputError :message="form.errors.password" />
                        </label>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <label class="flex items-center gap-2 text-sm font-semibold text-[#64748b]">
                                <Checkbox name="remember" v-model:checked="form.remember" />
                                <span>Remember this device</span>
                            </label>

                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm font-black text-[#4f46e5] hover:text-[#3730a3] focus:outline-none focus:ring-2 focus:ring-[#6366f1] focus:ring-offset-2"
                            >
                                Forgot password?
                            </Link>
                        </div>

                        <button
                            class="inline-flex h-12 items-center justify-center gap-2 rounded-lg border border-[#4338ca] bg-[#4f46e5] px-5 text-sm font-black text-white shadow-sm transition hover:bg-[#4338ca] focus:outline-none focus:ring-2 focus:ring-[#6366f1] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                            type="submit"
                            :disabled="form.processing"
                        >
                            Sign in to CMS
                            <ArrowRight class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </form>

                    <p class="mt-6 rounded-lg bg-[#eef2ff] px-4 py-3 text-xs font-semibold leading-5 text-[#475569]">
                        Registration is closed. New CMS users are created by an existing administrator.
                    </p>
                </div>
            </section>
        </div>
    </main>
</template>
