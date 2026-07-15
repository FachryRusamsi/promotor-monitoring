<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  reports: Array,
  date: String,
  regions: {
    type: Array,
    default: () => []
  },
  currentFilters: {
    type: Object,
    default: () => ({ region_id: '', area_id: '' })
  }
});

const selectedRegion = ref(props.currentFilters.region_id || '');
const selectedArea = ref(props.currentFilters.area_id || '');
const selectedDate = ref(props.date);
const expandedPromotorId = ref(null);

const availableAreas = computed(() => {
  if (!selectedRegion.value) return [];
  const region = props.regions.find(r => r.id == selectedRegion.value);
  return region ? region.areas : [];
});

watch([selectedRegion, selectedArea, selectedDate], ([newRegion, newArea, newDate], [oldRegion]) => {
  if (newRegion !== oldRegion) {
    selectedArea.value = ''; // Reset area when region changes
  }
  
  import('@inertiajs/vue3').then(({ router }) => {
    router.get(route('admin.reports.index'), { 
      region_id: selectedRegion.value, 
      area_id: selectedArea.value,
      date: selectedDate.value
    }, { preserveState: true });
  });
});

// CSV export has been replaced by Excel Export on the server side

const statusColors = {
  pending: 'bg-yellow-100 text-yellow-800',
  valid: 'bg-green-100 text-green-800',
  invalid: 'bg-red-100 text-red-800'
};

</script>

