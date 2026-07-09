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
    router.get(route('admin.monitoring'), { 
      region_id: selectedRegion.value, 
      area_id: selectedArea.value 
    }, { preserveState: true });
  });
});

const selectedPromotor = ref(null);
const isModalOpen = ref(false);

const mapContainer = ref(null);
let map = null;
let currentMarker = null;

const openModal = async (promotor) => {
  selectedPromotor.value = promotor;
  isModalOpen.value = true;
  
  await nextTick();
  initMap();
  fetchLatestLocation(promotor.id);
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

    <div class="p-4 md:p-6 h-full flex flex-col">
        <div class="flex justify-between items-center mb-6">
          <div>
            <h2 class="text-2xl font-bold text-gray-800">Daftar Promotor</h2>
            <p class="text-sm text-gray-500">Pilih promotor untuk melihat detail & live location</p>
          </div>
          
          <div class="flex items-center gap-3">
            <!-- Filter Dropdowns -->
            <select v-model="selectedRegion" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
              <option value="">Semua Region</option>
              <option v-for="region in regions" :key="region.id" :value="region.id">{{ region.name }}</option>
            </select>
            
            <select v-model="selectedArea" :disabled="!selectedRegion" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100">
              <option value="">Semua Branch</option>
              <option v-for="area in availableAreas" :key="area.id" :value="area.id">{{ area.name }}</option>
            </select>

            <div class="bg-emerald-50 text-emerald-700 px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2 border border-emerald-100 ml-4">
              <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
              </span>
              Live Socket Active
            </div>
          </div>
        </div>

      <!-- Grid Promotor -->
      <div v-if="promotors.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 pb-10">
        <div 
          v-for="promotor in promotors" 
          :key="promotor.id"
          @click="openModal(promotor)"
          class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 cursor-pointer hover:shadow-md hover:border-blue-300 transition-all group"
        >
          <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg group-hover:bg-blue-600 group-hover:text-white transition-colors">
              {{ promotor.name.substring(0, 2).toUpperCase() }}
            </div>
            <div>
              <h3 class="font-bold text-gray-800 text-lg line-clamp-1">{{ promotor.name }}</h3>
              <div class="flex flex-col gap-1">
                <p class="text-sm text-gray-500 flex items-center gap-1">
                  <span :class="promotor.check_in_time ? 'text-green-500' : 'text-red-500'">●</span>
                  {{ promotor.check_in_time ? `Masuk: ${promotor.check_in_time}` : 'Belum Absen' }}
                </p>
                <p class="text-sm text-gray-500 flex items-center gap-1">
                  <span :class="promotor.check_out_time ? 'text-green-500' : 'text-gray-400'">●</span>
                  {{ promotor.check_out_time ? `Keluar: ${promotor.check_out_time}` : 'Belum Keluar' }}
                </p>
              </div>
            </div>
          </div>
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

            <!-- Aktivitas Report -->
            <div>
              <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Aktivitas Report</h4>
              <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100">
                <div :class="['p-2 rounded-full', selectedPromotor?.has_reported ? 'bg-blue-100 text-blue-600' : 'bg-yellow-100 text-yellow-600']">
                  <svg v-if="selectedPromotor?.has_reported" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div>
                  <div class="font-bold text-gray-800">{{ selectedPromotor?.has_reported ? 'Sudah Report' : 'Belum Report' }}</div>
                  <div class="text-xs text-gray-500">Status Transaksi Hari Ini</div>
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
