// Uji blok tingkat 3 — milestone 6.2.
// "Selesai bila" (rencana-build.md): "Panel kode berubah langsung saat blok
// disusun" — dibuktikan di sini lewat kodeProgram/kodeUrutan menghasilkan
// teks yang benar untuk tiap simpul baru, dan lewat Playwright (perubahan
// nyata di editor) untuk buktinya secara langsung. Yang diuji unit di sini:
// jumlah blok (~50), konversi AST untuk ekspresi/daftar/fungsi, dan bahwa
// panel kode tidak pernah pecah untuk simpul baru manapun.
import { describe, expect, it } from 'vitest'
import { DEFINISI_BLOK, DEFINISI_BLOK_TINGKAT_3, TOOLBOX_TINGKAT_3, WARNA } from './definisi.js'
import { astKondisi, astNilai, astSatu, programAst, programFungsi } from './ast.js'
import { kodeProgram, kodeUrutan } from './kode.js'

// Blok tiruan yang bisa "menyambung" ke blok lain lewat getInputTargetBlock
// — dipakai menguji operator bersarang (t3_op_arit di dalam t3_op_arit dst).
function blokTiruan(type, { fields = {}, inputs = {}, next = null } = {}) {
  return {
    type,
    id: `id-${type}`,
    isEnabled: () => true,
    getFieldValue: (nm) => fields[nm],
    getField: () => undefined,
    getInputTargetBlock: (nm) => inputs[nm] || null,
    getNextBlock: () => next,
  }
}

describe('blok tingkat 3 (milestone 6.2)', () => {
  it('total blok tingkat 2 + tingkat 3 mendekati ~50 (PRD 5)', () => {
    const total = DEFINISI_BLOK.length + DEFINISI_BLOK_TINGKAT_3.length
    expect(total).toBeGreaterThanOrEqual(45)
    expect(total).toBeLessThanOrEqual(52)
  })

  it('setiap blok tingkat 3 memakai warna dari palet resmi (aturan tetap #4)', () => {
    const warnaResmi = new Set(Object.values(WARNA))
    for (const d of DEFINISI_BLOK_TINGKAT_3) {
      expect(warnaResmi.has(d.colour), `${d.type} pakai warna di luar palet resmi`).toBe(true)
    }
  })

  it('semua tipe blok tingkat 3 terdaftar tepat sekali di TOOLBOX_TINGKAT_3', () => {
    const diToolbox = TOOLBOX_TINGKAT_3.contents.flatMap((k) => k.contents.map((b) => b.type))
    const diDefinisiT3 = DEFINISI_BLOK_TINGKAT_3.map((d) => d.type)
    for (const type of diDefinisiT3) {
      expect(diToolbox, `${type} tidak ada di TOOLBOX_TINGKAT_3`).toContain(type)
    }
    // 30 blok tingkat 2 dipakai ulang APA ADANYA (bukan disalin ulang).
    for (const d of DEFINISI_BLOK) {
      expect(diToolbox, `blok tingkat 2 "${d.type}" harus tetap ada di toolbox tingkat 3`).toContain(d.type)
    }
  })

  it('astNilai: angka polos, operator aritmatika bersarang, posisi, acak', () => {
    expect(astNilai(blokTiruan('t3_angka', { fields: { N: 7 } }))).toBe(7)
    expect(astNilai(null)).toBe(0)
    expect(astNilai(blokTiruan('t3_posisi_x'))).toEqual({ t: 'posisi_x' })
    expect(astNilai(blokTiruan('t3_posisi_y'))).toEqual({ t: 'posisi_y' })

    const dua = blokTiruan('t3_angka', { fields: { N: 2 } })
    const tiga = blokTiruan('t3_angka', { fields: { N: 3 } })
    const tambah = blokTiruan('t3_op_arit', { fields: { OP: '+' }, inputs: { A: dua, B: tiga } })
    expect(astNilai(tambah)).toEqual({ t: 'op_arit', op: '+', kiri: 2, kanan: 3 })

    const acak = blokTiruan('t3_acak', { inputs: { MIN: dua, MAKS: tiga } })
    expect(astNilai(acak)).toEqual({ t: 'acak', min: 2, maks: 3 })
  })

  it('astKondisi: perbandingan & logika tingkat 3, termasuk bersarang dengan sensor tingkat 2', () => {
    const lima = blokTiruan('t3_angka', { fields: { N: 5 } })
    const posisiX = blokTiruan('t3_posisi_x')
    const banding = blokTiruan('t3_op_banding', { fields: { OP: 'lebih' }, inputs: { A: posisiX, B: lima } })
    expect(astKondisi(banding)).toEqual({ t: 'op_banding', op: 'lebih', kiri: { t: 'posisi_x' }, kanan: 5, id: 'id-t3_op_banding' })

    const sensor = blokTiruan('tombol_ditekan', { fields: { TOMBOL: 'ArrowUp' } })
    const bukan = blokTiruan('t3_op_bukan', { inputs: { A: sensor } })
    expect(astKondisi(bukan)).toEqual({
      t: 'op_bukan',
      nilai: { t: 'tombol_ditekan', tombol: 'ArrowUp', id: 'id-tombol_ditekan' },
      id: 'id-t3_op_bukan',
    })

    const logika = blokTiruan('t3_op_logika', { fields: { OP: 'dan' }, inputs: { A: banding, B: sensor } })
    const hasil = astKondisi(logika)
    expect(hasil.t).toBe('op_logika')
    expect(hasil.op).toBe('dan')
    expect(hasil.kiri.t).toBe('op_banding')
    expect(hasil.kanan.t).toBe('tombol_ditekan')
  })

  it('astSatu: ulangi_sampai, daftar, dan panggil fungsi', () => {
    const kondisi = blokTiruan('t3_op_banding', {
      fields: { OP: 'sama' },
      inputs: { A: blokTiruan('t3_angka', { fields: { N: 1 } }), B: blokTiruan('t3_angka', { fields: { N: 1 } }) },
    })
    const ulangiSampai = blokTiruan('t3_ulangi_sampai', { inputs: { KONDISI: kondisi, DO: null } })
    expect(astSatu(ulangiSampai)).toEqual({
      t: 'ulangi_sampai',
      kondisi: { t: 'op_banding', op: 'sama', kiri: 1, kanan: 1, id: 'id-t3_op_banding' },
      isi: [],
      id: 'id-t3_ulangi_sampai',
    })

    const buatDaftar = blokTiruan('t3_daftar_buat', { fields: { NAMA: 'buah' } })
    expect(astSatu(buatDaftar)).toEqual({ t: 'daftar_buat', nama: 'buah', id: 'id-t3_daftar_buat' })

    const tambahDaftar = blokTiruan('t3_daftar_tambah', {
      fields: { NAMA: 'buah' },
      inputs: { NILAI: blokTiruan('t3_angka', { fields: { N: 9 } }) },
    })
    expect(astSatu(tambahDaftar)).toEqual({ t: 'daftar_tambah', nama: 'buah', nilai: 9, id: 'id-t3_daftar_tambah' })

    const panggil = blokTiruan('t3_fungsi_panggil', { fields: { NAMA: 'majuDuaKali' } })
    expect(astSatu(panggil)).toEqual({ t: 'fungsi_panggil', nama: 'majuDuaKali', id: 'id-t3_fungsi_panggil' })
  })

  it('programAst menaruh deklarasi_fungsi paling depan kalau ada blok "fungsi ..."', () => {
    const isiFungsi = blokTiruan('maju', { fields: { N: 10 } })
    const fungsiBuat = blokTiruan('t3_fungsi_buat', { fields: { NAMA: 'majuSaja' }, next: isiFungsi })
    const bendera = blokTiruan('ketika_bendera', { next: blokTiruan('t3_fungsi_panggil', { fields: { NAMA: 'majuSaja' } }) })
    const ws = { getTopBlocks: () => [fungsiBuat, bendera] }

    expect(programFungsi(ws)).toEqual([{ nama: 'majuSaja', isi: [{ t: 'maju', n: 10, id: 'id-maju' }] }])

    const program = programAst(ws)
    expect(program[0]).toEqual({
      t: 'deklarasi_fungsi',
      daftar: [{ nama: 'majuSaja', isi: [{ t: 'maju', n: 10, id: 'id-maju' }] }],
      id: 'deklarasi-fungsi',
    })
    expect(program[1]).toEqual({ t: 'fungsi_panggil', nama: 'majuSaja', id: 'id-t3_fungsi_panggil' })
  })

  it('programAst TIDAK menaruh deklarasi_fungsi kalau tidak ada blok fungsi (kompatibel mundur)', () => {
    const bendera = blokTiruan('ketika_bendera', { next: blokTiruan('maju', { fields: { N: 5 } }) })
    const ws = { getTopBlocks: () => [bendera] }
    expect(programAst(ws)).toEqual([{ t: 'maju', n: 5, id: 'id-maju' }])
  })
})

