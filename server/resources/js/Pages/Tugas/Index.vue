<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ kelas: Array, tugas: Array, daftarMisi: Array });

const form = useForm({ kelas_id: '', misi_id: '', tenggat: '' });
function buatTugas() {
    form.post(route('tugas.store'), { onSuccess: () => form.reset('misi_id', 'tenggat') });
}
</script>

<template>
    <Head title="Tugas" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Tugas</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <h3 class="mb-3 font-semibold text-gray-900 dark:text-gray-100">Beri tugas baru</h3>
                    <form class="flex flex-wrap items-end gap-3" @submit.prevent="buatTugas">
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400">Kelas</label>
                            <select v-model="form.kelas_id" required class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="" disabled>Pilih kelas</option>
                                <option v-for="k in kelas" :key="k.id" :value="k.id">{{ k.nama }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400">Misi</label>
                            <select v-model="form.misi_id" required class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="" disabled>Pilih misi</option>
                                <option v-for="m in daftarMisi" :key="m.id" :value="m.id">{{ m.judul }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400">Tenggat (opsional)</label>
                            <input v-model="form.tenggat" type="datetime-local" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                        <button type="submit" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                            Beri tugas
                        </button>
                    </form>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="p-4">Kelas</th>
                                <th class="p-4">Misi</th>
                                <th class="p-4">Tenggat</th>
                                <th class="p-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in tugas" :key="t.id" class="border-b border-gray-100 dark:border-gray-700">
                                <td class="p-4 text-gray-900 dark:text-gray-100">{{ t.kelas?.nama }}</td>
                                <td class="p-4 text-gray-700 dark:text-gray-300">
                                    {{ daftarMisi.find((m) => m.id === t.misi_id)?.judul || t.misi_id }}
                                </td>
                                <td class="p-4 text-gray-700 dark:text-gray-300">
                                    {{ t.tenggat ? new Date(t.tenggat).toLocaleString('id-ID') : '—' }}
                                </td>
                                <td class="p-4 space-x-3">
                                    <a :href="route('progres.tampilkan', t.kelas_id)" class="text-sm font-semibold text-indigo-600 hover:underline">Progres</a>
                                    <a :href="route('lkpd.tampilkan', t.misi_id)" target="_blank" class="text-sm font-semibold text-indigo-600 hover:underline">LKPD</a>
                                </td>
                            </tr>
                            <tr v-if="tugas.length === 0">
                                <td colspan="4" class="p-4 text-sm text-gray-500">Belum ada tugas diberikan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
