// Uji parser teks -> AST — milestone 6.3 (tingkat 4).
// Strategi utama: bulat-balik lewat kodeProgram() (AST -> teks, sudah
// teruji sejak milestone 1.4/6.2) lalu teksKeAst() (teks -> AST) HARUS
// menghasilkan AST yang setara secara struktur dengan aslinya (id boleh
// beda — id di teks disintesis parser sendiri, bukan dibaca dari mana
// pun). Ini membuktikan tata bahasa kodeProgram() dan parserTeks.js benar-
// benar pasangan terbalik, bukan cuma "kelihatan mirip".
import { describe, expect, it } from 'vitest'
import { kodeProgram } from './kode.js'
import { GalatParser, teksKeAst } from './parserTeks.js'

// Buang id dari AST supaya bisa dibandingkan structural (id teks disintesis
// beda dari id blok Blockly) — rekursif ke isi/isiLain/daftar juga.
function tanpaId(ast) {
  return (ast || []).map((n) => {
    const { id, ...sisa } = n
    if (sisa.isi) sisa.isi = tanpaId(sisa.isi)
    if (sisa.isiLain) sisa.isiLain = tanpaId(sisa.isiLain)
    if (sisa.daftar) sisa.daftar = sisa.daftar.map((f) => ({ ...f, isi: tanpaId(f.isi) }))
    return sisa
  })
}

function bulatBalik(ast) {
  const teks = kodeProgram(ast)
  const hasil = teksKeAst(teks)
  return { teks, hasil: tanpaId(hasil), asli: tanpaId(ast) }
}

