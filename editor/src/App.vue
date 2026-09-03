<script setup>
import { onMounted, ref, shallowRef, watch } from 'vue'
import * as Blockly from 'blockly/core'
import * as LokalId from 'blockly/msg/id'
import 'blockly/blocks'
import { Interpreter, Panggung } from '@otak-atik/runtime'
import {
  BLOK_PER_TIPE,
  blocklyKeKartu,
  daftarkanBlok,
  kartuKeBlockly,
  kodeProgram,
  programAst as bangunProgramAst,
  TIPE_BENDERA,
  TOOLBOX_TINGKAT_1,
  TOOLBOX_TINGKAT_2,
} from '@otak-atik/blok'
import { MISI_TINGKAT_1, MISI_TINGKAT_2, periksaMisi, TEMPLAT_TINGKAT_2 } from '@otak-atik/misi'
import { bacaBerkasProjek, berkasProjek, muatProjek, simpanProjek } from './berkas'
import { unduhEksporHtml } from './ekspor'
import ModeKartu from './ModeKartu.vue'
import { kuncilLanskapUntukBermain, lepasKunciLanskap } from './orientasi'
import { bacaLokal, simpanLokal } from './simpanLokal'
import {
  catatPercobaanMisi,
  daftarVersi,
  dorongKeAwan,
  konfigurasiSinkron,
  pulihkanVersi,
  siapSinkron,
  tarikDariAwan,
} from './sinkronAwan'
import { TEMA_TINGKAT_1 } from './temaTingkat1'
import { aturTtsBisu, bicarakan, ttsBisu } from './tts'

// Tingkat dipilih lewat parameter URL (?tingkat=1), diteruskan dari iframe
// server/resources/js/Pages/Editor.vue — milestone 6.1. Bawaan tingkat 2
// (perilaku sebelum milestone ini, tidak berubah kalau parameter tidak ada).
const tingkat = new URLSearchParams(window.location.search).get('tingkat') === '1' ? 1 : 2
const toolboxAktif = tingkat === 1 ? TOOLBOX_TINGKAT_1 : TOOLBOX_TINGKAT_2
const misiDaftar = tingkat === 1 ? MISI_TINGKAT_1 : MISI_TINGKAT_2
const ttsBisuNow = ref(ttsBisu())
function ubahBisuTts() {
  ttsBisuNow.value = !ttsBisuNow.value
  aturTtsBisu(ttsBisuNow.value)
}

function ucapanBlok(type) {
  return BLOK_PER_TIPE[type]?.ucapan || null
}

// TTS "baca label saat disentuh" (PRD 4.1, wajib di tingkat 1) — dipasang
// di WORKSPACE UTAMA dan WORKSPACE FLYOUT (drawer toolbox) secara
// terpisah, karena keduanya adalah instance Blockly.Workspace yang
// berbeda; flyout baru ada setelah kategori pertama dibuka, jadi dipasang
// ulang (aman diulang lewat penanda __ttsTerpasang) tiap kategori diklik.
// CLICK saja tidak cukup: menyentuh blok di drawer flyout (Blockly zelos)
// biasanya langsung menaruhnya ke kanvas dalam satu gestur "ketuk", yang
// menghasilkan event SELECTED (lewat newElementId), BUKAN CLICK — dibuktikan
// lewat pengamatan langsung di browser saat verifikasi milestone 6.1.
// CLICK tetap didengarkan juga untuk kasus menyentuh blok yang sudah ada
// di kanvas tanpa menggesernya sama sekali (situasi yang tidak memicu
// SELECTED baru kalau bloknya memang sudah terpilih sebelumnya).
// Gestur "ketuk" flyout sering memicu beberapa event SELECTED berturut-turut
// untuk blok yang SAMA (pilih di flyout, lalu terpilih lagi setelah disalin
// ke kanvas) — dijaga di sini supaya TTS-nya tidak mengulang kata yang sama
// 2-3 kali dalam sepersekian detik (diamati langsung saat verifikasi 6.1).
let idBlokTerakhirDiucapkan = null
let waktuUcapTerakhir = 0
function dengarKetukUntukTts(ws) {
  return (e) => {
    let idBlok = null
    if (e.type === Blockly.Events.CLICK && e.targetType === 'block') idBlok = e.blockId
    else if (e.type === Blockly.Events.SELECTED && e.newElementId) idBlok = e.newElementId
    if (!idBlok) return
    const sekarang = Date.now()
    if (idBlok === idBlokTerakhirDiucapkan && sekarang - waktuUcapTerakhir < 600) return
    const blok = ws.getBlockById(idBlok)
    if (!blok) return
    idBlokTerakhirDiucapkan = idBlok
    waktuUcapTerakhir = sekarang
    bicarakan(ucapanBlok(blok.type))
  }
}
function pasangTtsKetukBlok(ws) {
  ws.addChangeListener(dengarKetukUntukTts(ws))
  const pasangFlyout = () => {
    const fw = ws.getFlyout?.()?.getWorkspace?.()
    if (fw && !fw.__ttsTerpasang) {
      fw.addChangeListener(dengarKetukUntukTts(fw))
      fw.__ttsTerpasang = true
    }
  }
  pasangFlyout()
  ws.addChangeListener((e) => {
    if (e.type === Blockly.Events.TOOLBOX_ITEM_SELECT) pasangFlyout()
  })
}

