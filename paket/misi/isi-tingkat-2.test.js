// Bukti bahwa ke-12 misi tingkat 2 benar-benar bisa diselesaikan: untuk
// setiap misi, ada satu program contoh yang lulus KEDUA lapis pemeriksaan.
// Ini bukan pengganti uji coba anak sungguhan (lihat rencana-build.md,
// "Titik uji pertama ke anak" sebelum lanjut ke fase 3), tapi bukti bahwa
// mesinnya tidak menolak solusi yang benar.
import { describe, expect, it } from 'vitest'
import { periksaMisi } from './mesin.js'
import { panggungTiruan } from './uji-bantu.js'
import { MISI_TINGKAT_2 } from './isi-tingkat-2.js'

const SOLUSI = {
  'tk2-01-maju': [{ t: 'maju', n: 100, id: 'a' }],

  'tk2-02-putar': [
    { t: 'maju', n: 80, id: 'a' },
    { t: 'putar', n: 90, id: 'b' },
    { t: 'maju', n: 80, id: 'c' },
  ],

  'tk2-03-pena': [
    { t: 'pena', turun: true, id: 'a' },
    { t: 'maju', n: 150, id: 'b' },
  ],

  'tk2-persegi': [
    { t: 'pena', turun: true, id: 'a' },
    { t: 'ulangi', n: 4, id: 'b', isi: [{ t: 'maju', n: 80, id: 'c' }, { t: 'putar', n: 90, id: 'd' }] },
  ],

  'tk2-05-segitiga': [
    { t: 'ulangi', n: 3, id: 'a', isi: [{ t: 'maju', n: 100, id: 'b' }, { t: 'putar', n: 120, id: 'c' }] },
  ],

  'tk2-06-pantul': [
    { t: 'selamanya', id: 'a', isi: [{ t: 'maju', n: 6, id: 'b' }, { t: 'pantul', id: 'c' }] },
  ],

  'tk2-07-katakan': [{ t: 'katakan', teks: 'Halo!', n: 0.05, id: 'a' }],

  'tk2-08-skor': [
    { t: 'var_atur', nama: 'skor', n: 0, id: 'a' },
    { t: 'ulangi', n: 5, id: 'b', isi: [{ t: 'var_ubah', nama: 'skor', n: 1, id: 'c' }] },
    { t: 'var_tampil', nama: 'skor', id: 'd' },
  ],

  'tk2-09-tombol': [
    {
      t: 'selamanya', id: 'a',
      isi: [{ t: 'jika', id: 'b', kondisi: { t: 'tombol_ditekan', tombol: 'ArrowRight' }, isi: [{ t: 'maju', n: 5, id: 'c' }] }],
    },
  ],

  'tk2-10-warna': [
    { t: 'warna', w: '#2F6FED', id: 'a' },
    { t: 'maju', n: 50, id: 'b' },
    { t: 'warna', w: '#E14B4B', id: 'c' },
    { t: 'maju', n: 50, id: 'd' },
  ],

  'tk2-11-deteksi-warna': [
    {
      t: 'selamanya', id: 'a',
      isi: [{ t: 'jika', id: 'b', kondisi: { t: 'menyentuh_warna', w: '#E14B4B' }, isi: [{ t: 'katakan', teks: 'Sampai!', n: 0.01, id: 'c' }] }],
    },
  ],

  'tk2-12-bintang': [
    { t: 'ulangi', n: 5, id: 'a', isi: [{ t: 'maju', n: 120, id: 'b' }, { t: 'putar', n: 144, id: 'c' }] },
  ],
}

describe('12 misi tingkat 2 (milestone 2.2) — solusi acuan lulus keduanya', () => {
  it('dua belas misi terdaftar dengan urutan berjenjang', () => {
    expect(MISI_TINGKAT_2).toHaveLength(12)
  })

  it.each(MISI_TINGKAT_2.map((m) => [m.id, m.judul]))('misi "%s" (%s) punya solusi yang lulus struktur & hasil', (id) => {
    const misi = MISI_TINGKAT_2.find((m) => m.id === id)
    const solusi = SOLUSI[id]
    expect(solusi, `belum ada solusi acuan untuk ${id}`).toBeDefined()

    const hasil = periksaMisi(misi, solusi, panggungTiruan())
    expect(hasil.struktur.lulus, `struktur ${id}: ${hasil.struktur.pesan}`).toBe(true)
    expect(hasil.hasil.lulus, `hasil ${id}: ${hasil.hasil.pesan}`).toBe(true)
    expect(hasil.lulusSemua).toBe(true)
  })

  it('misi 4 (persegi) masih menolak versi tanpa "ulangi" (regresi milestone 2.1)', () => {
    const misi = MISI_TINGKAT_2.find((m) => m.id === 'tk2-persegi')
    const tanpaUlangi = [
      { t: 'pena', turun: true, id: 'a' },
      { t: 'maju', n: 80, id: 'b' }, { t: 'putar', n: 90, id: 'c' },
      { t: 'maju', n: 80, id: 'd' }, { t: 'putar', n: 90, id: 'e' },
      { t: 'maju', n: 80, id: 'f' }, { t: 'putar', n: 90, id: 'g' },
      { t: 'maju', n: 80, id: 'h' }, { t: 'putar', n: 90, id: 'i' },
    ]
    expect(periksaMisi(misi, tanpaUlangi, panggungTiruan()).struktur.lulus).toBe(false)
  })
})
