<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';

const props = defineProps({ kelas: Object, siswa: Array, daftarMisi: Array });
const page = usePage();

function status(m) {
    if (m.lulus) return { teks: `Lulus (ke-${m.percobaan_ke})`, kelas: 'bg-emerald-100 text-emerald-700' };
    if (m.jumlah_percobaan > 0) return { teks: `Mencoba (${m.jumlah_percobaan}x)`, kelas: 'bg-amber-100 text-amber-700' };
    return { teks: 'Belum', kelas: 'bg-gray-100 text-gray-500' };
}

function undangOrangTua(keanggotaanId) {
    const nomor = prompt('Nomor WhatsApp orang tua (opsional, boleh dikosongkan):');
    router.post(route('undangan.store', keanggotaanId), { nomor_whatsapp: nomor || null }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Progres" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Progres · {{ kelas.nama }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <a
                    :href="route('progres.csv', kelas.id)"
                    class="inline-block rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700"
                >
                    Ekspor nilai (CSV)
                </a>

                <div v-if="page.props.flash?.tautanUndangan" class="rounded-md bg-indigo-50 p-4 text-sm text-indigo-900 dark:bg-indigo-950 dark:text-indigo-200">
                    Tautan undangan dibuat — bagikan ke orang tua:
                    <a :href="page.props.flash.tautanUndangan" target="_blank" class="font-mono underline">{{ page.props.flash.tautanUndangan }}</a>
                </div>

                <div class="overflow-auto bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <table class="text-left text-xs">
                        <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="sticky left-0 z-10 bg-white p-3 dark:bg-gray-800">Siswa</th>
                                <th v-for="m in daftarMisi" :key="m.id" class="p-3 whitespace-nowrap">{{ m.judul }}</th>
                                <th class="p-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in siswa" :key="s.keanggotaan_id" class="border-b border-gray-100 dark:border-gray-700">
                                <td class="sticky left-0 z-10 bg-white p-3 font-medium text-gray-900 dark:bg-gray-800 dark:text-gray-100">
                                    {{ s.nama_panggilan }}
                                </td>
                                <td v-for="m in s.misi" :key="m.misi_id" class="p-3">
                                    <span class="rounded-full px-2 py-1" :class="status(m).kelas">{{ status(m).teks }}</span>
                                </td>
                                <td class="p-3">
                                    <button
                                        @click="undangOrangTua(s.keanggotaan_id)"
                                        class="whitespace-nowrap rounded-md bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-200"
                                    >
                                        Undang orang tua
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="siswa.length === 0">
                                <td :colspan="daftarMisi.length + 2" class="p-4 text-sm text-gray-500">Belum ada siswa di kelas ini.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
