// 3 misi tingkat 1 (SD kelas 1-3) — milestone 6.1.
// Sengaja jauh lebih sedikit daripada 12 misi tingkat 2: cakupan PRD 5
// untuk tingkat 1 cuma "Urutan, kejadian, ulangi N kali", dan "selesai
// bila" milestone ini hanya menuntut MISI PERTAMA bisa diselesaikan anak
// yang belum lancar membaca — bukan jalur belajar penuh macam tingkat 2.
//
// periksaStruktur/periksaHasil di sini BEKERJA PERSIS SAMA dengan misi
// tingkat 2 (mesin AST yang sama, lihat mesin.js) karena blok tingkat 1
// memetakan ke simpul AST yang identik (lihat paket/blok/ast.js) — bukti
// bahwa satu mesin misi melayani semua tingkat tanpa kode terpisah.
import { normalisasiSudut } from '@otak-atik/runtime'

const dekat = (a, b, toleransi = 5) => Math.abs(a - b) <= toleransi

export const misiT1Maju = {
  id: 'tk1-01-maju',
  judul: '1. Jalan',
  instruksi: 'Tarik blok 🏁 Mulai, lalu tempel blok ⬆️ Maju di bawahnya. Ketuk Jalankan!',
  periksaStruktur(ast, { hitungAst }) {
    if (hitungAst(ast, 'maju') === 0) {
      return { lulus: false, pesan: 'Belum ada blok "Maju" yang tersambung ke blok "Mulai".' }
    }
    return { lulus: true, pesan: 'Ada blok "Maju"!' }
  },
  periksaHasil(panggung) {
    const jarak = Math.hypot(panggung.sprite.x, panggung.sprite.y)
    if (jarak < 5) {
      return { lulus: false, pesan: 'Si Pensil belum bergerak. Coba jalankan lagi.' }
    }
    return { lulus: true, pesan: 'Si Pensil berhasil jalan!' }
  },
}

export const misiT1Belok = {
  id: 'tk1-02-belok',
  judul: '2. Belok',
  instruksi: 'Maju, lalu ↻ Belok kanan, lalu maju lagi — buat si Pensil belok arah.',
  periksaStruktur(ast, { hitungAst }) {
    if (hitungAst(ast, 'maju') < 2) return { lulus: false, pesan: 'Butuh dua blok "Maju": sebelum dan sesudah belok.' }
    if (hitungAst(ast, 'putar') === 0) return { lulus: false, pesan: 'Belum ada blok "Belok kanan" atau "Belok kiri" di antaranya.' }
    return { lulus: true, pesan: 'Ada maju dan belok!' }
  },
  periksaHasil(panggung) {
    if (dekat(panggung.statistik.totalPutar, 0)) {
      return { lulus: false, pesan: 'Arah si Pensil belum berubah sama sekali.' }
    }
    return { lulus: true, pesan: 'Si Pensil berhasil belok!' }
  },
}

// Belok di tingkat 1 selalu tepat seperempat putaran (lihat definisi.js) —
// jadi "ulangi 4 kali: maju + belok kanan" SELALU menutup jadi persegi,
// tanpa anak perlu mengerti "derajat" sama sekali. 4 sudah jadi nilai
// bawaan blok "Ulangi", jadi anak bahkan tidak wajib mengetik angka.
export const misiT1Ulangi = {
  id: 'tk1-03-ulangi',
  judul: '3. Kotak Ajaib',
  instruksi: 'Pakai blok 🔁 Ulangi (4 kali): di dalamnya, tempel Maju lalu Belok kanan.',
  periksaStruktur(ast, { hitungAst, cariAst }) {
    const u = cariAst(ast, 'ulangi')
    if (!u) return { lulus: false, pesan: 'Belum ada blok "Ulangi".' }
    if (u.n !== 4) return { lulus: false, pesan: `Blok "Ulangi" diatur ${u.n} kali — kotak butuh tepat 4 kali.` }
    if (hitungAst(u.isi, 'maju') === 0 || hitungAst(u.isi, 'putar') === 0) {
      return { lulus: false, pesan: 'Di dalam "Ulangi" harus ada blok "Maju" dan "Belok kanan".' }
    }
    return { lulus: true, pesan: 'Ulangi 4 kali dengan maju dan belok, siap jadi kotak!' }
  },
  periksaHasil(panggung) {
    const s = panggung.sprite
    if (!dekat(s.x, 0) || !dekat(s.y, 0)) {
      return { lulus: false, pesan: 'Si Pensil belum kembali ke titik awal — kotaknya belum tertutup.' }
    }
    if (!dekat(normalisasiSudut(Math.abs(panggung.statistik.totalPutar)), 0, 8)) {
      return { lulus: false, pesan: 'Si Pensil belum berputar penuh satu kotak.' }
    }
    return { lulus: true, pesan: 'Kotak ajaib berhasil dibuat!' }
  },
}

export const MISI_TINGKAT_1 = [misiT1Maju, misiT1Belok, misiT1Ulangi]
