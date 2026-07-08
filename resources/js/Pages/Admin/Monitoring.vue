<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix Leaflet marker missing icons in Vite
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl,
  iconUrl,
  shadowUrl,
});

const mapContainer = ref(null);
let map = null;
const markers = {}; // Menyimpan marker berdasarkan user_id (promotor)

onMounted(() => {
  // Inisialisasi Peta (Default koordinat tengah ke Jakarta)
  map = L.map(mapContainer.value).setView([-6.2088, 106.8456], 10);
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  // Subscribe ke channel Reverb / Echo
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

const updatePromotorLocation = (payload) => {
  const { user_id, user_name, latitude, longitude, recorded_at } = payload;
  
  const latLng = new L.LatLng(latitude, longitude);
  const popupContent = `
    <div class="text-sm">
      <div class="font-bold text-blue-800 text-base">${user_name}</div>
      <div class="text-gray-500 mt-1">Lat: ${latitude.toFixed(5)}, Lng: ${longitude.toFixed(5)}</div>
      <div class="text-xs text-gray-400 mt-2">Waktu (App): ${new Date(recorded_at).toLocaleTimeString()}</div>
    </div>
  `;

  if (markers[user_id]) {
    // Pindahkan marker yang sudah ada
    markers[user_id].setLatLng(latLng);
    markers[user_id].getPopup().setContent(popupContent);
  } else {
    // Buat marker baru
    const marker = L.marker(latLng).addTo(map);
    marker.bindPopup(popupContent);
    markers[user_id] = marker;
  }
};

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
  <Head title="Live Monitoring Promotor" />

  <AdminLayout>
    <template #header>Live Monitoring</template>

    <div class="h-full flex flex-col p-4 md:p-6 gap-4">
      <div class="flex justify-between items-center mb-2">
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Peta Promotor</h2>
          <p class="text-sm text-gray-500">Lacak lokasi promotor secara real-time</p>
        </div>
        <div class="bg-emerald-50 text-emerald-700 px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2 border border-emerald-100">
          <span class="relative flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
          </span>
          Live Socket Active
        </div>
      </div>

      <!-- Peta Leaflet -->
      <div class="flex-1 min-h-[500px] bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden relative z-0">
        <div ref="mapContainer" class="w-full h-full"></div>
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
