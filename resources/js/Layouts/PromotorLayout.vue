<script setup lang="ts">
import { watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
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
    <div class="min-h-screen bg-gray-50 flex flex-col pb-20 md:pb-0">
        
        <!-- Top App Bar (Mobile) -->
        <header class="bg-slate-800 text-white shadow-md sticky top-0 z-40 md:hidden">
            <div class="flex justify-between items-center px-4 h-14">
                <h1 class="text-lg font-bold">
                    <slot name="header">PromotorApp</slot>
                </h1>
                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center overflow-hidden focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </button>
                    </template>
                    <template #content>
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $page.props.auth.user.name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $page.props.auth.user.phone }}</p>
                        </div>
                        <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-600 font-medium">
                            Log Out
                        </DropdownLink>
                    </template>
                </Dropdown>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 w-full max-w-lg mx-auto relative md:mt-10 md:mb-10 md:bg-white md:shadow-xl md:overflow-hidden md:border md:border-gray-100">
            <!-- Desktop Header Simulation -->
            <div class="hidden md:flex bg-slate-800 text-white h-16 items-center px-6 justify-between">
                <h1 class="text-xl font-bold"><slot name="header">PromotorApp</slot></h1>
                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button class="flex items-center gap-2 hover:bg-slate-700 px-3 py-1.5 rounded-lg transition-colors focus:outline-none">
                            <span class="text-sm">{{ $page.props.auth.user.name }}</span>
                            <div class="w-8 h-8 rounded-full bg-white text-slate-800 flex items-center justify-center font-bold">
                                {{ $page.props.auth.user.name.charAt(0) }}
                            </div>
                        </button>
                    </template>
                    <template #content>
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $page.props.auth.user.name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $page.props.auth.user.phone }}</p>
                        </div>
                        <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-600 font-medium">
                            Log Out
                        </DropdownLink>
                    </template>
                </Dropdown>
            </div>
            
            <div class="w-full relative">
                <slot />
            </div>
            
            <!-- Desktop Navigation Simulation -->
            <div class="hidden md:flex border-t border-gray-100 bg-gray-50">
                <div class="flex w-full justify-around py-3">
                    <Link :href="route('promotor.dashboard')" :class="['flex flex-col items-center p-2 rounded-xl w-24 transition-colors', route().current('promotor.dashboard') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-500 hover:bg-gray-100']">
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                        <span class="text-xs font-medium">Beranda</span>
                    </Link>
                    <Link :href="route('promotor.attendance.index')" :class="['flex flex-col items-center p-2 rounded-xl w-24 transition-colors', route().current('promotor.attendance.*') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-500 hover:bg-gray-100']">
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span class="text-xs font-medium">Absen</span>
                    </Link>
                    <Link :href="route('promotor.edukasi.index')" :class="['flex flex-col items-center p-2 rounded-xl w-24 transition-colors', route().current('promotor.edukasi.*') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-500 hover:bg-gray-100']">
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v7" /></svg>
                        <span class="text-xs font-medium">Edukasi</span>
                    </Link>
                    <Link :href="route('promotor.transactions.index')" :class="['flex flex-col items-center p-2 rounded-xl w-24 transition-colors', route().current('promotor.transactions.*') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-500 hover:bg-gray-100']">
                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                        <span class="text-xs font-medium">Transaksi</span>
                    </Link>
                </div>
            </div>
        </main>

        <!-- Bottom Navigation Bar (Mobile) -->
        <nav class="md:hidden fixed bottom-0 w-full bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-50">
            <div class="flex justify-around items-center h-16">
                
                <Link :href="route('promotor.dashboard')" :class="['flex flex-col items-center justify-center w-full h-full space-y-1', route().current('promotor.dashboard') ? 'text-indigo-600' : 'text-gray-500']">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="text-[10px] font-medium">Beranda</span>
                </Link>

                <Link :href="route('promotor.attendance.index')" :class="['flex flex-col items-center justify-center w-full h-full space-y-1', route().current('promotor.attendance.*') ? 'text-indigo-600' : 'text-gray-500']">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-[10px] font-medium">Absen</span>
                </Link>

                <Link :href="route('promotor.edukasi.index')" :class="['flex flex-col items-center justify-center w-full h-full space-y-1', route().current('promotor.edukasi.*') ? 'text-indigo-600' : 'text-gray-500']">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v7" />
                    </svg>
                    <span class="text-[10px] font-medium">Edukasi</span>
                </Link>

                <Link :href="route('promotor.transactions.index')" :class="['flex flex-col items-center justify-center w-full h-full space-y-1', route().current('promotor.transactions.*') ? 'text-indigo-600' : 'text-gray-500']">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span class="text-[10px] font-medium">Transaksi</span>
                </Link>

            </div>
        </nav>
    </div>
</template>
