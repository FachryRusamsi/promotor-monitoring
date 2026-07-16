<script setup>
import { onMounted, onUnmounted, ref, nextTick, watch, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';

// Fix Leaflet marker missing icons in Vite
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

const customIcon = L.icon({
    iconUrl: iconUrl,
    iconRetinaUrl: iconRetinaUrl,
    shadowUrl: shadowUrl,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

L.Marker.prototype.options.icon = customIcon;

const props = defineProps({
  promotors: {
    type: Array,
    default: () => []
  },
  regions: {
    type: Array,
    default: () => []
  },
  currentFilters: {
    type: Object,
    default: () => ({ region_id: '', area_id: '', date: '' })
  }
});

// KPI Targets & Helpers
const KPI_TARGETS = { edukasi: 600, rebuy: 200, sp: 100, gemini: 100 };
const kpiPct = (val, target) => Math.min(Math.round((val / target) * 100), 100);
const avgKpi = (p) => Math.round((kpiPct(p.total_edukasi, KPI_TARGETS.edukasi) + kpiPct(p.total_rebuy, KPI_TARGETS.rebuy) + kpiPct(p.total_sp, KPI_TARGETS.sp) + kpiPct(p.total_gemini, KPI_TARGETS.gemini)) / 4) || 0;
const kpiColor = (p) => p >= 100 ? 'bg-emerald-500' : p >= 60 ? 'bg-indigo-500' : p >= 30 ? 'bg-amber-400' : 'bg-red-400';
const kpiTextColor = (p) => p >= 100 ? 'text-emerald-600' : p >= 60 ? 'text-indigo-600' : p >= 30 ? 'text-amber-500' : 'text-red-500';

const selectedRegion = ref(props.currentFilters.region_id || '');
const selectedArea = ref(props.currentFilters.area_id || '');
const selectedDate = ref(props.currentFilters.date || new Date().toISOString().split('T')[0]);

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
    router.get(route('admin.monitoring'), { 
      region_id: selectedRegion.value, 
      area_id: selectedArea.value,
      date: selectedDate.value
    }, { preserveState: true });
  });
});

const selectedPromotor = ref(null);
const isModalOpen = ref(false);
const promotorHistory = ref([]);
const isLoadingHistory = ref(false);

const mapContainer = ref(null);
let map = null;
let currentMarker = null;

const openModal = async (promotor) => {
  selectedPromotor.value = promotor;
  isModalOpen.value = true;
  promotorHistory.value = [];
  
  await nextTick();
  initMap();
  fetchLatestLocation(promotor.id);
  fetchPromotorHistory(promotor.id);
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedPromotor.value = null;
  if (map) {
    map.remove();
    map = null;
    currentMarker = null;
  }
};

const initMap = () => {
  if (map) map.remove();
  map = L.map(mapContainer.value).setView([-6.2088, 106.8456], 10);
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  // Fix map sizing inside modal transition
  setTimeout(() => {
      map.invalidateSize();
  }, 300);
};

const fetchLatestLocation = async (id) => {
  try {
    const response = await axios.get(`/admin/tracking/${id}/latest`);
    if (response.data.status === 'online') {
      updatePromotorLocation(response.data.data);
    } else {
      // Fallback ke kordinat absen jika offline
      if (selectedPromotor.value?.check_in_lat && selectedPromotor.value?.check_in_lng) {
        updatePromotorLocation({
          user_id: selectedPromotor.value.id,
          user_name: selectedPromotor.value.name,
          latitude: parseFloat(selectedPromotor.value.check_in_lat),
          longitude: parseFloat(selectedPromotor.value.check_in_lng),
        }, true);
      }
    }
  } catch (error) {
    console.error("Failed to fetch location", error);
  }
};

const fetchPromotorHistory = async (id) => {
  isLoadingHistory.value = true;
  try {
    const response = await axios.get(`/admin/promotor/${id}/history-log`);
    promotorHistory.value = response.data;
  } catch (error) {
    console.error("Failed to fetch history log", error);
  } finally {
    isLoadingHistory.value = false;
  }
};

