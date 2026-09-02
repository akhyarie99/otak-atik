<script setup>
import KartuBaris from './KartuBaris.vue'
import KartuSisip from './KartuSisip.vue'

const props = defineProps({
  kartu: { type: Array, required: true },
  workspace: { type: Object, default: null },
})
</script>

<template>
  <div class="mode-kartu">
    <p v-if="kartu.length === 0" class="kosong">Belum ada blok. Mulai dengan "ketika bendera diklik" di bawah.</p>
    <KartuSisip :daftar="kartu" :posisi="0" konteks="atas" />
    <template v-for="(k, i) in kartu" :key="k.id">
      <KartuBaris :kartu="k" :daftar-induk="kartu" :indeks="i" :workspace="workspace" />
      <KartuSisip :daftar="kartu" :posisi="i + 1" konteks="atas" />
    </template>
  </div>
</template>

<style scoped>
.mode-kartu {
  padding: 12px 14px;
  overflow: auto;
  height: 100%;
}
.kosong {
  font-size: 13.5px;
  color: var(--tinta-2);
  margin: 0 0 10px;
}
</style>
