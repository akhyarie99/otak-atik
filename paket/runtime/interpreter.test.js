import { describe, expect, it } from 'vitest'
import { jalankanUrutan } from './interpreter.js'

function panggungBoneka() {
  const log = []
  return {
    log,
    maju: (n) => log.push(['maju', n]),
    putar: (n) => log.push(['putar', n]),
    pergiKe: (x, y) => log.push(['pergi', x, y]),
    pantulTepi: () => log.push(['pantul']),
    penaTurun: (t) => log.push(['pena', t]),
    warnaPena: (w) => log.push(['warna', w]),
    hapusGambar: () => log.push(['hapus']),
    katakan: (t) => log.push(['katakan', t]),
    ucapkan: () => null,
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
