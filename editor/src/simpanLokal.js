// Autosave lokal ke IndexedDB — milestone 4.3 (PRD 6.5): "Wajib — ini
// yang menyelamatkan karya saat listrik mati atau tab tertutup."
// idb (~1KB) dipakai murni supaya tidak menulis boilerplate callback
// IndexedDB tangan sendiri — bukan dependensi besar, tidak melanggar
// aturan "jangan tambah dependensi besar tanpa alasan" di CLAUDE.md.
import { openDB } from 'idb'

const NAMA_DB = 'otak-atik'
const TOKO = 'karya'
const KUNCI = 'aktif' // satu karya aktif per editor untuk sekarang (selaras dengan API server)

async function db() {
  return openDB(NAMA_DB, 1, {
    upgrade(db) {
      db.createObjectStore(TOKO)
    },
  })
}

// project: {blockly, program} — bentuk yang sama dengan project.json
// (milestone 2.3). clientUpdatedAt: ISO string, dasar "tulisan terakhir
// menang" saat sinkron ke server.
export async function simpanLokal(project, clientUpdatedAt = new Date().toISOString()) {
  const d = await db()
  await d.put(TOKO, { project, clientUpdatedAt }, KUNCI)
}

export async function bacaLokal() {
  const d = await db()
  return (await d.get(TOKO, KUNCI)) || null
}
