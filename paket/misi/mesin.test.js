import { describe, expect, it } from 'vitest'
import { normalisasiSudut, pantulkanDiTepi } from '@otak-atik/runtime'
import { periksaMisi, runTerpanjang } from './mesin.js'
import { misiPersegi } from './misi-contoh.js'

// Panggung tiruan tanpa canvas/DOM, memakai matematika murni yang sama
// dengan paket/runtime/panggung.js, supaya periksaHasil() bisa diuji di
// Node tanpa perlu jsdom+canvas asli.
function panggungTiruan() {
  const LEBAR = 480
  const TINGGI = 360
  const p = {
    sprite: { x: 0, y: 0, arah: 90, penaTurun: false, ucap: '' },
    statistik: { totalPutar: 0, pantul: 0, jarakTotal: 0 },
    skor: null,
    aturUlang() {
      p.sprite = { x: 0, y: 0, arah: 90, penaTurun: false, ucap: '' }
      p.statistik = { totalPutar: 0, pantul: 0, jarakTotal: 0 }
    },
    maju(n) {
      const r = (p.sprite.arah * Math.PI) / 180
      p.pergiKe(p.sprite.x + Math.sin(r) * n, p.sprite.y + Math.cos(r) * n)
      p.statistik.jarakTotal += Math.abs(n)
    },
    pergiKe(x, y) {
      p.sprite.x = x
      p.sprite.y = y
    },
    putar(d) {
      p.sprite.arah = normalisasiSudut(p.sprite.arah + d)
      p.statistik.totalPutar += d
    },
    pantulTepi() {
      const hasil = pantulkanDiTepi(p.sprite, LEBAR, TINGGI)
      p.sprite.x = hasil.x
      p.sprite.y = hasil.y
      p.sprite.arah = hasil.arah
      if (hasil.kena) p.statistik.pantul++
    },
    penaTurun() {},
    warnaPena() {},
    hapusGambar() {},
    katakan() {},
    ucapkan: () => null,
  }
  return p
}

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