const kanvasBlok = ref(null)
const kanvasPanggung = ref(null)
const modeTampilan = ref('kanvas') // 'kanvas' | 'kartu'
const kartuProgram = ref([])
const berkasMasuk = ref(null)
const pesanBerkas = ref(
  'Projek tersimpan sebagai .json. Game hasil ekspor berupa satu berkas .html yang bisa dibuka tanpa internet.',
)
const statusSimpan = ref('') // status autosave lokal/awan — milestone 4.3
const daftarVersiTampil = ref(null) // null = panel tertutup
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
const misiAktif = ref(misiDaftar[0])
const misiLulus = ref(new Set())
const hasilPeriksa = ref(null)

function pilihMisi(i) {
  misiIndeks.value = i
  misiAktif.value = misiDaftar[i]
  hasilPeriksa.value = null
}

function periksaMisiSekarang() {
  if (!workspace.value || !panggung.value) return
  const programAst = bangunProgramAst(workspace.value)
  hasilPeriksa.value = periksaMisi(misiAktif.value, programAst, panggung.value)
  if (hasilPeriksa.value.lulusSemua) {
    misiLulus.value = new Set(misiLulus.value).add(misiAktif.value.id)
  }
  catatPercobaanMisi(misiAktif.value.id, hasilPeriksa.value.lulusSemua)
  simpanKeJson()
}

function muatTemplat(templat) {
  Blockly.serialization.workspaces.load(templat.blockly, workspace.value)
  hasilPeriksa.value = null
}

function variabelWorkspace() {
  return workspace.value.getAllVariables().map((v) => ({ name: v.name, id: v.getId(), type: v.type }))
}

// Sinkron kanvas -> kartu: bendera (dan seluruh badan lewat rantai .next)
// dibaca dari workspace Blockly yang sama, tanpa ubah bentuk data sama
// sekali (milestone 3.1 — "selesai bila" program identik di kedua mode).
function segarkanKartuDariWorkspace() {
  const data = Blockly.serialization.workspaces.save(workspace.value)
  const top = data.blocks?.blocks?.[0] || null
  kartuProgram.value = blocklyKeKartu(top)
}

// Sinkron kartu -> kanvas: kebalikannya, dipanggil saat pindah balik ke
// mode kanvas supaya perubahan yang dibuat lewat kartu tidak hilang.
function tulisKartuKeWorkspace() {
  const top = kartuKeBlockly(kartuProgram.value)
  const data = { variables: variabelWorkspace(), blocks: { languageVersion: 0, blocks: top ? [top] : [] } }
  Blockly.serialization.workspaces.load(data, workspace.value)
}

function gantiMode(baru) {
  if (baru === modeTampilan.value) return
  if (baru === 'kartu') segarkanKartuDariWorkspace()
  else tulisKartuKeWorkspace()
  modeTampilan.value = baru
  simpanKeJson()
}

