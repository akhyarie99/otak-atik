import { describe, expect, it } from 'vitest'
import { evalKondisi, jalankanUrutan, nilaiEkspresi } from './interpreter.js'

function panggungBoneka() {
  const log = []
  return {
    log,
    sprite: { x: 30, y: 40 },
    maju: (n) => log.push(['maju', n]),
    putar: (n) => log.push(['putar', n]),
    pergiKe: (x, y) => log.push(['pergi', x, y]),
    pantulTepi: () => log.push(['pantul']),
    penaTurun: (t) => log.push(['pena', t]),
    warnaPena: (w) => log.push(['warna', w]),
    hapusGambar: () => log.push(['hapus']),
    katakan: (t) => log.push(['katakan', t]),
    ucapkan: () => null,
    tampilkanSkor: (nama, nilai) => log.push(['skor', nama, nilai]),
  }
}

function habiskan(utas) {
  let r
  while (!(r = utas.next()).done) {
    /* jalankan sampai selesai */
  }
}

describe('pengaman loop (aturan tetap #1)', () => {
  it('"ulangi selamanya" berisi blok kosong tetap yield setiap putaran, tidak pernah membekukan', () => {
    const ast = [{ t: 'selamanya', isi: [], id: 'loop-kosong' }]
    const utas = jalankanUrutan(ast, panggungBoneka())

    const mulai = Date.now()
    for (let i = 0; i < 50000; i++) {
      const r = utas.next()
      expect(r.done).toBe(false)
      expect(r.value).toEqual({ tipe: 'langkah', id: 'loop-kosong' })
    }
    // 50.000 langkah generator murni harus selesai dalam hitungan milidetik.
    // Kalau yield-nya hilang, baris di atas macet total (timeout uji), bukan
    // sekadar lambat — jadi ambang waktu ini sengaja dibuat longgar.
    expect(Date.now() - mulai).toBeLessThan(2000)
  })

  it('"ulangi N kali" berisi blok kosong tetap yield tepat N kali lalu selesai', () => {
    const ast = [{ t: 'ulangi', n: 5, isi: [], id: 'loop-n' }]
    const utas = jalankanUrutan(ast, panggungBoneka())
    for (let i = 0; i < 5; i++) {
      expect(utas.next().value).toEqual({ tipe: 'langkah', id: 'loop-n' })
    }
    expect(utas.next().done).toBe(true)
  })

  it('perulangan selamanya bersarang tetap yield di setiap putaran ulangi terluar', () => {
    const ast = [{ t: 'selamanya', isi: [{ t: 'selamanya', isi: [], id: 'dalam' }], id: 'luar' }]
    const utas = jalankanUrutan(ast, panggungBoneka())
    expect(utas.next().value).toEqual({ tipe: 'langkah', id: 'luar' })
    for (let i = 0; i < 1000; i++) {
      expect(utas.next().value).toEqual({ tipe: 'langkah', id: 'dalam' })
    }
  })
})

describe('eksekusi Game API sesuai AST', () => {
  it('menjalankan urutan sederhana memanggil fungsi panggung yang sesuai', () => {
    const p = panggungBoneka()
    const ast = [
      { t: 'pena', turun: true, id: '1' },
      { t: 'maju', n: 80, id: '2' },
      { t: 'putar', n: 90, id: '3' },
    ]
    habiskan(jalankanUrutan(ast, p))
    expect(p.log).toEqual([
      ['pena', true],
      ['maju', 80],
      ['putar', 90],
    ])
  })

  it('ulangi menjalankan isi tepat N kali', () => {
    const p = panggungBoneka()
    const ast = [{ t: 'ulangi', n: 4, isi: [{ t: 'maju', n: 10, id: 'm' }], id: 'u' }]
    habiskan(jalankanUrutan(ast, p))
    expect(p.log.filter((x) => x[0] === 'maju')).toHaveLength(4)
  })

  it('tunggu hanya menghasilkan tick "tunggu" sampai waktunya lewat', () => {
    const ast = [{ t: 'tunggu', n: 0.01, id: 'w' }] // 10ms
    const utas = jalankanUrutan(ast, panggungBoneka())
    let r = utas.next()
    expect(r.value.tipe).toBe('tunggu')
    while (!r.done) {
      expect(r.value.tipe).toBe('tunggu')
      r = utas.next()
    }
  })

  it('katakan mengatur lalu mengosongkan ucapan setelah jeda', () => {
    const p = panggungBoneka()
    const ast = [{ t: 'katakan', teks: 'Halo!', n: 0.01, id: 'k' }]
    habiskan(jalankanUrutan(ast, p))
    expect(p.log[0]).toEqual(['katakan', 'Halo!'])
    expect(p.log[p.log.length - 1]).toEqual(['katakan', ''])
  })
})

