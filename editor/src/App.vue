<script setup>
import { onMounted, ref, shallowRef } from 'vue'
import * as Blockly from 'blockly/core'
import * as LokalId from 'blockly/msg/id'
import 'blockly/blocks'
import { Interpreter, Panggung } from '@otak-atik/runtime'
import { daftarkanBlok, kodeProgram, programAst as bangunProgramAst, TOOLBOX_TINGKAT_2 } from '@otak-atik/blok'
import { MISI_TINGKAT_2, periksaMisi, TEMPLAT_TINGKAT_2 } from '@otak-atik/misi'
import { bacaBerkasProjek, muatProjek, simpanProjek } from './berkas'
import { unduhEksporHtml } from './ekspor'

const kanvasBlok = ref(null)
const kanvasPanggung = ref(null)
const berkasMasuk = ref(null)
const pesanBerkas = ref(
  'Projek tersimpan sebagai .json. Game hasil ekspor berupa satu berkas .html yang bisa dibuka tanpa internet.',
)
const tabAktif = ref('json')
const isiJson = ref('// Susun blok untuk melihat project.json di sini.')
const isiKode = ref('// Susun blok untuk melihat kode JavaScript di sini.')
const workspace = shallowRef(null)
const panggung = shallowRef(null)
const interpreter = shallowRef(null)
const sedangJalan = ref(false)
const kecepatan = ref('normal')
const pesanJalan = ref('')

const misiIndeks = ref(0)
const misiAktif = ref(MISI_TINGKAT_2[0])
const misiLulus = ref(new Set())
const hasilPeriksa = ref(null)

function pilihMisi(i) {
  misiIndeks.value = i
  misiAktif.value = MISI_TINGKAT_2[i]
  hasilPeriksa.value = null
}

function periksaMisiSekarang() {
  if (!workspace.value || !panggung.value) return
  const programAst = bangunProgramAst(workspace.value)
  hasilPeriksa.value = periksaMisi(misiAktif.value, programAst, panggung.value)
  if (hasilPeriksa.value.lulusSemua) {
    misiLulus.value = new Set(misiLulus.value).add(misiAktif.value.id)
  }
  simpanKeJson()
}

function muatTemplat(templat) {
  Blockly.serialization.workspaces.load(templat.blockly, workspace.value)
  hasilPeriksa.value = null
}

function klikSimpan() {
  const ukuran = simpanProjek(workspace.value)
  pesanBerkas.value = `Projek tersimpan (${(ukuran / 1024).toFixed(1)} KB).`
}

function klikBuka() {
  berkasMasuk.value.click()
}

async function berkasDipilih(e) {
  const file = e.target.files[0]
  e.target.value = ''
  if (!file) return
  try {
    const data = await bacaBerkasProjek(file)
    muatProjek(data, workspace.value)
    hasilPeriksa.value = null
    pesanBerkas.value = `Projek "${file.name}" berhasil dibuka.`
  } catch (err) {
    pesanBerkas.value = err.message
  }
}

function klikEkspor() {
  const programAst = bangunProgramAst(workspace.value)
  const judul = misiAktif.value?.judul || 'Karyaku'
  const { ukuran } = unduhEksporHtml(programAst, judul)
  const kb = (ukuran / 1024).toFixed(2)
  pesanBerkas.value =
    ukuran < 15 * 1024
      ? `Game diekspor: ${kb} KB (di bawah 15 KB). Buka berkasnya langsung tanpa internet.`
      : `Game diekspor: ${kb} KB — di atas target 15 KB, program ini cukup besar.`
}

function simpanKeJson() {
  if (!workspace.value) return
  const data = Blockly.serialization.workspaces.save(workspace.value)
  isiJson.value = JSON.stringify(data, null, 2)
  isiKode.value = kodeProgram(bangunProgramAst(workspace.value))
}

function sorotBlok(id) {
  try {
    workspace.value.highlightBlock(id)
  } catch {
    // workspace belum siap atau id tidak ada — aman diabaikan.
  }
}

function jalankan() {
  const bendera = workspace.value.getTopBlocks(true).find((b) => b.type === 'ketika_bendera')
  if (!bendera) {
    pesanJalan.value = 'Belum ada blok "ketika bendera diklik". Tarik dulu dari kategori Kejadian.'
    return
  }
  pesanJalan.value = ''
  const programAst = bangunProgramAst(workspace.value)
  interpreter.value.aturKecepatan(kecepatan.value)
  interpreter.value.mulai(programAst)
  sedangJalan.value = true
}

