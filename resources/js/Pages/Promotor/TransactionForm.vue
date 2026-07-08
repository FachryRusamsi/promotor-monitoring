<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  jml_edukasi: 0,
  jml_sp: 0,
  jml_pulsa: 0,
  jml_aktivasi_gemini: 0,
  foto_edukasi: null,
  foto_penjualan: null,
  msisdns: [
    { number: '', type: 'starter_pack', notes: '' }
  ]
});

// Sanitizer for MSISDN input
const sanitizeNumber = (index) => {
  let val = form.msisdns[index].number;
  
  // 1. Bersihkan dari semua karakter selain angka
  val = val.replace(/\D/g, '');
  
  // 2. Normalisasi awalan agar menjadi bersih dan berawal dari angka '8'
  // (Karena di UI sudah kita beri prefix statis "0")
  if (val.startsWith('62')) {
    val = val.substring(2);
  } else if (val.startsWith('0')) {
    val = val.substring(1);
  }
  
  form.msisdns[index].number = val;
};

const addMsisdn = () => {
  form.msisdns.push({ number: '', type: 'starter_pack', notes: '' });
};

const removeMsisdn = (index) => {
  form.msisdns.splice(index, 1);
};

const submit = () => {
  form.post(route('promotor.transactions.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    }
  });
};
</script>

<template>
  <div class="max-w-3xl mx-auto p-6 bg-white rounded-xl shadow-sm mt-8 border border-gray-100">
    <h1 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-4">Form Transaksi Penjualan</h1>

    <form @submit.prevent="submit" class="space-y-8">
      
      <!-- Metrics Section -->
      <div>
        <h2 class="text-lg font-medium text-gray-800 mb-4">Jumlah Aktivitas</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Edukasi</label>
            <input type="number" min="0" v-model="form.jml_edukasi" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Starter Pack</label>
            <input type="number" min="0" v-model="form.jml_sp" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Pulsa / Reload</label>
            <input type="number" min="0" v-model="form.jml_pulsa" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Aktivasi Gemini</label>
            <input type="number" min="0" v-model="form.jml_aktivasi_gemini" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
          </div>
        </div>
      </div>

      <!-- Photo Uploads Section -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700">Foto Edukasi</label>
          <input type="file" @input="form.foto_edukasi = $event.target.files[0]" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Foto Penjualan</label>
          <input type="file" @input="form.foto_penjualan = $event.target.files[0]" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100" />
        </div>
      </div>
       
      <!-- MSISDN Dynamic List Section -->
      <div class="border-t pt-6">
        <h2 class="text-lg font-medium text-gray-800 mb-4">List No. HP (MSISDN)</h2>
        
        <div class="space-y-4">
          <div v-for="(item, index) in form.msisdns" :key="index" class="flex flex-col md:flex-row gap-4 items-start p-4 bg-gray-50 rounded-lg border border-gray-200">
            <!-- Input No HP with static '0' -->
            <div class="flex-1 w-full">
              <label class="block text-xs font-medium text-gray-500 mb-1">Nomor HP</label>
              <div class="flex rounded-md shadow-sm">
                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-200 text-gray-700 font-semibold sm:text-sm">
                  0
                </span>
                <input 
                  type="text" 
                  v-model="item.number" 
                  @input="sanitizeNumber(index)"
                  class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md sm:text-sm transition-colors"
                  :class="{ 
                    'border-red-500 ring-1 ring-red-500 focus:border-red-500 focus:ring-red-500': item.number.length > 0 && (item.number.length < 8 || item.number.length > 12), 
                    'border-gray-300 focus:border-blue-500 focus:ring-blue-500': item.number.length === 0 || (item.number.length >= 8 && item.number.length <= 12) 
                  }"
                  placeholder="81234567890"
                  required
                />
              </div>
              <p v-if="item.number.length > 0 && (item.number.length < 8 || item.number.length > 12)" class="mt-1 text-xs text-red-600 font-medium">
                Panjang nomor tidak valid.
              </p>
            </div>
            
            <!-- Tipe Transaksi -->
            <div class="w-full md:w-48">
              <label class="block text-xs font-medium text-gray-500 mb-1">Tipe</label>
              <select v-model="item.type" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="starter_pack">Starter Pack</option>
                <option value="reload">Reload / Pulsa</option>
                <option value="gemini_activation">Aktivasi Gemini</option>
              </select>
            </div>
            
            <!-- Tombol Hapus -->
            <div class="pt-5">
              <button type="button" @click="removeMsisdn(index)" class="w-full md:w-auto px-3 py-2 bg-red-50 text-red-600 rounded-md hover:bg-red-100 border border-red-200 transition-colors" :disabled="form.msisdns.length === 1">
                Hapus
              </button>
            </div>
          </div>
        </div>
         
        <button type="button" @click="addMsisdn" class="mt-4 px-4 py-2 border border-blue-300 border-dashed text-sm font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors focus:outline-none w-full text-center">
          + Tambah Nomor
        </button>
      </div>
       
      <!-- Submit Button -->
      <div class="border-t pt-6">
        <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-50">
          <span v-if="form.processing">Menyimpan...</span>
          <span v-else>Submit Transaksi</span>
        </button>
      </div>

    </form>
  </div>
</template>