// --- milestone 6.2 (tingkat 3): ekspresi, daftar, fungsi buatan sendiri ---

describe('nilaiEkspresi (simpul NILAI tingkat 3)', () => {
  it('angka polos dikembalikan apa adanya (kompatibel mundur dengan n.n dkk lama)', () => {
    expect(nilaiEkspresi(42, panggungBoneka(), new Map())).toBe(42)
  })

  it('membaca variabel, posisi sprite, dan panjang daftar', () => {
    const p = panggungBoneka()
    const vars = new Map([['skor', 7], ['daftar', [1, 2, 3]]])
    expect(nilaiEkspresi({ t: 'var_nilai', nama: 'skor' }, p, vars)).toBe(7)
    expect(nilaiEkspresi({ t: 'var_nilai', nama: 'tidak-ada' }, p, vars)).toBe(0)
    expect(nilaiEkspresi({ t: 'posisi_x' }, p, vars)).toBe(30)
    expect(nilaiEkspresi({ t: 'posisi_y' }, p, vars)).toBe(40)
    expect(nilaiEkspresi({ t: 'daftar_panjang', nama: 'daftar' }, p, vars)).toBe(3)
  })

  it('op_arit menghitung +, -, *, / termasuk bersarang', () => {
    const p = panggungBoneka()
    const vars = new Map()
    const tambah = { t: 'op_arit', op: '+', kiri: 2, kanan: 3 }
    expect(nilaiEkspresi(tambah, p, vars)).toBe(5)
    const bersarang = { t: 'op_arit', op: '*', kiri: tambah, kanan: 10 }
    expect(nilaiEkspresi(bersarang, p, vars)).toBe(50)
    expect(nilaiEkspresi({ t: 'op_arit', op: '/', kiri: 10, kanan: 0 }, p, vars)).toBe(0) // bagi nol aman, bukan Infinity/NaN
  })

  it('acak selalu di dalam rentang min..maks (inklusif)', () => {
    const p = panggungBoneka()
    for (let i = 0; i < 200; i++) {
      const n = nilaiEkspresi({ t: 'acak', min: 1, maks: 3 }, p, new Map())
      expect(n).toBeGreaterThanOrEqual(1)
      expect(n).toBeLessThanOrEqual(3)
      expect(Number.isInteger(n)).toBe(true)
    }
  })
})

describe('evalKondisi diperluas tingkat 3 (op_banding, op_logika, op_bukan)', () => {
  it('op_banding: sama, kurang, lebih', () => {
    const p = panggungBoneka()
    const vars = new Map()
    expect(evalKondisi({ t: 'op_banding', op: 'sama', kiri: 5, kanan: 5 }, p, vars)).toBe(true)
    expect(evalKondisi({ t: 'op_banding', op: 'kurang', kiri: 2, kanan: 5 }, p, vars)).toBe(true)
    expect(evalKondisi({ t: 'op_banding', op: 'lebih', kiri: 2, kanan: 5 }, p, vars)).toBe(false)
  })

  it('op_logika: dan/atau, op_bukan: negasi', () => {
    const p = panggungBoneka()
    const benar = { t: 'op_banding', op: 'sama', kiri: 1, kanan: 1 }
    const salah = { t: 'op_banding', op: 'sama', kiri: 1, kanan: 2 }
    expect(evalKondisi({ t: 'op_logika', op: 'dan', kiri: benar, kanan: salah }, p, new Map())).toBe(false)
    expect(evalKondisi({ t: 'op_logika', op: 'atau', kiri: benar, kanan: salah }, p, new Map())).toBe(true)
    expect(evalKondisi({ t: 'op_bukan', nilai: salah }, p, new Map())).toBe(true)
  })

  it('op_banding bisa memakai posisi sprite sebagai operan ("jika posisi x lebih dari 100")', () => {
    const p = panggungBoneka() // sprite.x = 30
    const kondisi = { t: 'op_banding', op: 'lebih', kiri: { t: 'posisi_x' }, kanan: 100 }
    expect(evalKondisi(kondisi, p, new Map())).toBe(false)
    p.sprite.x = 150
    expect(evalKondisi(kondisi, p, new Map())).toBe(true)
  })
})

