// Contoh blok untuk kerangka editor (milestone 1.1).
// Bukan daftar 30 blok tingkat 2 — itu pekerjaan milestone 1.4.
// Warna kategori mengikuti aturan tetap #4 di CLAUDE.md, jangan diubah.
import * as Blockly from 'blockly/core'

export const WARNA = {
  kejadian: '#F5B32E',
  gerak: '#2F6FED',
  kontrol: '#12A472',
  pena: '#7A4FD1',
  tampilan: '#D9407E',
  suara: '#EE6C2B',
}

const DEFINISI_BLOK = [
  {
    type: 'ketika_dijalankan',
    message0: 'ketika bendera diklik',
    nextStatement: null,
    colour: WARNA.kejadian,
    tooltip: 'Semua blok di bawah ini akan dijalankan.',
  },
  {
    type: 'maju',
    message0: 'maju %1 langkah',
    args0: [{ type: 'field_number', name: 'N', value: 50 }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.gerak,
  },
  {
    type: 'putar_kanan',
    message0: 'putar ke kanan %1 derajat',
    args0: [{ type: 'field_number', name: 'N', value: 90 }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.gerak,
  },
  {
    type: 'ulangi',
    message0: 'ulangi %1 kali',
    message1: '%1',
    args0: [{ type: 'field_number', name: 'N', value: 4, min: 0, precision: 1 }],
    args1: [{ type: 'input_statement', name: 'DO' }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.kontrol,
    tooltip: 'Setiap putaran wajib melepas kendali ke browser saat dijalankan interpreter.',
  },
  {
    type: 'pena',
    message0: 'pena %1',
    args0: [
      {
        type: 'field_dropdown',
        name: 'AKSI',
        options: [
          ['turun', 'turun'],
          ['naik', 'naik'],
        ],
      },
    ],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.pena,
  },
  {
    type: 'katakan',
    message0: 'katakan %1 selama %2 detik',
    args0: [
      { type: 'field_input', name: 'TEKS', text: 'Halo!' },
      { type: 'field_number', name: 'N', value: 2, min: 0 },
    ],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.tampilan,
  },
  {
    type: 'ucapkan',
    message0: 'ucapkan %1',
    args0: [{ type: 'field_input', name: 'TEKS', text: 'Selamat belajar' }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.suara,
  },
  {
    type: 'selamanya',
    message0: 'ulangi selamanya',
    message1: '%1',
    args1: [{ type: 'input_statement', name: 'DO' }],
    previousStatement: null,
    colour: WARNA.kontrol,
    tooltip: 'Berjalan terus sampai tombol Berhenti ditekan. Aman walau isinya kosong.',
  },
]

// Konverter blok -> AST SEMENTARA, hanya untuk 8 blok contoh di atas, agar
// milestone 1.3 (interpreter) bisa diuji dengan blok Blockly sungguhan.
// Konverter penuh untuk 30 blok tingkat 2 adalah pekerjaan paket/blok di
// milestone 1.4 dan akan menggantikan fungsi ini.
function astSatuContoh(b) {
  const f = (nm) => {
    const v = parseFloat(b.getFieldValue(nm))
    return Number.isNaN(v) ? 0 : v
  }
  switch (b.type) {
    case 'maju':
      return { t: 'maju', n: f('N'), id: b.id }
    case 'putar_kanan':
      return { t: 'putar', n: f('N'), id: b.id }
    case 'pena':
      return { t: 'pena', turun: b.getFieldValue('AKSI') === 'turun', id: b.id }
    case 'katakan':
      return { t: 'katakan', teks: b.getFieldValue('TEKS'), n: f('N'), id: b.id }
    case 'ucapkan':
      return { t: 'ucapkan', teks: b.getFieldValue('TEKS'), id: b.id }
    case 'ulangi':
      return { t: 'ulangi', n: Math.floor(f('N')), isi: astUrutanContoh(b.getInputTargetBlock('DO')), id: b.id }
    case 'selamanya':
      return { t: 'selamanya', isi: astUrutanContoh(b.getInputTargetBlock('DO')), id: b.id }
    default:
      return null
  }
}

function astUrutanContoh(b) {
  const out = []
  while (b) {
    if (!b.isEnabled || b.isEnabled()) {
      const n = astSatuContoh(b)
      if (n) out.push(n)
    }
    b = b.getNextBlock()
  }
  return out
}

export function programAstContoh(workspace) {
  const bendera = workspace.getTopBlocks(true).find((b) => b.type === 'ketika_dijalankan')
  return bendera ? astUrutanContoh(bendera.getNextBlock()) : []
}

export function daftarkanBlokContoh() {
  Blockly.common.defineBlocks(
    Blockly.common.createBlockDefinitionsFromJsonArray(DEFINISI_BLOK),
  )
}

export const TOOLBOX_CONTOH = {
  kind: 'categoryToolbox',
  contents: [
    { kind: 'category', name: 'Kejadian', colour: WARNA.kejadian, contents: [{ kind: 'block', type: 'ketika_dijalankan' }] },
    {
      kind: 'category',
      name: 'Gerak',
      colour: WARNA.gerak,
      contents: [{ kind: 'block', type: 'maju' }, { kind: 'block', type: 'putar_kanan' }],
    },
    {
      kind: 'category',
      name: 'Kontrol',
      colour: WARNA.kontrol,
      contents: [{ kind: 'block', type: 'ulangi' }, { kind: 'block', type: 'selamanya' }],
    },
    { kind: 'category', name: 'Pena', colour: WARNA.pena, contents: [{ kind: 'block', type: 'pena' }] },
    { kind: 'category', name: 'Tampilan', colour: WARNA.tampilan, contents: [{ kind: 'block', type: 'katakan' }] },
    { kind: 'category', name: 'Suara', colour: WARNA.suara, contents: [{ kind: 'block', type: 'ucapkan' }] },
  ],
}
