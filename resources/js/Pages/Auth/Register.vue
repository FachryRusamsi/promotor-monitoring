<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Region {
    id: number;
    name: string;
}

interface Area {
    id: number;
    region_id: number;
    name: string;
}

const props = defineProps<{
    regions: Region[];
    areas: Area[];
}>();

const form = useForm({
    name: '',
    phone: '',
    email: '',

    region_id: '',
    area_id: '',

    password: '',
    password_confirmation: '',
});

const filteredAreas = computed(() => {
    return props.areas.filter(
        area => area.region_id === Number(form.region_id)
    );
});

watch(
    () => form.region_id,
    () => {
        form.area_id = '';
    }
);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit">

            <!-- Name -->
            <div>
                <InputLabel
                    for="name"
                    value="Name"
                />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.name"
                />
            </div>

            <!-- Phone -->
            <div class="mt-4">
                <InputLabel
                    for="phone"
                    value="Phone Number"
                />

                <TextInput
                    id="phone"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.phone"
                    required
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.phone"
                />
            </div>

            <!-- Email -->
            <div class="mt-4">
                <InputLabel
                    for="email"
                    value="Email"
                />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.email"
                />
            </div>

            <!-- Region -->
            <div class="mt-4">
                <InputLabel
                    for="region"
                    value="Region"
                />

                <select
                    id="region"
                    v-model="form.region_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="">
                        Select Region
                    </option>

                    <option
                        v-for="region in props.regions"
                        :key="region.id"
                        :value="region.id"
                    >
                        {{ region.name }}
                    </option>
                </select>

                <InputError
                    class="mt-2"
                    :message="form.errors.region_id"
                />
            </div>

            <!-- Branch -->
            <div class="mt-4">
                <InputLabel
                    for="area"
                    value="Branch"
                />

                <select
                    id="area"
                    v-model="form.area_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="">
                        Select Branch
                    </option>

                    <option
                        v-for="area in filteredAreas"
                        :key="area.id"
                        :value="area.id"
                    >
                        {{ area.name }}
                    </option>
                </select>

                <InputError
                    class="mt-2"
                    :message="form.errors.area_id"
                />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <InputLabel
                    for="password"
                    value="Password"
                />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password"
                />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Already registered?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Register
                </PrimaryButton>
            </div>

        </form>
    </GuestLayout>
</template>