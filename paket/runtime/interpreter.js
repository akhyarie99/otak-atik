// Interpreter AST — milestone 1.3, diperluas di milestone 1.4.
//
// FORMAT AST — aturan tetap #3: daftar simpul ini hanya boleh DITAMBAH di
// masa depan, tidak pernah diubah atau dihapus. Game yang sudah diekspor
// harus tetap bisa diputar bertahun-tahun kemudian.
//
//   maju        {t:'maju',   n, id}              — panggung.maju(n)
//   putar       {t:'putar',  n, id}               — panggung.putar(n); n negatif = kiri
//   arahkan     {t:'arahkan', n, id}               — panggung.arahkanKe(n)
//   pergi       {t:'pergi',  x, y, id}             — panggung.pergiKe(x,y)
//   pantul      {t:'pantul', id}                   — panggung.pantulTepi()
//   pena        {t:'pena',   turun, id}            — panggung.penaTurun(turun)
//   warna       {t:'warna',  w, id}                — panggung.warnaPena(w)
//   hapus       {t:'hapus',  id}                   — panggung.hapusGambar()
//   katakan     {t:'katakan',teks, n, id}          — balon bicara selama n detik
//   ucapkan     {t:'ucapkan',teks, id}             — TTS, tunggu sampai selesai/6 detik
//   tunggu      {t:'tunggu', n, id}                — jeda n detik
//   ulangi      {t:'ulangi', n, isi, id}           — ulangi isi sebanyak n kali
//   selamanya   {t:'selamanya', isi, id}           — ulangi isi selamanya
//   jika        {t:'jika', kondisi, isi, id}       — jalankan isi bila kondisi benar
//   jika_lain   {t:'jika_lain', kondisi, isi, isiLain, id}
//   atur_tampil {t:'atur_tampil', tampak, id}      — panggung.aturTampil(tampak)
//   ukuran      {t:'ukuran', n, id}                — panggung.gantiUkuran(n)
//   kostum      {t:'kostum', nama, id}             — panggung.gantiKostum(nama)
//   bunyi       {t:'bunyi', nama, id}              — panggung.mainkanBunyi(nama)
//   var_atur    {t:'var_atur', nama, n, id}        — set variabel = n
//   var_ubah    {t:'var_ubah', nama, n, id}        — variabel += n
//   var_tampil  {t:'var_tampil', nama, id}         — tampilkan skor variabel
//
//   Simpul KONDISI (nilai boolean, dipakai di dalam jika/jika_lain, tidak
//   pernah berdiri sendiri di urutan statement):
//   menyentuh_warna  {t:'menyentuh_warna', w, id}
//   menyentuh_sprite {t:'menyentuh_sprite', id}
//   tombol_ditekan   {t:'tombol_ditekan', tombol, id}
//
// PENGAMAN LOOP — aturan tetap #1: "ulangi" dan "selamanya" WAJIB yield di
// setiap putaran, sebelum menjalankan isinya, walau isinya kosong. Itu
// satu-satunya hal yang mencegah blok "ulangi selamanya" kosong membekukan
// HP anak. Jangan pernah menghapus yield itu — lihat interpreter.test.js.

export const KECEPATAN = { lambat: 1, normal: 6, kilat: 400 }

function* jeda(ms, id) {
  const sampai = performance.now() + ms
  while (performance.now() < sampai) yield { tipe: 'tunggu', id }
}

// Simpul kondisi dievaluasi langsung (bukan generator) — dianggap satu
// langkah atomik seperti reporter block di Scratch, tidak yield sendiri.
export function evalKondisi(n, panggung) {
  if (!n) return false
  switch (n.t) {
    case 'menyentuh_warna':
      return panggung.menyentuhWarna(n.w)
    case 'menyentuh_sprite':
      return panggung.menyentuhSprite()
    case 'tombol_ditekan':
      return panggung.apakahTombolDitekan(n.tombol)
    default:
      return false
  }
}

export function* jalankanUrutan(list, panggung, vars) {
  for (const n of list || []) yield* jalankanSatu(n, panggung, vars)
}

