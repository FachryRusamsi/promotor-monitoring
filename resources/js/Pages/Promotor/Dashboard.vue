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
        <div class="bg-slate-100 text-slate-950 pt-28 pb-16 pl-6 pr-0 shadow-lg relative overflow-hidden">
            <img src="/image/logos/Logogram IOH.png" alt="Logogram IOH" class="pointer-events-none absolute -left-16 -top-16 h-96 w-auto opacity-80" />

            <div class="relative z-10 flex w-full items-start justify-center">
                <div class="text-left w-full max-w-2xl mx-auto">
                    <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-white">Halo, {{ $page.props.auth.user.name.split(' ')[0] }}!</h1>
                </div>
            </div>
        </div>

        <!-- Main Content inside overlapping card -->
        <div class="px-5 -mt-10 mb-8 relative z-20 space-y-6">
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-3 gap-3 md:gap-4">
                <Link :href="route('promotor.attendance.index')" class="bg-white shadow-md p-3 md:p-4 flex flex-col items-center justify-center gap-2 md:gap-3 transition-transform active:scale-95 border border-gray-100 rounded-xl text-center">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-full bg-indigo-50 flex items-center justify-center">
                        <img src="/image/icon/Icon%20IOH-Register.png" alt="Absensi" class="w-7 h-7 md:w-8 md:h-8 object-contain" />
                    </div>
                    <span class="font-semibold text-gray-800 text-xs md:text-sm">Absensi</span>
                </Link>
                <Link :href="route('promotor.edukasi.index')" class="bg-white shadow-md p-3 md:p-4 flex flex-col items-center justify-center gap-2 md:gap-3 transition-transform active:scale-95 border border-gray-100 rounded-xl text-center">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-full bg-blue-50 flex items-center justify-center">
                        <img src="/image/icon/Icon%20IOH-Stock%20Market.png" alt="Lapor Edukasi" class="w-7 h-7 md:w-8 md:h-8 object-contain" />
                    </div>
                    <span class="font-semibold text-gray-800 text-xs md:text-sm">Lapor Edukasi</span>
                </Link>
                <Link :href="route('promotor.transactions.index')" class="bg-white shadow-md p-3 md:p-4 flex flex-col items-center justify-center gap-2 md:gap-3 transition-transform active:scale-95 border border-gray-100 rounded-xl text-center">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-full bg-emerald-50 flex items-center justify-center">
                        <img src="/image/icon/Icon IOH-Report.png" alt="Lapor Penjualan" class="w-7 h-7 md:w-8 md:h-8 object-contain" />
                    </div>
                    <span class="font-semibold text-gray-800 text-xs md:text-sm">Lapor Penjualan</span>
                </Link>
            </div>

            <!-- Metrics -->
            <div class="bg-white shadow-sm border border-gray-100 p-5">
                <h2 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <img src="/image/icon/Icon%20IOH-Stock%20Market.png" alt="Pencapaian Hari Ini" class="w-5 h-5 object-contain" />
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
            <div class="bg-white shadow-sm border border-gray-100 p-5 flex items-center justify-between">
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