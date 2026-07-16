<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import PromotorLayout from '@/Layouts/PromotorLayout.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
  msisdn: '',
  type: 'starter_pack',
  notes: '',
  foto_penjualan: null as File | null,
  latitude: null as number | null,
  longitude: null as number | null,
  location_name: ''
});

onMounted(() => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        form.latitude = position.coords.latitude;
        form.longitude = position.coords.longitude;
      },
      (error) => {
        console.warn('Geolocation error:', error);
      },
      { enableHighAccuracy: true }
    );
  }
});

// Sanitizer for MSISDN input
const sanitizeNumber = () => {
  let val = form.msisdn;
  
  val = val.replace(/\D/g, '');
  
  if (val.startsWith('62')) {
    val = '0' + val.substring(2);
  } else if (val.startsWith('8')) {
    val = '0' + val;
  }
  
  form.msisdn = val;
};

const isMsisdnFormatValid = computed(() => {
  if (!form.msisdn) return true; // Don't show error if empty, let required handle it
  const regex = /^(0814|0815|0816|0855|0856|0857|0858|0895|0896|0897|0898|0899)[0-9]{4,11}$/;
  return regex.test(form.msisdn);
});

const isDataComplete = computed(() => {
  return form.msisdn.length >= 8 && isMsisdnFormatValid.value && form.location_name.length > 0;
});

const submit = () => {
  if (!isDataComplete.value) {
    return;
  }
  
  form.post(route('promotor.transactions.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('msisdn', 'type', 'notes', 'foto_penjualan', 'location_name');
    },
    onError: (errors) => {
        let errorMessages = Object.values(errors).flat().join('<br>');
        import('sweetalert2').then(({ default: Swal }) => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                html: errorMessages,
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#4f46e5'
            });
        });
    }
  });
};
</script>

<template>
  <Head title="Lapor Transaksi" />
  <PromotorLayout>
    <div class="px-4 py-6 mb-8 max-w-lg mx-auto">
      <!-- Page Header with Back Button -->
      <div class="flex items-center gap-3 mb-6">
        <Link :href="route('promotor.dashboard')" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border border-gray-200 shadow-sm hover:bg-gray-50 transition-colors active:scale-95 shrink-0">
          <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </Link>
        <h1 class="text-2xl font-bold text-gray-800">Lapor Transaksi</h1>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        
        <!-- MSISDN & Product Type -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
              Input Data Penjualan
            </h2>
          </div>
          
          <div class="space-y-4">
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
              <div class="space-y-3">
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1">Nomor HP</label>
                  <input 
                    type="text" 
                    v-model="form.msisdn" 
                    @input="sanitizeNumber"
                    class="block w-full px-3 py-2 rounded-lg text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    :class="{'border-red-500 ring-1 ring-red-500': form.msisdn.length > 0 && !isMsisdnFormatValid}"
                    placeholder="0815xxxxxxx"
                    required
                  />
                  <p v-if="form.msisdn.length > 0 && !isMsisdnFormatValid" class="mt-1 text-xs text-red-600">
                    Prefix tidak valid (harus Indosat/Tri) atau panjang kurang.
                  </p>
                  <InputError :message="form.errors.msisdn" class="mt-1" />
                </div>
                
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1">Tipe Produk</label>
                  <select v-model="form.type" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="starter_pack">Starter Pack</option>
                    <option value="reload">Reload / Pulsa</option>
                    <option value="gemini_activation">Aktivasi Gemini</option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1">Keterangan / Catatan (Opsional)</label>
                  <input 
                    type="text" 
                    v-model="form.notes" 
                    class="block w-full px-3 py-2 rounded-lg text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Catatan tambahan"
                  />
                  <InputError :message="form.errors.notes" class="mt-1" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Location Section -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
          <h2 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            Lokasi Transaksi
          </h2>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Keterangan Lokasi (Nama Toko/Area)</label>
            <input type="text" v-model="form.location_name" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Contoh: Toko Maju Jaya / Alun-alun" required />
            <InputError :message="form.errors.location_name" class="mt-1" />
            <div class="mt-2 flex items-center gap-2 text-xs text-gray-500">
              <span v-if="form.latitude && form.longitude" class="text-emerald-600 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                Titik GPS ditemukan
              </span>
              <span v-else class="text-amber-600 flex items-center gap-1">
                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                Mencari titik GPS...
              </span>
            </div>
          </div>
        </div>

        <!-- Photo Uploads Section -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
           <h2 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            Bukti Foto
          </h2>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Foto Penjualan</label>
              <input type="file" @change="form.foto_penjualan = ($event.target as HTMLInputElement).files?.[0] || null" accept="image/*" 
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-gray-200 rounded-lg" />
              <InputError :message="form.errors.foto_penjualan" class="mt-1" />
            </div>
          </div>
        </div>
         
        <!-- Submit Button -->
        <button type="submit" :disabled="form.processing || !isDataComplete" class="w-full flex justify-center items-center gap-2 py-4 px-4 border border-transparent rounded-xl shadow-lg text-base font-bold text-white transition-colors disabled:opacity-50" :class="isDataComplete ? 'bg-indigo-600 hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 focus:outline-none' : 'bg-gray-400 cursor-not-allowed'">
          <span v-if="form.processing" class="flex items-center gap-2">
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Menyimpan...
          </span>
          <span v-else>Submit Laporan</span>
        </button>
      </form>
    </div>
  </PromotorLayout>
</template>
