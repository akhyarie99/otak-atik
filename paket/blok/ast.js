// Konverter blok Blockly -> AST — milestone 1.4.
// Menerima objek blok Blockly langsung (duck-typed: b.type, b.id,
// b.getFieldValue, b.getNextBlock, b.getInputTargetBlock, b.isEnabled).
// Tidak mengimpor 'blockly/core' di sini — cukup bergantung pada bentuk
// objek blok yang sudah dibuat editor lewat Blockly.inject.
//
// Bentuk simpul AST yang dihasilkan didokumentasikan di
// paket/runtime/interpreter.js (kontrak tetap, aturan #3).

function angka(b, nm) {
  const v = parseFloat(b.getFieldValue(nm))
  return Number.isNaN(v) ? 0 : v
}

// Blok Kondisi (nilai boolean) yang dicolokkan ke soket "jika"/"jika_lain".
export function astKondisi(b) {
  if (!b) return null
  switch (b.type) {
    case 'menyentuh_warna':
      return { t: 'menyentuh_warna', w: b.getFieldValue('W'), id: b.id }
    case 'menyentuh_sprite':
      return { t: 'menyentuh_sprite', id: b.id }
    case 'tombol_ditekan':
      return { t: 'tombol_ditekan', tombol: b.getFieldValue('TOMBOL'), id: b.id }
    default:
      return null
  }
}

function namaVariabel(b) {
  // field_variable menyimpan objek model variabel Blockly; ambil namanya
  // sebagai kunci di peta variabel interpreter (lihat paket/runtime).
  const v = b.getField('NAMA')?.getVariable?.()
  return v ? v.name : b.getFieldValue('NAMA')
}

export function astSatu(b) {
  switch (b.type) {
    case 'maju':
      return { t: 'maju', n: angka(b, 'N'), id: b.id }
    case 'putar_kanan':
      return { t: 'putar', n: angka(b, 'N'), id: b.id }
    case 'putar_kiri':
      return { t: 'putar', n: -angka(b, 'N'), id: b.id }
    case 'arahkan_ke':
      return { t: 'arahkan', n: angka(b, 'N'), id: b.id }
    case 'pergi_ke':
      return { t: 'pergi', x: angka(b, 'X'), y: angka(b, 'Y'), id: b.id }
    case 'pantul_tepi':
      return { t: 'pantul', id: b.id }
    case 'pena':
      return { t: 'pena', turun: b.getFieldValue('AKSI') === 'turun', id: b.id }
    case 'warna_pena':
      return { t: 'warna', w: b.getFieldValue('W'), id: b.id }
    case 'hapus_gambar':
      return { t: 'hapus', id: b.id }
    case 'katakan':
      return { t: 'katakan', teks: b.getFieldValue('TEKS'), n: angka(b, 'N'), id: b.id }
    case 'ucapkan':
      return { t: 'ucapkan', teks: b.getFieldValue('TEKS'), id: b.id }
    case 'tunggu':
      return { t: 'tunggu', n: angka(b, 'N'), id: b.id }
    case 'ulangi':
      return { t: 'ulangi', n: Math.floor(angka(b, 'N')), isi: astUrutan(b.getInputTargetBlock('DO')), id: b.id }
    case 'selamanya':
      return { t: 'selamanya', isi: astUrutan(b.getInputTargetBlock('DO')), id: b.id }
    case 'jika':
      return {
        t: 'jika',
        kondisi: astKondisi(b.getInputTargetBlock('KONDISI')),
        isi: astUrutan(b.getInputTargetBlock('DO')),
        id: b.id,
      }
    case 'jika_lain':
      return {
        t: 'jika_lain',
        kondisi: astKondisi(b.getInputTargetBlock('KONDISI')),
        isi: astUrutan(b.getInputTargetBlock('DO')),
        isiLain: astUrutan(b.getInputTargetBlock('LAIN')),
        id: b.id,
      }
    case 'atur_tampil':
      return { t: 'atur_tampil', tampak: b.getFieldValue('STATUS') === 'tampil', id: b.id }
    case 'ukuran':
      return { t: 'ukuran', n: angka(b, 'N'), id: b.id }
    case 'kostum':
      return { t: 'kostum', nama: b.getFieldValue('NAMA'), id: b.id }
    case 'bunyi':
      return { t: 'bunyi', nama: b.getFieldValue('NAMA'), id: b.id }
    case 'var_atur':
      return { t: 'var_atur', nama: namaVariabel(b), n: angka(b, 'N'), id: b.id }
    case 'var_ubah':
      return { t: 'var_ubah', nama: namaVariabel(b), n: angka(b, 'N'), id: b.id }
    case 'var_tampil':
      return { t: 'var_tampil', nama: namaVariabel(b), id: b.id }
    default:
      return null
  }
}

export function astUrutan(b) {
  const out = []
  while (b) {
    if (!b.isEnabled || b.isEnabled()) {
      const n = astSatu(b)
      if (n) out.push(n)
    }
    b = b.getNextBlock()
  }
  return out
}

const TIPE_KEJADIAN = new Set(['ketika_bendera', 'ketika_tombol', 'ketika_disentuh'])

// Program utama: hanya skrip di bawah "ketika bendera diklik" yang
// dijalankan Interpreter sekarang. Skrip "ketika tombol"/"ketika disentuh"
// terstruktur AST-nya juga (lihat programSkripLain), tapi penjadwalan
// paralelnya adalah pekerjaan lanjutan di luar milestone 1.4.
export function programAst(workspace) {
  const bendera = workspace.getTopBlocks(true).find((b) => b.type === 'ketika_bendera')
  return bendera ? astUrutan(bendera.getNextBlock()) : []
}

export function programSkripLain(workspace) {
  return workspace
    .getTopBlocks(true)
    .filter((b) => TIPE_KEJADIAN.has(b.type) && b.type !== 'ketika_bendera')
    .map((b) => ({ pemicu: b.type, tombol: b.getFieldValue?.('TOMBOL'), isi: astUrutan(b.getNextBlock()) }))
}
