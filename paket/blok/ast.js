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

function namaVariabel(b) {
  // field_variable menyimpan objek model variabel Blockly; ambil namanya
  // sebagai kunci di peta variabel interpreter (lihat paket/runtime).
  const v = b.getField('NAMA')?.getVariable?.()
  return v ? v.name : b.getFieldValue('NAMA')
}

// Blok NILAI (angka) tingkat 3 (milestone 6.2) — dicolokkan ke soket
// bertipe Number (operan operator, MIN/MAKS acak, dst). Blok kosong/belum
// disambung menghasilkan angka polos 0, konsisten dengan angka() di atas.
export function astNilai(b) {
  if (!b) return 0
  switch (b.type) {
    case 't3_angka':
      return angka(b, 'N')
    case 't3_op_arit':
      return { t: 'op_arit', op: b.getFieldValue('OP'), kiri: astNilai(b.getInputTargetBlock('A')), kanan: astNilai(b.getInputTargetBlock('B')) }
    case 't3_acak':
      return { t: 'acak', min: astNilai(b.getInputTargetBlock('MIN')), maks: astNilai(b.getInputTargetBlock('MAKS')) }
    case 't3_var_nilai':
      return { t: 'var_nilai', nama: namaVariabel(b) }
    case 't3_posisi_x':
      return { t: 'posisi_x' }
    case 't3_posisi_y':
      return { t: 'posisi_y' }
    case 't3_daftar_panjang':
      return { t: 'daftar_panjang', nama: namaVariabel(b) }
    default:
      return 0
  }
}

// Blok Kondisi (nilai boolean) yang dicolokkan ke soket "jika"/"jika_lain"/
// "ulangi sampai". Sejak milestone 6.2 juga menangani operator perbandingan
// & logika tingkat 3 — operannya lewat astNilai (angka) / astKondisi
// (boolean, rekursif) di atas.
export function astKondisi(b) {
  if (!b) return null
  switch (b.type) {
    case 'menyentuh_warna':
      return { t: 'menyentuh_warna', w: b.getFieldValue('W'), id: b.id }
    case 'menyentuh_sprite':
      return { t: 'menyentuh_sprite', id: b.id }
    case 'tombol_ditekan':
      return { t: 'tombol_ditekan', tombol: b.getFieldValue('TOMBOL'), id: b.id }
    case 't3_op_banding':
      return {
        t: 'op_banding',
        op: b.getFieldValue('OP'),
        kiri: astNilai(b.getInputTargetBlock('A')),
        kanan: astNilai(b.getInputTargetBlock('B')),
        id: b.id,
      }
    case 't3_op_logika':
      return {
        t: 'op_logika',
        op: b.getFieldValue('OP'),
        kiri: astKondisi(b.getInputTargetBlock('A')),
        kanan: astKondisi(b.getInputTargetBlock('B')),
        id: b.id,
      }
    case 't3_op_bukan':
      return { t: 'op_bukan', nilai: astKondisi(b.getInputTargetBlock('A')), id: b.id }
    default:
      return null
  }
}

