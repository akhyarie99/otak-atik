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
]

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
    { kind: 'category', name: 'Kontrol', colour: WARNA.kontrol, contents: [{ kind: 'block', type: 'ulangi' }] },
    { kind: 'category', name: 'Pena', colour: WARNA.pena, contents: [{ kind: 'block', type: 'pena' }] },
    { kind: 'category', name: 'Tampilan', colour: WARNA.tampilan, contents: [{ kind: 'block', type: 'katakan' }] },
    { kind: 'category', name: 'Suara', colour: WARNA.suara, contents: [{ kind: 'block', type: 'ucapkan' }] },
  ],
}
