<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    regions: Array,
    currentFilters: Object,
    kpi: Object,
    regionRankings: Array,
    topBranches: Array,
    topPromotors: Array,
    allPromotors: Array,
});

const selectedRegion = ref(props.currentFilters?.region_id || '');
const selectedArea = ref(props.currentFilters?.area_id || '');
const startDate = ref(props.currentFilters?.start_date || '');
const endDate = ref(props.currentFilters?.end_date || '');
const showAllPromotors = ref(false);

const availableAreas = computed(() => {
  if (!selectedRegion.value) return [];
  const region = props.regions.find(r => r.id == selectedRegion.value);
  return region ? region.areas : [];
});

watch([selectedRegion, selectedArea, startDate, endDate], ([newRegion, newArea, newStart, newEnd], [oldRegion]) => {
  if (newRegion !== oldRegion) {
    selectedArea.value = ''; // Reset area when region changes
  }
  
  import('@inertiajs/vue3').then(({ router }) => {
    router.get(route('admin.dashboard'), { 
      region_id: selectedRegion.value, 
      area_id: selectedArea.value,
      start_date: startDate.value,
      end_date: endDate.value
    }, { preserveState: true });
  });
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <template #header>Dashboard KPI Penjualan</template>

        <div class="p-4 md:p-6 space-y-6">
            <!-- Filter Section -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Filter Data</h2>
                    <p class="text-sm text-gray-500">Filter berdasarkan region, branch, atau rentang waktu.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 w-full xl:w-auto">
                    <input type="date" v-model="startDate" class="w-full md:w-36 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <span class="text-gray-400 hidden md:inline">-</span>
                    <input type="date" v-model="endDate" class="w-full md:w-36 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    
                    <select v-model="selectedRegion" class="w-full md:w-44 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">Semua Region (Nasional)</option>
                        <option v-for="region in regions" :key="region.id" :value="region.id">
                            {{ region.name }}
                        </option>
                    </select>
                    <select v-model="selectedArea" :disabled="!selectedRegion" class="w-full md:w-44 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm disabled:bg-gray-100">
                        <option value="">Semua Branch</option>
                        <option v-for="area in availableAreas" :key="area.id" :value="area.id">
                            {{ area.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- View Toggle Section -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-gray-800">Mode Tampilan</h2>
                    <p class="text-xs text-gray-500">Ubah cara data ditampilkan pada dashboard</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="font-medium text-gray-700 text-sm">Tampilkan Semua Promotor</span>
                    <button 
                        @click="showAllPromotors = !showAllPromotors"
                        :class="showAllPromotors ? 'bg-indigo-600' : 'bg-gray-300'"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
                    >
                        <span 
                            :class="showAllPromotors ? 'translate-x-5' : 'translate-x-0'"
                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                        />
                    </button>
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

            <!-- All Promotors Table View (Toggled) -->
            <div v-if="showAllPromotors" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">List Semua Promotor</h3>
                        <p class="text-sm text-gray-500 mt-1">Detail KPI individual untuk setiap promotor berdasarkan filter area aktif.</p>
                    </div>
                    <div class="text-sm font-medium text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full">
                        {{ allPromotors.length }} Promotor
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-sm font-semibold text-gray-600">
                                <th class="py-4 px-6">Nama Promotor</th>
                                <th class="py-4 px-6">Region / Branch</th>
                                <th class="py-4 px-6 text-right">Total Edukasi</th>
                                <th class="py-4 px-6 text-right">Total Starter Pack</th>
                                <th class="py-4 px-6 text-right">Total Pulsa</th>
                                <th class="py-4 px-6 text-right">Aktivasi Gemini</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="promotor in allPromotors" :key="promotor.id" class="hover:bg-gray-50 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="font-medium text-gray-800">{{ promotor.name }}</div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="text-sm text-gray-600">
                                        <span class="block">{{ promotor.region_name || '-' }}</span>
                                        <span class="block text-xs text-gray-400">{{ promotor.area_name || '-' }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 font-semibold w-16">
                                        {{ promotor.total_edukasi }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 font-semibold w-16">
                                        {{ promotor.total_sp }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 font-semibold w-16">
                                        {{ promotor.total_pulsa }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-md bg-purple-50 text-purple-700 font-semibold w-16">
                                        {{ promotor.total_gemini }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="allPromotors.length === 0">
                                <td colspan="6" class="py-8 text-center text-gray-500">
                                    Tidak ada data promotor yang sesuai dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Rankings (Hidden when showAllPromotors is true) -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Top 5 Branch</h3>
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

                <!-- Promotor Ranking -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Top 10 Promotor</h3>
                    <div class="space-y-4 h-[400px] overflow-y-auto pr-2">
                        <div v-for="(promotor, index) in topPromotors" :key="promotor.id" class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold flex-shrink-0">
                                    {{ index + 1 }}
                                </span>
                                <div>
                                    <p class="font-medium text-gray-700 line-clamp-1">{{ promotor.name }}</p>
                                    <p class="text-xs text-gray-400">{{ promotor.area_name }}, {{ promotor.region_name }}</p>
                                </div>
                            </div>
                            <span class="font-bold text-blue-600 flex-shrink-0">{{ promotor.total_sales }} Trx</span>
                        </div>
                        <div v-if="topPromotors.length === 0" class="text-center text-gray-500 py-4 text-sm">
                            Tidak ada data promotor.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>