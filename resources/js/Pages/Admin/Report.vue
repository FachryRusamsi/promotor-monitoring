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

const availableAreas = computed(() => {
  if (!selectedRegion.value) return [];
  const region = props.regions.find(r => r.id == selectedRegion.value);
  return region ? region.areas : [];
});

watch([selectedRegion, selectedArea], ([newRegion, newArea], [oldRegion]) => {
  if (newRegion !== oldRegion) {
    selectedArea.value = ''; // Reset area when region changes
  }
  
  import('@inertiajs/vue3').then(({ router }) => {
    router.get(route('admin.reports.index'), { 
      region_id: selectedRegion.value, 
      area_id: selectedArea.value 
    }, { preserveState: true });
  });
});

// Simple client-side CSV Export
const exportToCSV = () => {
  let csvContent = "data:text/csv;charset=utf-8,";
  // CSV Headers
  csvContent += "Promotor,Jam Masuk,Jam Keluar,MSISDN,Tipe Transaksi,Status Validasi,Waktu Transaksi,Catatan\n";
  
  // Flatten MSISDN data from all promotors
  props.reports.forEach(report => {
    if (report.msisdn_list.length === 0) {
       const row = [
        `"${report.promotor_name}"`,
        `"${report.check_in_at || ''}"`,
        `"${report.check_out_at || ''}"`,
        `""`,`""`,`""`,`""`,`""`
      ];
      csvContent += row.join(",") + "\n";
    } else {
      report.msisdn_list.forEach(detail => {
        const row = [
          `"${report.promotor_name}"`,
          `"${report.check_in_at || ''}"`,
          `"${report.check_out_at || ''}"`,
          `"${detail.msisdn}"`,
          `"${detail.type}"`,
          `"${detail.validation_status}"`,
          `"${detail.transaction_date}"`,
          `"${detail.notes || ''}"`
        ];
        csvContent += row.join(",") + "\n";
      });
    }
  });
  
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", `Laporan_Insentif_MSISDN_${props.date}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

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
          
          <input type="date" :value="date" class="border-gray-300 rounded-lg shadow-sm text-sm" disabled />
          
          <button 
            @click="exportToCSV" 
            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shadow-sm transition-colors flex items-center justify-center gap-2 whitespace-nowrap"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            Export CSV
          </button>
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

      <div v-else class="space-y-6">
        <div v-for="report in reports" :key="report.promotor_id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          
          <!-- Summary Header -->
          <div class="bg-gradient-to-r from-indigo-50 to-white p-5 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-lg">
                {{ report.promotor_name.charAt(0) }}
              </div>
              <h2 class="text-lg font-bold text-gray-800">{{ report.promotor_name }}</h2>
            </div>
            
            <div class="flex flex-col gap-3 items-end">
              <div class="text-xs text-gray-500 font-medium">
                Masuk: <span class="text-emerald-600 font-bold mr-3">{{ report.check_in_at ? report.check_in_at.split(' ')[1] : '-' }}</span>
                Keluar: <span class="text-red-600 font-bold">{{ report.check_out_at ? report.check_out_at.split(' ')[1] : '-' }}</span>
              </div>
              <div class="flex flex-wrap gap-2 text-sm font-medium text-gray-600">
                <div class="bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-sm flex items-center gap-2">
                  <span class="text-gray-400 text-xs uppercase">Edukasi</span>
                  <span class="text-indigo-600 font-bold">{{ report.total_edukasi }}</span>
                </div>
                <div class="bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-sm flex items-center gap-2">
                  <span class="text-gray-400 text-xs uppercase">SP</span>
                  <span class="text-indigo-600 font-bold">{{ report.total_sp }}</span>
                </div>
                <div class="bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-sm flex items-center gap-2">
                  <span class="text-gray-400 text-xs uppercase">Pulsa</span>
                  <span class="text-indigo-600 font-bold">{{ report.total_pulsa }}</span>
                </div>
                <div class="bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-sm flex items-center gap-2">
                  <span class="text-gray-400 text-xs uppercase">Gemini</span>
                  <span class="text-indigo-600 font-bold">{{ report.total_aktivasi_gemini }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- MSISDN Details Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No. HP (MSISDN)</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipe</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status Validasi</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu Transaksi</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-100">
                <tr v-if="report.msisdn_list.length === 0">
                  <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 italic">Tidak ada rincian MSISDN untuk transaksi ini.</td>
                </tr>
                <tr v-for="(detail, index) in report.msisdn_list" :key="index" class="hover:bg-gray-50 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 font-mono">{{ detail.msisdn }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                      {{ detail.type.replace('_', ' ') }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="['px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full capitalize border', 
                      detail.validation_status === 'valid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                      detail.validation_status === 'invalid' ? 'bg-red-50 text-red-700 border-red-200' :
                      'bg-yellow-50 text-yellow-700 border-yellow-200'
                    ]">
                      {{ detail.validation_status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ detail.transaction_date }}</td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </AdminLayout>
</template>