function berhenti() {
  interpreter.value.berhenti()
  sedangJalan.value = false
}

function ubahKecepatan(nama) {
  kecepatan.value = nama
  if (interpreter.value) interpreter.value.aturKecepatan(nama)
}

onMounted(() => {
  Blockly.setLocale(LokalId)
  daftarkanBlok()

  workspace.value = Blockly.inject(kanvasBlok.value, {
    toolbox: TOOLBOX_TINGKAT_2,
    renderer: 'zelos',
    trashcan: true,
    sounds: false,
    zoom: { controls: true, wheel: true, startScale: 0.85, minScale: 0.5, maxScale: 1.6 },
    move: { scrollbars: true, drag: true, wheel: true },
    grid: { spacing: 26, length: 2, colour: '#E4E9F5', snap: false },
  })

  workspace.value.addChangeListener(simpanKeJson)
  simpanKeJson()

  panggung.value = new Panggung(kanvasPanggung.value)
  interpreter.value = new Interpreter(panggung.value, {
    onLangkah: sorotBlok,
    onSelesai: () => {
      sedangJalan.value = false
    },
    onError: (e) => {
      sedangJalan.value = false
      pesanJalan.value = 'Program berhenti karena galat: ' + e.message
      console.error(e)
    },
  })

  // Game API tetap bisa dicoba langsung dari konsol saat dev (milestone 1.2).
  if (import.meta.env.DEV) {
    window.panggung = panggung.value
    window.interpreter = interpreter.value
    window.workspace = workspace.value
    window.Blockly = Blockly
  }
})
</script>

<template>
  <div class="aplikasi">
    <header class="header">
      <div class="merek">
        <h1>Otak-atik</h1>
        <p>Editor · kerangka fase 1</p>
      </div>
      <span class="lencana">Tingkat 2 · SD kelas 4–6</span>
    </header>

    <main class="tiga-panel">
      <div class="kolom-kiri">
        <section class="panel panel-misi">
          <div class="misi-daftar" role="group" aria-label="Pilih misi">
            <button
              v-for="(m, i) in MISI_TINGKAT_2"
              :key="m.id"
              class="misi-chip"
              :class="{ tuntas: misiLulus.has(m.id) }"
              :aria-pressed="misiIndeks === i"
              @click="pilihMisi(i)"
            >
              <span class="nomor">{{ misiLulus.has(m.id) ? '✓' : i + 1 }}</span>
              {{ m.judul.replace(/^\d+\.\s*/, '') }}
            </button>
          </div>
          <div class="misi-isi">
            <p>{{ misiAktif.instruksi }}</p>
            <div class="periksa">
              <button class="tbl hantu" @click="periksaMisiSekarang">Periksa misi</button>
              <div v-if="hasilPeriksa" class="hasil-periksa">
                <div class="cek" :class="hasilPeriksa.struktur.lulus ? 'ok' : 'gagal'">
                  <span class="tanda">{{ hasilPeriksa.struktur.lulus ? '✓' : '✕' }}</span>
                  <span><b>Cara mengerjakan</b><span>{{ hasilPeriksa.struktur.pesan }}</span></span>
                </div>
                <div class="cek" :class="hasilPeriksa.hasil.lulus ? 'ok' : 'gagal'">
                  <span class="tanda">{{ hasilPeriksa.hasil.lulus ? '✓' : '✕' }}</span>
                  <span><b>Hasil di panggung</b><span>{{ hasilPeriksa.hasil.pesan }}</span></span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="panel panel-blok">
          <div class="panel-kepala">
            <span class="judul">Susun bloknya</span>
            <span class="ket">Tarik blok dari kiri, sambungkan di bawah blok bendera</span>
            <div class="templat">
              <span>Mulai dari templat:</span>
              <button v-for="t in TEMPLAT_TINGKAT_2" :key="t.id" class="tbl kecil hantu" @click="muatTemplat(t)">
                {{ t.judul }}
              </button>
            </div>
          </div>
          <div ref="kanvasBlok" class="kanvas-blok"></div>
        </section>
      </div>

      <div class="kolom-kanan">
        <section class="panel panel-panggung">
          <div class="panel-kepala">
            <span class="judul">Panggung</span>
            <span class="ket">Si Pensil, lapisan pena, dan pantulan tepi</span>
          </div>
          <div class="panggung-bungkus">
            <canvas ref="kanvasPanggung" width="480" height="360" role="img" aria-label="Panggung tempat si Pensil bergerak"></canvas>
          </div>
          <div class="kendali">
            <button class="tbl jalan" :disabled="sedangJalan" @click="jalankan">▶ Jalankan</button>
            <button class="tbl henti" :disabled="!sedangJalan" @click="berhenti">■ Berhenti</button>
            <div class="kanan">
              <label for="seg-kecepatan">Kecepatan</label>
              <div class="seg" id="seg-kecepatan" role="group" aria-label="Kecepatan jalan">
                <button
                  v-for="nama in ['lambat', 'normal', 'kilat']"
                  :key="nama"
                  :aria-pressed="kecepatan === nama"
                  @click="ubahKecepatan(nama)"
                >
                  {{ nama[0].toUpperCase() + nama.slice(1) }}
                </button>
              </div>
            </div>
          </div>
          <p v-if="pesanJalan" class="pesan-galat">{{ pesanJalan }}</p>
          <div class="berkas">
            <button class="tbl kecil" @click="klikSimpan">Simpan projek</button>
            <button class="tbl kecil hantu" @click="klikBuka">Buka projek</button>
            <button class="tbl kecil" style="background: #ee6c2b" @click="klikEkspor">Ekspor jadi game</button>
            <input ref="berkasMasuk" type="file" accept=".json,application/json" hidden @change="berkasDipilih" />
            <span class="pesan">{{ pesanBerkas }}</span>
          </div>
        </section>

        <section class="panel panel-kode">
          <div class="tabs" role="tablist">
            <button
              class="tab"
              role="tab"
              :aria-selected="tabAktif === 'js'"
              @click="tabAktif = 'js'"
            >
              Kode JavaScript
            </button>
            <button
              class="tab"
              role="tab"
              :aria-selected="tabAktif === 'json'"
              @click="tabAktif = 'json'"
            >
              project.json
            </button>
          </div>
          <pre v-if="tabAktif === 'js'" class="kode">{{ isiKode }}</pre>
          <pre v-else class="kode">{{ isiJson }}</pre>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.aplikasi {
  min-height: 100vh;
  background: var(--meja);
  color: var(--tinta);
}

