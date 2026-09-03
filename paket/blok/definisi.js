// Definisi blok tingkat 2 — milestone 1.4.
// Tanpa logika interpreter di sini; hanya bentuk visual blok. Warna
// kategori mengikuti aturan tetap #4 di CLAUDE.md, jangan pernah diubah.
//
// Kondisi & Variabel tidak punya warna resmi sendiri di PRD — diputuskan
// memakai warna Kontrol (#12A472) sebagai kategori toolbox terpisah,
// bukan warna baru, supaya aturan tetap #4 tidak dilanggar.
//
// Hanya file ini di paket/blok yang mengimpor 'blockly/core' — paket/blok
// dipakai editor saja (pemutar hasil ekspor tidak pernah memuat Blockly).
import * as Blockly from 'blockly/core'

export const WARNA = {
  kejadian: '#F5B32E',
  gerak: '#2F6FED',
  kontrol: '#12A472',
  pena: '#7A4FD1',
  tampilan: '#D9407E',
  suara: '#EE6C2B',
}

// field_colour BUKAN bagian dari blockly/core lagi (dipindah ke plugin
// terpisah @blockly/field-colour sejak Blockly modern) — dropdown warna
// bernama ini menghindari dependensi baru DAN lebih ramah anak SD
// daripada palet warna mentah (sama seperti pendekatan prototipe rujukan).
export const PALET_WARNA = [
  ['biru', '#2F6FED'],
  ['merah', '#E14B4B'],
  ['hijau', '#12A472'],
  ['kuning', '#F5B32E'],
  ['ungu', '#7A4FD1'],
  ['hitam', '#232B4D'],
]

export const TOMBOL = [
  ['panah atas', 'ArrowUp'],
  ['panah bawah', 'ArrowDown'],
  ['panah kiri', 'ArrowLeft'],
  ['panah kanan', 'ArrowRight'],
  ['spasi', ' '],
]

