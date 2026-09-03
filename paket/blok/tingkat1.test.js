// Uji blok tingkat 1 — milestone 6.1.
// "Selesai bila" (rencana-build.md): "~12 blok dominan ikon ... blok >= 56
// px, tema cerah" — ukuran/tema adalah urusan Blockly.Theme & CSS editor
// (diuji lewat Playwright, bukan di sini). Yang dibuktikan lewat unit test:
// jumlah blok, bahwa setiap blok tingkat 1 menghasilkan AST yang IDENTIK
// dengan padanan tingkat 2-nya (aturan tetap #3 — format AST tidak boleh
// berbeda per tingkat), dan bahwa semua warnanya dari palet resmi (aturan
// tetap #4).
import { describe, expect, it } from 'vitest'
import { DEFINISI_BLOK_TINGKAT_1, TOOLBOX_TINGKAT_1, WARNA } from './definisi.js'
import { astSatu } from './ast.js'

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

const FIELDS_CONTOH = { N: 10, TEKS: 'halo', AKSI: 'turun', W: '#2F6FED', NAMA: 'kring' }

describe('blok tingkat 1 (milestone 6.1)', () => {
  it('tepat sekitar 12 blok — sesuai PRD 5 ("~12, dominan ikon")', () => {
    expect(DEFINISI_BLOK_TINGKAT_1.length).toBe(12)
  })

  it('setiap blok punya ikon di message0 dan teks ucapan TTS yang tidak kosong', () => {
    const IKON = /[\u{1F300}-\u{1FAFF}\u{2190}-\u{2BFF}]/u
    for (const d of DEFINISI_BLOK_TINGKAT_1) {
      expect(IKON.test(d.message0), `${d.type} harus punya ikon di message0`).toBe(true)
      expect(d.ucapan, `${d.type} harus punya teks "ucapan" untuk TTS`).toBeTypeOf('string')
      expect(d.ucapan.length).toBeGreaterThan(0)
    }
  })

  it('setiap blok memakai warna dari palet resmi (aturan tetap #4)', () => {
    const warnaResmi = new Set(Object.values(WARNA))
    for (const d of DEFINISI_BLOK_TINGKAT_1) {
      expect(warnaResmi.has(d.colour), `${d.type} pakai warna di luar palet resmi: ${d.colour}`).toBe(true)
    }
  })

  it('semua tipe blok terdaftar tepat sekali di TOOLBOX_TINGKAT_1, tidak kurang tidak lebih', () => {
    const diToolbox = TOOLBOX_TINGKAT_1.contents.flatMap((k) => k.contents.map((b) => b.type))
    const diDefinisi = DEFINISI_BLOK_TINGKAT_1.map((d) => d.type)
    expect(diToolbox.sort()).toEqual(diDefinisi.sort())
  })

  // Bukti inti aturan tetap #3: blok tingkat 1 BUKAN format AST baru,
  // cuma tampilan baru dari simpul AST yang sudah ada.
  it.each([
    ['t1_maju', 'maju', { N: 10 }],
    ['t1_ulangi', 'ulangi', { N: 4 }],
    ['t1_tunggu', 'tunggu', { N: 1 }],
    ['t1_pena', 'pena', { AKSI: 'turun' }],
    ['t1_warna_pena', 'warna_pena', { W: '#2F6FED' }],
    ['t1_hapus_gambar', 'hapus_gambar', {}],
    ['t1_katakan', 'katakan', { TEKS: 'Halo!', N: 2 }],
    ['t1_ucapkan', 'ucapkan', { TEKS: 'Halo!' }],
    ['t1_bunyi', 'bunyi', { NAMA: 'kring' }],
  ])('astSatu("%s") identik dengan astSatu("%s") untuk field yang sama (di luar id, yang memang selalu beda)', (tipeT1, tipeT2, fields) => {
    const { id: _id1, ...astT1 } = astSatu(blokTiruan(tipeT1, fields))
    const { id: _id2, ...astT2 } = astSatu(blokTiruan(tipeT2, fields))
    expect(astT1).toEqual(astT2)
  })

  it('belok kanan/kiri tingkat 1 selalu tepat seperempat putaran (90°), tanpa field derajat', () => {
    const kanan = astSatu(blokTiruan('t1_putar_kanan', FIELDS_CONTOH))
    const kiri = astSatu(blokTiruan('t1_putar_kiri', FIELDS_CONTOH))
    expect(kanan).toEqual({ t: 'putar', n: 90, id: 'id-t1_putar_kanan' })
    expect(kiri).toEqual({ t: 'putar', n: -90, id: 'id-t1_putar_kiri' })
  })

  it('t1_ketika_bendera menghasilkan program yang sama seperti ketika_bendera (lewat programAst)', async () => {
    const { programAst } = await import('./ast.js')
    const bendera = blokTiruan('t1_ketika_bendera')
    bendera.getNextBlock = () => blokTiruan('t1_maju', { N: 20 })
    const ws = { getTopBlocks: () => [bendera] }
    expect(programAst(ws)).toEqual([{ t: 'maju', n: 20, id: 'id-t1_maju' }])
  })
})