.header {
  background: var(--tinta);
  color: #fff;
  padding: 12px 18px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.merek h1 {
  font-size: 22px;
  margin: 0;
}
.merek p {
  margin: 0;
  font-size: 12.5px;
  color: #a9b2d6;
}
.lencana {
  margin-left: auto;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.18);
  padding: 5px 12px;
  border-radius: 999px;
  font-size: 12.5px;
  color: #dde3f7;
  font-weight: 500;
}

.tiga-panel {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 400px;
  gap: 14px;
  padding: 14px;
  align-items: start;
}

.kolom-kanan,
.kolom-kiri {
  display: flex;
  flex-direction: column;
  gap: 14px;
  min-width: 0;
}

.misi-daftar {
  display: flex;
  gap: 8px;
  padding: 12px 14px 0;
  flex-wrap: wrap;
}
.misi-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1.5px solid var(--garis);
  background: #fff;
  color: var(--tinta);
  padding: 7px 13px 7px 8px;
  border-radius: 999px;
  cursor: pointer;
  font-family: inherit;
  font-size: 13.5px;
  font-weight: 500;
}
.misi-chip .nomor {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--meja);
  display: grid;
  place-items: center;
  font-weight: 700;
  font-size: 12.5px;
  color: var(--tinta-2);
}
.misi-chip[aria-pressed='true'] {
  border-color: var(--tinta);
  background: var(--tinta);
  color: #fff;
}
.misi-chip[aria-pressed='true'] .nomor {
  background: rgba(255, 255, 255, 0.16);
  color: #fff;
}
.misi-chip.tuntas .nomor {
  background: #12a472;
  color: #fff;
}
.misi-isi {
  padding: 12px 14px 14px;
}
.misi-isi p {
  margin: 0;
  font-size: 14px;
  color: var(--tinta-2);
}
.periksa {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  margin-top: 12px;
  flex-wrap: wrap;
}
.hasil-periksa {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  font-size: 13.5px;
}
.cek {
  display: flex;
  align-items: flex-start;
  gap: 7px;
  max-width: 320px;
}
.cek .tanda {
  flex: none;
  width: 19px;
  height: 19px;
  border-radius: 50%;
  background: var(--garis);
  display: grid;
  place-items: center;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  margin-top: 1px;
}
.cek.ok .tanda {
  background: #12a472;
}
.cek.gagal .tanda {
  background: #e14b4b;
}
.cek b {
  display: block;
  font-weight: 700;
  font-size: 12.5px;
}
.cek span span {
  color: var(--tinta-2);
}

