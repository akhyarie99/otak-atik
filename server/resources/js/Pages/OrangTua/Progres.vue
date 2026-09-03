<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({ anak: Object, misi: Array, izinPublikasiLuarSekolah: Boolean });

function status(m) {
    if (m.lulus) return { teks: `Lulus (percobaan ke-${m.percobaan_ke})`, kelas: 'bg-emerald-100 text-emerald-700' };
    if (m.jumlah_percobaan > 0) return { teks: `Sedang mencoba (${m.jumlah_percobaan}x)`, kelas: 'bg-amber-100 text-amber-700' };
    return { teks: 'Belum dicoba', kelas: 'bg-gray-100 text-gray-500' };
}

function ubahIzin(event) {
    router.post(route('orangtua.izin'), { izin: event.target.checked }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Progres anak" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Progres belajar {{ anak.nama_panggilan }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-for="m in misi" :key="m.misi_id" class="flex items-center justify-between p-4 text-sm">
                            <span class="text-gray-900 dark:text-gray-100">{{ m.judul }}</span>
                            <span class="rounded-full px-2 py-1 text-xs" :class="status(m).kelas">{{ status(m).teks }}</span>
                        </li>
                    </ul>
                </div>

                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" :checked="izinPublikasiLuarSekolah" @change="ubahIzin" class="mt-1 rounded border-gray-300" />
                        <span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">Izinkan karya {{ anak.nama_panggilan }} diterbitkan ke luar sekolah</span>
                            <br />
                            <span class="text-xs text-gray-500">
                                Selain izin ini, guru pengampu tetap harus menyetujui sebelum karya benar-benar
                                tampil ke luar sekolah.
                            </span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
