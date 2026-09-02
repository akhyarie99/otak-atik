<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
// computed, bukan snapshot statis — kalau tidak, nilainya tidak ikut
// berubah setelah "Buat sekolah" redirect balik ke halaman yang sama
// dengan props baru (komponennya dipakai ulang oleh Inertia, tidak
// dibuat ulang dari nol).
const keanggotaan = computed(() => page.props.keanggotaanAktif);

const form = useForm({ nama: '' });
function buatSekolah() {
    form.post(route('sekolah.buat'));
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div v-if="!keanggotaan" class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Belum ada sekolah</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Buat sekolah untuk mulai membuat kelas dan mengundang siswa.
                    </p>
                    <form class="mt-4 flex gap-3" @submit.prevent="buatSekolah">
                        <input
                            v-model="form.nama"
                            type="text"
                            placeholder="Nama sekolah"
                            class="flex-1 rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        />
                        <button
                            type="submit"
                            class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700"
                        >
                            Buat sekolah
                        </button>
                    </form>
                </div>

                <div v-else class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <p class="text-gray-900 dark:text-gray-100">
                        <strong>{{ keanggotaan.sekolah }}</strong> — {{ keanggotaan.peran }}
                    </p>
                    <a :href="route('kelas.index')" class="mt-4 inline-block text-sm font-semibold text-indigo-600 hover:underline">
                        Kelola kelas &rarr;
                    </a>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
