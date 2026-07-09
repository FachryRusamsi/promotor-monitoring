<template>
    <div class="flex h-screen bg-[#F8F9FA] overflow-hidden">

        <!-- Sidebar -->
        <aside class="w-72 bg-white border-r border-gray-200 flex flex-col shadow-sm">

            <!-- Logo -->
            <div class="px-8 py-7 border-b border-gray-100">
                <div class="flex items-center gap-3">

                    <div
                        class="w-12 h-12 rounded-2xl bg-[#ED1C24] text-white flex items-center justify-center font-bold text-xl shadow-md">
                        I
                    </div>

                    <div>
                        <h1 class="font-bold text-gray-900 text-lg">
                            Promotor Monitoring
                        </h1>

                        <p class="text-xs text-gray-500">
                            Indosat Internal Dashboard
                        </p>
                    </div>

                </div>
            </div>

            <!-- Menu -->
            <nav class="flex-1 px-5 py-6 space-y-2">

                <Link
                    :href="route('admin.dashboard')"
                    :class="[
                        'flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200',
                        route().current('admin.dashboard')
                            ? 'bg-red-50 text-[#ED1C24] font-semibold shadow-sm'
                            : 'text-gray-600 hover:bg-red-50 hover:text-[#ED1C24]'
                    ]"
                >

                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            ? 'bg-red-50 text-[#ED1C24] font-semibold shadow-sm'
                            : 'text-gray-600 hover:bg-red-50 hover:text-[#ED1C24]'
                    ]"
                >

                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            ? 'bg-red-50 text-[#ED1C24] font-semibold shadow-sm'
                            : 'text-gray-600 hover:bg-red-50 hover:text-[#ED1C24]'
                    ]"
                >

                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            d="M9 17v-2m3 2v-5m3 5v-8M5 21h14"/>
                    </svg>

                    Reports

                </Link>

            </nav>

            <!-- Footer -->
            <div class="border-t border-gray-100 p-5 space-y-2">

                <Link
                    :href="route('profile.edit')"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-gray-100 transition">

                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5z"/>
                    </svg>

                    Profile

                </Link>

                <Link
                    method="post"
                    as="button"
                    :href="route('logout')"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 transition">

                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            d="M17 16l4-4-4-4m4 4H9m4 8H7a2 2 0 01-2-2V6a2 2 0 012-2h6"/>
                    </svg>

                    Logout

                </Link>

            </div>

        </aside>

        <!-- Main -->
        <main class="flex-1 flex flex-col overflow-hidden">

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

                    <div
                        class="w-11 h-11 rounded-full bg-[#ED1C24] text-white flex items-center justify-center font-bold shadow">

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