<script setup>
import { onMounted, ref, shallowRef } from 'vue'
import * as Blockly from 'blockly/core'
import * as LokalId from 'blockly/msg/id'
import 'blockly/blocks'
import { daftarkanBlokContoh, TOOLBOX_CONTOH } from './blok-contoh'

const kanvasBlok = ref(null)
const panggung = ref(null)
const tabAktif = ref('json')
const isiJson = ref('// Susun blok untuk melihat project.json di sini.')
const workspace = shallowRef(null)

function simpanKeJson() {
  if (!workspace.value) return
  const data = Blockly.serialization.workspaces.save(workspace.value)
  isiJson.value = JSON.stringify(data, null, 2)
}

onMounted(() => {
  Blockly.setLocale(LokalId)
  daftarkanBlokContoh()

  workspace.value = Blockly.inject(kanvasBlok.value, {
    toolbox: TOOLBOX_CONTOH,
    renderer: 'zelos',
    trashcan: true,
    sounds: false,
    zoom: { controls: true, wheel: true, startScale: 0.85, minScale: 0.5, maxScale: 1.6 },
    move: { scrollbars: true, drag: true, wheel: true },
    grid: { spacing: 26, length: 2, colour: '#E4E9F5', snap: false },
  })

  workspace.value.addChangeListener(simpanKeJson)
  simpanKeJson()
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
      <section class="panel panel-blok">
        <div class="panel-kepala">
          <span class="judul">Susun bloknya</span>
          <span class="ket">Tarik blok dari kiri, sambungkan di bawah blok bendera</span>
        </div>
        <div ref="kanvasBlok" class="kanvas-blok"></div>
      </section>

      <div class="kolom-kanan">
        <section class="panel panel-panggung">
          <div class="panel-kepala">
            <span class="judul">Panggung</span>
            <span class="ket">Diisi di milestone 1.2</span>
          </div>
          <div class="panggung-bungkus">
            <canvas ref="panggung" width="480" height="360" role="img" aria-label="Panggung tempat si Pensil bergerak"></canvas>
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
          <pre v-if="tabAktif === 'js'" class="kode">// Generator kode diisi di milestone 1.4.</pre>
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

.kolom-kanan {
  display: flex;
  flex-direction: column;
  gap: 14px;
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