describe('panel kode (kodeProgram/kodeUrutan) untuk simpul tingkat 3', () => {
  it('tidak pernah mengembalikan string kosong untuk simpul statement baru manapun', () => {
    const contoh = [
      { t: 'ulangi_sampai', kondisi: { t: 'op_banding', op: 'sama', kiri: 1, kanan: 1 }, isi: [], id: 'a' },
      { t: 'daftar_buat', nama: 'buah', id: 'b' },
      { t: 'daftar_tambah', nama: 'buah', nilai: 5, id: 'c' },
      { t: 'daftar_tampil', nama: 'buah', id: 'd' },
      { t: 'fungsi_panggil', nama: 'x', id: 'e' },
      { t: 'deklarasi_fungsi', daftar: [{ nama: 'x', isi: [] }], id: 'f' },
    ]
    for (const n of contoh) {
      const kode = kodeUrutan([n], 0)
      expect(kode.trim(), `kodeUrutan untuk ${n.t} tidak boleh kosong`).not.toBe('')
    }
  })

  it('kodeProgram merender operator aritmatika, perbandingan, dan daftar dengan sintaks JS yang masuk akal', () => {
    const ast = [
      { t: 'daftar_buat', nama: 'skorSemua', id: 'a' },
      {
        t: 'daftar_tambah',
        nama: 'skorSemua',
        nilai: { t: 'op_arit', op: '+', kiri: { t: 'posisi_x' }, kanan: 10 },
        id: 'b',
      },
      {
        t: 'jika',
        kondisi: { t: 'op_banding', op: 'lebih', kiri: { t: 'daftar_panjang', nama: 'skorSemua' }, kanan: 3 },
        isi: [{ t: 'daftar_tampil', nama: 'skorSemua', id: 'd' }],
        id: 'c',
      },
    ]
    const kode = kodeProgram(ast)
    expect(kode).toContain('let skorSemua = [];')
    expect(kode).toContain('skorSemua.push((panggung.sprite.x + 10));')
    expect(kode).toContain('if ((skorSemua.length > 3))')
    expect(kode).toContain('skorSemua.join(", ")')
  })
})
