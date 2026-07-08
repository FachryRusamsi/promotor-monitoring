<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
  reports: Array,
  date: String,
});

// Simple client-side CSV Export
const exportToCSV = () => {
  let csvContent = "data:text/csv;charset=utf-8,";
  // CSV Headers
  csvContent += "Promotor,MSISDN,Tipe Transaksi,Status Validasi,Waktu Transaksi,Catatan\n";
  
  // Flatten MSISDN data from all promotors
  props.reports.forEach(report => {
    report.msisdn_list.forEach(detail => {
      const row = [
        `"${report.promotor_name}"`,
        `"${detail.msisdn}"`,
        `"${detail.type}"`,
        `"${detail.validation_status}"`,
        `"${detail.transaction_date}"`,
        `"${detail.notes || ''}"`
      ];
      csvContent += row.join(",") + "\n";
    });
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

  <div class="max-w-7xl mx-auto p-6 space-y-6">
    <div class="flex justify-between items-center border-b pb-4">
      <h1 class="text-3xl font-bold text-gray-800">Rekapitulasi Harian & Pencairan Insentif</h1>
      <div class="flex items-center gap-4">
        <span class="text-gray-600 font-medium">Tanggal: {{ date }}</span>
        <button 
          @click="exportToCSV" 
          class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 shadow-sm transition-colors flex items-center gap-2"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Export CSV MSISDN
        </button>
      </div>
    </div>

    <!-- Promotor Cards -->
    <div v-if="reports.length === 0" class="text-center py-12 text-gray-500 bg-white rounded-xl shadow-sm border border-gray-100">
      Belum ada data transaksi hari ini.
    </div>

    <div v-else class="space-y-8">
      <div v-for="report in reports" :key="report.promotor_id" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        
        <!-- Summary Header -->
        <div class="bg-blue-50 p-4 border-b border-blue-100 flex justify-between items-center">
          <h2 class="text-xl font-bold text-blue-900">{{ report.promotor_name }}</h2>
          <div class="flex gap-4 text-sm font-medium text-blue-800">
            <div class="bg-white px-3 py-1 rounded shadow-sm">Edukasi: {{ report.total_edukasi }}</div>
            <div class="bg-white px-3 py-1 rounded shadow-sm">SP: {{ report.total_sp }}</div>
            <div class="bg-white px-3 py-1 rounded shadow-sm">Pulsa: {{ report.total_pulsa }}</div>
            <div class="bg-white px-3 py-1 rounded shadow-sm">Gemini: {{ report.total_aktivasi_gemini }}</div>
          </div>
        </div>

        <!-- MSISDN Details Table -->
        <div class="p-0 overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. HP (MSISDN)</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Validasi</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="report.msisdn_list.length === 0">
                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada rincian MSISDN.</td>
              </tr>
              <tr v-for="(detail, index) in report.msisdn_list" :key="index" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ detail.msisdn }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ detail.type }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span :class="['px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full capitalize', statusColors[detail.validation_status] || 'bg-gray-100 text-gray-800']">
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
</template>
