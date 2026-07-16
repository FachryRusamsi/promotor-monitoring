<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const sidebarOpen = ref(false);
</script>

<template>
    <div class="relative flex h-screen bg-[#F8F9FA] overflow-hidden">

        <!-- Mobile Backdrop -->
        <div 
            v-if="sidebarOpen" 
            @click="sidebarOpen = false" 
            class="fixed inset-0 z-20 bg-gray-900/50 transition-opacity md:hidden"
        ></div>

        <!-- Sidebar -->
        <aside
            class="absolute inset-y-0 left-0 z-30 flex h-full flex-col justify-between overflow-hidden border-r border-gray-200 bg-white shadow-lg transition-all duration-300 md:z-20 md:translate-x-0"
            :class="[
                sidebarOpen ? 'w-72 translate-x-0' : '-translate-x-full w-72 md:w-16 md:translate-x-0'
            ]"
            @mouseenter="sidebarOpen = true"
            @mouseleave="sidebarOpen = false"
        >
            <div
                v-show="!sidebarOpen"
                class="hidden md:flex h-full flex-col justify-between transition-all duration-150 ease-in-out"
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
                v-show="sidebarOpen || !sidebarOpen"
                :class="sidebarOpen ? 'flex' : 'hidden md:hidden'"
                class="h-full flex-col transition-all duration-150 ease-in-out"
            >
                <!-- Close Button on Mobile -->
                <button 
                    @click="sidebarOpen = false"
                    class="absolute top-4 right-4 p-2 text-gray-500 hover:text-gray-800 md:hidden"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="px-6 py-6 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <img
                            src="/image/logos/Logo IOH-Color.png"
                            alt="Indosat Logo"
                            class="h-10 w-10 object-contain"
                        />
                        <div class="space-y-0.5 mt-1">
                            <h1 class="font-semibold text-gray-900 text-sm leading-tight">
                                Promotor Monitoring
                            </h1>
                            <p class="text-xs text-gray-500 leading-relaxed">
                                Indosat Dashboard
                            </p>
                        </div>
                    </div>
                </div>

                <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                    <Link
                        :href="route('admin.dashboard')"
                        @click="sidebarOpen = false"
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
                        @click="sidebarOpen = false"
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
                        @click="sidebarOpen = false"
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

                    <Link
                        :href="route('admin.powerbi')"
                        @click="sidebarOpen = false"
                        :class="[
                            'flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200',
                            route().current('admin.powerbi')
                                ? 'bg-gray-500/10 text-gray-700 font-semibold shadow-sm'
                                : 'text-gray-600 hover:bg-red-50 hover:text-[#ED1C24]'
                        ]"
                    >
                        <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                        Power BI
                    </Link>
                </nav>

                <div class="border-t border-gray-100 bg-white p-4">
                    <div class="flex flex-col gap-2">
                        <Link
                            :href="route('profile.edit')"
                            @click="sidebarOpen = false"
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
                            class="flex items-center gap-3 rounded-xl px-4 py-3 text-red-600 transition hover:bg-red-50 w-full"
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
        <main class="flex-1 flex flex-col overflow-hidden transition-all duration-300 ml-0 md:ml-16">

            <!-- Header -->
            <header class="bg-white border-b border-gray-200 px-4 md:px-8 py-4 flex items-center justify-between z-10 shadow-sm md:shadow-none">

                <div class="flex items-center gap-3">
                    <!-- Hamburger Menu (Mobile Only) -->
                    <button 
                        @click="sidebarOpen = true"
                        class="p-2 -ml-2 text-gray-600 hover:bg-gray-100 rounded-lg md:hidden"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div>
                        <h1 class="text-lg md:text-2xl font-bold text-gray-900 leading-tight">
                            <slot name="header">
                                Dashboard
                            </slot>
                        </h1>
                        <p class="text-xs md:text-sm text-gray-500 mt-0.5 hidden md:block">
                            Indosat Promotor Monitoring System
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 md:gap-4">
                    <div class="text-right hidden sm:block">
                        <p class="font-semibold text-gray-800 text-sm md:text-base">
                            {{ $page.props.auth.user.name }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Administrator
                        </p>
                    </div>

                    <div class="w-9 h-9 md:w-11 md:h-11 rounded-full bg-[#ED1C24] text-white flex items-center justify-center font-bold shadow text-sm md:text-base">
                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                    </div>
                </div>
            </header>

            <!-- Content -->
            <section class="flex-1 overflow-auto p-4 md:p-8 bg-[#F8F9FA]">
                <slot />
            </section>
        </main>
    </div>
</template>