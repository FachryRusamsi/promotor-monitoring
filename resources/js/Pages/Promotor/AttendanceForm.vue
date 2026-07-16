<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PromotorLayout from '@/Layouts/PromotorLayout.vue';

const props = defineProps<{
    status: string;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const stream = ref<MediaStream | null>(null);
const photoPreview = ref<string | null>(null);
const location = ref<{ lat: number; lng: number } | null>(null);
const locationError = ref<string | null>(null);

const form = useForm({
    photo: null as File | null,
    lat: null as number | null,
    lng: null as number | null,
});

const cameraError = ref<string | null>(null);

const startCamera = async () => {
    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error("Browser Anda tidak mendukung akses kamera atau koneksi tidak aman (harus HTTPS atau localhost).");
        }
        
        const mediaStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user' }, // Front camera
            audio: false
        });
        stream.value = mediaStream;
        if (videoRef.value) {
            videoRef.value.srcObject = mediaStream;
        }
        cameraError.value = null;
    } catch (err: any) {
        console.error("Camera access denied or unavailable", err);
        cameraError.value = err.message || "Akses kamera ditolak atau tidak tersedia.";
    }
};

const stopCamera = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
        stream.value = null;
    }
};

const takePhoto = () => {
    if (!videoRef.value || !canvasRef.value) return;
    
    const context = canvasRef.value.getContext('2d');
    if (!context) return;
    
    // Set canvas dimensions to video
    canvasRef.value.width = videoRef.value.videoWidth;
    canvasRef.value.height = videoRef.value.videoHeight;
    
    context.drawImage(videoRef.value, 0, 0, canvasRef.value.width, canvasRef.value.height);
    
    // Get image as blob
    canvasRef.value.toBlob((blob) => {
        if (blob) {
            const file = new File([blob], "attendance_photo.jpg", { type: "image/jpeg" });
            form.photo = file;
            photoPreview.value = URL.createObjectURL(blob);
            stopCamera();
        }
    }, 'image/jpeg', 0.8);
};

const retakePhoto = () => {
    photoPreview.value = null;
    form.photo = null;
    startCamera();
};

const getLocation = () => {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                location.value = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                form.lat = position.coords.latitude;
                form.lng = position.coords.longitude;
                locationError.value = null;
            },
            (error) => {
                locationError.value = "Gagal mendapatkan lokasi. Pastikan GPS aktif dan izin diberikan.";
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    } else {
        locationError.value = "Peramban ini tidak mendukung Geolocation.";
    }
};

onMounted(() => {
    if (props.status !== 'Sudah Check Out') {
        startCamera();
        getLocation();
    }
});

onUnmounted(() => {
    stopCamera();
});

const submit = () => {
    if (!form.photo || !form.lat || !form.lng) {
        alert("Foto dan Lokasi GPS wajib ada sebelum submit.");
        return;
    }

    const handleError = (errors: any) => {
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
    };

    if (props.status === 'Belum Check In') {
        form.post(route('promotor.attendance.checkin'), {
            onError: handleError
        });
    } else if (props.status === 'Sudah Check In') {
        form.post(route('promotor.attendance.checkout'), {
            onError: handleError
        });
    }
};
</script>

<template>
    <Head title="Absensi" />
    <PromotorLayout :hide-bottom-nav="true">
        <div class="px-5 py-6 mb-8 max-w-lg mx-auto">
            <!-- Page Header with Back Button -->
            <div class="flex items-center gap-3 mb-6">
                <Link :href="route('promotor.dashboard')" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border border-gray-200 shadow-sm hover:bg-gray-50 transition-colors active:scale-95 shrink-0">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-2xl font-bold text-gray-800">Absensi Harian</h1>
            </div>

            <!-- Status Banner -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-6 flex items-center justify-between">
                <div>
                    <p class="text-sm text-indigo-800 font-medium">Status Anda saat ini:</p>
                    <p class="text-lg font-bold text-indigo-900">{{ status }}</p>
                </div>
                <div v-if="status === 'Sudah Check In'" class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
            </div>

            <div v-if="status === 'Sudah Check Out'" class="bg-white rounded-xl shadow-sm p-6 text-center border border-gray-100">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto text-gray-500 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Tugas Selesai</h3>
                <p class="text-gray-500 mt-2 text-sm">Anda telah menyelesaikan jam kerja hari ini. Selamat beristirahat!</p>
            </div>

            <form v-else @submit.prevent="submit" class="space-y-6">
                
                <!-- GPS Location Section -->
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                    <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-3">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Lokasi GPS
                    </h3>
                    
                    <div v-if="locationError" class="text-red-500 text-sm p-3 bg-red-50 rounded-lg">
                        {{ locationError }}
                        <button type="button" @click="getLocation" class="mt-2 text-indigo-600 underline text-xs block">Coba lagi</button>
                    </div>
                    <div v-else-if="!location" class="flex items-center gap-3 text-gray-500 text-sm">
                        <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Mendapatkan lokasi...
                    </div>
                    <div v-else class="text-sm">
                        <p class="text-gray-700">Lat: <span class="font-mono text-gray-500">{{ location.lat.toFixed(6) }}</span></p>
                        <p class="text-gray-700 mt-1">Lng: <span class="font-mono text-gray-500">{{ location.lng.toFixed(6) }}</span></p>
                        <div class="mt-3 flex items-center gap-2 text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-md w-max">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            <span class="text-xs font-semibold">Lokasi Terkunci</span>
                        </div>
                    </div>
                </div>

                <!-- Camera Section -->
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                    <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-3">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Bukti Kehadiran (Selfie)
                    </h3>
                    
                    <div class="relative w-full aspect-[3/4] bg-gray-100 rounded-xl overflow-hidden mb-4">
                        <video v-show="!photoPreview" ref="videoRef" autoplay playsinline class="w-full h-full object-cover"></video>
                        <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" />
                        
                        <div v-if="cameraError" class="absolute inset-0 flex items-center justify-center bg-gray-100 p-4 text-center">
                            <span class="text-red-500 text-sm font-medium">{{ cameraError }}</span>
                        </div>
                        <div v-else-if="!stream && !photoPreview" class="absolute inset-0 flex items-center justify-center text-gray-400">
                            Meminta akses kamera...
                        </div>
                    </div>
                    <canvas ref="canvasRef" class="hidden"></canvas>

                    <div class="flex gap-2">
                        <button v-if="!photoPreview" type="button" @click="takePhoto" :disabled="!stream" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 rounded-lg transition-colors disabled:opacity-50">
                            Ambil Foto
                        </button>
                        <button v-else type="button" @click="retakePhoto" class="flex-1 border border-indigo-200 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 font-medium py-3 rounded-lg transition-colors">
                            Ulangi Foto
                        </button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.photo" />
                </div>

                <!-- Submit Button -->
                <button type="submit" :disabled="form.processing || !location || !form.photo" 
                    class="w-full font-bold py-4 rounded-xl shadow-lg transition-transform active:scale-95 disabled:opacity-50 disabled:active:scale-100 text-white flex justify-center"
                    :class="status === 'Belum Check In' ? 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-200' : 'bg-red-600 hover:bg-red-700 shadow-red-200'">
                    <span v-if="form.processing" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Menyimpan...
                    </span>
                    <span v-else>
                        {{ status === 'Belum Check In' ? 'Submit Check In' : 'Submit Check Out' }}
                    </span>
                </button>
            </form>
        </div>
    </PromotorLayout>
</template>
