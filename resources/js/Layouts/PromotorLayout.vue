<script setup lang="ts">
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
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
    <div class="min-h-screen bg-gray-50 flex flex-col">
        
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
        <main class="flex-1 w-full max-w-lg mx-auto relative md:mt-10 md:mb-10 md:bg-white md:shadow-xl md:overflow-hidden md:border md:border-gray-100 md:flex md:flex-col">
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
            
            <div class="w-full relative flex-1">
                <slot />
            </div>
            
            <!-- Desktop Navigation Simulation removed -->
        </main>
    </div>
</template>
