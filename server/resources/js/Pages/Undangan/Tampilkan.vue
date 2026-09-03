<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({ token: String, berlaku: Boolean, namaSiswa: String, namaSekolah: String });
</script>

<template>
    <Head title="Undangan orang tua" />
    <GuestLayout>
        <div v-if="berlaku" class="space-y-4 text-center">
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Undangan dari {{ namaSekolah }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Anda diundang untuk memantau progres belajar <strong>{{ namaSiswa }}</strong> di Otak-atik.
            </p>
            <p class="text-xs text-gray-500">
                Masuk kalau sudah punya akun, atau daftar dulu kalau belum — undangan ini otomatis tertaut
                setelahnya.
            </p>
            <Link :href="route('undangan.terima', token)" as="button">
                <PrimaryButton class="w-full justify-center">Lanjutkan</PrimaryButton>
            </Link>
        </div>
        <div v-else class="space-y-3 text-center">
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Undangan tidak berlaku</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Tautan ini sudah dipakai atau sudah kedaluwarsa. Minta guru anak Anda membuat undangan baru.
            </p>
        </div>
    </GuestLayout>
</template>
