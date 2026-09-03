<script setup>
import { computed } from 'vue'
import { BLOK_PER_TIPE, TOOLBOX_TINGKAT_2, WARNA, kartuBaru } from '@otak-atik/blok'
import { bicarakan } from './tts'

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
  toolbox: { type: Object, default: () => TOOLBOX_TINGKAT_2 },
  // Tingkat 1 (milestone 6.1) — grid tombol ikon besar dengan TTS saat
  // ditekan, dipakai anak yang belum lancar membaca; bukan dropdown teks.
  besar: { type: Boolean, default: false },
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

// Kategori "kejadian" (blok hat) dicocokkan lewat WARNA, bukan nama —
// toolbox tingkat 1 dan tingkat 2 memakai nama kategori yang berbeda
// ("🏁 Mulai" vs "Kejadian") tapi warnanya SELALU sama (aturan tetap #4),
// jadi ini satu-satunya cara mengenali kategori itu tanpa toolbox tahu
// tingkat berapa dirinya. "Kondisi" (jika/jika_lain) hanya ada di
// toolbox tingkat 2 — di tingkat 1 filter ini aman mengembalikan larik
// kosong karena tingkat 1 memang tidak punya blok jika sama sekali.
const kategoriTampil = computed(() => {
  if (props.konteks === 'kondisi') {
    return props.toolbox.contents.filter((k) => k.name === 'Kondisi')
  }
  if (posisiAwalKosong.value) {
    return props.toolbox.contents.filter((k) => k.colour === WARNA.kejadian)
  }
  return props.toolbox.contents.filter((k) => k.colour !== WARNA.kejadian && k.name !== 'Kondisi')
})

const tampilkanSisip = computed(() => {
  if (props.konteks !== 'atas') return true
  if (props.posisi === 0) return posisiAwalKosong.value
  return true
})

function sisipkan(type) {
  props.daftar.splice(props.posisi, 0, kartuBaru(type))
}

function tambah(e) {
  const type = e.target.value
  if (!type) return
  e.target.value = ''
  sisipkan(type)
}

// Tingkat 1 (milestone 6.1): tombol ikon, bukan dropdown teks — menyentuh
// tombolnya SEKALIGUS membacakan nama bloknya (TTS) dan menambahkannya,
// supaya anak yang belum lancar membaca tetap tahu blok apa yang baru
// saja dia pilih.
function tambahBesar(type) {
  bicarakan(BLOK_PER_TIPE[type]?.ucapan || null)
  sisipkan(type)
}
</script>

<template>
  <select v-if="tampilkanSisip && !besar" class="kartu-sisip" @change="tambah" aria-label="Tambah blok di sini">
    <option value="">+ tambah blok</option>
    <optgroup v-for="kat in kategoriTampil" :key="kat.name" :label="kat.name">
      <option v-for="b in kat.contents" :key="b.type" :value="b.type">
        {{ labelBlok(b.type) }}
      </option>
    </optgroup>
  </select>
  <div v-else-if="tampilkanSisip && besar" class="kartu-sisip-besar">
    <button
      v-for="b in kategoriTampil.flatMap((k) => k.contents)"
      :key="b.type"
      type="button"
      class="tombol-ikon"
      @click="tambahBesar(b.type)"
    >
      {{ labelBlok(b.type) }}
    </button>
  </div>
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

/* Tingkat 1 (milestone 6.1): grid tombol ikon, bukan dropdown — target
   sentuh >= 56px SELALU, bukan cuma di layar HP (rencana-build.md 6.1). */
.kartu-sisip-besar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 4px 0;
}
.tombol-ikon {
  font-family: 'Baloo 2', inherit;
  font-weight: 700;
  font-size: 15px;
  min-height: 56px;
  padding: 8px 16px;
  border: 2px solid var(--garis);
  border-radius: 14px;
  background: #fff;
  cursor: pointer;
}
.tombol-ikon:active {
  transform: translateY(1px);
}
</style>
