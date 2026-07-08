<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PromotorLayout from '@/Layouts/PromotorLayout.vue';

defineProps<{
    metrics: {
        edukasi: number;
        penjualan: number;
        aktivasi: number;
    };
    attendanceStatus: string;
}>();
</script>

<template>
    <Head title="Beranda Promotor" />
    <PromotorLayout>
        <!-- Header Section -->
        <div class="bg-indigo-600 text-white pt-8 pb-16 px-6 rounded-b-3xl shadow-lg relative overflow-hidden">
            <!-- Decorative circles -->
            <div class="absolute top-0 right-0 -mr-16 -mt-16 w-48 h-48 rounded-full bg-white opacity-10"></div>
            <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-40 h-40 rounded-full bg-white opacity-10"></div>
            
            <div class="relative z-10">
                <h1 class="text-3xl font-extrabold tracking-tight">Halo, {{ $page.props.auth.user.name.split(' ')[0] }}!</h1>
                <p class="text-indigo-200 mt-2 text-sm font-medium">Tetap semangat capai target hari ini ya!</p>
            </div>
        </div>

        <!-- Main Content inside overlapping card -->
        <div class="px-5 -mt-10 mb-8 relative z-20 space-y-6">
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-2 gap-4">
                <Link :href="route('promotor.attendance.index')" class="bg-white rounded-2xl shadow-md p-4 flex flex-col items-center justify-center gap-3 transition-transform active:scale-95 border border-gray-100">
                    <div class="w-14 h-14 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span class="font-semibold text-gray-800 text-sm">Absensi</span>
                </Link>
                <Link :href="route('promotor.transactions.index')" class="bg-white rounded-2xl shadow-md p-4 flex flex-col items-center justify-center gap-3 transition-transform active:scale-95 border border-gray-100">
                    <div class="w-14 h-14 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    </div>
                    <span class="font-semibold text-gray-800 text-sm">Lapor Penjualan</span>
                </Link>
            </div>

            <!-- Metrics -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    Pencapaian Hari Ini
                </h2>
                
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-gray-50 rounded-xl p-3 text-center border border-gray-100">
                        <h3 class="text-2xl font-extrabold text-indigo-600">{{ metrics.edukasi }}</h3>
                        <p class="text-[11px] font-medium text-gray-500 mt-1 uppercase tracking-wide">Edukasi</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-center border border-gray-100">
                        <h3 class="text-2xl font-extrabold text-emerald-600">{{ metrics.penjualan }}</h3>
                        <p class="text-[11px] font-medium text-gray-500 mt-1 uppercase tracking-wide">Penjualan</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-center border border-gray-100">
                        <h3 class="text-2xl font-extrabold text-blue-600">{{ metrics.aktivasi }}</h3>
                        <p class="text-[11px] font-medium text-gray-500 mt-1 uppercase tracking-wide">Gemini</p>
                    </div>
                </div>
            </div>

            <!-- Attendance Status -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-500">Status Kehadiran</h2>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="relative flex h-3 w-3">
                            <span v-if="attendanceStatus === 'Sudah Check In'" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3" 
                                  :class="{'bg-emerald-500': attendanceStatus === 'Sudah Check In', 'bg-red-500': attendanceStatus === 'Belum Check In', 'bg-gray-500': attendanceStatus === 'Sudah Check Out'}"></span>
                        </span>
                        <p class="font-bold text-gray-800" 
                           :class="{'text-emerald-700': attendanceStatus === 'Sudah Check In', 'text-red-600': attendanceStatus === 'Belum Check In', 'text-gray-600': attendanceStatus === 'Sudah Check Out'}">
                            {{ attendanceStatus }}
                        </p>
                    </div>
                </div>
                <Link v-if="attendanceStatus === 'Belum Check In'" :href="route('promotor.attendance.index')" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors shadow-sm">
                    Absen Sekarang
                </Link>
                <Link v-else-if="attendanceStatus === 'Sudah Check In'" :href="route('promotor.attendance.index')" class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors shadow-sm">
                    Check Out
                </Link>
                <div v-else class="bg-gray-100 text-gray-500 text-sm font-medium px-4 py-2 rounded-lg">
                    Selesai
                </div>
            </div>
            
        </div>
    </PromotorLayout>
</template>