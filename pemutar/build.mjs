// Membundel paket/runtime jadi satu berkas kecil untuk pemutar hasil
// ekspor — milestone 2.3.
//
// Aturan tetap #6: editor dan pemutar memakai SATU mesin dari
// paket/runtime, dilarang menyalin logika runtime ke template ekspor.
// Skrip ini membuktikannya secara teknis: motor.min.js dibangun langsung
// dari sumber yang sama dipakai editor (paket/runtime/index.js), bukan
// ditulis ulang tangan seperti string minifikasi manual di prototipe.
import { build } from 'esbuild'
import { fileURLToPath } from 'node:url'
import { writeFileSync } from 'node:fs'

const masuk = fileURLToPath(new URL('../paket/runtime/index.js', import.meta.url))
const keluar = fileURLToPath(new URL('./motor.min.js', import.meta.url))

const hasil = await build({
  entryPoints: [masuk],
  bundle: true,
  minify: true,
  format: 'iife',
  globalName: 'OtakAtik',
  target: 'es2018',
  write: false,
})

const kode = hasil.outputFiles[0].text
writeFileSync(keluar, kode)
console.log(`pemutar/motor.min.js ditulis — ${kode.length} bita (${(kode.length / 1024).toFixed(2)} KB)`)
