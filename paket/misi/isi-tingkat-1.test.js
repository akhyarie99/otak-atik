// Bukti bahwa ke-3 misi tingkat 1 benar-benar bisa diselesaikan — sama
// seperti isi-tingkat-2.test.js, tapi juga membuktikan mesin misi yang
// SAMA melayani AST dari blok tingkat 1 tanpa kode khusus apa pun
// (lihat catatan di isi-tingkat-1.js).
import { describe, expect, it } from 'vitest'
import { periksaMisi } from './mesin.js'
import { panggungTiruan } from './uji-bantu.js'
import { MISI_TINGKAT_1 } from './isi-tingkat-1.js'

const SOLUSI = {
  'tk1-01-maju': [{ t: 'maju', n: 10, id: 'a' }],

  'tk1-02-belok': [
    { t: 'maju', n: 30, id: 'a' },
    { t: 'putar', n: 90, id: 'b' },
    { t: 'maju', n: 30, id: 'c' },
  ],

  'tk1-03-ulangi': [
    { t: 'ulangi', n: 4, id: 'a', isi: [{ t: 'maju', n: 50, id: 'b' }, { t: 'putar', n: 90, id: 'c' }] },
  ],
}

describe('misi tingkat 1 (milestone 6.1) — masing-masing punya solusi yang lulus', () => {
  it.each(MISI_TINGKAT_1.map((m) => m.id))('misi "%s" lulus kedua lapis pemeriksaan dengan solusi contoh', (id) => {
    const misi = MISI_TINGKAT_1.find((m) => m.id === id)
    const solusi = SOLUSI[id]
    expect(solusi, `belum ada solusi contoh untuk ${id}`).toBeDefined()

    const panggung = panggungTiruan()
    const hasil = periksaMisi(misi, solusi, panggung)

    expect(hasil.struktur.lulus, `struktur ${id}: ${hasil.struktur.pesan}`).toBe(true)
    expect(hasil.hasil.lulus, `hasil ${id}: ${hasil.hasil.pesan}`).toBe(true)
    expect(hasil.lulusSemua).toBe(true)
  })

  it('semua id misi tingkat 1 berawalan "tk1-" dan tidak bentrok dengan tingkat 2', () => {
    for (const m of MISI_TINGKAT_1) {
      expect(m.id.startsWith('tk1-'), `${m.id} harus berawalan "tk1-"`).toBe(true)
    }
  })

  it('misi kotak ajaib menolak jumlah ulangi selain 4', () => {
    const misi = MISI_TINGKAT_1.find((m) => m.id === 'tk1-03-ulangi')
    const salah = [{ t: 'ulangi', n: 3, id: 'a', isi: [{ t: 'maju', n: 50, id: 'b' }, { t: 'putar', n: 90, id: 'c' }] }]
    const hasil = periksaMisi(misi, salah, panggungTiruan())
    expect(hasil.struktur.lulus).toBe(false)
  })

  it('misi jalan menolak program tanpa blok maju', () => {
    const misi = MISI_TINGKAT_1.find((m) => m.id === 'tk1-01-maju')
    const hasil = periksaMisi(misi, [], panggungTiruan())
    expect(hasil.struktur.lulus).toBe(false)
    expect(hasil.lulusSemua).toBe(false)
  })
})