// Tipe blok tingkat 1 ('t1_...', milestone 6.1) ditambahkan ke case yang
// SAMA dengan padanan tingkat 2-nya di bawah — keduanya menghasilkan
// simpul AST yang identik (aturan tetap #3: format AST hanya boleh
// ditambah, tidak diubah). Dua kasus TIDAK punya padanan field yang sama
// persis karena field derajatnya sengaja dihilangkan di tingkat 1 (lihat
// definisi.js) — belok kanan/kiri di tingkat 1 selalu seperempat putaran.
export function astSatu(b) {
  switch (b.type) {
    case 'maju':
    case 't1_maju':
      return { t: 'maju', n: angka(b, 'N'), id: b.id }
    case 'putar_kanan':
      return { t: 'putar', n: angka(b, 'N'), id: b.id }
    case 'putar_kiri':
      return { t: 'putar', n: -angka(b, 'N'), id: b.id }
    case 't1_putar_kanan':
      return { t: 'putar', n: 90, id: b.id }
    case 't1_putar_kiri':
      return { t: 'putar', n: -90, id: b.id }
    case 'arahkan_ke':
      return { t: 'arahkan', n: angka(b, 'N'), id: b.id }
    case 'pergi_ke':
      return { t: 'pergi', x: angka(b, 'X'), y: angka(b, 'Y'), id: b.id }
    case 'pantul_tepi':
      return { t: 'pantul', id: b.id }
    case 'pena':
    case 't1_pena':
      return { t: 'pena', turun: b.getFieldValue('AKSI') === 'turun', id: b.id }
    case 'warna_pena':
    case 't1_warna_pena':
      return { t: 'warna', w: b.getFieldValue('W'), id: b.id }
    case 'hapus_gambar':
    case 't1_hapus_gambar':
      return { t: 'hapus', id: b.id }
    case 'katakan':
    case 't1_katakan':
      return { t: 'katakan', teks: b.getFieldValue('TEKS'), n: angka(b, 'N'), id: b.id }
    case 'ucapkan':
    case 't1_ucapkan':
      return { t: 'ucapkan', teks: b.getFieldValue('TEKS'), id: b.id }
    case 'tunggu':
    case 't1_tunggu':
      return { t: 'tunggu', n: angka(b, 'N'), id: b.id }
    case 'ulangi':
    case 't1_ulangi':
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
    case 't3_ulangi_sampai':
      return {
        t: 'ulangi_sampai',
        kondisi: astKondisi(b.getInputTargetBlock('KONDISI')),
        isi: astUrutan(b.getInputTargetBlock('DO')),
        id: b.id,
      }
    case 't3_daftar_buat':
      return { t: 'daftar_buat', nama: namaVariabel(b), id: b.id }
    case 't3_daftar_tambah':
      return { t: 'daftar_tambah', nama: namaVariabel(b), nilai: astNilai(b.getInputTargetBlock('NILAI')), id: b.id }
    case 't3_daftar_tampil':
      return { t: 'daftar_tampil', nama: namaVariabel(b), id: b.id }
    case 't3_fungsi_panggil':
      return { t: 'fungsi_panggil', nama: b.getFieldValue('NAMA'), id: b.id }
    case 'atur_tampil':
      return { t: 'atur_tampil', tampak: b.getFieldValue('STATUS') === 'tampil', id: b.id }
    case 'ukuran':
      return { t: 'ukuran', n: angka(b, 'N'), id: b.id }
    case 'kostum':
      return { t: 'kostum', nama: b.getFieldValue('NAMA'), id: b.id }
    case 'bunyi':
    case 't1_bunyi':
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

const TIPE_KEJADIAN = new Set(['ketika_bendera', 'ketika_tombol', 'ketika_disentuh', 't1_ketika_bendera'])
export const TIPE_BENDERA = new Set(['ketika_bendera', 't1_ketika_bendera'])

// Blok "fungsi ..." (t3_fungsi_buat, milestone 6.2) adalah skrip TOP-LEVEL
// tersendiri (seperti ketika_tombol/ketika_disentuh) — bukan bagian rantai
// bendera. Dikumpulkan terpisah di sini, lalu programAst() menaruhnya
// sebagai simpul 'deklarasi_fungsi' PALING DEPAN (lihat interpreter.js)
// supaya sudah terdaftar sebelum baris manapun sempat memanggilnya.
export function programFungsi(workspace) {
  return workspace
    .getTopBlocks(true)
    .filter((b) => b.type === 't3_fungsi_buat')
    .map((b) => ({ nama: b.getFieldValue('NAMA'), isi: astUrutan(b.getNextBlock()) }))
}

// Program utama: hanya skrip di bawah "ketika bendera diklik" (atau
// padanan tingkat 1-nya, "🏁 Mulai") yang dijalankan Interpreter sekarang.
// Skrip "ketika tombol"/"ketika disentuh" terstruktur AST-nya juga (lihat
// programSkripLain), tapi penjadwalan paralelnya adalah pekerjaan lanjutan
// di luar milestone 1.4.
export function programAst(workspace) {
  const bendera = workspace.getTopBlocks(true).find((b) => TIPE_BENDERA.has(b.type))
  const utama = bendera ? astUrutan(bendera.getNextBlock()) : []
  const daftarFungsi = programFungsi(workspace)
  if (daftarFungsi.length === 0) return utama
  return [{ t: 'deklarasi_fungsi', daftar: daftarFungsi, id: 'deklarasi-fungsi' }, ...utama]
}

export function programSkripLain(workspace) {
  return workspace
    .getTopBlocks(true)
    .filter((b) => TIPE_KEJADIAN.has(b.type) && !TIPE_BENDERA.has(b.type))
    .map((b) => ({ pemicu: b.type, tombol: b.getFieldValue?.('TOMBOL'), isi: astUrutan(b.getNextBlock()) }))
}