.templat {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  font-size: 12px;
  color: var(--tinta-2);
  margin-left: auto;
}
.tbl.kecil {
  font-size: 12.5px;
  padding: 6px 12px;
}
.tbl.hantu {
  background: transparent;
  color: var(--tinta-2);
  border: 1.5px solid var(--garis);
}

.panel {
  background: var(--kertas);
  border: 1px solid var(--garis);
  border-radius: 16px;
  overflow: hidden;
}
.panel-kepala {
  padding: 11px 14px;
  border-bottom: 1px solid var(--garis);
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.panel-kepala .judul {
  font-family: 'Baloo 2', sans-serif;
  font-weight: 700;
  font-size: 16px;
}
.panel-kepala .ket {
  font-size: 12.5px;
  color: var(--tinta-2);
  opacity: 0.8;
}

.kanvas-blok {
  height: min(620px, calc(100vh - 220px));
  min-height: 400px;
  width: 100%;
}

.panggung-bungkus {
  padding: 14px;
  background: var(--meja);
}
canvas {
  width: 100%;
  height: auto;
  display: block;
  background: #fff;
  border: 1px solid var(--garis);
  border-radius: 12px;
}

.kendali {
  padding: 12px 14px;
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
  border-top: 1px solid var(--garis);
}
.kendali .kanan {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 8px;
}
.kendali label {
  font-size: 12.5px;
  color: var(--tinta-2);
  font-weight: 500;
}

.tbl {
  font-family: 'Baloo 2', inherit;
  font-weight: 700;
  font-size: 15px;
  border: none;
  border-radius: 999px;
  padding: 9px 20px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  color: #fff;
  background: var(--tinta);
}
.tbl:active {
  transform: translateY(1px);
}
.tbl.jalan {
  background: #12a472;
}
.tbl.henti {
  background: #e14b4b;
}
.tbl[disabled] {
  opacity: 0.45;
  cursor: not-allowed;
}

.seg {
  display: inline-flex;
  background: var(--meja);
  border-radius: 999px;
  padding: 3px;
  gap: 2px;
}
.seg button {
  border: none;
  background: transparent;
  font-family: inherit;
  font-size: 13px;
  font-weight: 600;
  color: var(--tinta-2);
  padding: 5px 12px;
  border-radius: 999px;
  cursor: pointer;
}
.seg button[aria-pressed='true'] {
  background: #fff;
  color: var(--tinta);
  box-shadow: 0 1px 2px rgba(35, 43, 77, 0.14);
}

.berkas {
  padding: 11px 14px;
  border-top: 1px solid var(--garis);
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}
.berkas .pesan {
  font-size: 12.5px;
  color: var(--tinta-2);
  flex-basis: 100%;
}

.pesan-galat {
  margin: 0;
  padding: 0 14px 12px;
  font-size: 12.5px;
  color: #e14b4b;
}

.tabs {
  display: flex;
  gap: 4px;
  border-bottom: 1px solid var(--garis);
  padding: 0 10px;
}
.tab {
  border: none;
  background: transparent;
  font-family: inherit;
  font-weight: 600;
  font-size: 13.5px;
  color: var(--tinta-2);
  padding: 11px 12px;
  cursor: pointer;
  border-bottom: 2.5px solid transparent;
}
.tab[aria-selected='true'] {
  color: var(--tinta);
  border-bottom-color: var(--tinta);
}
.kode {
  margin: 0;
  padding: 14px;
  max-height: 230px;
  overflow: auto;
  font-family: 'JetBrains Mono', ui-monospace, monospace;
  font-size: 12.5px;
  line-height: 1.65;
  color: var(--tinta);
  background: #fafbff;
  white-space: pre;
}

@media (max-width: 920px) {
  .tiga-panel {
    grid-template-columns: 1fr;
  }
  .kanvas-blok {
    height: 56vh;
    min-height: 320px;
  }
}
</style>
