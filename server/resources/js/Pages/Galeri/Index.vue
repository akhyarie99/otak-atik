<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ galeriKelas: Array, galeriSekolah: Array, karyaSaya: Array, isGuru: Boolean });

const tab = ref('kelas');
const daftar = () => (tab.value === 'kelas' ? props.galeriKelas : props.galeriSekolah);

const reaksiTersedia = [
    { jenis: 'suka', label: '👍 Suka' },
    { jenis: 'keren', label: '🤩 Keren' },
    { jenis: 'lucu', label: '😄 Lucu' },
    { jenis: 'kreatif', label: '💡 Kreatif' },
];

function beriReaksi(karyaId, jenis) {
    router.post(route('karya.reaksi', karyaId), { jenis }, { preserveScroll: true });
}

function sembunyikan(karyaId) {
    router.post(route('karya.sembunyikan', karyaId), {}, { preserveScroll: true });
}

function tampilkanKembali(karyaId) {
    router.post(route('karya.tampilkan', karyaId), {}, { preserveScroll: true });
}

function remix(karyaId) {
    router.post(route('galeri.remix', karyaId));
}

function promosikan(karyaId) {
    router.post(route('karya.promosikan', karyaId), {}, { preserveScroll: true });
}

const formTerbitkan = useForm({ status: 'kelas' });
function terbitkan(karyaId) {
    formTerbitkan.status = 'kelas';
    formTerbitkan.post(route('karya.terbitkan', karyaId), { preserveScroll: true });
}
function batalTerbitkan(karyaId) {
    formTerbitkan.status = 'privat';
    formTerbitkan.post(route('karya.terbitkan', karyaId), { preserveScroll: true });
}
</script>

<template>
    <Head title="Galeri" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Galeri karya</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <div v-if="karyaSaya.length" class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <h3 class="mb-3 font-semibold text-gray-900 dark:text-gray-100">Karyaku</h3>
                    <ul class="space-y-2">
                        <li v-for="k in karyaSaya" :key="k.id" class="flex items-center justify-between text-sm">
                            <span class="text-gray-900 dark:text-gray-100">{{ k.judul }}</span>
                            <span class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">{{ k.status_publikasi === 'privat' ? 'Belum diterbitkan' : (k.status_publikasi === 'kelas' ? 'Terbit ke kelas' : 'Terbit ke sekolah') }}</span>
                                <button v-if="k.status_publikasi === 'privat'" @click="terbitkan(k.id)" class="rounded-md bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-500">
                                    Terbitkan ke kelas
                                </button>
                                <button v-else @click="batalTerbitkan(k.id)" class="rounded-md bg-gray-200 px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-300">
                                    Batal terbitkan
                                </button>
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="flex gap-2">
                    <button
                        @click="tab = 'kelas'"
                        class="rounded-md px-4 py-2 text-sm font-semibold"
                        :class="tab === 'kelas' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-300'"
                    >
                        Galeri kelas
                    </button>
                    <button
                        @click="tab = 'sekolah'"
                        class="rounded-md px-4 py-2 text-sm font-semibold"
                        :class="tab === 'sekolah' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-300'"
                    >
                        Galeri sekolah
                    </button>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-for="k in daftar()" :key="k.id" class="flex flex-wrap items-center justify-between gap-3 p-4">
                            <div>
                                <a :href="route('galeri.mainkan', k.id)" target="_blank" class="font-semibold text-indigo-600 hover:underline">
                                    ▶ {{ k.judul }}
                                </a>
                                <p class="text-xs text-gray-500">
                                    oleh {{ k.pembuat }} · {{ k.jumlah_reaksi }} reaksi
                                </p>
                                <p v-if="k.rantai_remix" class="text-xs text-indigo-500">
                                    remix dari karya asal "{{ k.rantai_remix[0].judul }}" oleh {{ k.rantai_remix[0].pembuat }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    v-for="r in reaksiTersedia"
                                    :key="r.jenis"
                                    @click="beriReaksi(k.id, r.jenis)"
                                    class="rounded-md bg-gray-100 px-2 py-1 text-xs hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200"
                                >
                                    {{ r.label }}
                                </button>
                                <button
                                    v-if="!isGuru"
                                    @click="remix(k.id)"
                                    class="rounded-md bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-200"
                                >
                                    Remix
                                </button>
                                <button
                                    v-if="isGuru && tab === 'kelas' && k.status_publikasi === 'kelas'"
                                    @click="promosikan(k.id)"
                                    class="rounded-md bg-emerald-600 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-500"
                                >
                                    Promosikan ke sekolah
                                </button>
                                <button
                                    v-if="isGuru"
                                    @click="sembunyikan(k.id)"
                                    class="rounded-md bg-red-100 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-200"
                                >
                                    Sembunyikan
                                </button>
                            </div>
                        </li>
                        <li v-if="daftar().length === 0" class="p-4 text-sm text-gray-500">Belum ada karya yang terbit di sini.</li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
