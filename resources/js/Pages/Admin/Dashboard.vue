<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, PointElement, LineElement, CategoryScale, LinearScale } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { Pie, Line } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend, PointElement, LineElement, CategoryScale, LinearScale, ChartDataLabels);

const props = defineProps({
    regions: Array,
    currentFilters: Object,
    kpi: Object,
    regionRankings: Array,
    topBranches: Array,
    allBranches: Array,
    topPromotors: Array,
    allPromotors: Array,
});

const selectedRegion = ref(props.currentFilters?.region_id || '');
const selectedArea = ref(props.currentFilters?.area_id || '');
const startDate = ref(props.currentFilters?.start_date || '');
const endDate = ref(props.currentFilters?.end_date || '');
const showAllPromotors = ref(false);
const forcePromotorView = ref(false);
const viewMode = ref('table'); // 'table' or 'chart'

const chartDataMode = computed(() => {
    if (forcePromotorView.value) return 'promotor';
    if (selectedArea.value) return 'promotor';
    if (selectedRegion.value) return 'branch';
    return 'region';
});

const currentChartDataList = computed(() => {
    if (chartDataMode.value === 'promotor') return props.allPromotors;
    if (chartDataMode.value === 'branch') return props.allBranches;
    return props.regionRankings;
});

const pieChartTitle = computed(() => {
    if (chartDataMode.value === 'promotor') return 'Dominasi Keseluruhan (Berdasarkan Promotor)';
    if (chartDataMode.value === 'branch') return 'Dominasi Keseluruhan (Berdasarkan Branch)';
    return 'Dominasi Keseluruhan (Berdasarkan Region)';
});

const lineChartTitle = computed(() => {
    if (chartDataMode.value === 'promotor') return 'Perbandingan Performa Promotor (DSE)';
    if (chartDataMode.value === 'branch') return 'Perbandingan Performa Branch';
    return 'Perbandingan Performa Region';
});

const pieChartData = computed(() => ({
    labels: currentChartDataList.value.map(r => r.name),
    datasets: [{
        backgroundColor: ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#14B8A6', '#6366F1', '#84CC16'],
        data: currentChartDataList.value.map(r => r.total_sales !== undefined ? r.total_sales : (r.total_edukasi + r.total_sp + r.total_pulsa + r.total_gemini))
    }]
}));

const pieChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        datalabels: {
            color: '#fff',
            font: {
                weight: 'bold',
                size: 14
            },
            formatter: (value, ctx) => {
                const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                if (total === 0) return '0%';
                const percentage = Math.round((value / total) * 100) + '%';
                return percentage;
            },
        }
    }
};

const lineChartData = computed(() => ({
    labels: currentChartDataList.value.map(p => (p.name.length > 15 ? p.name.substring(0, 15) + '...' : p.name)),
    datasets: [
        { 
            label: 'Edukasi', 
            borderColor: '#EF4444', 
            backgroundColor: '#EF4444',
            tension: 0.3,
            data: currentChartDataList.value.map(p => p.total_edukasi) 
        },
        { 
            label: 'Starter Pack', 
            borderColor: '#F59E0B', 
            backgroundColor: '#F59E0B',
            tension: 0.3,
            data: currentChartDataList.value.map(p => p.total_sp) 
        },
        { 
            label: 'Pulsa', 
            borderColor: '#10B981', 
            backgroundColor: '#10B981',
            tension: 0.3,
            data: currentChartDataList.value.map(p => p.total_pulsa) 
        },
        { 
            label: 'Aktivasi Gemini', 
            borderColor: '#8B5CF6', 
            backgroundColor: '#8B5CF6',
            tension: 0.3,
            data: currentChartDataList.value.map(p => p.total_gemini) 
        }
    ]
}));

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        datalabels: {
            display: false
        }
    }
};

const availableAreas = computed(() => {
  if (!selectedRegion.value) return [];
  const region = props.regions.find(r => r.id == selectedRegion.value);
  return region ? region.areas : [];
});

