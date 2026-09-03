<script setup>
import { computed } from 'vue'
import { BLOK_PER_TIPE, daftarFieldBlok, TOOLBOX_TINGKAT_2 } from '@otak-atik/blok'
import KartuSisip from './KartuSisip.vue'
import { bicarakan } from './tts'

defineOptions({ name: 'KartuBaris' })

const props = defineProps({
  kartu: { type: Object, required: true },
  daftarInduk: { type: Array, required: true },
  indeks: { type: Number, required: true },
  workspace: { type: Object, default: null },
  bolehGeser: { type: Boolean, default: true },
  toolbox: { type: Object, default: () => TOOLBOX_TINGKAT_2 },
  besar: { type: Boolean, default: false },
})

const def = computed(() => BLOK_PER_TIPE[props.kartu.type] || {})
const field = computed(() => daftarFieldBlok(props.kartu.type))

function labelDasar() {
  return (def.value.message0 || props.kartu.type).replace(/%\d/g, '').trim()
}

// Tingkat 1 (milestone 6.1): ketuk baris yang SUDAH ada di programnya
// juga membacakan namanya — anak sering menengok ulang urutan blok yang
// sudah disusun, bukan cuma saat memilih dari drawer.
function ketukBaris() {
  if (besar) bicarakan(def.value.ucapan || null)
}

function naik() {
  if (props.indeks === 0) return
  const a = props.daftarInduk
  ;[a[props.indeks - 1], a[props.indeks]] = [a[props.indeks], a[props.indeks - 1]]
}
function turun() {
  const a = props.daftarInduk
  if (props.indeks >= a.length - 1) return
  ;[a[props.indeks], a[props.indeks + 1]] = [a[props.indeks + 1], a[props.indeks]]
}
function hapus() {
  props.daftarInduk.splice(props.indeks, 1)
}

function daftarVariabel() {
  return props.workspace ? props.workspace.getAllVariables() : []
}
function aturVariabel(nm, e) {
  props.kartu.fields[nm] = { id: e.target.value }
}
function hapusKondisi() {
  props.kartu.kondisi = null
}
</script>

<template>
  <div class="baris" :class="{ besar }" :style="{ borderLeftColor: def.colour || '#ccc' }">
    <div class="isi">
      <span class="tipe" :class="{ ketuk: besar }" @click="ketukBaris">{{ labelDasar() }}</span>
      <template v-for="f in field" :key="f.name">
        <input
          v-if="f.type === 'field_number'"
          type="number"
          class="bidang bidang-angka"
          v-model.number="kartu.fields[f.name]"
        />
        <input v-else-if="f.type === 'field_input'" type="text" class="bidang bidang-teks" v-model="kartu.fields[f.name]" />
        <select v-else-if="f.type === 'field_dropdown'" class="bidang" v-model="kartu.fields[f.name]">
          <option v-for="opt in f.options" :key="opt[1]" :value="opt[1]">{{ opt[0] }}</option>
        </select>
        <select
          v-else-if="f.type === 'field_variable'"
          class="bidang"
          :value="kartu.fields[f.name]?.id"
          @change="aturVariabel(f.name, $event)"
        >
          <option v-for="v in daftarVariabel()" :key="v.getId()" :value="v.getId()">{{ v.name }}</option>
        </select>
      </template>

      <span v-if="!bolehGeser" class="ket-tetap">tidak bisa dipindah</span>
      <div v-if="bolehGeser" class="aksi">
        <button type="button" title="Naik" :disabled="indeks === 0" @click="naik">▲</button>
        <button type="button" title="Turun" :disabled="indeks === daftarInduk.length - 1" @click="turun">▼</button>
        <button type="button" title="Hapus" class="hapus" @click="hapus">✕</button>
      </div>
    </div>

    <!-- Soket kondisi (jika/jika_lain) -->
    <div v-if="'kondisi' in kartu" class="soket soket-kondisi">
      <span class="label-soket">kondisi:</span>
      <template v-if="kartu.kondisi">
        <KartuBaris
          :kartu="kartu.kondisi"
          :daftar-induk="[kartu.kondisi]"
          :indeks="0"
          :workspace="workspace"
          :boleh-geser="false"
          :toolbox="toolbox"
          :besar="besar"
        />
        <button type="button" class="hapus-kecil" @click="hapusKondisi">✕</button>
      </template>
      <KartuSisip v-else :daftar="{ splice: (i, n, k) => (kartu.kondisi = k) }" :posisi="0" konteks="kondisi" :toolbox="toolbox" :besar="besar" />
    </div>

    <!-- Isi "DO" -->
    <div v-if="'do' in kartu" class="soket soket-badan">
      <KartuSisip :daftar="kartu.do" :posisi="0" konteks="badan" :toolbox="toolbox" :besar="besar" />
      <template v-for="(k, i) in kartu.do" :key="k.id">
        <KartuBaris :kartu="k" :daftar-induk="kartu.do" :indeks="i" :workspace="workspace" :toolbox="toolbox" :besar="besar" />
        <KartuSisip :daftar="kartu.do" :posisi="i + 1" konteks="badan" :toolbox="toolbox" :besar="besar" />
      </template>
    </div>

    <!-- "kalau tidak" (jika_lain) -->
    <template v-if="'lain' in kartu">
      <div class="label-lain">kalau tidak:</div>
      <div class="soket soket-badan">
        <KartuSisip :daftar="kartu.lain" :posisi="0" konteks="badan" :toolbox="toolbox" :besar="besar" />
        <template v-for="(k, i) in kartu.lain" :key="k.id">
          <KartuBaris :kartu="k" :daftar-induk="kartu.lain" :indeks="i" :workspace="workspace" :toolbox="toolbox" :besar="besar" />
          <KartuSisip :daftar="kartu.lain" :posisi="i + 1" konteks="badan" :toolbox="toolbox" :besar="besar" />
        </template>
      </div>
    </template>
  </div>