export const DEFINISI_BLOK = [
  // --- Kejadian ---
  {
    type: 'ketika_bendera',
    message0: 'ketika bendera diklik',
    nextStatement: null,
    colour: WARNA.kejadian,
    tooltip: 'Semua blok di bawah ini dijalankan saat tombol Jalankan ditekan.',
  },
  {
    type: 'ketika_tombol',
    message0: 'ketika tombol %1 ditekan',
    args0: [{ type: 'field_dropdown', name: 'TOMBOL', options: TOMBOL }],
    nextStatement: null,
    colour: WARNA.kejadian,
    tooltip: 'Skrip terpisah yang berjalan paralel — belum dijalankan interpreter (rencana lanjutan).',
  },
  {
    type: 'ketika_disentuh',
    message0: 'ketika disentuh',
    nextStatement: null,
    colour: WARNA.kejadian,
    tooltip: 'Skrip terpisah yang berjalan paralel — belum dijalankan interpreter (rencana lanjutan).',
  },

  // --- Gerak ---
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
    message0: 'putar kanan %1 derajat',
    args0: [{ type: 'field_number', name: 'N', value: 90 }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.gerak,
  },
  {
    type: 'putar_kiri',
    message0: 'putar kiri %1 derajat',
    args0: [{ type: 'field_number', name: 'N', value: 90 }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.gerak,
  },
  {
    type: 'pergi_ke',
    message0: 'pergi ke x %1 y %2',
    args0: [
      { type: 'field_number', name: 'X', value: 0 },
      { type: 'field_number', name: 'Y', value: 0 },
    ],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.gerak,
  },
  {
    type: 'pantul_tepi',
    message0: 'jika di tepi, pantul',
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.gerak,
  },
  {
    type: 'arahkan_ke',
    message0: 'arahkan ke %1 derajat',
    args0: [{ type: 'field_number', name: 'N', value: 90 }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.gerak,
    tooltip: '0 = ke atas, 90 = ke kanan, 180 = ke bawah, 270 = ke kiri.',
  },

  // --- Kontrol ---
  {
    type: 'ulangi',
    message0: 'ulangi %1 kali',
    message1: '%1',
    args0: [{ type: 'field_number', name: 'N', value: 4, min: 0, precision: 1 }],
    args1: [{ type: 'input_statement', name: 'DO' }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.kontrol,
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
  {
    type: 'tunggu',
    message0: 'tunggu %1 detik',
    args0: [{ type: 'field_number', name: 'N', value: 1, min: 0 }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.kontrol,
  },
  {
    type: 'jika',
    message0: 'jika %1 maka',
    message1: '%1',
    args0: [{ type: 'input_value', name: 'KONDISI', check: 'Boolean' }],
    args1: [{ type: 'input_statement', name: 'DO' }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.kontrol,
  },
  {
    type: 'jika_lain',
    message0: 'jika %1 maka',
    message1: '%1',
    message2: 'kalau tidak',
    message3: '%1',
    args0: [{ type: 'input_value', name: 'KONDISI', check: 'Boolean' }],
    args1: [{ type: 'input_statement', name: 'DO' }],
    args3: [{ type: 'input_statement', name: 'LAIN' }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.kontrol,
  },

  // --- Kondisi (nilai boolean, dicolokkan ke soket "jika") ---
  {
    type: 'menyentuh_warna',
    message0: 'menyentuh warna %1',
    args0: [{ type: 'field_dropdown', name: 'W', options: PALET_WARNA }],
    output: 'Boolean',
    colour: WARNA.kontrol,
  },
  {
    type: 'menyentuh_sprite',
    message0: 'menyentuh sprite lain',
    output: 'Boolean',
    colour: WARNA.kontrol,
    tooltip: 'Selalu salah untuk sekarang — panggung baru memuat satu sprite.',
  },
  {
    type: 'tombol_ditekan',
    message0: 'tombol %1 ditekan',
    args0: [{ type: 'field_dropdown', name: 'TOMBOL', options: TOMBOL }],
    output: 'Boolean',
    colour: WARNA.kontrol,
  },

  // --- Variabel ---
  {
    type: 'var_atur',
    message0: 'atur %1 ke %2',
    args0: [
      { type: 'field_variable', name: 'NAMA', variable: 'skor' },
      { type: 'field_number', name: 'N', value: 0 },
    ],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.kontrol,
  },
  {
    type: 'var_ubah',
    message0: 'ubah %1 sebanyak %2',
    args0: [
      { type: 'field_variable', name: 'NAMA', variable: 'skor' },
      { type: 'field_number', name: 'N', value: 1 },
    ],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.kontrol,
  },
  {
    type: 'var_tampil',
    message0: 'tampilkan skor %1',
    args0: [{ type: 'field_variable', name: 'NAMA', variable: 'skor' }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.kontrol,
  },

  // --- Pena ---
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
    type: 'warna_pena',
    message0: 'warna pena %1',
    args0: [{ type: 'field_dropdown', name: 'W', options: PALET_WARNA }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.pena,
  },
  {
    type: 'hapus_gambar',
    message0: 'hapus semua gambar',
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.pena,
  },

  // --- Tampilan ---
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
    type: 'kostum',
    message0: 'ganti kostum ke %1',
    args0: [
      {
        type: 'field_dropdown',
        name: 'NAMA',
        options: [
          ['pensil', 'pensil'],
          ['bulat', 'bulat'],
        ],
      },
    ],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.tampilan,
  },
  {
    type: 'atur_tampil',
    message0: 'atur tampilan %1',
    args0: [
      {
        type: 'field_dropdown',
        name: 'STATUS',
        options: [
          ['tampil', 'tampil'],
          ['sembunyi', 'sembunyi'],
        ],
      },
    ],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.tampilan,
  },
  {
    type: 'ukuran',
    message0: 'ganti ukuran jadi %1 %',
    args0: [{ type: 'field_number', name: 'N', value: 100, min: 10, max: 400 }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.tampilan,
  },

  // --- Suara ---
  {
    type: 'bunyi',
    message0: 'mainkan bunyi %1',
    args0: [
      {
        type: 'field_dropdown',
        name: 'NAMA',
        options: [
          ['pop', 'pop'],
          ['ting', 'ting'],
          ['kring', 'kring'],
        ],
      },
    ],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.suara,
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

// Palet warna dengan ikon kotak warna — dipakai blok tingkat 1 (milestone
// 6.1) supaya bisa dipilih tanpa membaca nama warnanya. Nilai (kode hex)
// sama persis dengan PALET_WARNA supaya AST ('warna') tidak perlu tahu
// bedanya blok tingkat 1 atau tingkat 2 sama sekali.
export const PALET_WARNA_IKON = [
  ['🔵 biru', '#2F6FED'],
  ['🔴 merah', '#E14B4B'],
  ['🟢 hijau', '#12A472'],
  ['🟡 kuning', '#F5B32E'],
  ['🟣 ungu', '#7A4FD1'],
  ['⚫ hitam', '#232B4D'],
]

// Blok tingkat 1 (SD kelas 1-3, milestone 6.1) — PRD 4.1/4.2/5: ~12 blok
// dominan ikon, teks minimal, target sentuh besar. BUKAN blok baru secara
// konsep — setiap satu memetakan ke SIMPUL AST YANG SAMA dengan padanan
// tingkat 2-nya (lihat ast.js: tipe blok 't1_...' ditambahkan ke case yang
// sama), supaya format AST tetap satu (aturan tetap #3) dan anak yang naik
// ke tingkat 2 tidak kehilangan apa pun dari karyanya.
//
// Field angka yang BUKAN inti konsep yang diajarkan (derajat putar) sengaja
// dihilangkan — "belok kanan/kiri" selalu seperempat putaran tetap, supaya
// anak tidak perlu memahami "derajat" dulu. Field yang MEMANG inti konsep
// (jumlah pengulangan di "ulangi") tetap ada, sesuai cakupan PRD 5:
// "Urutan, kejadian, ulangi N kali".
//
// "ucapan" (bukan properti resmi Blockly, diabaikan dengan aman oleh
// createBlockDefinitionsFromJsonArray) adalah teks yang DIBACAKAN TTS saat
// blok disentuh (PRD 4.1, wajib di tingkat 1) — sengaja terpisah dari
// message0 (yang berisi ikon & simbol %N yang tidak enak dibacakan apa
// adanya).
export const DEFINISI_BLOK_TINGKAT_1 = [
  {
    type: 't1_ketika_bendera',
    message0: '🏁 Mulai',
    nextStatement: null,
    colour: WARNA.kejadian,
    ucapan: 'Mulai',
    tooltip: 'Semua blok di bawah ini dijalankan saat tombol Jalankan ditekan.',
  },
  {
    type: 't1_maju',
    message0: '⬆️ Maju %1 langkah',
    args0: [{ type: 'field_number', name: 'N', value: 10, min: 0 }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.gerak,
    ucapan: 'Maju',
  },
  {
    type: 't1_putar_kanan',
    message0: '↻ Belok kanan',
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.gerak,
    ucapan: 'Belok kanan',
  },
  {
    type: 't1_putar_kiri',
    message0: '↺ Belok kiri',
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.gerak,
    ucapan: 'Belok kiri',
  },
  {
    type: 't1_ulangi',
    message0: '🔁 Ulangi %1 kali',
    message1: '%1',
    args0: [{ type: 'field_number', name: 'N', value: 4, min: 1, precision: 1 }],
    args1: [{ type: 'input_statement', name: 'DO' }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.kontrol,
    ucapan: 'Ulangi',
  },
  {
    type: 't1_tunggu',
    message0: '⏱️ Tunggu %1 detik',
    args0: [{ type: 'field_number', name: 'N', value: 1, min: 0 }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.kontrol,
    ucapan: 'Tunggu',
  },
  {
    type: 't1_pena',
    message0: '✏️ Pena %1',
    args0: [
      {
        type: 'field_dropdown',
        name: 'AKSI',
        options: [
          ['⬇️ turun', 'turun'],
          ['⬆️ naik', 'naik'],
        ],
      },
    ],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.pena,
    ucapan: 'Pena',
  },
  {
    type: 't1_warna_pena',
    message0: '🎨 Warna %1',
    args0: [{ type: 'field_dropdown', name: 'W', options: PALET_WARNA_IKON }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.pena,
    ucapan: 'Warna pena',
  },
  {
    type: 't1_hapus_gambar',
    message0: '🧹 Hapus gambar',
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.pena,
    ucapan: 'Hapus gambar',
  },
  {
    type: 't1_katakan',
    message0: '💬 Kata %1 (%2 detik)',
    args0: [
      { type: 'field_input', name: 'TEKS', text: 'Halo!' },
      { type: 'field_number', name: 'N', value: 2, min: 0 },
    ],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.tampilan,
    ucapan: 'Kata',
  },
  {
    type: 't1_ucapkan',
    message0: '🔊 Ucapkan %1',
    args0: [{ type: 'field_input', name: 'TEKS', text: 'Halo!' }],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.tampilan,
    ucapan: 'Ucapkan',
  },
  {
    type: 't1_bunyi',
    message0: '🎵 Bunyi %1',
    args0: [
      {
        type: 'field_dropdown',
        name: 'NAMA',
        options: [
          ['🔔 kring', 'kring'],
          ['🎵 ting', 'ting'],
          ['💥 pop', 'pop'],
        ],
      },
    ],
    previousStatement: null,
    nextStatement: null,
    colour: WARNA.suara,
    ucapan: 'Bunyi',
  },
]

export function daftarkanBlok() {
  Blockly.common.defineBlocks(Blockly.common.createBlockDefinitionsFromJsonArray(DEFINISI_BLOK))
  Blockly.common.defineBlocks(Blockly.common.createBlockDefinitionsFromJsonArray(DEFINISI_BLOK_TINGKAT_1))
}

// --- Metadata generik dipakai mode kartu (milestone 3.1) untuk merender
//     field & drawer tanpa menulis UI khusus per blok satu-satu. ---

export const BLOK_PER_TIPE = Object.fromEntries(
  [...DEFINISI_BLOK, ...DEFINISI_BLOK_TINGKAT_1].map((d) => [d.type, d]),
)

// Field yang bisa diedit langsung (field_number/field_input/field_dropdown/
// field_variable) — input_value/input_statement bukan "field", itu soket.
export function daftarFieldBlok(type) {
  const def = BLOK_PER_TIPE[type]
  if (!def) return []
  const semua = []
  for (const k of ['args0', 'args1', 'args2', 'args3']) {
    for (const a of def[k] || []) {
      if (a.type && a.type.startsWith('field_')) semua.push(a)
    }
  }
  return semua
}

export function punyaSoket(type, nama) {
  const def = BLOK_PER_TIPE[type]
  if (!def) return false
  for (const k of ['args0', 'args1', 'args2', 'args3']) {
    for (const a of def[k] || []) {
      if (a.name === nama && (a.type === 'input_statement' || a.type === 'input_value')) return true
    }
  }
  return false
}

export const TOOLBOX_TINGKAT_2 = {
  kind: 'categoryToolbox',
  contents: [
    {
      kind: 'category',
      name: 'Kejadian',
      colour: WARNA.kejadian,
      contents: [
        { kind: 'block', type: 'ketika_bendera' },
        { kind: 'block', type: 'ketika_tombol' },
        { kind: 'block', type: 'ketika_disentuh' },
      ],
    },
    {
      kind: 'category',
      name: 'Gerak',
      colour: WARNA.gerak,
      contents: [
        { kind: 'block', type: 'maju' },
        { kind: 'block', type: 'putar_kanan' },
        { kind: 'block', type: 'putar_kiri' },
        { kind: 'block', type: 'arahkan_ke' },
        { kind: 'block', type: 'pergi_ke' },
        { kind: 'block', type: 'pantul_tepi' },
      ],
    },
    {
      kind: 'category',
      name: 'Kontrol',
      colour: WARNA.kontrol,
      contents: [
        { kind: 'block', type: 'ulangi' },
        { kind: 'block', type: 'selamanya' },
        { kind: 'block', type: 'tunggu' },
        { kind: 'block', type: 'jika' },
        { kind: 'block', type: 'jika_lain' },
      ],
    },
    {
      kind: 'category',
      name: 'Kondisi',
      colour: WARNA.kontrol,
      contents: [
        { kind: 'block', type: 'menyentuh_warna' },
        { kind: 'block', type: 'menyentuh_sprite' },
        { kind: 'block', type: 'tombol_ditekan' },
      ],
    },
    {
      kind: 'category',
      name: 'Variabel',
      colour: WARNA.kontrol,
      contents: [
        { kind: 'block', type: 'var_atur' },
        { kind: 'block', type: 'var_ubah' },
        { kind: 'block', type: 'var_tampil' },
      ],
    },
    {
      kind: 'category',
      name: 'Pena',
      colour: WARNA.pena,
      contents: [
        { kind: 'block', type: 'pena' },
        { kind: 'block', type: 'warna_pena' },
        { kind: 'block', type: 'hapus_gambar' },
      ],
    },
    {
      kind: 'category',
      name: 'Tampilan',
      colour: WARNA.tampilan,
      contents: [
        { kind: 'block', type: 'katakan' },
        { kind: 'block', type: 'kostum' },
        { kind: 'block', type: 'atur_tampil' },
        { kind: 'block', type: 'ukuran' },
      ],
    },
    {
      kind: 'category',
      name: 'Suara',
      colour: WARNA.suara,
      contents: [
        { kind: 'block', type: 'bunyi' },
        { kind: 'block', type: 'ucapkan' },
      ],
    },
  ],
}

// Toolbox tingkat 1 (milestone 6.1) — 6 kategori pendek berikon, 12 blok
// total. Sengaja TIDAK memakai nama kategori yang sama dengan tingkat 2
// ("Kejadian", "Gerak", dst) supaya KartuSisip.vue (mode kartu) tidak
// keliru mencampur toolbox tingkat 1 & 2 kalau suatu saat dibaca bersamaan
// — setiap toolbox berdiri sendiri, dipilih App.vue lewat parameter tingkat.
export const TOOLBOX_TINGKAT_1 = {
  kind: 'categoryToolbox',
  contents: [
    {
      kind: 'category',
      name: '🏁 Mulai',
      colour: WARNA.kejadian,
      contents: [{ kind: 'block', type: 't1_ketika_bendera' }],
    },
    {
      kind: 'category',
      name: '⬆️ Gerak',
      colour: WARNA.gerak,
      contents: [
        { kind: 'block', type: 't1_maju' },
        { kind: 'block', type: 't1_putar_kanan' },
        { kind: 'block', type: 't1_putar_kiri' },
      ],
    },
    {
      kind: 'category',
      name: '🔁 Ulangi',
      colour: WARNA.kontrol,
      contents: [
        { kind: 'block', type: 't1_ulangi' },
        { kind: 'block', type: 't1_tunggu' },
      ],
    },
    {
      kind: 'category',
      name: '🎨 Gambar',
      colour: WARNA.pena,
      contents: [
        { kind: 'block', type: 't1_pena' },
        { kind: 'block', type: 't1_warna_pena' },
        { kind: 'block', type: 't1_hapus_gambar' },
      ],
    },
    {
      kind: 'category',
      name: '💬 Bicara',
      colour: WARNA.tampilan,
      contents: [
        { kind: 'block', type: 't1_katakan' },
        { kind: 'block', type: 't1_ucapkan' },
      ],
    },
    {
      kind: 'category',
      name: '🎵 Bunyi',
      colour: WARNA.suara,
      contents: [{ kind: 'block', type: 't1_bunyi' }],
    },
  ],
}
