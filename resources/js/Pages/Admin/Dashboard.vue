<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    regions: Array,
    currentRegionId: [String, Number],
    kpi: Object,
    regionRankings: Array,
    topBranches: Array,
});

const selectedRegion = ref(props.currentRegionId || '');

watch(selectedRegion, (newVal) => {
    router.get(route('admin.dashboard'), { region_id: newVal }, { preserveState: true });
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <template #header>Dashboard KPI Penjualan</template>

        <div class="p-4 md:p-6 space-y-6">
            <!-- Filter Section -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Filter Data</h2>
                    <p class="text-sm text-gray-500">Pilih region untuk melihat data spesifik.</p>
                </div>
                <div class="w-64">
                    <select v-model="selectedRegion" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua Region (Nasional)</option>
                        <option v-for="region in regions" :key="region.id" :value="region.id">
                            {{ region.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-5 shadow-lg text-white">
                    <h3 class="text-indigo-100 font-medium text-sm">Total Edukasi</h3>
                    <p class="text-3xl font-bold mt-2">{{ kpi.total_edukasi }}</p>
                </div>
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-5 shadow-lg text-white">
                    <h3 class="text-emerald-100 font-medium text-sm">Total Starter Pack</h3>
                    <p class="text-3xl font-bold mt-2">{{ kpi.total_sp }}</p>
                </div>
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 shadow-lg text-white">
                    <h3 class="text-blue-100 font-medium text-sm">Total Pulsa</h3>
                    <p class="text-3xl font-bold mt-2">{{ kpi.total_pulsa }}</p>
                </div>
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-5 shadow-lg text-white">
                    <h3 class="text-purple-100 font-medium text-sm">Aktivasi Gemini</h3>
                    <p class="text-3xl font-bold mt-2">{{ kpi.total_gemini }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Region Ranking -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Ranking Region (Nasional)</h3>
                    <div class="space-y-4">
                        <div v-for="(region, index) in regionRankings" :key="region.id" class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full flex items-center justify-center font-bold" 
                                    :class="index === 0 ? 'bg-yellow-100 text-yellow-700' : (index === 1 ? 'bg-gray-100 text-gray-700' : 'bg-orange-50 text-orange-700')">
                                    {{ index + 1 }}
                                </span>
                                <span class="font-medium text-gray-700">{{ region.name }}</span>
                            </div>
                            <span class="font-bold text-indigo-600">{{ region.total_sales }} Transaksi</span>
                        </div>
                    </div>
                </div>

                <!-- Branch Ranking -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Top 5 Branch {{ selectedRegion ? '(Filtered)' : '(Nasional)' }}</h3>
                    <div class="space-y-4">
                        <div v-for="(branch, index) in topBranches" :key="branch.id" class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                    {{ index + 1 }}
                                </span>
                                <div>
                                    <p class="font-medium text-gray-700">{{ branch.name }}</p>
                                    <p class="text-xs text-gray-400">{{ branch.region_name }}</p>
                                </div>
                            </div>
                            <span class="font-bold text-emerald-600">{{ branch.total_sales }} Transaksi</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>