<template>
  <Head title="Report Admin" />

  <AdminLayout>
    <template #header>Rekapitulasi Harian</template>

    <div class="p-4 md:p-6 space-y-6">
      
      <!-- Top Action Bar -->
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
        <div>
          <h2 class="text-xl font-bold text-gray-800">Laporan Penjualan</h2>
          <p class="text-gray-500 text-sm mt-1">Tanggal: <span class="font-semibold text-indigo-600">{{ date }}</span></p>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
          
          <select v-model="selectedRegion" class="border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm flex-1 md:flex-none">
            <option value="">Semua Region</option>
            <option v-for="region in regions" :key="region.id" :value="region.id">{{ region.name }}</option>
          </select>
          
          <select v-model="selectedArea" :disabled="!selectedRegion" class="border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm flex-1 md:flex-none disabled:bg-gray-100">
            <option value="">Semua Branch</option>
            <option v-for="area in availableAreas" :key="area.id" :value="area.id">{{ area.name }}</option>
          </select>
          
          <input type="date" v-model="selectedDate" class="border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
          
          <a 
            :href="`/admin/reports/export?region_id=${selectedRegion}&area_id=${selectedArea}&start_date=${selectedDate}&end_date=${selectedDate}`" 
            target="_blank"
            class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 shadow-sm transition-colors flex items-center justify-center gap-2 whitespace-nowrap"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export Excel
          </a>
        </div>
      </div>

      <!-- Promotor Cards -->
      <div v-if="reports.length === 0" class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto text-gray-400 mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
        </div>
        <h3 class="text-lg font-bold text-gray-700">Belum Ada Data</h3>
        <p class="text-gray-500 text-sm mt-1">Belum ada transaksi penjualan yang dilaporkan hari ini.</p>
      </div>

      <div v-else class="mb-10 w-full">
        <div class="overflow-x-auto bg-white rounded-xl shadow-sm border border-gray-200">
          <table class="min-w-full border-collapse">
            <thead class="border-b-2 border-gray-200 bg-gray-50">
              <tr>
                <th scope="col" class="py-4 px-6 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No.</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Promotor</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Waktu</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pencapaian</th>
                <th scope="col" class="px-6 py-4"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <template v-for="(report, index) in reports" :key="report.promotor_id">
                <!-- Summary Row -->
                <tr 
                  @click="expandedPromotorId = expandedPromotorId === report.promotor_id ? null : report.promotor_id"
                  class="hover:bg-indigo-50/30 transition-colors cursor-pointer group"
                >
                  <td class="py-4 px-6 whitespace-nowrap text-sm font-bold text-gray-500">
                    {{ index + 1 }}.
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                      <div class="h-10 w-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-lg shrink-0">
                        {{ report.promotor_name.charAt(0) }}
                      </div>
                      <span class="font-bold text-gray-800 text-sm">{{ report.promotor_name }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div class="flex flex-col gap-1">
                      <div><span class="font-medium text-gray-400">Masuk:</span> <span class="text-emerald-600 font-bold ml-1">{{ report.check_in_at ? report.check_in_at.split(' ')[1] : '-' }}</span></div>
                      <div><span class="font-medium text-gray-400">Keluar:</span> <span class="text-red-600 font-bold ml-1">{{ report.check_out_at ? report.check_out_at.split(' ')[1] : '-' }}</span></div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex flex-wrap gap-2">
                      <span class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-md text-xs">
                        <span class="text-gray-500 uppercase font-medium">Edu</span>
                        <span class="text-indigo-600 font-bold">{{ report.total_edukasi }}</span>
                      </span>
                      <span class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-md text-xs">
                        <span class="text-gray-500 uppercase font-medium">SP</span>
                        <span class="text-indigo-600 font-bold">{{ report.total_sp }}</span>
                      </span>
                      <span class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-md text-xs">
                        <span class="text-gray-500 uppercase font-medium">Pls</span>
                        <span class="text-indigo-600 font-bold">{{ report.total_pulsa }}</span>
                      </span>
                      <span class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-md text-xs">
                        <span class="text-gray-500 uppercase font-medium">Gem</span>
                        <span class="text-indigo-600 font-bold">{{ report.total_aktivasi_gemini }}</span>
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right">
                    <svg :class="{'rotate-180': expandedPromotorId === report.promotor_id}" class="w-5 h-5 text-gray-400 inline-block transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                  </td>
                </tr>

                <!-- Details Row -->
                <tr v-show="expandedPromotorId === report.promotor_id">
                  <td colspan="5" class="p-0 border-b-0">
                    <div class="bg-indigo-50/30 p-4 border-b border-gray-100">
                      
                      <!-- MSISDN Details Table (Desktop View) -->
                      <div class="hidden md:block overflow-x-auto bg-white rounded-xl border border-gray-200 shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200">
                          <thead class="bg-gray-50">
                            <tr>
                              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No. HP (MSISDN)</th>
                              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipe</th>
                              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu Transaksi</th>
                            </tr>
                          </thead>
                          <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-if="report.msisdn_list.length === 0">
                              <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500 italic">Tidak ada rincian MSISDN untuk transaksi ini.</td>
                            </tr>
                            <tr v-for="(detail, index) in report.msisdn_list" :key="index" class="hover:bg-gray-50 transition-colors">
                              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 font-mono">{{ detail.msisdn }}</td>
                              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                                  {{ detail.type.replace('_', ' ') }}
                                </span>
                              </td>
                              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ detail.transaction_date }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>

                      <!-- MSISDN Details List (Mobile View) -->
                      <div class="md:hidden flex flex-col divide-y divide-gray-100 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div v-if="report.msisdn_list.length === 0" class="p-6 text-center text-sm text-gray-500 italic">
                          Tidak ada rincian MSISDN untuk transaksi ini.
                        </div>
                        
                        <div v-for="(detail, index) in report.msisdn_list" :key="'mob-'+index" class="p-4 flex flex-col gap-3">
                          <div class="flex justify-between items-start">
                            <div>
                              <div class="text-xs text-gray-500 uppercase font-semibold tracking-wider mb-1">No. HP (MSISDN)</div>
                              <div class="text-base font-bold text-gray-900 font-mono">{{ detail.msisdn }}</div>
                            </div>
                          </div>
                          
                          <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <div>
                              <div class="text-[10px] text-gray-400 uppercase font-semibold mb-0.5">Tipe Transaksi</div>
                              <span class="inline-flex items-center text-xs font-medium text-gray-700 capitalize">
                                {{ detail.type.replace('_', ' ') }}
                              </span>
                            </div>
                            <div class="text-right">
                              <div class="text-[10px] text-gray-400 uppercase font-semibold mb-0.5">Waktu</div>
                              <span class="text-xs font-medium text-gray-700">{{ detail.transaction_date }}</span>
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
  </AdminLayout>
</template>