</template>

<style scoped>
.baris {
  border-left: 6px solid #ccc;
  border-radius: 8px;
  background: #fff;
  border-top: 1px solid var(--garis);
  border-right: 1px solid var(--garis);
  border-bottom: 1px solid var(--garis);
  padding: 8px 10px;
  margin: 4px 0;
}
.isi {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.tipe {
  font-weight: 600;
  font-size: 13.5px;
  color: var(--tinta);
}
.bidang {
  font-family: inherit;
  font-size: 13px;
  border: 1px solid var(--garis);
  border-radius: 6px;
  padding: 3px 6px;
}
.bidang-angka {
  width: 64px;
}
.bidang-teks {
  width: 120px;
}
.aksi {
  margin-left: auto;
  display: flex;
  gap: 4px;
}
.aksi button {
  border: 1px solid var(--garis);
  background: #fff;
  border-radius: 6px;
  width: 26px;
  height: 26px;
  cursor: pointer;
  font-size: 12px;
}
.aksi button.hapus {
  color: #e14b4b;
}
.aksi button[disabled] {
  opacity: 0.3;
  cursor: not-allowed;
}
.ket-tetap {
  margin-left: auto;
  font-size: 11px;
  color: var(--tinta-2);
  font-style: italic;
}
.soket {
  margin-top: 6px;
  padding-left: 16px;
  border-left: 2px dashed var(--garis);
}
.soket-kondisi {
  display: flex;
  align-items: center;
  gap: 6px;
}
.label-soket {
  font-size: 12px;
  color: var(--tinta-2);
}
.label-lain {
  font-size: 12px;
  color: var(--tinta-2);
  margin-top: 6px;
  padding-left: 6px;
}
.hapus-kecil {
  border: none;
  background: transparent;
  color: #e14b4b;
  cursor: pointer;
}
.tipe.ketuk {
  cursor: pointer;
  text-decoration: underline dotted;
  text-underline-offset: 3px;
}

/* Tingkat 1 (milestone 6.1) — target sentuh >= 56px SELALU, bukan cuma
   di layar HP; blok besar & sedikit itu poinnya (rencana-build.md 6.1). */
.baris.besar {
  border-left-width: 10px;
  padding: 12px 14px;
}
.baris.besar .tipe {
  font-size: 18px;
  font-weight: 700;
}
.baris.besar .bidang {
  min-height: 44px;
  font-size: 16px;
}
.baris.besar .aksi button {
  width: 56px;
  height: 56px;
  font-size: 18px;
}

/* Target sentuh >= 56px di layar HP — milestone 3.2. Tombol boleh
   membungkus ke bawah kartu kalau tidak muat sebaris, tapi ukurannya
   sendiri tidak pernah dikecilkan di bawah 56px. */
@media (max-width: 768px) {
  .isi {
    row-gap: 8px;
  }
  .aksi {
    width: 100%;
    justify-content: flex-end;
  }
  .aksi button {
    width: 56px;
    height: 56px;
    font-size: 17px;
  }
  .bidang {
    min-height: 44px;
    font-size: 15px;
  }
  .bidang-angka {
    width: 76px;
  }
}
</style>
