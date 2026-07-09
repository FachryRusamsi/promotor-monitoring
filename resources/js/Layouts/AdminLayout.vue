<script setup lang="ts">
import { watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const page = usePage();

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

watch(() => page.props.flash, (flash: any) => {
    if (flash?.success) {
        Toast.fire({
            icon: 'success',
            title: flash.success
        });
    }
    if (flash?.error) {
        Toast.fire({
            icon: 'error',
            title: flash.error
        });
    }
}, { deep: true, immediate: true });
</script>

<template>
    <div class="flex h-screen bg-gray-100 overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-indigo-900 text-white flex flex-col">
            <div class="h-16 flex items-center justify-center border-b border-indigo-800">
                <h1 class="text-xl font-bold tracking-wider">PROMOTOR<span class="text-indigo-400">MONITOR</span></h1>
            </div>
            
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <Link :href="route('admin.dashboard')" 
                    :class="['flex items-center px-4 py-3 rounded-lg transition-colors', 
                             route().current('admin.dashboard') ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50']">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    KPI Dashboard
                </Link>

                <Link :href="route('admin.monitoring')" 
                    :class="['flex items-center px-4 py-3 rounded-lg transition-colors', 
                             route().current('admin.monitoring') ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50']">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    Live Monitoring
                </Link>

                <Link :href="route('admin.reports.index')" 
                    :class="['flex items-center px-4 py-3 rounded-lg transition-colors', 
                             route().current('admin.reports.index') ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50']">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Reports
                </Link>
            </nav>

            <div class="p-4 border-t border-indigo-800">
                <Link :href="route('profile.edit')" class="flex items-center px-4 py-2 text-indigo-200 hover:text-white transition-colors">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Profile
                </Link>
                <Link :href="route('logout')" method="post" as="button" class="mt-2 w-full flex items-center px-4 py-2 text-red-300 hover:text-red-100 transition-colors text-left">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Log Out
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 border-b border-gray-200 z-10">
                <div class="text-xl font-semibold text-gray-800">
                    <slot name="header">Dashboard</slot>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">{{ $page.props.auth.user.name }}</span>
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="flex-1 overflow-auto bg-gray-50 relative">
                <slot />
            </div>
        </main>
    </div>
</template>
