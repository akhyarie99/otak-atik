<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ kelas: Array });

const formKelas = useForm({ nama: '', tahun_ajaran: '2026/2027' });
function buatKelas() {
    formKelas.post(route('kelas.store'), { onSuccess: () => formKelas.reset('nama') });
}

// --- Wizard impor: pratinjau -> pilih kolom -> proses ---
const kelasImporId = ref(null);
const pratinjau = ref(null); // {token, header, pratinjau, total_baris}
const kolomNama = ref(0);
const memproses = ref(false);
const hasilImpor = ref(null);
const galat = ref('');

function bukaImpor(id) {
    kelasImporId.value = id;
    pratinjau.value = null;
    hasilImpor.value = null;
    galat.value = '';
}

async function unggahPratinjau(e) {
    const berkas = e.target.files[0];
    if (!berkas) return;
    const fd = new FormData();
    fd.append('berkas', berkas);
    galat.value = '';
    try {
        const { data } = await window.axios.post(`/kelas/${kelasImporId.value}/impor/pratinjau`, fd);
        pratinjau.value = data;
        kolomNama.value = 0;
    } catch (err) {
        galat.value = err.response?.data?.message || 'Gagal membaca berkas.';
    }
}

async function prosesImpor() {
    memproses.value = true;
    galat.value = '';
    try {
        const { data } = await window.axios.post(`/kelas/${kelasImporId.value}/impor/proses`, {
            token: pratinjau.value.token,
            kolom_nama: kolomNama.value,
        });
        hasilImpor.value = data;
        pratinjau.value = null;
        router.reload({ only: ['kelas'] }); // supaya kolom "Siswa" ikut ter-update
    } catch (err) {
        galat.value = err.response?.data?.message || 'Gagal mengimpor.';
    } finally {
        memproses.value = false;
    }
}
</script>

<template>
    <Head title="Kelas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Kelas</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <form class="flex flex-wrap items-end gap-3" @submit.prevent="buatKelas">
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400">Nama kelas</label>
                            <input v-model="formKelas.nama" type="text" required class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400">Tahun ajaran</label>
                            <input v-model="formKelas.tahun_ajaran" type="text" required class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                        <button type="submit" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                            Tambah kelas
                        </button>
                    </form>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="p-4">Nama</th>
                                <th class="p-4">Kode kelas</th>
                                <th class="p-4">Siswa</th>
                                <th class="p-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="k in kelas" :key="k.id" class="border-b border-gray-100 dark:border-gray-700">
                                <td class="p-4 text-gray-900 dark:text-gray-100">{{ k.nama }}</td>
                                <td class="p-4 font-mono text-gray-700 dark:text-gray-300">{{ k.kode_kelas }}</td>
                                <td class="p-4 text-gray-700 dark:text-gray-300">{{ k.anggota_count }}</td>
                                <td class="p-4">
                                    <button class="text-sm font-semibold text-indigo-600 hover:underline" @click="bukaImpor(k.id)">
                                        Impor siswa
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="kelasImporId" class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Impor daftar siswa</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Berkas Excel (.xlsx) atau CSV. Kolom lain selain nama akan diabaikan (privasi anak).
                    </p>

                    <div v-if="!pratinjau && !hasilImpor" class="mt-4">
                        <input type="file" accept=".csv,.xlsx,.xls" @change="unggahPratinjau" />
                    </div>

                    <div v-if="pratinjau" class="mt-4">
                        <label class="block text-sm text-gray-600 dark:text-gray-400">Kolom mana yang berisi nama siswa?</label>
                        <select v-model.number="kolomNama" class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option v-for="(h, i) in pratinjau.header" :key="i" :value="i">{{ h || `Kolom ${i + 1}` }}</option>
                        </select>

                        <table class="mt-4 w-full text-left text-xs">
                            <thead>
                                <tr>
                                    <th v-for="(h, i) in pratinjau.header" :key="i" class="p-2 text-gray-500 dark:text-gray-400" :class="{ 'text-indigo-600 dark:text-indigo-400': i === kolomNama }">
                                        {{ h }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(baris, bi) in pratinjau.pratinjau" :key="bi">
                                    <td v-for="(sel, i) in baris" :key="i" class="p-2 text-gray-700 dark:text-gray-300" :class="{ 'font-semibold text-indigo-700 dark:text-indigo-300': i === kolomNama }">
                                        {{ sel }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Total {{ pratinjau.total_baris }} baris ditemukan.</p>

                        <button :disabled="memproses" class="mt-4 rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50" @click="prosesImpor">
                            {{ memproses ? 'Mengimpor...' : `Impor ${pratinjau.total_baris} siswa` }}
                        </button>
                    </div>

                    <div v-if="hasilImpor" class="mt-4">
                        <p class="font-semibold text-emerald-700 dark:text-emerald-400">
                            {{ hasilImpor.jumlah_dibuat }} akun siswa berhasil dibuat. Kode kelas: <span class="font-mono">{{ hasilImpor.kode_kelas }}</span>
                        </p>
                        <table class="mt-3 w-full text-left text-xs">
                            <thead><tr><th class="p-2">Nama panggilan</th><th class="p-2">PIN</th></tr></thead>
                            <tbody>
                                <tr v-for="s in hasilImpor.siswa" :key="s.nama_panggilan">
                                    <td class="p-2">{{ s.nama_panggilan }}</td>
                                    <td class="p-2 font-mono">{{ s.pin }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p v-if="galat" class="mt-3 text-sm text-red-600">{{ galat }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
