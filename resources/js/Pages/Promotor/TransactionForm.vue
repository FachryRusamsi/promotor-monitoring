<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PromotorLayout from '@/Layouts/PromotorLayout.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
  jml_edukasi: 0,
  jml_sp: 0,
  jml_pulsa: 0,
  jml_aktivasi_gemini: 0,
  foto_edukasi: null as File | null,
  foto_penjualan: null as File | null,
  msisdns: [
    { number: '', type: 'starter_pack', notes: '' }
  ]
});

// Sanitizer for MSISDN input
const sanitizeNumber = (index: number) => {
  let val = form.msisdns[index].number;
  
  val = val.replace(/\D/g, '');
  
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

const removeMsisdn = (index: number) => {
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
  <Head title="Lapor Transaksi" />
  <PromotorLayout>
    <div class="px-4 py-6 mb-8 max-w-lg mx-auto">
      <h1 class="text-2xl font-bold text-gray-800 mb-6">Lapor Transaksi</h1>

      <form @submit.prevent="submit" class="space-y-6">
        
        <!-- Metrics Section -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
          <h2 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            Jumlah Aktivitas (Qty)
          </h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Edukasi</label>
              <input type="number" min="0" v-model="form.jml_edukasi" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg font-semibold text-center" />
              <InputError :message="form.errors.jml_edukasi" class="mt-1" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Starter Pack</label>
              <input type="number" min="0" v-model="form.jml_sp" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg font-semibold text-center" />
              <InputError :message="form.errors.jml_sp" class="mt-1" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Pulsa / Reload</label>
              <input type="number" min="0" v-model="form.jml_pulsa" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg font-semibold text-center" />
              <InputError :message="form.errors.jml_pulsa" class="mt-1" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Aktivasi Gemini</label>
              <input type="number" min="0" v-model="form.jml_aktivasi_gemini" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg font-semibold text-center" />
              <InputError :message="form.errors.jml_aktivasi_gemini" class="mt-1" />
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
              <label class="block text-sm font-medium text-gray-700 mb-1">Foto Edukasi</label>
              <input type="file" @input="form.foto_edukasi = ($event.target as HTMLInputElement).files?.[0] || null" accept="image/*" 
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-200 rounded-lg" />
              <InputError :message="form.errors.foto_edukasi" class="mt-1" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Foto Penjualan</label>
              <input type="file" @input="form.foto_penjualan = ($event.target as HTMLInputElement).files?.[0] || null" accept="image/*" 
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-gray-200 rounded-lg" />
              <InputError :message="form.errors.foto_penjualan" class="mt-1" />
            </div>
          </div>
        </div>
         
        <!-- MSISDN List -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
              Input MSISDN
            </h2>
          </div>
          
          <div class="space-y-4">
            <div v-for="(item, index) in form.msisdns" :key="index" class="p-4 bg-gray-50 rounded-xl border border-gray-200 relative">
              <button v-if="form.msisdns.length > 1" type="button" @click="removeMsisdn(index)" class="absolute -top-2 -right-2 bg-red-100 text-red-600 rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
              
              <div class="space-y-3">
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1">Nomor HP</label>
                  <div class="flex rounded-lg shadow-sm">
                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-200 text-gray-700 font-semibold text-sm">
                      0
                    </span>
                    <input 
                      type="text" 
                      v-model="item.number" 
                      @input="sanitizeNumber(index)"
                      class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-lg text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                      :class="{'border-red-500 ring-1 ring-red-500': item.number.length > 0 && (item.number.length < 8 || item.number.length > 13)}"
                      placeholder="81234567890"
                      required
                    />
                  </div>
                  <p v-if="item.number.length > 0 && (item.number.length < 8 || item.number.length > 13)" class="mt-1 text-xs text-red-600">
                    Tidak valid (8-13 digit setelah 0).
                  </p>
                  <InputError :message="form.errors[`msisdns.${index}.number`]" class="mt-1" />
                </div>
                
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1">Tipe Produk</label>
                  <select v-model="item.type" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="starter_pack">Starter Pack</option>
                    <option value="reload">Reload / Pulsa</option>
                    <option value="gemini_activation">Aktivasi Gemini</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
           
          <button type="button" @click="addMsisdn" class="mt-4 px-4 py-3 border border-indigo-300 border-dashed font-semibold rounded-xl text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors focus:outline-none w-full text-center flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
            Tambah MSISDN
          </button>
        </div>
         
        <!-- Submit Button -->
        <button type="submit" :disabled="form.processing" class="w-full flex justify-center items-center gap-2 py-4 px-4 border border-transparent rounded-xl shadow-lg text-base font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50">
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
