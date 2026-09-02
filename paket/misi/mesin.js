// Mesin misi — milestone 2.1.
// Setiap misi diperiksa DUA LAPIS, dan keduanya harus lulus (PRD 6.4):
//   1. Cara mengerjakan — membaca struktur AST, sebelum program dijalankan.
//   2. Hasil di panggung — menjalankan program lalu memeriksa keadaan akhir.
// Lapis 1 sengaja diperiksa lebih dulu dan independen dari lapis 2, supaya
// misi seperti "gambar persegi" bisa menolak 4 blok "maju" berturut-turut
// walau gambarnya sempurna — tujuannya mengajarkan perulangan, bukan cuma
// hasil akhirnya.
import { jalankanUrutan } from '@otak-atik/runtime'

export function hitungAst(list, t) {
  let n = 0
  for (const x of list || []) {
    if (x.t === t) n++
    if (x.isi) n += hitungAst(x.isi, t)
    if (x.isiLain) n += hitungAst(x.isiLain, t)
  }
  return n
}

export function cariAst(list, t) {
  for (const x of list || []) {
    if (x.t === t) return x
    if (x.isi) {
      const r = cariAst(x.isi, t)
      if (r) return r
    }
    if (x.isiLain) {
      const r = cariAst(x.isiLain, t)
      if (r) return r
    }
  }
  return null
}

// Urutan blok "mentah" tanpa perulangan/percabangan sama sekali di level
// teratas — dipakai memeriksa pola "N blok yang sama ditempel berturut-turut
// padahal seharusnya pakai ulangi". Menghitung run terpanjang dari tipe yang
// sama pada SATU level (tidak turun ke isi ulangi/jika).
export function runTerpanjang(list, t) {
  let terpanjang = 0
  let berjalan = 0
  for (const x of list || []) {
    if (x.t === t) {
      berjalan++
      terpanjang = Math.max(terpanjang, berjalan)
    } else {
      berjalan = 0
    }
  }
  return terpanjang
}

// Cukup besar untuk membuktikan perilaku berulang (misalnya bola yang
// harus memantul beberapa kali) tanpa terasa lambat — lihat
// Panggung.tanpaGambar(), yang membuat setiap langkah murni komputasi.
const BATAS_LANGKAH_PERIKSA = 20000

// Menjalankan AST TANPA requestAnimationFrame — khusus pemeriksaan misi
// (proses cepat di belakang layar). Menjalankan program untuk ANAK tetap
// lewat Interpreter di paket/runtime, bukan fungsi ini.
//
// Misi dengan "ulangi selamanya" (bola memantul, kendali tombol) memang
// tidak pernah selesai sendiri — di situ pemeriksaan berhenti begitu
// batasLangkah tercapai, lalu memeriksa keadaan yang sudah dicapai
// sejauh itu. `selesai` memberi tahu pemanggil mana yang terjadi.
export function jalankanUntukPeriksa(ast, panggung, batasLangkah = BATAS_LANGKAH_PERIKSA) {
  const vars = new Map()
  const jalankanSemua = () => {
    const utas = jalankanUrutan(ast, panggung, vars)
    let langkah = 0
    let r = utas.next()
    while (!r.done && langkah < batasLangkah) {
      langkah++
      r = utas.next()
    }
    return { langkah, selesai: !!r.done }
  }

  const { langkah, selesai } =
    typeof panggung.tanpaGambar === 'function'
      ? (() => {
          let hasil
          panggung.tanpaGambar(() => {
            hasil = jalankanSemua()
          })
          return hasil
        })()
      : jalankanSemua()

  return { vars, langkah, selesai }
}

// Bentuk satu Misi:
//   { id, judul, instruksi,
//     periksaStruktur(ast, alat) => {lulus, pesan},
//     periksaHasil(panggung, vars) => {lulus, pesan} }
// `alat` = {hitungAst, cariAst, runTerpanjang} supaya penulis misi tidak
// perlu impor manual.
const ALAT = { hitungAst, cariAst, runTerpanjang }

export function periksaMisi(misi, ast, panggung) {
  const struktur = misi.periksaStruktur(ast, ALAT)
  if (!struktur.lulus) {
    return {
      struktur,
      hasil: { lulus: false, pesan: 'Belum diperiksa — perbaiki cara mengerjakan dulu.' },
      lulusSemua: false,
    }
  }

  panggung.aturUlang()
  const { vars } = jalankanUntukPeriksa(ast, panggung)

  const hasil = misi.periksaHasil(panggung, vars)
  return { struktur, hasil, lulusSemua: struktur.lulus && hasil.lulus }
}
