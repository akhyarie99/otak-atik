// 3 templat game tingkat 2 — milestone 2.2.
// "Kanvas kosong membuat anak macet; kerangka yang sudah jalan membuat
// anak berani mengubah" (PRD 6.4). Disimpan sebagai data serialisasi
// Blockly siap dimuat lewat Blockly.serialization.workspaces.load().
//
// Blok "atur/ubah/tampilkan skor" memakai field_variable — di format
// serialisasi Blockly, field itu MERUJUK ke id variabel lewat
// {id: 'VAR_SKOR'}, bukan namanya langsung, supaya ketiga blok yang
// memakai variabel yang sama benar-benar berbagi satu variabel yang sama
// (bukan diam-diam membuat variabel baru — sudah pernah terjadi saat
// milestone 1.4 diverifikasi, lihat catatan commit).

const VAR_SKOR = { name: 'skor', id: 'VAR_SKOR', type: '' }

// Menyusun rantai blok "next" secara berurutan tanpa nesting manual —
// bentuk JSON blok Blockly yang benar sangat mudah salah kurung kalau
// ditulis tangan bertingkat-tingkat.
function rantai(list) {
  let hasil = null
  for (let i = list.length - 1; i >= 0; i--) {
    const blok = { ...list[i] }
    if (hasil) blok.next = { block: hasil }
    hasil = blok
  }
  return hasil
}

function program(topBlok) {
  return { blocks: { languageVersion: 0, blocks: [{ ...topBlok, x: 40, y: 40 }] } }
}

const badanBolaMemantul = rantai([
  { type: 'pena', fields: { AKSI: 'naik' } },
  { type: 'warna_pena', fields: { W: '#2F6FED' } },
  {
    type: 'selamanya',
    inputs: { DO: { block: rantai([{ type: 'maju', fields: { N: 6 } }, { type: 'pantul_tepi' }]) } },
  },
])

export const templatBolaMemantul = {
  id: 'templat-bola-memantul',
  judul: 'Bola Memantul',
  deskripsi: 'Si Pensil bergerak terus dan memantul dari tepi panggung.',
  blockly: program(rantai([{ type: 'ketika_bendera' }, badanBolaMemantul])),
}

const badanKetukSkor = rantai([
  { type: 'var_atur', fields: { NAMA: { id: 'VAR_SKOR' }, N: 0 } },
  { type: 'var_tampil', fields: { NAMA: { id: 'VAR_SKOR' } } },
  {
    type: 'selamanya',
    inputs: {
      DO: {
        block: {
          type: 'jika',
          inputs: {
            KONDISI: { block: { type: 'tombol_ditekan', fields: { TOMBOL: ' ' } } },
            DO: {
              block: rantai([
                { type: 'var_ubah', fields: { NAMA: { id: 'VAR_SKOR' }, N: 1 } },
                { type: 'var_tampil', fields: { NAMA: { id: 'VAR_SKOR' } } },
                { type: 'tunggu', fields: { N: 0.2 } },
              ]),
            },
          },
        },
      },
    },
  },
])

export const templatSkorKetuk = {
  id: 'templat-skor-ketuk',
  judul: 'Skor Ketuk',
  deskripsi: 'Tekan tombol spasi berulang-ulang untuk menaikkan skor.',
  blockly: { variables: [VAR_SKOR], ...program(rantai([{ type: 'ketika_bendera' }, badanKetukSkor])) },
}

const badanBintang = rantai([
  { type: 'pena', fields: { AKSI: 'turun' } },
  { type: 'warna_pena', fields: { W: '#7A4FD1' } },
  {
    type: 'ulangi',
    fields: { N: 5 },
    inputs: { DO: { block: rantai([{ type: 'maju', fields: { N: 100 } }, { type: 'putar_kanan', fields: { N: 144 } }]) } },
  },
])

export const templatBintangBerputar = {
  id: 'templat-bintang-berputar',
  judul: 'Bintang Berputar',
  deskripsi: 'Menggambar bintang bersudut lima memakai perulangan.',
  blockly: program(rantai([{ type: 'ketika_bendera' }, badanBintang])),
}

export const TEMPLAT_TINGKAT_2 = [templatBolaMemantul, templatSkorKetuk, templatBintangBerputar]
