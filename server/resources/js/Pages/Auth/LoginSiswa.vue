<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    kode_kelas: '',
    nama_panggilan: '',
    pin: '',
});

const submit = () => {
    form.post(route('siswa.login'), { onFinish: () => form.reset('pin') });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk siswa" />

        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Tanyakan kode kelas ke gurumu, lalu ketik nama panggilan dan PIN 4 angka.
        </p>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="kode_kelas" value="Kode kelas" />
                <TextInput
                    id="kode_kelas"
                    v-model="form.kode_kelas"
                    type="text"
                    class="mt-1 block w-full uppercase"
                    required
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.kode_kelas" />
            </div>

            <div class="mt-4">
                <InputLabel for="nama_panggilan" value="Nama panggilan" />
                <TextInput
                    id="nama_panggilan"
                    v-model="form.nama_panggilan"
                    type="text"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-2" :message="form.errors.nama_panggilan" />
            </div>

            <div class="mt-4">
                <InputLabel for="pin" value="PIN (4 angka)" />
                <TextInput
                    id="pin"
                    v-model="form.pin"
                    type="text"
                    inputmode="numeric"
                    maxlength="4"
                    class="mt-1 block w-full text-center text-2xl tracking-[0.5em]"
                    required
                />
                <InputError class="mt-2" :message="form.errors.pin" />
            </div>

            <div class="mt-4 flex items-center justify-between">
                <Link :href="route('login')" class="text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400">
                    Aku guru/orang tua
                </Link>

                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Masuk
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