describe('parserTeks — bulat-balik lewat kodeProgram (tingkat 4, milestone 6.3)', () => {
  it('program kosong', () => {
    const { hasil, asli } = bulatBalik([])
    expect(hasil).toEqual(asli)
  })

  it('urutan gerak/pena/tampilan/suara dasar', () => {
    const ast = [
      { t: 'pena', turun: true, id: 'a' },
      { t: 'maju', n: 80, id: 'b' },
      { t: 'putar', n: 90, id: 'c' },
      { t: 'arahkan', n: 180, id: 'd' },
      { t: 'pergi', x: 10, y: -20, id: 'e' },
      { t: 'pantul', id: 'f' },
      { t: 'warna', w: '#2F6FED', id: 'g' },
      { t: 'hapus', id: 'h' },
      { t: 'atur_tampil', tampak: false, id: 'i' },
      { t: 'ukuran', n: 150, id: 'j' },
      { t: 'kostum', nama: 'bulat', id: 'k' },
      { t: 'bunyi', nama: 'pop', id: 'l' },
    ]
    const { hasil, asli } = bulatBalik(ast)
    expect(hasil).toEqual(asli)
  })

  it('katakan, ucapkan, tunggu (statement await)', () => {
    const ast = [
      { t: 'katakan', teks: 'Halo!', n: 2, id: 'a' },
      { t: 'ucapkan', teks: 'Selamat pagi', id: 'b' },
      { t: 'tunggu', n: 1.5, id: 'c' },
    ]
    const { hasil, asli } = bulatBalik(ast)
    expect(hasil).toEqual(asli)
  })

  it('ulangi, selamanya, ulangi_sampai bersarang', () => {
    const ast = [
      { t: 'ulangi', n: 4, isi: [{ t: 'maju', n: 50, id: 'x' }, { t: 'putar', n: 90, id: 'y' }], id: 'a' },
      {
        t: 'selamanya',
        isi: [{ t: 'ulangi_sampai', kondisi: { t: 'menyentuh_sprite' }, isi: [{ t: 'maju', n: 1, id: 'z' }], id: 'w' }],
        id: 'b',
      },
    ]
    const { hasil, asli } = bulatBalik(ast)
    expect(hasil).toEqual(asli)
  })

  it('jika dan jika_lain dengan sensor tingkat 2', () => {
    const ast = [
      { t: 'jika', kondisi: { t: 'menyentuh_warna', w: '#E14B4B' }, isi: [{ t: 'maju', n: 5, id: 'x' }], id: 'a' },
      {
        t: 'jika_lain',
        kondisi: { t: 'tombol_ditekan', tombol: 'ArrowUp' },
        isi: [{ t: 'maju', n: 5, id: 'y' }],
        isiLain: [{ t: 'putar', n: 10, id: 'z' }],
        id: 'b',
      },
    ]
    const { hasil, asli } = bulatBalik(ast)
    expect(hasil).toEqual(asli)
  })

  it('variabel: atur, ubah, tampil', () => {
    const ast = [
      { t: 'var_atur', nama: 'skor', n: 0, id: 'a' },
      { t: 'var_ubah', nama: 'skor', n: 5, id: 'b' },
      { t: 'var_tampil', nama: 'skor', id: 'c' },
    ]
    const { hasil, asli } = bulatBalik(ast)
    expect(hasil).toEqual(asli)
  })

  it('daftar: buat, tambah (angka polos & ekspresi), tampil', () => {
    const ast = [
      { t: 'daftar_buat', nama: 'buah', id: 'a' },
      { t: 'daftar_tambah', nama: 'buah', nilai: 5, id: 'b' },
      { t: 'daftar_tambah', nama: 'buah', nilai: { t: 'op_arit', op: '+', kiri: 2, kanan: 3 }, id: 'c' },
      { t: 'daftar_tampil', nama: 'buah', id: 'd' },
    ]
    const { hasil, asli } = bulatBalik(ast)
    expect(hasil).toEqual(asli)
  })

  it('ekspresi NILAI: var_nilai, posisi x/y, daftar_panjang, acak, op_arit bersarang', () => {
    const ast = [
      {
        t: 'jika',
        kondisi: {
          t: 'op_banding',
          op: 'lebih',
          kiri: { t: 'op_arit', op: '*', kiri: { t: 'posisi_x' }, kanan: 2 },
          kanan: { t: 'daftar_panjang', nama: 'buah' },
        },
        isi: [{ t: 'var_atur', nama: 'skor', n: 1, id: 'x' }],
        id: 'a',
      },
      { t: 'daftar_tambah', nama: 'buah', nilai: { t: 'acak', min: 1, maks: 10 }, id: 'b' },
      { t: 'var_atur', nama: 'y2', n: 0, id: 'c' },
      {
        t: 'jika',
        kondisi: { t: 'op_banding', op: 'sama', kiri: { t: 'var_nilai', nama: 'y2' }, kanan: { t: 'posisi_y' } },
        isi: [],
        id: 'd',
      },
    ]
    const { hasil, asli } = bulatBalik(ast)
    expect(hasil).toEqual(asli)
  })

  it('ekspresi KONDISI: op_logika (dan/atau) dan op_bukan, bersarang', () => {
    const ast = [
      {
        t: 'jika',
        kondisi: {
          t: 'op_logika',
          op: 'dan',
          kiri: { t: 'op_bukan', nilai: { t: 'menyentuh_sprite' } },
          kanan: { t: 'op_logika', op: 'atau', kiri: { t: 'tombol_ditekan', tombol: ' ' }, kanan: { t: 'menyentuh_warna', w: '#12A472' } },
        },
        isi: [{ t: 'maju', n: 1, id: 'x' }],
        id: 'a',
      },
    ]
    const { hasil, asli } = bulatBalik(ast)
    expect(hasil).toEqual(asli)
  })

  it('fungsi buatan sendiri: deklarasi + panggilan, isi berisi perulangan', () => {
    const ast = [
      {
        t: 'deklarasi_fungsi',
        id: 'deklarasi-fungsi',
        daftar: [{ nama: 'kotak', isi: [{ t: 'ulangi', n: 4, isi: [{ t: 'maju', n: 50, id: 'p' }, { t: 'putar', n: 90, id: 'q' }], id: 'r' }] }],
      },
      { t: 'fungsi_panggil', nama: 'kotak', id: 'a' },
      { t: 'fungsi_panggil', nama: 'kotak', id: 'b' },
    ]
    const { hasil, asli } = bulatBalik(ast)
    expect(hasil).toEqual(asli)
  })

  it('program gabungan realistis (semua kategori sekaligus)', () => {
    const ast = [
      { t: 'var_atur', nama: 'skor', n: 0, id: 'a' },
      { t: 'daftar_buat', nama: 'jejak', id: 'b' },
      {
        t: 'selamanya',
        id: 'c',
        isi: [
          { t: 'maju', n: 5, id: 'd' },
          { t: 'daftar_tambah', nama: 'jejak', nilai: { t: 'posisi_x' }, id: 'e' },
          {
            t: 'jika_lain',
            id: 'f',
            kondisi: { t: 'op_banding', op: 'lebih', kiri: { t: 'daftar_panjang', nama: 'jejak' }, kanan: 10 },
            isi: [{ t: 'var_ubah', nama: 'skor', n: 1, id: 'g' }],
            isiLain: [{ t: 'pantul', id: 'h' }],
          },
        ],
      },
      { t: 'var_tampil', nama: 'skor', id: 'i' },
    ]
    const { hasil, asli } = bulatBalik(ast)
    expect(hasil).toEqual(asli)
  })
})

describe('parserTeks — galat teks tidak dikenali (BUKAN eval, aturan tetap #2)', () => {
  it('baris sembarangan (bukan tata bahasa yang dikenali) melempar GalatParser dengan nomor baris', () => {
    expect(() => teksKeAst('async function ketikaBenderaDiklik() {\n  alert("halo");\n}\n')).toThrow(GalatParser)
    try {
      teksKeAst('async function ketikaBenderaDiklik() {\n  alert("halo");\n}\n')
    } catch (e) {
      expect(e.baris).toBe(2)
    }
  })

  it('sensor/metode panggung yang tidak dikenali ditolak, bukan diam-diam dijalankan sebagai sesuatu yang lain', () => {
    expect(() => teksKeAst('async function ketikaBenderaDiklik() {\n  panggung.terbang(10);\n}\n')).toThrow(GalatParser)
  })

  it('tidak pernah memakai eval atau Function (jaminan statis — cek sumbernya sendiri)', async () => {
    const src = await import('node:fs/promises').then((fs) => fs.readFile(new URL('./parserTeks.js', import.meta.url), 'utf-8'))
    expect(src).not.toMatch(/\beval\s*\(/)
    expect(src).not.toMatch(/new\s+Function\s*\(/)
  })
})
