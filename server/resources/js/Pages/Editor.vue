<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({ editorUrl: String });
const iframe = ref(null);
const status = ref('Menyiapkan editor...');

// Editor (Vite, npm workspace terpisah) minta token lewat postMessage
// begitu ia siap — kredensial TIDAK ditaruh di URL (bisa bocor lewat
// riwayat/log), dikirim lewat channel yang cuma diketahui dua jendela
// ini (milestone 4.3).
async function kirimToken() {
    const { data } = await window.axios.post('/api-token');
    iframe.value.contentWindow.postMessage(
        { jenis: 'otak-atik:token', token: data.token, apiBase: window.location.origin },
        props.editorUrl,
    );
    status.value = '';
}

onMounted(() => {
    window.addEventListener('message', (e) => {
        if (e.origin === new URL(props.editorUrl).origin && e.data?.jenis === 'otak-atik:siap') {
            kirimToken();
        }
    });
});
</script>

<template>
    <Head title="Editor" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Editor</h2>
        </template>

        <div class="mx-auto max-w-7xl px-4">
            <p v-if="status" class="py-2 text-sm text-gray-500">{{ status }}</p>
            <iframe ref="iframe" :src="editorUrl" class="h-[80vh] w-full rounded-lg border border-gray-200" />
        </div>
    </AuthenticatedLayout>
</template>
