<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ keanggotaan: Array });

const form = useForm({ keanggotaan_id: null });
function pilih(id) {
    form.keanggotaan_id = id;
    form.post(route('sekolah.pilih.aktifkan'));
}
</script>

<template>
    <GuestLayout>
        <Head title="Pilih sekolah" />

        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Akunmu terdaftar di lebih dari satu sekolah. Pilih salah satu untuk melanjutkan.
        </p>

        <ul class="space-y-2">
            <li v-for="k in keanggotaan" :key="k.id">
                <button
                    type="button"
                    class="w-full rounded-md border border-gray-300 p-3 text-left hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                    @click="pilih(k.id)"
                >
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ k.sekolah?.nama }}</span>
                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ k.peran }}</span>
                </button>
            </li>
        </ul>
    </GuestLayout>
</template>
