import { describe, expect, it } from 'vitest'
import { periksaMisi, runTerpanjang } from './mesin.js'
import { misiPersegi } from './misi-contoh.js'
import { panggungTiruan } from './uji-bantu.js'

describe('runTerpanjang', () => {
  it('menghitung run terpanjang dari tipe yang sama di satu level', () => {
    const ast = [{ t: 'maju' }, { t: 'putar' }, { t: 'maju' }, { t: 'maju' }, { t: 'maju' }]
    expect(runTerpanjang(ast, 'maju')).toBe(3)
  })
})

describe('misi persegi — bukti mesin misi dua lapis (milestone 2.1)', () => {
  it('MENOLAK 4 blok "maju"+"putar" berturut-turut walau gambarnya persegi sempurna', () => {
    const ast = [
      { t: 'pena', turun: true },
      { t: 'maju', n: 80 },
      { t: 'putar', n: 90 },
      { t: 'maju', n: 80 },
      { t: 'putar', n: 90 },
      { t: 'maju', n: 80 },
      { t: 'putar', n: 90 },
      { t: 'maju', n: 80 },
      { t: 'putar', n: 90 },
    ]
    const panggung = panggungTiruan()
    const hasil = periksaMisi(misiPersegi, ast, panggung)

    expect(hasil.struktur.lulus).toBe(false)
    expect(hasil.lulusSemua).toBe(false)

    // Buktikan alasannya BUKAN karena gambarnya salah: kalau tetap
    // dijalankan, hasil akhirnya sungguh persegi sempurna (kembali ke
    // titik awal, tidak menabrak tepi, total putar 360°).
    const panggungTerpisah = panggungTiruan()
    for (const n of ast) {
      if (n.t === 'maju') panggungTerpisah.maju(n.n)
      if (n.t === 'putar') panggungTerpisah.putar(n.n)
    }
    expect(panggungTerpisah.sprite.x).toBeCloseTo(0, 1)
    expect(panggungTerpisah.sprite.y).toBeCloseTo(0, 1)
    expect(panggungTerpisah.statistik.totalPutar).toBe(360)
  })

  it('MELULUSKAN program yang sama tapi disusun dengan "ulangi 4 kali"', () => {
    const ast = [
      { t: 'pena', turun: true },
      { t: 'ulangi', n: 4, isi: [{ t: 'maju', n: 80 }, { t: 'putar', n: 90 }] },
    ]
    const panggung = panggungTiruan()
    const hasil = periksaMisi(misiPersegi, ast, panggung)

    expect(hasil.struktur.lulus).toBe(true)
    expect(hasil.hasil.lulus).toBe(true)
    expect(hasil.lulusSemua).toBe(true)
  })

  it('menolak di lapis struktur dulu (tanpa menjalankan program) kalau belum ada "ulangi"', () => {
    const ast = [{ t: 'maju', n: 80 }]
    const panggung = panggungTiruan()
    const hasil = periksaMisi(misiPersegi, ast, panggung)
    expect(hasil.struktur.lulus).toBe(false)
    expect(hasil.hasil.pesan).toMatch(/belum diperiksa/i)
    // Panggung tidak pernah dijalankan — posisi tetap di titik awal (0,0).
    expect(panggung.sprite.x).toBe(0)
  })

  it('lulus struktur tapi gagal hasil kalau ulangi-nya salah jumlah (bukan persegi)', () => {
    const ast = [{ t: 'ulangi', n: 3, isi: [{ t: 'maju', n: 80 }, { t: 'putar', n: 90 }] }]
    const panggung = panggungTiruan()
    const hasil = periksaMisi(misiPersegi, ast, panggung)
    expect(hasil.struktur.lulus).toBe(true)
    expect(hasil.hasil.lulus).toBe(false)
    expect(hasil.lulusSemua).toBe(false)
  })
})
