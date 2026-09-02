<script setup>
import { computed } from 'vue'
import { BLOK_PER_TIPE, TOOLBOX_TINGKAT_2, kartuBaru } from '@otak-atik/blok'

function labelBlok(type) {
  const def = BLOK_PER_TIPE[type]
  if (!def) return type
  return [def.message0, def.message1]
    .filter(Boolean)
    .join(' ')
    .replace(/%\d/g, '…')
    .trim()
}

const props = defineProps({
  daftar: { type: Array, required: true },
  posisi: { type: Number, required: true },
  konteks: { type: String, default: 'badan' }, // 'atas' | 'badan' | 'kondisi'
})

// Blok Kejadian (hat: ketika bendera/tombol/disentuh) hanya boleh jadi
// kartu PERTAMA — dia satu-satunya yang tidak punya soket "sebelum",
// jadi tidak bisa disambung sebagai next dari kartu lain. Menyisipkan
// apa pun di posisi 0 saat sudah ada kartu akan mendorong hat itu jadi
// "next" milik kartu baru, yang ditolak Blockly (pernah kejadian saat
// verifikasi milestone 3.1). Jadi: posisi 0 hanya untuk daftar kosong,
// dan hanya menawarkan Kejadian; selain itu Kejadian tidak pernah
// ditawarkan sama sekali.
const posisiAwalKosong = computed(() => props.konteks === 'atas' && props.posisi === 0 && props.daftar.length === 0)

const kategoriTampil = computed(() => {
  if (props.konteks === 'kondisi') {
    return TOOLBOX_TINGKAT_2.contents.filter((k) => k.name === 'Kondisi')
  }
  if (posisiAwalKosong.value) {
    return TOOLBOX_TINGKAT_2.contents.filter((k) => k.name === 'Kejadian')
  }
  return TOOLBOX_TINGKAT_2.contents.filter((k) => k.name !== 'Kejadian' && k.name !== 'Kondisi')
})

const tampilkanSisip = computed(() => {
  if (props.konteks !== 'atas') return true
  if (props.posisi === 0) return posisiAwalKosong.value
  return true
})

function tambah(e) {
  const type = e.target.value
  if (!type) return
  e.target.value = ''
  props.daftar.splice(props.posisi, 0, kartuBaru(type))
}
</script>

<template>
  <select v-if="tampilkanSisip" class="kartu-sisip" @change="tambah" aria-label="Tambah blok di sini">
    <option value="">+ tambah blok</option>
    <optgroup v-for="kat in kategoriTampil" :key="kat.name" :label="kat.name">
      <option v-for="b in kat.contents" :key="b.type" :value="b.type">
        {{ labelBlok(b.type) }}
      </option>
    </optgroup>
  </select>
</template>

<style scoped>
.kartu-sisip {
  font-family: inherit;
  font-size: 12px;
  color: var(--tinta-2);
  background: #fff;
  border: 1.5px dashed var(--garis);
  border-radius: 8px;
  padding: 4px 8px;
  cursor: pointer;
  max-width: 160px;
}

/* Target sentuh >= 56px di layar HP — milestone 3.2. */
@media (max-width: 768px) {
  .kartu-sisip {
    min-height: 56px;
    max-width: none;
    width: 100%;
    font-size: 15px;
  }
}
</style>
