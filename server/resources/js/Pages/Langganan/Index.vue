<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({ paketSekarang: String, langganan: Object, tagihan: Array });

const formMulai = useForm({ paket: 'sekolah' });
function mulaiLangganan() {
    formMulai.post(route('langganan.mulai'));
}

function bayar(id) {
    router.post(route('tagihan.bayar', id), {}, { preserveScroll: true });
}

const labelStatus = {
    percobaan: 'Masa uji coba',
    aktif: 'Aktif',
    tenggang: 'Masa tenggang',
    hanya_baca: 'Hanya-baca (langganan habis)',
};
</script>

<template>
    <Head title="Langganan" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Langganan</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Paket sekarang</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ paketSekarang }}</p>

                    <div v-if="langganan" class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                        <p>Status: <strong>{{ labelStatus[langganan.status] || langganan.status }}</strong></p>
                        <p>Berlaku {{ langganan.mulai_pada }} sampai {{ langganan.berakhir_pada }}</p>
                    </div>

                    <form v-else class="mt-4 flex items-end gap-3" @submit.prevent="mulaiLangganan">
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400">Mulai uji coba (6 bulan gratis)</label>
                            <select v-model="formMulai.paket" class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="sekolah">Sekolah</option>
                                <option value="yayasan">Yayasan</option>
                            </select>
                        </div>
                        <button type="submit" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                            Mulai uji coba
                        </button>
                    </form>
                </div>

                <div v-if="tagihan.length" class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="p-4">Faktur</th>
                                <th class="p-4">Jumlah</th>
                                <th class="p-4">Jatuh tempo</th>
                                <th class="p-4">Status</th>
                                <th class="p-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in tagihan" :key="t.id" class="border-b border-gray-100 dark:border-gray-700">
                                <td class="p-4 font-mono text-gray-900 dark:text-gray-100">{{ t.nomor_faktur }}</td>
                                <td class="p-4 text-gray-700 dark:text-gray-300">{{ t.jumlah_format }}</td>
                                <td class="p-4 text-gray-700 dark:text-gray-300">{{ t.jatuh_tempo }}</td>
                                <td class="p-4">
                                    <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="t.status === 'lunas' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                        {{ t.status }}
                                    </span>
                                    <span v-if="t.midtrans_va_nomor" class="ml-2 font-mono text-xs text-gray-500">
                                        VA {{ t.midtrans_bank }}: {{ t.midtrans_va_nomor }}
                                    </span>
                                </td>
                                <td class="p-4 space-x-3">
                                    <button v-if="t.status !== 'lunas'" class="text-sm font-semibold text-indigo-600 hover:underline" @click="bayar(t.id)">
                                        {{ t.midtrans_va_nomor ? 'Lihat VA' : 'Bayar (buat VA)' }}
                                    </button>
                                    <a :href="route('tagihan.cetak', t.id)" target="_blank" class="text-sm font-semibold text-indigo-600 hover:underline">
                                        Cetak
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
