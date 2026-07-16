<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    powerBiEmbedUrl: {
        type: String,
        default: ''
    }
});
</script>

<template>
    <Head title="Power BI Dashboard" />

    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Power BI Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="mb-4 text-gray-600">
                        Ini adalah halaman untuk melihat Dashboard Power BI yang di-*embed*. 
                        Saat ini menggunakan metode iframe (Publish to Web).
                    </p>
                    
                    <div v-if="powerBiEmbedUrl" class="w-full h-[600px] border border-gray-300 rounded-lg overflow-hidden">
                        <iframe 
                            :src="powerBiEmbedUrl" 
                            frameborder="0" 
                            allowFullScreen="true"
                            class="w-full h-full">
                        </iframe>
                    </div>
                    <div v-else class="w-full h-[300px] flex items-center justify-center bg-gray-100 border border-gray-300 rounded-lg text-gray-500">
                        URL Embed Power BI belum dikonfigurasi di file .env (POWERBI_EMBED_URL)
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
