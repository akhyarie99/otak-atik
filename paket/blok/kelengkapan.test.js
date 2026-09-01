// Uji kelengkapan — milestone 1.4.
// "Selesai bila": setiap blok punya padanan AST dan padanan baris kode;
// tidak ada blok tanpa keduanya. Blok Kejadian (hat) dikecualikan karena
// perannya membuka skrip, bukan jadi satu simpul AST (sama seperti
// prototipe rujukan: ketika_dijalankan tidak pernah muncul di astSatu).

import { describe, expect, it } from 'vitest'
import { DEFINISI_BLOK } from './definisi.js'
import { astKondisi, astSatu } from './ast.js'
import { kodeUrutan } from './kode.js'

const TIPE_KEJADIAN = new Set(['ketika_bendera', 'ketika_tombol', 'ketika_disentuh'])
const TIPE_KONDISI = new Set(['menyentuh_warna', 'menyentuh_sprite', 'tombol_ditekan'])

function blokTiruan(type, fields = {}) {
  return {
    type,
    id: `id-${type}`,
    isEnabled: () => true,
    getFieldValue: (nm) => fields[nm],
    getField: () => undefined,
    getInputTargetBlock: () => null,
    getNextBlock: () => null,
  }
}

const FIELDS_CONTOH = {
  N: 10,
  X: 0,
  Y: 0,
  TEKS: 'halo',
  AKSI: 'turun',
  W: '#2F6FED',
  STATUS: 'tampil',
  NAMA: 'skor',
  TOMBOL: 'ArrowUp',
}

describe('kelengkapan blok tingkat 2 (milestone 1.4)', () => {
  const blokStatement = DEFINISI_BLOK.filter((d) => !TIPE_KEJADIAN.has(d.type) && !TIPE_KONDISI.has(d.type))
  const blokKondisi = DEFINISI_BLOK.filter((d) => TIPE_KONDISI.has(d.type))
  const blokKejadian = DEFINISI_BLOK.filter((d) => TIPE_KEJADIAN.has(d.type))

  it.each(blokStatement.map((d) => d.type))('blok statement "%s" punya AST dan baris kode', (type) => {
    const b = blokTiruan(type, FIELDS_CONTOH)
    const ast = astSatu(b)
    expect(ast, `astSatu(${type}) tidak boleh null`).not.toBeNull()
    expect(ast.t, `${type} harus punya field t`).toBeTypeOf('string')

    const kode = kodeUrutan([ast], 0)
    expect(kode.trim(), `kodeUrutan untuk ${type} tidak boleh kosong`).not.toBe('')
  })

  it.each(blokKondisi.map((d) => d.type))('blok kondisi "%s" punya AST boolean', (type) => {
    const b = blokTiruan(type, FIELDS_CONTOH)
    const ast = astKondisi(b)
    expect(ast, `astKondisi(${type}) tidak boleh null`).not.toBeNull()
    expect(ast.t).toBe(type)
  })

  it('setiap blok kejadian punya nama tipe yang valid (dikecualikan dari astSatu, jadi pembuka skrip)', () => {
    expect(blokKejadian.map((d) => d.type)).toEqual(['ketika_bendera', 'ketika_tombol', 'ketika_disentuh'])
  })

  it('total blok terdaftar sesuai jumlah yang diuji (tidak ada yang lolos tanpa terdeteksi)', () => {
    expect(blokStatement.length + blokKondisi.length + blokKejadian.length).toBe(DEFINISI_BLOK.length)
  })

  it('perulangan dan ulangi-selamanya kosong tetap menghasilkan baris kode for/while yang valid', () => {
    const ulangi = astSatu(blokTiruan('ulangi', FIELDS_CONTOH))
    expect(kodeUrutan([ulangi], 0)).toContain('for (let i = 0;')

    const selamanya = astSatu(blokTiruan('selamanya', FIELDS_CONTOH))
    expect(kodeUrutan([selamanya], 0)).toContain('while (true)')
  })

  it('jika/jika_lain menghasilkan if/else dengan kondisi dari blok kondisi', () => {
    const bJika = blokTiruan('jika', FIELDS_CONTOH)
    bJika.getInputTargetBlock = (nm) => (nm === 'KONDISI' ? blokTiruan('tombol_ditekan', FIELDS_CONTOH) : null)
    const jika = astSatu(bJika)
    expect(jika.kondisi.t).toBe('tombol_ditekan')
    expect(kodeUrutan([jika], 0)).toContain('if (panggung.apakahTombolDitekan(')
  })
})