// Selagi di mode kartu, setiap perubahan (tambah/hapus/geser/ubah field)
// langsung ditulis balik ke workspace Blockly yang sama, supaya panel
// Kode/JSON dan tombol Jalankan/Periksa misi tetap hidup tanpa harus
// pindah mode dulu. Ini juga yang membuat kedua mode "satu struktur yang
// sama", bukan dua salinan yang disinkron sesekali.
watch(
  kartuProgram,
  () => {
    if (modeTampilan.value !== 'kartu' || !workspace.value) return
    tulisKartuKeWorkspace()
    simpanKeJson()
  },
  { deep: true },
)

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
  const bendera = workspace.value.getTopBlocks(true).find((b) => TIPE_BENDERA.has(b.type))
  if (!bendera) {
    pesanJalan.value =
      tingkat === 1
        ? 'Belum ada blok "🏁 Mulai". Tarik dulu dari kategori Mulai.'
        : 'Belum ada blok "ketika bendera diklik". Tarik dulu dari kategori Kejadian.'
    return
  }
  pesanJalan.value = ''
  const programAst = bangunProgramAst(workspace.value)
  interpreter.value.aturKecepatan(kecepatan.value)
  interpreter.value.mulai(programAst)
  sedangJalan.value = true
  kuncilLanskapUntukBermain()
}

function berhenti() {
  interpreter.value.berhenti()
  sedangJalan.value = false
  lepasKunciLanskap()
}

function ubahKecepatan(nama) {
  kecepatan.value = nama
  if (interpreter.value) interpreter.value.aturKecepatan(nama)
}

// --- Autosave lokal + sinkron awan — milestone 4.3 ---

let waktuAutosave = null
function jadwalkanAutosave() {
  clearTimeout(waktuAutosave)
  waktuAutosave = setTimeout(autosaveSekarang, 1500)
}

async function autosaveSekarang() {
  const project = berkasProjek(workspace.value)
  const waktu = new Date().toISOString()
  await simpanLokal(project, waktu)
  statusSimpan.value = 'Tersimpan di perangkat ini.'

  if (!siapSinkron()) return
  try {
    const hasil = await dorongKeAwan(project, waktu)
    if (hasil && new Date(hasil.client_updated_at) > new Date(waktu)) {
      // Server menang (ada tulisan lain yang lebih baru, mis. dari
      // perangkat lain) — samakan editor ini ke keadaan itu supaya
      // tidak diam-diam berbeda dari yang tersimpan.
      muatProjek(hasil.project, workspace.value)
      await simpanLokal(hasil.project, hasil.client_updated_at)
      statusSimpan.value = 'Disamakan dengan versi terbaru dari perangkat lain.'
    } else {
      statusSimpan.value = 'Tersinkron ke awan · ' + new Date(waktu).toLocaleTimeString('id-ID')
    }
  } catch (e) {
    statusSimpan.value = 'Belum tersinkron (luring): ' + e.message
  }
}

async function sinkronSaatMulai(lokal) {
  try {
    const server = await tarikDariAwan()
    if (server && (!lokal || new Date(server.client_updated_at) > new Date(lokal.clientUpdatedAt))) {
      muatProjek(server.project, workspace.value)
      await simpanLokal(server.project, server.client_updated_at)
      statusSimpan.value = 'Karya dimuat dari awan (versi terbaru).'
    } else if (lokal) {
      await dorongKeAwan(lokal.project, lokal.clientUpdatedAt)
      statusSimpan.value = 'Karya lokal disinkronkan ke awan.'
    }
  } catch (e) {
    statusSimpan.value = 'Sinkron awal gagal (luring?): ' + e.message
  }
}

async function bukaRiwayatVersi() {
  daftarVersiTampil.value = await daftarVersi()
}

async function pulihkanKeVersi(idVersi) {
  const hasil = await pulihkanVersi(idVersi)
  muatProjek(hasil.project, workspace.value)
  await simpanLokal(hasil.project, hasil.client_updated_at)
  daftarVersiTampil.value = null
  statusSimpan.value = 'Versi lama dipulihkan.'
}

