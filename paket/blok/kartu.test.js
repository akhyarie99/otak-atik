// Bukti "selesai bila" milestone 3.1: program yang sama harus identik
// dipandang dari kanvas dan dari kartu. Diuji dua cara sekaligus:
//   1. Kesetaraan struktural: Blockly -> Kartu -> Blockly harus SAMA
//      PERSIS (deep equal) dengan Blockly aslinya.
//   2. Kesetaraan perilaku: AST yang dibangun dari hasil pulang-pergi
//      harus identik dengan AST dari yang asli — ini yang benar-benar
//      dijalankan interpreter, jadi ini bukti yang paling berarti.
import { describe, expect, it } from 'vitest'
import { blocklyKeKartu, kartuBaru, kartuKeBlockly } from './kartu.js'
import { astUrutan } from './ast.js'

// Adaptor: bungkus JSON serialisasi Blockly jadi objek "blok" duck-typed
// yang dipahami astSatu/astUrutan (yang sama dipakai jalur kanvas
// sungguhan), supaya bisa bukti kesetaraan AST tanpa perlu Blockly asli.
function blokDariJson(json) {
  if (!json) return null
  return {
    type: json.type,
    id: json.id,
    isEnabled: () => true,
    getFieldValue: (nm) => {
      const v = json.fields?.[nm]
      return v && typeof v === 'object' ? v.id : v
    },
    getField: () => undefined,
    getNextBlock: () => blokDariJson(json.next?.block),
    getInputTargetBlock: (nm) => blokDariJson(json.inputs?.[nm]?.block),
  }
}

const PROGRAM_KAYA = {
  type: 'ketika_bendera',
  id: 'bendera',
  next: {
    block: {
      type: 'pena',
      id: 'p1',
      fields: { AKSI: 'turun' },
      next: {
        block: {
          type: 'ulangi',
          id: 'u1',
          fields: { N: 4 },
          inputs: {
            DO: {
              block: {
                type: 'maju',
                id: 'm1',
                fields: { N: 80 },
                next: { block: { type: 'putar_kanan', id: 'r1', fields: { N: 90 } } },
              },
            },
          },
          next: {
            block: {
              type: 'jika_lain',
              id: 'j1',
              inputs: {
                KONDISI: { block: { type: 'tombol_ditekan', id: 'k1', fields: { TOMBOL: 'ArrowUp' } } },
                DO: { block: { type: 'katakan', id: 'kt1', fields: { TEKS: 'Lompat!', N: 1 } } },
                LAIN: { block: { type: 'bunyi', id: 'b1', fields: { NAMA: 'pop' } } },
              },
              next: {
                block: {
                  type: 'var_atur',
                  id: 'v1',
                  fields: { NAMA: { id: 'VAR_SKOR' }, N: 0 },
                },
              },
            },
          },
        },
      },
    },
  },
}

describe('kesetaraan mode kanvas <-> mode kartu (milestone 3.1)', () => {
  it('Blockly -> Kartu -> Blockly menghasilkan struktur yang sama persis', () => {
    const kartu = blocklyKeKartu(PROGRAM_KAYA)
    const pulang = kartuKeBlockly(kartu)
    expect(pulang).toEqual(PROGRAM_KAYA)
  })

  it('AST dari hasil pulang-pergi identik dengan AST aslinya', () => {
    const astAsli = astUrutan(blokDariJson(PROGRAM_KAYA.next.block)) // lewati blok bendera, sama seperti programAst()
    const kartu = blocklyKeKartu(PROGRAM_KAYA)
    const pulang = kartuKeBlockly(kartu)
    const astPulang = astUrutan(blokDariJson(pulang.next.block))
    expect(astPulang).toEqual(astAsli)
  })

  it('kartuBaru() membuat kartu dengan nilai bawaan yang benar dan soket yang sesuai', () => {
    expect(kartuBaru('maju').fields).toEqual({ N: 50 })
    expect(kartuBaru('ulangi')).toMatchObject({ type: 'ulangi', fields: { N: 4 }, do: [] })
    expect(kartuBaru('jika')).toMatchObject({ type: 'jika', do: [], kondisi: null })
    expect(kartuBaru('jika_lain')).toMatchObject({ type: 'jika_lain', do: [], lain: [], kondisi: null })
    expect(kartuBaru('hapus_gambar')).toEqual({ id: expect.any(String), type: 'hapus_gambar', fields: {} })
  })

  it('program kosong (tanpa blok sama sekali) tetap pulang-pergi dengan aman', () => {
    expect(blocklyKeKartu(null)).toEqual([])
    expect(kartuKeBlockly([])).toBeNull()
  })

  it('menambah kartu baru ke tengah program lalu kembali ke Blockly menghasilkan urutan yang benar', () => {
    const kartu = blocklyKeKartu(PROGRAM_KAYA)
    // kartu[0]=bendera, kartu[1]=pena — sisipkan "hapus_gambar" tepat
    // setelah "pena" (indeks 1), sebelum "ulangi".
    kartu.splice(2, 0, kartuBaru('hapus_gambar'))
    const pulang = kartuKeBlockly(kartu)
    const tipeUrutan = []
    let b = pulang
    while (b) {
      tipeUrutan.push(b.type)
      b = b.next?.block
    }
    expect(tipeUrutan).toEqual(['ketika_bendera', 'pena', 'hapus_gambar', 'ulangi', 'jika_lain', 'var_atur'])
  })
})