describe('daftar (list) — milestone 6.2', () => {
  it('daftar_buat lalu daftar_tambah membangun larik di vars', () => {
    const p = panggungBoneka()
    const ast = [
      { t: 'daftar_buat', nama: 'buah', id: 'a' },
      { t: 'daftar_tambah', nama: 'buah', nilai: 5, id: 'b' },
      { t: 'daftar_tambah', nama: 'buah', nilai: { t: 'op_arit', op: '+', kiri: 2, kanan: 3 }, id: 'c' },
    ]
    const vars = new Map()
    habiskan(jalankanUrutan(ast, p, vars))
    expect(vars.get('buah')).toEqual([5, 5])
  })

  it('daftar_tampil menampilkan isi daftar lewat tampilkanSkor', () => {
    const p = panggungBoneka()
    const vars = new Map([['buah', [1, 2, 3]]])
    habiskan(jalankanUrutan([{ t: 'daftar_tampil', nama: 'buah', id: 'a' }], p, vars))
    expect(p.log).toEqual([['skor', 'buah', '1, 2, 3']])
  })
})

describe('fungsi buatan sendiri — milestone 6.2', () => {
  it('deklarasi_fungsi mendaftarkan fungsi, fungsi_panggil menjalankan isinya', () => {
    const p = panggungBoneka()
    const ast = [
      {
        t: 'deklarasi_fungsi',
        id: 'deklarasi',
        daftar: [{ nama: 'majuDuaKali', isi: [{ t: 'maju', n: 10, id: 'm1' }, { t: 'maju', n: 20, id: 'm2' }] }],
      },
      { t: 'fungsi_panggil', nama: 'majuDuaKali', id: 'panggil' },
    ]
    habiskan(jalankanUrutan(ast, p))
    expect(p.log).toEqual([['maju', 10], ['maju', 20]])
  })

  it('memanggil fungsi yang belum terdaftar diam-diam diabaikan (bukan galat)', () => {
    const p = panggungBoneka()
    const ast = [{ t: 'fungsi_panggil', nama: 'tidak-ada', id: 'x' }]
    expect(() => habiskan(jalankanUrutan(ast, p))).not.toThrow()
    expect(p.log).toEqual([])
  })

  it('fungsi bisa dipanggil berkali-kali dan isinya boleh berisi perulangan', () => {
    const p = panggungBoneka()
    const ast = [
      {
        t: 'deklarasi_fungsi',
        id: 'deklarasi',
        daftar: [{ nama: 'kotak', isi: [{ t: 'ulangi', n: 4, id: 'u', isi: [{ t: 'maju', n: 50, id: 'm' }, { t: 'putar', n: 90, id: 'p' }] }] }],
      },
      { t: 'fungsi_panggil', nama: 'kotak', id: 'panggil1' },
      { t: 'fungsi_panggil', nama: 'kotak', id: 'panggil2' },
    ]
    habiskan(jalankanUrutan(ast, p))
    expect(p.log.filter((x) => x[0] === 'maju')).toHaveLength(8) // 4 sisi x 2 panggilan
  })
})

describe('ulangi_sampai — pengaman loop (aturan tetap #1)', () => {
  it('kondisi yang tidak pernah benar tetap yield setiap putaran, tidak pernah membekukan', () => {
    const p = panggungBoneka()
    const selaluSalah = { t: 'op_banding', op: 'sama', kiri: 1, kanan: 2 }
    const ast = [{ t: 'ulangi_sampai', kondisi: selaluSalah, isi: [], id: 'loop' }]
    const utas = jalankanUrutan(ast, p)

    const mulai = Date.now()
    for (let i = 0; i < 50000; i++) {
      const r = utas.next()
      expect(r.done).toBe(false)
      expect(r.value).toEqual({ tipe: 'langkah', id: 'loop' })
    }
    expect(Date.now() - mulai).toBeLessThan(2000)
  })

  it('berhenti tepat saat kondisi jadi benar', () => {
    const p = panggungBoneka()
    const vars = new Map([['n', 0]])
    const ast = [
      {
        t: 'ulangi_sampai',
        id: 'loop',
        kondisi: { t: 'op_banding', op: 'sama', kiri: { t: 'var_nilai', nama: 'n' }, kanan: 3 },
        isi: [{ t: 'var_ubah', nama: 'n', n: 1, id: 'tambah' }],
      },
    ]
    habiskan(jalankanUrutan(ast, p, vars))
    expect(vars.get('n')).toBe(3)
  })
})
