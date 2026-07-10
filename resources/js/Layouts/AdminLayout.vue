<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const sidebarOpen = ref(false);
</script>

<template>
    <div class="relative flex h-screen bg-[#F8F9FA] overflow-hidden">

        <!-- Sidebar -->
        <aside
            class="absolute inset-y-0 left-0 z-20 flex h-full flex-col justify-between overflow-hidden border-r border-gray-200 bg-white shadow-lg transition-all duration-200"
            :class="sidebarOpen ? 'w-80' : 'w-16'"
            @mouseenter="sidebarOpen = true"
            @mouseleave="sidebarOpen = false"
        >
            <div
                v-show="!sidebarOpen"
                class="flex h-full flex-col justify-between transition-all duration-150 ease-in-out"
            >
                <div class="space-y-2">
                    <div class="flex items-center justify-center p-4">
                        <img
                            src="/image/logos/Logo IOH-Color.png"
                            alt="Indosat Logo"
                            class="w-8 h-8 object-contain"
                        />
                    </div>

                    <div class="flex items-center justify-center py-2">
                        <div class="flex w-10 flex-col gap-1 items-center">
                            <span class="block h-[2px] w-[70%] rounded-full bg-gray-500/70"></span>
                            <span class="block h-[2px] w-[70%] rounded-full bg-gray-500/70"></span>
                            <span class="block h-[2px] w-[70%] rounded-full bg-gray-500/70"></span>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 bg-white p-3">
                    <div class="flex flex-col gap-2">
                        <Link
                            :href="route('profile.edit')"
                            class="flex items-center justify-center rounded-xl p-2 text-gray-600 transition hover:bg-gray-100"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5z"/>
                            </svg>
                        </Link>

                        <Link
                            method="post"
                            as="button"
                            :href="route('logout')"
                            class="flex items-center justify-center rounded-xl p-2 text-red-600 transition hover:bg-red-50"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 16l4-4-4-4m4 4H9m4 8H7a2 2 0 01-2-2V6a2 2 0 012-2h6"/>
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>

            <div
                v-show="sidebarOpen"
                class="flex h-full flex-col transition-all duration-150 ease-in-out"
            >
                <div class="px-8 py-7 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <img
                            src="/image/logos/Logo IOH-Color.png"
                            alt="Indosat Logo"
                            class="h-12 w-12 object-contain"
                        />
                        <div class="space-y-1">
                            <h1 class="font-semibold text-gray-900 text-base leading-tight">
                                Promotor Monitoring
                            </h1>
                            <p class="text-sm text-gray-500 leading-relaxed">
                                Indosat Internal Dashboard
                            </p>
                        </div>
                    </div>
                </div>

                <nav class="flex-1 px-5 py-6 space-y-2">
                    <Link
                        :href="route('admin.dashboard')"
                        :class="[
                            'flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200',
                            route().current('admin.dashboard')
                                ? 'bg-gray-500/10 text-gray-700 font-semibold shadow-sm'
                                : 'text-gray-600 hover:bg-red-50 hover:text-[#ED1C24]'
                        ]"
                    >
                        <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                d="M3 12l2-2 7-7 7 7 2 2M5 10v10h14V10"/>
                        </svg>
                        KPI Dashboard
                    </Link>

                    <Link
                        :href="route('admin.monitoring')"
                        :class="[
                            'flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200',
                            route().current('admin.monitoring')
                                ? 'bg-gray-500/10 text-gray-700 font-semibold shadow-sm'
                                : 'text-gray-600 hover:bg-red-50 hover:text-[#ED1C24]'
                        ]"
                    >
                        <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                d="M9 20l-5-2V6l5 2 6-2 6 2v12l-6-2-6 2z"/>
                        </svg>
                        Live Monitoring
                    </Link>

                    <Link
                        :href="route('admin.reports.index')"
                        :class="[
                            'flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200',
                            route().current('admin.reports.index')
                                ? 'bg-gray-500/10 text-gray-700 font-semibold shadow-sm'
                                : 'text-gray-600 hover:bg-red-50 hover:text-[#ED1C24]'
                        ]"
                    >
                        <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                d="M9 17v-2m3 2v-5m3 5v-8M5 21h14"/>
                        </svg>
                        Reports
                    </Link>
                </nav>

                <div class="border-t border-gray-100 bg-white p-4">
                    <div class="flex flex-col gap-2">
                        <Link
                            :href="route('profile.edit')"
                            class="flex items-center gap-3 rounded-xl px-4 py-3 text-gray-600 transition hover:bg-gray-100"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5z"/>
                            </svg>
                            Profile
                        </Link>

                        <Link
                            method="post"
                            as="button"
                            :href="route('logout')"
                            class="flex items-center gap-3 rounded-xl px-4 py-3 text-red-600 transition hover:bg-red-50"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 16l4-4-4-4m4 4H9m4 8H7a2 2 0 01-2-2V6a2 2 0 012-2h6"/>
                            </svg>
                            Logout
                        </Link>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <main class="ml-16 flex-1 flex flex-col overflow-hidden">

            <!-- Header -->
            <header class="bg-white border-b border-gray-200 px-8 py-5 flex items-center justify-between">

                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <slot name="header">
                            Dashboard
                        </slot>
                    </h1>

                    <p class="text-sm text-gray-500 mt-1">
                        Indosat Promotor Monitoring System
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="font-semibold text-gray-800">
                            {{ $page.props.auth.user.name }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Administrator
                        </p>
                    </div>

                    <div class="w-11 h-11 rounded-full bg-[#ED1C24] text-white flex items-center justify-center font-bold shadow">
                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                    </div>
                </div>
            </header>

            <!-- Content -->
            <section class="flex-1 overflow-auto p-8 bg-[#F8F9FA]">
                <slot />
            </section>
        </main>
    </div>
</template>