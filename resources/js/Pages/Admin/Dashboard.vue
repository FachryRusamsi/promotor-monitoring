<script setup lang="ts">

const stats = [
    {
        title: "Promotor Aktif",
        value: 0,
        color: "bg-blue-500"
    },
    {
        title: "Sudah Check In",
        value: 0,
        color: "bg-green-500"
    },
    {
        title: "Total Edukasi",
        value: 0,
        color: "bg-yellow-500"
    },
    {
        title: "Aktivasi Gemini",
        value: 0,
        color: "bg-purple-500"
    }
]

</script>

<template>

<div class="min-h-screen bg-gray-100">

    <!-- Header -->

    <div class="bg-indigo-700 text-white shadow">

        <div class="max-w-7xl mx-auto px-8 py-6">

            <h1 class="text-3xl font-bold">
                Dashboard Monitoring Promotor
            </h1>

            <p class="text-indigo-100 mt-1">
                Monitoring aktivitas promotor secara real-time
            </p>

        </div>

    </div>

    <div class="max-w-7xl mx-auto p-8">

        <!-- Card -->

        <div class="grid grid-cols-4 gap-6">

            <div
                v-for="item in stats"
                :key="item.title"
                class="bg-white rounded-xl shadow p-6">

                <div
                    class="w-12 h-12 rounded-lg mb-4"
                    :class="item.color">
                </div>

                <p class="text-gray-500">
                    {{ item.title }}
                </p>

                <h2 class="text-4xl font-bold mt-2">

                    {{ item.value }}

                </h2>

            </div>

        </div>

        <!-- Map -->

        <div class="bg-white rounded-xl shadow mt-8 p-6">

            <h2 class="text-xl font-semibold mb-4">

                Live Monitoring

            </h2>

            <div
                class="h-[450px] rounded-lg bg-gray-200 flex items-center justify-center">

                Leaflet Map

            </div>

        </div>

        <!-- Table -->

        <div class="bg-white rounded-xl shadow mt-8 p-6">

            <h2 class="text-xl font-semibold mb-5">

                Daftar Promotor

            </h2>

            <table class="w-full">

                <thead>

                    <tr class="border-b">

                        <th class="text-left py-3">Nama</th>

                        <th class="text-left">Outlet</th>

                        <th>Status</th>

                        <th>Edukasi</th>

                        <th>Sales</th>

                    </tr>

                </thead>

                <tbody>

                    <tr>

                        <td class="py-4">

                            Belum ada data

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</div>

</template>