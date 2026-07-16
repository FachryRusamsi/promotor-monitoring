<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PromotorLayout from '@/Layouts/PromotorLayout.vue';

defineProps<{
    metrics: {
        edukasi: number;
        penjualan: number;
        aktivasi: number;
    };
    kpiAchievement: {
        edukasi: number;
        rebuy: number;
        sp: number;
        gemini: number;
    };
    attendanceStatus: string;
}>();

const KPI_TARGETS = {
    edukasi: 600,
    rebuy:   200,
    sp:      100,
    gemini:  100,
};

function pct(val: number, target: number): number {
    return Math.min(Math.round((val / target) * 100), 100);
}

function colorClass(p: number): string {
    if (p >= 100) return 'bg-emerald-500';
    if (p >= 60)  return 'bg-indigo-500';
    if (p >= 30)  return 'bg-amber-400';
    return 'bg-red-400';
}

function textColor(p: number): string {
    if (p >= 100) return 'text-emerald-600';
    if (p >= 60)  return 'text-indigo-600';
    if (p >= 30)  return 'text-amber-500';
    return 'text-red-500';
}
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
            
            <!-- Metrics + KPI Progress (merged card) -->
            <div class="bg-white shadow-sm border border-gray-100 p-5 rounded-xl">
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

                <!-- Divider -->
                <div class="border-t border-gray-100 my-5"></div>

                <!-- Progress KPI -->
                <h3 class="text-sm font-bold text-gray-800 mb-4">Progress KPI</h3>

                <div class="space-y-4">
                    <!-- Edukasi -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700">Edukasi</span>
                            <span :class="['text-sm font-bold', textColor(pct(kpiAchievement.edukasi, KPI_TARGETS.edukasi))]">
                                {{ kpiAchievement.edukasi }} / {{ KPI_TARGETS.edukasi }}
                                <span class="text-xs font-normal text-gray-400 ml-1">({{ pct(kpiAchievement.edukasi, KPI_TARGETS.edukasi) }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div :class="['h-2.5 rounded-full transition-all duration-700', colorClass(pct(kpiAchievement.edukasi, KPI_TARGETS.edukasi))]" :style="{ width: pct(kpiAchievement.edukasi, KPI_TARGETS.edukasi) + '%' }"></div>
                        </div>
                    </div>

                    <!-- Rebuy -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700">Rebuy (Pulsa)</span>
                            <span :class="['text-sm font-bold', textColor(pct(kpiAchievement.rebuy, KPI_TARGETS.rebuy))]">
                                {{ kpiAchievement.rebuy }} / {{ KPI_TARGETS.rebuy }}
                                <span class="text-xs font-normal text-gray-400 ml-1">({{ pct(kpiAchievement.rebuy, KPI_TARGETS.rebuy) }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div :class="['h-2.5 rounded-full transition-all duration-700', colorClass(pct(kpiAchievement.rebuy, KPI_TARGETS.rebuy))]" :style="{ width: pct(kpiAchievement.rebuy, KPI_TARGETS.rebuy) + '%' }"></div>
                        </div>
                    </div>

                    <!-- Starter Pack -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700">Acquisition (SP)</span>
                            <span :class="['text-sm font-bold', textColor(pct(kpiAchievement.sp, KPI_TARGETS.sp))]">
                                {{ kpiAchievement.sp }} / {{ KPI_TARGETS.sp }}
                                <span class="text-xs font-normal text-gray-400 ml-1">({{ pct(kpiAchievement.sp, KPI_TARGETS.sp) }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div :class="['h-2.5 rounded-full transition-all duration-700', colorClass(pct(kpiAchievement.sp, KPI_TARGETS.sp))]" :style="{ width: pct(kpiAchievement.sp, KPI_TARGETS.sp) + '%' }"></div>
                        </div>
                    </div>

                    <!-- Gemini Claim -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700">Gemini Claim</span>
                            <span :class="['text-sm font-bold', textColor(pct(kpiAchievement.gemini, KPI_TARGETS.gemini))]">
                                {{ kpiAchievement.gemini }} / {{ KPI_TARGETS.gemini }}
                                <span class="text-xs font-normal text-gray-400 ml-1">({{ pct(kpiAchievement.gemini, KPI_TARGETS.gemini) }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div :class="['h-2.5 rounded-full transition-all duration-700', colorClass(pct(kpiAchievement.gemini, KPI_TARGETS.gemini))]" :style="{ width: pct(kpiAchievement.gemini, KPI_TARGETS.gemini) + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Status -->
            <div class="bg-white shadow-sm border border-gray-100 p-5 rounded-xl flex items-center justify-between">
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

            <!-- Quick Actions -->
            <div class="grid grid-cols-2 gap-3 md:gap-4">
                <Link :href="route('promotor.edukasi.index')" class="bg-white shadow-md p-3 md:p-4 flex flex-col items-center justify-center gap-2 md:gap-3 transition-transform active:scale-95 border border-gray-100 rounded-xl text-center">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-full bg-blue-50 flex items-center justify-center">
                        <img src="/image/icon/Icon IOH-Career.png" alt="Lapor Edukasi" class="w-7 h-7 md:w-8 md:h-8 object-contain" />
                    </div>
                    <span class="font-semibold text-gray-800 text-xs md:text-sm">Lapor Edukasi</span>
                </Link>
                <Link :href="route('promotor.transactions.index')" class="bg-white shadow-md p-3 md:p-4 flex flex-col items-center justify-center gap-2 md:gap-3 transition-transform active:scale-95 border border-gray-100 rounded-xl text-center">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-full bg-emerald-50 flex items-center justify-center">
                        <img src="/image/icon/Icon IOH-Report.png" alt="Lapor Transaksi" class="w-7 h-7 md:w-8 md:h-8 object-contain" />
                    </div>
                    <span class="font-semibold text-gray-800 text-xs md:text-sm">Lapor Transaksi</span>
                </Link>
            </div>
            
        </div>
    </PromotorLayout>
</template>