watch([selectedRegion, selectedArea, startDate, endDate], ([newRegion, newArea, newStart, newEnd], [oldRegion]) => {
  if (newRegion !== oldRegion) {
    selectedArea.value = '';
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

// Modal State and Methods
const isModalOpen = ref(false);
const selectedPromotorId = ref(null);
const selectedPromotorName = ref('');
const promotorTransactions = ref([]);
const modalStartDate = ref('');
const modalEndDate = ref('');
const isLoadingTransactions = ref(false);
const expandedTrxId = ref(null);

const openPromotorModal = (promotor) => {
    selectedPromotorId.value = promotor.id;
    selectedPromotorName.value = promotor.name;
    modalStartDate.value = startDate.value;
    modalEndDate.value = endDate.value;
    isModalOpen.value = true;
    fetchTransactions();
};

const closePromotorModal = () => {
    isModalOpen.value = false;
    promotorTransactions.value = [];
    expandedTrxId.value = null;
};

const fetchTransactions = async () => {
    if (!selectedPromotorId.value) return;
    isLoadingTransactions.value = true;
    try {
        const params = new URLSearchParams();
        if (modalStartDate.value) params.append('start_date', modalStartDate.value);
        if (modalEndDate.value) params.append('end_date', modalEndDate.value);
        
        const res = await fetch(route('admin.promotor.transactions', { user: selectedPromotorId.value }) + '?' + params.toString());
        const data = await res.json();
        promotorTransactions.value = data;
    } catch (e) {
        console.error("Failed to fetch transactions:", e);
    } finally {
        isLoadingTransactions.value = false;
    }
};

const formatTime = (datetime) => {
    return new Date(datetime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};
const formatDate = (datetime) => {
    return new Date(datetime).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
};

// KPI Targets & Helpers
const KPI_TARGETS = { edukasi: 600, rebuy: 200, sp: 100, gemini: 100 };
const kpiPct = (val, target) => Math.min(Math.round((val / target) * 100), 100);
const avgKpi = (p) => Math.round((kpiPct(p.total_edukasi, KPI_TARGETS.edukasi) + kpiPct(p.total_pulsa, KPI_TARGETS.rebuy) + kpiPct(p.total_sp, KPI_TARGETS.sp) + kpiPct(p.total_gemini, KPI_TARGETS.gemini)) / 4);
const kpiColor = (p) => p >= 100 ? 'bg-emerald-500' : p >= 60 ? 'bg-indigo-500' : p >= 30 ? 'bg-amber-400' : 'bg-red-400';
const kpiTextColor = (p) => p >= 100 ? 'text-emerald-600' : p >= 60 ? 'text-indigo-600' : p >= 30 ? 'text-amber-500' : 'text-red-500';
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <template #header>Dashboard KPI Penjualan</template>

        <div class="p-4 md:p-6 flex flex-col xl:flex-row gap-6">
            <!-- LEFT COLUMN: Filters (Vertical) -->
            <div class="w-full xl:w-1/4 flex flex-col gap-4">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex flex-col gap-5 sticky top-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Filter Data</h2>
                        <p class="text-sm text-gray-500">Filter berdasarkan region, branch, atau rentang waktu.</p>
                    </div>
                    
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-1.5">
                            <span class="text-xs text-gray-500 font-medium">Mulai:</span>
                            <input type="date" v-model="startDate" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <span class="text-xs text-gray-500 font-medium">Hingga:</span>
                            <input type="date" v-model="endDate" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        
                        <div class="flex flex-col gap-1.5">
                            <span class="text-xs text-gray-500 font-medium">Region:</span>
                            <select v-model="selectedRegion" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">Semua Region (Nasional)</option>
                                <option v-for="region in regions" :key="region.id" :value="region.id">
                                    {{ region.name }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="flex flex-col gap-1.5">
                            <span class="text-xs text-gray-500 font-medium">Branch:</span>
                            <select v-model="selectedArea" :disabled="!selectedRegion" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm disabled:bg-gray-100">
                                <option value="">Semua Branch</option>
                                <option v-for="area in availableAreas" :key="area.id" :value="area.id">
                                    {{ area.name }}
                                </option>
                            </select>
                        </div>

                        <div class="flex items-center justify-between mt-2 pt-4 border-t border-gray-100">
                            <div>
                                <span class="text-sm font-bold text-gray-700 block">Tampilkan Promotor</span>
                                <span class="text-xs text-gray-500">Ubah grafik ke data promotor</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="forcePromotorView" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Main Content -->
            <div class="w-full xl:w-3/4 flex flex-col gap-6">
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

                <!-- CHART VIEW -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Pie Chart: Dominasi -->
                        <div class="lg:col-span-1 flex flex-col h-[350px]">
                            <h4 class="text-sm font-bold text-gray-700 mb-4 text-center">{{ pieChartTitle }}</h4>
                            <div class="flex-1 relative">
                                <Pie v-if="currentChartDataList.length > 0" :data="pieChartData" :options="pieChartOptions" />
                                <div v-else class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm">
                                    Tidak ada data.
                                </div>
                            </div>
                        </div>

                        <!-- Bar Chart: Performa -->
                        <div class="lg:col-span-2 flex flex-col h-[350px]">
                            <h4 class="text-sm font-bold text-gray-700 mb-4 text-center">{{ lineChartTitle }}</h4>
                            <div class="flex-1 relative">
                                <Line v-if="currentChartDataList.length > 0" :data="lineChartData" :options="lineChartOptions" />
                                <div v-else class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm">
                                    Tidak ada data.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TOP 5 RANKINGS -->
                <div v-if="!forcePromotorView" class="flex flex-col gap-6">
                    <!-- Top Region and Top Branch Side-by-Side -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                    </div>

                    <!-- Promotor Ranking Full Width -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Top 5 Promotor</h3>
                        <div class="space-y-4">
                            <div v-for="(promotor, index) in topPromotors" :key="promotor.id" @click="openPromotorModal(promotor)" class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors group border border-transparent hover:border-gray-100">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                        {{ index + 1 }}
                                    </span>
                                    <div>
                                        <p class="font-medium text-gray-700 group-hover:text-indigo-600 transition-colors">{{ promotor.name }}</p>
                                        <p class="text-xs text-gray-400">{{ promotor.area_name }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <div class="flex items-center gap-3">
                                        <span :class="['text-sm font-bold', kpiTextColor(avgKpi(promotor))]">KPI: {{ avgKpi(promotor) }}%</span>
                                        <span class="text-gray-300">|</span>
                                        <span class="font-bold text-lg text-blue-600">{{ promotor.total_sales }} Transaksi</span>
                                    </div>
                                    <div class="flex items-center gap-4 mt-1">
                                        <!-- Edukasi -->
                                        <div class="flex flex-col items-center gap-1.5">
                                            <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">Edu</span>
                                            <div class="w-14 bg-gray-100 rounded-full h-2 flex overflow-hidden">
                                                <div :class="['h-full transition-all', kpiColor(kpiPct(promotor.total_edukasi, KPI_TARGETS.edukasi))]" :style="{ width: kpiPct(promotor.total_edukasi, KPI_TARGETS.edukasi) + '%' }"></div>
                                            </div>
                                            <span :class="['text-xs font-bold', kpiTextColor(kpiPct(promotor.total_edukasi, KPI_TARGETS.edukasi))]">{{ kpiPct(promotor.total_edukasi, KPI_TARGETS.edukasi) }}%</span>
                                        </div>
                                        <!-- Rebuy -->
                                        <div class="flex flex-col items-center gap-1.5">
                                            <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">Rby</span>
                                            <div class="w-14 bg-gray-100 rounded-full h-2 flex overflow-hidden">
                                                <div :class="['h-full transition-all', kpiColor(kpiPct(promotor.total_pulsa, KPI_TARGETS.rebuy))]" :style="{ width: kpiPct(promotor.total_pulsa, KPI_TARGETS.rebuy) + '%' }"></div>
                                            </div>
                                            <span :class="['text-xs font-bold', kpiTextColor(kpiPct(promotor.total_pulsa, KPI_TARGETS.rebuy))]">{{ kpiPct(promotor.total_pulsa, KPI_TARGETS.rebuy) }}%</span>
                                        </div>
                                        <!-- SP -->
                                        <div class="flex flex-col items-center gap-1.5">
                                            <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">SP</span>
                                            <div class="w-14 bg-gray-100 rounded-full h-2 flex overflow-hidden">
                                                <div :class="['h-full transition-all', kpiColor(kpiPct(promotor.total_sp, KPI_TARGETS.sp))]" :style="{ width: kpiPct(promotor.total_sp, KPI_TARGETS.sp) + '%' }"></div>
                                            </div>
                                            <span :class="['text-xs font-bold', kpiTextColor(kpiPct(promotor.total_sp, KPI_TARGETS.sp))]">{{ kpiPct(promotor.total_sp, KPI_TARGETS.sp) }}%</span>
                                        </div>
                                        <!-- Gemini -->
                                        <div class="flex flex-col items-center gap-1.5">
                                            <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">Gem</span>
                                            <div class="w-14 bg-gray-100 rounded-full h-2 flex overflow-hidden">
                                                <div :class="['h-full transition-all', kpiColor(kpiPct(promotor.total_gemini, KPI_TARGETS.gemini))]" :style="{ width: kpiPct(promotor.total_gemini, KPI_TARGETS.gemini) + '%' }"></div>
                                            </div>
                                            <span :class="['text-xs font-bold', kpiTextColor(kpiPct(promotor.total_gemini, KPI_TARGETS.gemini))]">{{ kpiPct(promotor.total_gemini, KPI_TARGETS.gemini) }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="topPromotors.length === 0" class="text-center text-gray-500 py-8 text-sm">
                                Tidak ada data promotor.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ALL PROMOTORS TABLE -->
                <div v-if="forcePromotorView" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h3 class="font-bold text-gray-800">Daftar Seluruh Promotor</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Promotor</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Area / Region</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Edukasi</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">SP</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Pulsa</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Gemini</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="promotor in allPromotors" :key="promotor.id" @click="openPromotorModal(promotor)" class="hover:bg-indigo-50/50 transition-colors cursor-pointer group">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900 group-hover:text-indigo-600 transition-colors">{{ promotor.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-600">{{ promotor.area_name }}</div>
                                        <div class="text-xs text-gray-400">{{ promotor.region_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-700">{{ promotor.total_edukasi }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-700">{{ promotor.total_sp }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-700">{{ promotor.total_pulsa }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-700">{{ promotor.total_gemini }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-indigo-600">{{ promotor.total_sales }}</td>
                                </tr>
                                <tr v-if="allPromotors.length === 0">
                                    <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                        Tidak ada data promotor.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal Detail Promotor -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75" @click="closePromotorModal"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-gray-50 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full flex flex-col h-[90vh]">
                    <!-- Modal Header -->
                    <div class="bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center shrink-0">
                        <div>
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                                Performa Kinerja Promotor: {{ selectedPromotorName }}
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">Akumulasi kinerja per tanggal.</p>
                        </div>
                        <button @click="closePromotorModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Modal Filter -->
                    <div class="bg-white px-6 py-3 border-b border-gray-100 flex flex-col sm:flex-row gap-4 shrink-0">
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <span class="text-xs text-gray-500 min-w-[3rem] sm:min-w-0">Mulai:</span>
                            <input type="date" v-model="modalStartDate" @change="fetchTransactions" class="flex-1 sm:w-auto rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1">
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <span class="text-xs text-gray-500 min-w-[3rem] sm:min-w-0">Selesai:</span>
                            <input type="date" v-model="modalEndDate" @change="fetchTransactions" class="flex-1 sm:w-auto rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1">
                        </div>
                    </div>
                    
                    <!-- Modal Content (Scrollable) -->
                    <div class="px-6 py-4 bg-gray-50 flex-1 overflow-y-auto">
                        <div v-if="isLoadingTransactions" class="flex justify-center py-10">
                            <svg class="animate-spin h-8 w-8 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        
                        <div v-else-if="promotorTransactions.length === 0" class="text-center py-10 text-gray-500">
                            Tidak ada transaksi untuk rentang waktu ini.
                        </div>
                        
                        <div v-else class="space-y-4">
                            <!-- Accumulation Summary for Modal -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 bg-white p-4 rounded-xl border border-gray-200">
                                <div class="text-center pb-3 md:pb-0 border-b md:border-b-0 md:border-r border-gray-100">
                                    <p class="text-xs text-gray-500">Edukasi</p>
                                    <p class="text-xl font-bold text-indigo-600">{{ promotorTransactions.reduce((acc, t) => acc + t.jml_edukasi, 0) }}</p>
                                    <p :class="['text-sm font-bold mt-0.5', kpiTextColor(kpiPct(promotorTransactions.reduce((acc, t) => acc + t.jml_edukasi, 0), KPI_TARGETS.edukasi))]">
                                        {{ kpiPct(promotorTransactions.reduce((acc, t) => acc + t.jml_edukasi, 0), KPI_TARGETS.edukasi) }}% KPI
                                    </p>
                                </div>
                                <div class="text-center pb-3 md:pb-0 border-b md:border-b-0 md:border-r border-gray-100">
                                    <p class="text-xs text-gray-500">Starter Pack</p>
                                    <p class="text-xl font-bold text-emerald-600">{{ promotorTransactions.reduce((acc, t) => acc + t.jml_sp, 0) }}</p>
                                    <p :class="['text-sm font-bold mt-0.5', kpiTextColor(kpiPct(promotorTransactions.reduce((acc, t) => acc + t.jml_sp, 0), KPI_TARGETS.sp))]">
                                        {{ kpiPct(promotorTransactions.reduce((acc, t) => acc + t.jml_sp, 0), KPI_TARGETS.sp) }}% KPI
                                    </p>
                                </div>
                                <div class="text-center pt-3 md:pt-0 border-r-0 md:border-r border-gray-100">
                                    <p class="text-xs text-gray-500">Rebuy</p>
                                    <p class="text-xl font-bold text-blue-600">{{ promotorTransactions.reduce((acc, t) => acc + t.jml_pulsa, 0) }}</p>
                                    <p :class="['text-sm font-bold mt-0.5', kpiTextColor(kpiPct(promotorTransactions.reduce((acc, t) => acc + t.jml_pulsa, 0), KPI_TARGETS.rebuy))]">
                                        {{ kpiPct(promotorTransactions.reduce((acc, t) => acc + t.jml_pulsa, 0), KPI_TARGETS.rebuy) }}% KPI
                                    </p>
                                </div>
                                <div class="text-center pt-3 md:pt-0 border-l border-gray-100 md:border-l-0">
                                    <p class="text-xs text-gray-500">Gemini</p>
                                    <p class="text-xl font-bold text-purple-600">{{ promotorTransactions.reduce((acc, t) => acc + t.jml_aktivasi_gemini, 0) }}</p>
                                    <p :class="['text-sm font-bold mt-0.5', kpiTextColor(kpiPct(promotorTransactions.reduce((acc, t) => acc + t.jml_aktivasi_gemini, 0), KPI_TARGETS.gemini))]">
                                        {{ kpiPct(promotorTransactions.reduce((acc, t) => acc + t.jml_aktivasi_gemini, 0), KPI_TARGETS.gemini) }}% KPI
                                    </p>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No.</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipe Laporan</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-100">
                                            <template v-for="(trx, index) in promotorTransactions" :key="trx.id">
                                                <tr @click="expandedTrxId = expandedTrxId === trx.id ? null : trx.id" class="hover:bg-gray-50 transition-colors cursor-pointer">
                                                    <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-700">{{ index + 1 }}.</td>
                                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ formatDate(trx.created_at) }}</td>
                                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ formatTime(trx.created_at) }}</td>
                                                    <td class="px-6 py-3 whitespace-nowrap text-sm">
                                                        <span v-if="trx.jml_edukasi > 0" class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100">Edukasi</span>
                                                        <span v-else class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-100">Penjualan</span>
                                                    </td>
                                                </tr>
                                                
                                                <tr v-if="expandedTrxId === trx.id">
                                                    <td colspan="4" class="p-0 border-b-0">
                                                        <div class="bg-indigo-50/30 p-4 sm:p-6 border-b border-gray-100">
                                                            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex flex-col md:flex-row gap-6">
                                                                <!-- Info Kiri -->
                                                                <div class="flex-1 space-y-3">
                                                                    <div class="flex justify-between items-start">
                                                                        <div>
                                                                            <span class="text-xs font-bold text-indigo-500 uppercase tracking-wider">{{ formatDate(trx.created_at) }}</span>
                                                                            <h4 class="text-lg font-bold text-gray-800 mt-1 flex items-center gap-2">
                                                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                                                {{ formatTime(trx.created_at) }}
                                                                            </h4>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div v-if="trx.details && trx.details.length > 0" class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                                                        <p class="text-xs font-semibold text-gray-500 mb-2">DETAIL TRANSAKSI</p>
                                                                        <div v-for="d in trx.details" :key="d.id" class="flex justify-between text-sm">
                                                                            <span class="font-medium text-gray-800">{{ d.msisdn }}</span>
                                                                            <span class="text-gray-500 capitalize">{{ d.type.replace('_', ' ') }}</span>
                                                                        </div>
                                                                    </div>

                                                                    <div>
                                                                        <p class="text-xs font-semibold text-gray-500 flex items-center gap-1 mb-1">
                                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                                                            LOKASI
                                                                        </p>
                                                                        <p class="text-sm font-medium text-gray-800">{{ trx.location_name || '-' }}</p>
                                                                        <a v-if="trx.latitude && trx.longitude" :href="`https://www.google.com/maps/search/?api=1&query=${trx.latitude},${trx.longitude}`" target="_blank" class="text-xs text-indigo-600 hover:underline mt-1 inline-block">Buka di Maps ({{ trx.latitude }}, {{ trx.longitude }})</a>
                                                                    </div>
                                                                </div>

                                                                <!-- Bukti Kanan -->
                                                                <div class="w-full md:w-48 flex gap-2 md:flex-col shrink-0">
                                                                    <div v-if="trx.jml_edukasi > 0" class="flex-1 md:w-full">
                                                                        <p class="text-xs font-semibold text-gray-500 mb-1 text-center">FOTO EDUKASI</p>
                                                                        <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                                                                            <img v-if="trx.foto_edukasi" :src="typeof trx.foto_edukasi === 'string' && trx.foto_edukasi.startsWith('[') ? '/storage/' + JSON.parse(trx.foto_edukasi)[0] : '/storage/' + trx.foto_edukasi" alt="Edukasi" class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition-opacity" onclick="window.open(this.src, '_blank')" />
                                                                            <div v-else class="w-full h-full flex items-center justify-center text-xs text-gray-400">N/A</div>
                                                                        </div>
                                                                    </div>
                                                                    <div v-else class="flex-1 md:w-full">
                                                                        <p class="text-xs font-semibold text-gray-500 mb-1 text-center">FOTO PENJUALAN</p>
                                                                        <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                                                                            <img v-if="trx.foto_penjualan" :src="typeof trx.foto_penjualan === 'string' && trx.foto_penjualan.startsWith('[') ? '/storage/' + JSON.parse(trx.foto_penjualan)[0] : '/storage/' + trx.foto_penjualan" alt="Penjualan" class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition-opacity" onclick="window.open(this.src, '_blank')" />
                                                                            <div v-else class="w-full h-full flex items-center justify-center text-xs text-gray-400">N/A</div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>