const updatePromotorLocation = (payload, isFallback = false) => {
  // Hanya perbarui jika payload dari promotor yang sedang dipilih
  if (!selectedPromotor.value || payload.user_id !== selectedPromotor.value.id) return;
  
  const { user_name, latitude, longitude, recorded_at } = payload;
  const latLng = new L.LatLng(latitude, longitude);
  
  const timeText = isFallback 
    ? `<span class="text-yellow-600 font-bold">Lokasi saat Check-in (Offline)</span>`
    : `Waktu (App): ${new Date(recorded_at).toLocaleTimeString()}`;

  const popupContent = `
    <div class="text-sm">
      <div class="font-bold text-blue-800 text-base">${user_name}</div>
      <div class="text-gray-500 mt-1">Lat: ${latitude.toFixed(5)}, Lng: ${longitude.toFixed(5)}</div>
      <div class="text-xs text-gray-500 mt-2">${timeText}</div>
    </div>
  `;

  if (currentMarker) {
    currentMarker.setLatLng(latLng);
    currentMarker.getPopup().setContent(popupContent);
  } else {
    currentMarker = L.marker(latLng, { icon: customIcon }).addTo(map);
    currentMarker.bindPopup(popupContent).openPopup();
  }
  
  // Force map to pan directly to the pointer with proper zoom
  setTimeout(() => {
      map.invalidateSize();
      map.setView(latLng, 16, { animate: true });
  }, 350);
};

onMounted(() => {
  if (window.Echo) {
    console.log("Listening to Reverb channel: promotor-tracking");
    window.Echo.channel('promotor-tracking')
      .listen('.LocationUpdated', (payload) => {
        updatePromotorLocation(payload);
      });
  } else {
    console.warn("window.Echo tidak terdeteksi. Pastikan laravel-echo dan pusher-js di-install dan di-setup di bootstrap.ts.");
  }
});

onUnmounted(() => {
  if (map) {
    map.remove();
  }
  if (window.Echo) {
    window.Echo.leaveChannel('promotor-tracking');
  }
});
</script>