export function* jalankanSatu(n, panggung, vars = new Map()) {
  switch (n.t) {
    case 'maju':
      panggung.maju(n.n)
      yield { tipe: 'langkah', id: n.id }
      break
    case 'putar':
      panggung.putar(n.n)
      yield { tipe: 'langkah', id: n.id }
      break
    case 'arahkan':
      panggung.arahkanKe(n.n)
      yield { tipe: 'langkah', id: n.id }
      break
    case 'pergi':
      panggung.pergiKe(n.x, n.y)
      yield { tipe: 'langkah', id: n.id }
      break
    case 'pantul':
      panggung.pantulTepi()
      yield { tipe: 'langkah', id: n.id }
      break
    case 'pena':
      panggung.penaTurun(n.turun)
      yield { tipe: 'langkah', id: n.id }
      break
    case 'warna':
      panggung.warnaPena(n.w)
      yield { tipe: 'langkah', id: n.id }
      break
    case 'hapus':
      panggung.hapusGambar()
      yield { tipe: 'langkah', id: n.id }
      break
    case 'tunggu':
      yield* jeda(n.n * 1000, n.id)
      break
    case 'katakan':
      panggung.katakan(n.teks)
      yield* jeda(n.n * 1000, n.id)
      panggung.katakan('')
      break
    case 'ucapkan': {
      const u = panggung.ucapkan(n.teks)
      if (u) {
        let selesai = false
        u.onend = u.onerror = () => {
          selesai = true
        }
        const batas = performance.now() + 6000
        while (!selesai && performance.now() < batas) yield { tipe: 'tunggu', id: n.id }
      } else {
        yield { tipe: 'langkah', id: n.id }
      }
      break
    }
    case 'ulangi':
      for (let i = 0; i < n.n; i++) {
        yield { tipe: 'langkah', id: n.id }
        yield* jalankanUrutan(n.isi, panggung, vars)
      }
      break
    case 'selamanya':
      while (true) {
        yield { tipe: 'langkah', id: n.id }
        yield* jalankanUrutan(n.isi, panggung, vars)
      }
    // eslint-disable-next-line no-fallthrough -- selamanya tidak pernah selesai sendiri
    case 'jika':
      yield { tipe: 'langkah', id: n.id }
      if (evalKondisi(n.kondisi, panggung)) yield* jalankanUrutan(n.isi, panggung, vars)
      break
    case 'jika_lain':
      yield { tipe: 'langkah', id: n.id }
      if (evalKondisi(n.kondisi, panggung)) yield* jalankanUrutan(n.isi, panggung, vars)
      else yield* jalankanUrutan(n.isiLain, panggung, vars)
      break
    case 'atur_tampil':
      panggung.aturTampil(n.tampak)
      yield { tipe: 'langkah', id: n.id }
      break
    case 'ukuran':
      panggung.gantiUkuran(n.n)
      yield { tipe: 'langkah', id: n.id }
      break
    case 'kostum':
      panggung.gantiKostum(n.nama)
      yield { tipe: 'langkah', id: n.id }
      break
    case 'bunyi':
      panggung.mainkanBunyi(n.nama)
      yield { tipe: 'langkah', id: n.id }
      break
    case 'var_atur':
      vars.set(n.nama, n.n)
      yield { tipe: 'langkah', id: n.id }
      break
    case 'var_ubah':
      vars.set(n.nama, (vars.get(n.nama) || 0) + n.n)
      yield { tipe: 'langkah', id: n.id }
      break
    case 'var_tampil':
      panggung.tampilkanSkor(n.nama, vars.get(n.nama) ?? 0)
      yield { tipe: 'langkah', id: n.id }
      break
    default:
      yield { tipe: 'langkah', id: n.id }
  }
}

// Menjalankan satu program AST lengkap terhadap sebuah Panggung, dengan
// jatah langkah per frame lewat requestAnimationFrame — persis seperti
// prototipe rujukan, tapi tanpa Blockly. Editor menyambungkan onLangkah ke
// workspace.highlightBlock(id) untuk menyorot blok aktif; pemutar hasil
// ekspor cukup mengabaikannya.
export class Interpreter {
  constructor(panggung, { onLangkah, onSelesai, onError } = {}) {
    this.panggung = panggung
    this.onLangkah = onLangkah
    this.onSelesai = onSelesai
    this.onError = onError
    this.utas = null
    this.jalan = false
    this.langkahPerFrame = KECEPATAN.normal
    this._frameId = null
    this._detak = this._detak.bind(this)
  }

  aturKecepatan(nama) {
    this.langkahPerFrame = KECEPATAN[nama] ?? KECEPATAN.normal
  }

  mulai(programAst) {
    this.berhenti()
    this.panggung.aturUlang()
    this.panggung.sembunyikanSkor()
    this.vars = new Map()
    this.utas = jalankanUrutan(programAst, this.panggung, this.vars)
    this.jalan = true
    this._frameId = requestAnimationFrame(this._detak)
  }

  berhenti() {
    this.jalan = false
    this.utas = null
    if (this._frameId !== null) {
      cancelAnimationFrame(this._frameId)
      this._frameId = null
    }
    if (this.onLangkah) this.onLangkah(null)
  }

  _detak() {
    if (this.jalan && this.utas) {
      let sisa = this.langkahPerFrame
      while (sisa-- > 0) {
        let r
        try {
          r = this.utas.next()
        } catch (e) {
          this.berhenti()
          if (this.onError) this.onError(e)
          break
        }
        if (r.done) {
          this.berhenti()
          if (this.onSelesai) this.onSelesai()
          break
        }
        if (this.onLangkah) this.onLangkah(r.value.id ?? null)
        if (r.value.tipe === 'tunggu') break
      }
    }
    this.panggung.gambar()
    if (this.jalan) {
      this._frameId = requestAnimationFrame(this._detak)
    }
  }
}