onMounted(async () => {
  Blockly.setLocale(LokalId)
  daftarkanBlok()

  workspace.value = Blockly.inject(kanvasBlok.value, {
    toolbox: toolboxAktif,
    renderer: 'zelos',
    trashcan: true,
    sounds: false,
    theme: tingkat === 1 ? TEMA_TINGKAT_1 : undefined,
    zoom: { controls: true, wheel: true, startScale: tingkat === 1 ? 1.1 : 0.85, minScale: 0.5, maxScale: 1.6 },
    move: { scrollbars: true, drag: true, wheel: true },
    grid: { spacing: 26, length: 2, colour: '#E4E9F5', snap: false },
  })

  workspace.value.addChangeListener(simpanKeJson)
  workspace.value.addChangeListener(jadwalkanAutosave)
  if (tingkat === 1) pasangTtsKetukBlok(workspace.value)
  simpanKeJson()

  // Layar HP (< 768px, sama dengan batas PRD 6.1 untuk mode kanvas) —
  // buka mode kartu dari awal. Anak yang hanya punya HP tidak boleh
  // disambut kanvas kecil yang susah diseret (milestone 3.2).
  if (window.innerWidth < 768) {
    segarkanKartuDariWorkspace()
    modeTampilan.value = 'kartu'
  }

  panggung.value = new Panggung(kanvasPanggung.value)
  interpreter.value = new Interpreter(panggung.value, {
    onLangkah: sorotBlok,
    onSelesai: () => {
      sedangJalan.value = false
      lepasKunciLanskap()
    },
    onError: (e) => {
      sedangJalan.value = false
      lepasKunciLanskap()
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

  // --- Pulihkan kerja lokal dulu (offline-first, milestone 4.3) ---
  const lokal = await bacaLokal()
  if (lokal) {
    muatProjek(lokal.project, workspace.value)
    statusSimpan.value = 'Kerja terakhir dipulihkan dari perangkat ini.'
  }

  // --- Kalau ditempel di iframe (server/resources/js/Pages/Editor.vue),
  //     tunggu token sinkron dari halaman induk lewat postMessage. ---
  if (window.parent !== window) {
    window.addEventListener('message', (e) => {
      if (e.data?.jenis !== 'otak-atik:token') return
      konfigurasiSinkron({ token: e.data.token, apiBase: e.data.apiBase })
      sinkronSaatMulai(lokal)
    })
    window.parent.postMessage({ jenis: 'otak-atik:siap' }, '*')
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
      <button
        v-if="tingkat === 1"
        class="tombol-bisu"
        :aria-pressed="ttsBisuNow"
        :title="ttsBisuNow ? 'Suara mati — ketuk untuk menyalakan' : 'Suara menyala — ketuk untuk mematikan'"
        @click="ubahBisuTts"
      >
        {{ ttsBisuNow ? '🔇' : '🔊' }}
      </button>
      <span class="lencana">{{ tingkat === 1 ? 'Tingkat 1 · SD kelas 1–3' : 'Tingkat 2 · SD kelas 4–6' }}</span>
    </header>

    <main class="tiga-panel">
      <div class="kolom-kiri">
        <section class="panel panel-misi">
          <div class="misi-daftar" role="group" aria-label="Pilih misi">
            <button
              v-for="(m, i) in misiDaftar"
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
            <div class="seg seg-mode" role="group" aria-label="Mode tampilan blok">
              <button :aria-pressed="modeTampilan === 'kanvas'" @click="gantiMode('kanvas')">Kanvas</button>
              <button :aria-pressed="modeTampilan === 'kartu'" @click="gantiMode('kartu')">Kartu</button>
            </div>
            <div v-if="tingkat === 2" class="templat">
              <span>Mulai dari templat:</span>
              <button v-for="t in TEMPLAT_TINGKAT_2" :key="t.id" class="tbl kecil hantu" @click="muatTemplat(t)">
                {{ t.judul }}
              </button>
            </div>
          </div>
          <span class="ket ket-blok">{{
            modeTampilan === 'kanvas'
              ? 'Tarik blok dari kiri, sambungkan di bawah blok bendera'
              : 'Ketuk "+ tambah blok" untuk menyisipkan, panah untuk memindah urutan'
          }}</span>
          <div v-show="modeTampilan === 'kanvas'" ref="kanvasBlok" class="kanvas-blok"></div>
          <ModeKartu
            v-if="modeTampilan === 'kartu'"
            class="kanvas-blok"
            :kartu="kartuProgram"
            :workspace="workspace"
            :toolbox="toolboxAktif"
            :besar="tingkat === 1"
          />
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
            <button class="tbl kecil hantu" @click="bukaRiwayatVersi">Riwayat versi</button>
            <input ref="berkasMasuk" type="file" accept=".json,application/json" hidden @change="berkasDipilih" />
            <span class="pesan">{{ pesanBerkas }}</span>
            <span v-if="statusSimpan" class="pesan status-simpan">{{ statusSimpan }}</span>
          </div>
          <div v-if="daftarVersiTampil" class="riwayat-versi">
            <div class="riwayat-kepala">
              <span class="judul">Riwayat versi</span>
              <button class="tutup" @click="daftarVersiTampil = null">✕</button>
            </div>
            <p v-if="daftarVersiTampil.length === 0" class="pesan">Belum ada riwayat tersimpan di awan.</p>
            <ul v-else>
              <li v-for="v in daftarVersiTampil" :key="v.id">
                <span>{{ new Date(v.client_updated_at).toLocaleString('id-ID') }}</span>
                <button class="tbl kecil hantu" @click="pulihkanKeVersi(v.id)">Pulihkan</button>
              </li>
            </ul>
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
.tombol-bisu {
  margin-left: auto;
  border: 1px solid rgba(255, 255, 255, 0.18);
  background: rgba(255, 255, 255, 0.1);
  border-radius: 999px;
  width: 40px;
  height: 40px;
  font-size: 18px;
  cursor: pointer;
  display: grid;
  place-items: center;
}
/* Kalau lencana tingkat juga ada, tombol bisu tidak perlu dorong sendiri. */
.tombol-bisu + .lencana {
  margin-left: 0;
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
.ket-blok {
  display: block;
  padding: 8px 14px 0;
  font-size: 12.5px;
  color: var(--tinta-2);
  opacity: 0.8;
}
.seg-mode {
  margin-left: 4px;
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
.status-simpan {
  color: #12a472 !important;
}

.riwayat-versi {
  padding: 11px 14px;
  border-top: 1px solid var(--garis);
}
.riwayat-kepala {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}
.riwayat-kepala .judul {
  font-weight: 700;
  font-size: 13.5px;
}
.riwayat-kepala .tutup {
  margin-left: auto;
  border: none;
  background: transparent;
  cursor: pointer;
  color: var(--tinta-2);
}
.riwayat-versi ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-height: 160px;
  overflow: auto;
}
.riwayat-versi li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 12.5px;
  gap: 8px;
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

/* Target sentuh >= 56px di layar HP — milestone 3.2 (rencana-build.md).
   Di atas 768px (mode kanvas biasa dipakai dari komputer/tablet lebar)
   target sentuh yang lebih pas-desktop sudah cukup, jadi tidak dipaksa
   sebesar ini di sana — memaksakannya di layar lebar cuma membuang ruang. */
@media (max-width: 768px) {
  .tbl {
    min-height: 56px;
    padding-left: 22px;
    padding-right: 22px;
    font-size: 16px;
  }
  .tbl.kecil {
    min-height: 48px;
  }
  .seg button {
    min-height: 56px;
    padding: 10px 16px;
    font-size: 14px;
  }
  .misi-chip {
    min-height: 56px;
    padding: 8px 16px 8px 10px;
    font-size: 15px;
  }
  .misi-chip .nomor {
    width: 30px;
    height: 30px;
  }
  .tab {
    min-height: 56px;
    padding: 14px 16px;
  }
}
</style>