<template>
  <Head title="Monitoring Promotor" />

  <AdminLayout>
    <template #header>Daftar Promotor</template>

    <div class="p-4 md:p-6 pb-20">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
          <div>
            <h2 class="text-xl md:text-2xl font-bold text-gray-800">Daftar Promotor</h2>
            <p class="text-xs md:text-sm text-gray-500">Pilih promotor untuk melihat detail & live location</p>
          </div>
          
          <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <input type="date" v-model="selectedDate" class="w-full md:w-auto rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            
            <select v-model="selectedRegion" class="w-full md:w-auto rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
              <option value="">Semua Region</option>
              <option v-for="region in regions" :key="region.id" :value="region.id">{{ region.name }}</option>
            </select>
            
            <select v-model="selectedArea" :disabled="!selectedRegion" class="w-full md:w-auto rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100">
              <option value="">Semua Branch</option>
              <option v-for="area in availableAreas" :key="area.id" :value="area.id">{{ area.name }}</option>
            </select>

            <div class="w-full md:w-auto bg-emerald-50 text-emerald-700 px-4 py-2 rounded-full text-xs md:text-sm font-medium flex items-center justify-center gap-2 border border-emerald-100">
              <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
              </span>
              Live Socket Active
            </div>
          </div>
        </div>

      <!-- List Promotor -->
      <div v-if="promotors.length > 0" class="mb-10 w-full">
        <div class="overflow-x-auto">
          <table class="min-w-full border-collapse">
            <thead class="border-b-2 border-gray-200">
              <tr>
                <th scope="col" class="py-4 pr-6 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No.</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Promotor</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jam Masuk</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jam Keluar</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">KPI Progress</th>
                <th scope="col" class="pl-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr 
                v-for="(promotor, index) in promotors" 
                :key="promotor.id"
                @click="openModal(promotor)"
                class="hover:bg-white transition-colors cursor-pointer group"
              >
                <td class="py-4 pr-6 whitespace-nowrap text-sm font-bold text-gray-500">
                  {{ index + 1 }}.
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-xs group-hover:bg-blue-100 transition-colors shrink-0">
                      {{ promotor.name.substring(0, 2).toUpperCase() }}
                    </div>
                    <span class="font-bold text-gray-800 text-sm">{{ promotor.name }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span v-if="promotor.check_in_time" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-green-50 text-green-700 border border-green-100">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                    {{ promotor.check_in_time }}
                  </span>
                  <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-red-50 text-red-700 border border-red-100">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                    Belum / Tidak Absen
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span v-if="promotor.check_out_time" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-green-50 text-green-700 border border-green-100">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                    {{ promotor.check_out_time }}
                  </span>
                  <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                    Belum Keluar
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex flex-col gap-1.5" @click.stop>
                      <span :class="['text-sm font-bold', kpiTextColor(avgKpi(promotor))]">KPI: {{ avgKpi(promotor) }}%</span>
                      <div class="flex items-center gap-3">
                          <div class="flex flex-col items-center gap-1 w-8">
                              <span class="text-[9px] text-gray-500 uppercase font-bold tracking-wider">Edu</span>
                              <div class="w-full bg-gray-100 rounded-full h-1.5 flex overflow-hidden">
                                  <div :class="['h-full transition-all', kpiColor(kpiPct(promotor.total_edukasi, KPI_TARGETS.edukasi))]" :style="{ width: kpiPct(promotor.total_edukasi, KPI_TARGETS.edukasi) + '%' }"></div>
                              </div>
                              <span :class="['text-[9px] font-bold', kpiTextColor(kpiPct(promotor.total_edukasi, KPI_TARGETS.edukasi))]">{{ kpiPct(promotor.total_edukasi, KPI_TARGETS.edukasi) }}%</span>
                          </div>
                          <div class="flex flex-col items-center gap-1 w-8">
                              <span class="text-[9px] text-gray-500 uppercase font-bold tracking-wider">Rby</span>
                              <div class="w-full bg-gray-100 rounded-full h-1.5 flex overflow-hidden">
                                  <div :class="['h-full transition-all', kpiColor(kpiPct(promotor.total_rebuy, KPI_TARGETS.rebuy))]" :style="{ width: kpiPct(promotor.total_rebuy, KPI_TARGETS.rebuy) + '%' }"></div>
                              </div>
                              <span :class="['text-[9px] font-bold', kpiTextColor(kpiPct(promotor.total_rebuy, KPI_TARGETS.rebuy))]">{{ kpiPct(promotor.total_rebuy, KPI_TARGETS.rebuy) }}%</span>
                          </div>
                          <div class="flex flex-col items-center gap-1 w-8">
                              <span class="text-[9px] text-gray-500 uppercase font-bold tracking-wider">SP</span>
                              <div class="w-full bg-gray-100 rounded-full h-1.5 flex overflow-hidden">
                                  <div :class="['h-full transition-all', kpiColor(kpiPct(promotor.total_sp, KPI_TARGETS.sp))]" :style="{ width: kpiPct(promotor.total_sp, KPI_TARGETS.sp) + '%' }"></div>
                              </div>
                              <span :class="['text-[9px] font-bold', kpiTextColor(kpiPct(promotor.total_sp, KPI_TARGETS.sp))]">{{ kpiPct(promotor.total_sp, KPI_TARGETS.sp) }}%</span>
                          </div>
                          <div class="flex flex-col items-center gap-1 w-8">
                              <span class="text-[9px] text-gray-500 uppercase font-bold tracking-wider">Gem</span>
                              <div class="w-full bg-gray-100 rounded-full h-1.5 flex overflow-hidden">
                                  <div :class="['h-full transition-all', kpiColor(kpiPct(promotor.total_gemini, KPI_TARGETS.gemini))]" :style="{ width: kpiPct(promotor.total_gemini, KPI_TARGETS.gemini) + '%' }"></div>
                              </div>
                              <span :class="['text-[9px] font-bold', kpiTextColor(kpiPct(promotor.total_gemini, KPI_TARGETS.gemini))]">{{ kpiPct(promotor.total_gemini, KPI_TARGETS.gemini) }}%</span>
                          </div>
                      </div>
                  </div>
                </td>
                <td class="pl-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button class="text-indigo-600 hover:text-indigo-900 inline-flex items-center gap-1">
                    Detail Lokasi
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <div v-else class="flex-1 flex flex-col items-center justify-center text-gray-500 bg-white rounded-2xl border border-gray-200 min-h-[400px]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <p class="text-lg font-medium">Belum ada data promotor</p>
      </div>
    </div>

    <!-- Modal Detail Promotor -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200">
        
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
          <div>
            <h3 class="text-xl font-bold text-gray-800">{{ selectedPromotor?.name }}</h3>
            <p class="text-sm text-gray-500">Live Location & Detail Aktivitas</p>
          </div>
          <button @click="closeModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 flex flex-col md:flex-row overflow-hidden min-h-[500px]">
          
          <!-- Detail Panel -->
          <div class="w-full md:w-1/3 p-6 bg-white border-b md:border-b-0 md:border-r border-gray-100 flex flex-col gap-6 overflow-y-auto">
            
            <!-- Status Jam Masuk -->
            <div>
              <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Status Jam Masuk</h4>
              <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100">
                <div :class="['p-2 rounded-full', selectedPromotor?.check_in_time ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600']">
                  <svg v-if="selectedPromotor?.check_in_time" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div>
                  <div class="font-bold text-gray-800">{{ selectedPromotor?.check_in_time || 'Belum Absen' }}</div>
                  <div class="text-xs text-gray-500">Waktu Check-in Hari Ini</div>
                </div>
              </div>
            </div>

            <!-- Status Jam Keluar -->
            <div>
              <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Status Jam Keluar</h4>
              <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100">
                <div :class="['p-2 rounded-full', selectedPromotor?.check_out_time ? 'bg-green-100 text-green-600' : 'bg-gray-200 text-gray-500']">
                  <svg v-if="selectedPromotor?.check_out_time" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div>
                  <div class="font-bold text-gray-800">{{ selectedPromotor?.check_out_time || 'Belum Keluar' }}</div>
                  <div class="text-xs text-gray-500">Waktu Check-out Hari Ini</div>
                </div>
              </div>
            </div>

            <!-- Aktivitas Report & Rincian Penjualan -->
            <div>
              <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Aktivitas Penjualan ({{ selectedDate }})</h4>
              
              <!-- Report Status -->
              <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100 mb-3">
                <div :class="['p-2 rounded-full', selectedPromotor?.has_reported ? 'bg-blue-100 text-blue-600' : 'bg-yellow-100 text-yellow-600']">
                  <svg v-if="selectedPromotor?.has_reported" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div>
                  <div class="font-bold text-gray-800">{{ selectedPromotor?.has_reported ? 'Terdapat Transaksi' : 'Belum Ada Transaksi' }}</div>
                  <div class="text-xs text-gray-500">Status Laporan</div>
                </div>
              </div>

              <!-- Rincian -->
              <div v-if="selectedPromotor?.has_reported" class="grid grid-cols-2 gap-3 mt-4">
                <div class="bg-gray-50 border border-gray-100 rounded-lg p-3 text-center">
                  <div class="text-xs text-gray-500 mb-1">Edukasi</div>
                  <div class="text-lg font-bold text-gray-800">{{ selectedPromotor?.total_edukasi }}</div>
                </div>
                <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-3 text-center">
                  <div class="text-xs text-indigo-500 mb-1">Starter Pack</div>
                  <div class="text-lg font-bold text-indigo-800">{{ selectedPromotor?.total_sp }}</div>
                </div>
                <div class="bg-green-50 border border-green-100 rounded-lg p-3 text-center">
                  <div class="text-xs text-green-500 mb-1">Rebuy</div>
                  <div class="text-lg font-bold text-green-800">{{ selectedPromotor?.total_rebuy }}</div>
                </div>
                <div class="bg-rose-50 border border-rose-100 rounded-lg p-3 text-center">
                  <div class="text-xs text-rose-500 mb-1">Gemini</div>
                  <div class="text-lg font-bold text-rose-800">{{ selectedPromotor?.total_gemini }}</div>
                </div>
              </div>
            </div>

            <!-- History Log 7 Hari -->
            <div class="mt-4 border-t pt-4">
              <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Riwayat 7 Hari Terakhir</h4>
              <div v-if="isLoadingHistory" class="flex justify-center p-4">
                <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </div>
              <div v-else class="space-y-3">
                <div v-for="log in promotorHistory" :key="log.date" class="bg-white border rounded-lg p-3 shadow-sm hover:shadow-md transition">
                  <div class="flex justify-between items-center mb-2">
                    <span class="font-bold text-gray-700 text-sm">{{ log.date }}</span>
                    <span v-if="log.check_in_time" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded font-medium">{{ log.check_in_time }} - {{ log.check_out_time || '??' }}</span>
                    <span v-else class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded font-medium">Tidak Masuk</span>
                  </div>
                  <div class="grid grid-cols-4 gap-1 text-center">
                    <div class="bg-gray-50 rounded p-1">
                      <div class="text-[10px] text-gray-500">Edu</div>
                      <div class="text-xs font-bold">{{ log.total_edukasi }}</div>
                    </div>
                    <div class="bg-indigo-50 rounded p-1">
                      <div class="text-[10px] text-indigo-500">SP</div>
                      <div class="text-xs font-bold">{{ log.total_sp }}</div>
                    </div>
                    <div class="bg-green-50 rounded p-1">
                      <div class="text-[10px] text-green-500">Pls</div>
                      <div class="text-xs font-bold">{{ log.total_rebuy }}</div>
                    </div>
                    <div class="bg-rose-50 rounded p-1">
                      <div class="text-[10px] text-rose-500">Gem</div>
                      <div class="text-xs font-bold">{{ log.total_gemini }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- Map Panel -->
          <div class="w-full md:w-2/3 h-64 md:h-auto relative z-0 bg-gray-100">
            <div ref="mapContainer" class="w-full h-full absolute inset-0"></div>
          </div>

        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style>
/* Z-index fix for leaflet map so it doesn't overlap header/menus */
.leaflet-container {
  z-index: 0;
}
.leaflet-top, .leaflet-bottom {
  z-index: 10;
}
</style>
