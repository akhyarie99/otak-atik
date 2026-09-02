// 12 misi tingkat 2 (SD kelas 4-6) — milestone 2.2.
// Berjenjang: 1-3 mengenalkan satu konsep per misi (gerak, putar, pena),
// 4-6 memperkenalkan "ulangi", 7-9 variabel/kejadian/kendali, 10-12
// menggabungkan semuanya. Misi 1-6 harus bisa diselesaikan tanpa
// penjelasan lisan guru (target uji anak, lihat rencana-build.md fase 2).
import { misiPersegi } from './misi-contoh.js'

const dekat = (a, b, toleransi = 3) => Math.abs(a - b) <= toleransi

export const misiMajuSederhana = {
  id: 'tk2-01-maju',
  judul: '1. Jalan-Jalan',
  instruksi: 'Buat si Pensil maju 100 langkah. Tarik blok "ketika bendera diklik", lalu tempel blok "maju" di bawahnya.',
  periksaStruktur(ast, { hitungAst }) {
    if (hitungAst(ast, 'maju') === 0) {
      return { lulus: false, pesan: 'Belum ada blok "maju" yang tersambung ke blok bendera.' }
    }
    return { lulus: true, pesan: 'Ada blok "maju"!' }
  },
  periksaHasil(panggung) {
    const jarak = Math.hypot(panggung.sprite.x, panggung.sprite.y)
    if (!dekat(jarak, 100, 15)) {
      return { lulus: false, pesan: 'Si Pensil belum berjalan 100 langkah dari titik awal.' }
    }
    return { lulus: true, pesan: 'Si Pensil berhasil jalan-jalan!' }
  },
}

export const misiBerputar = {
  id: 'tk2-02-putar',
  judul: '2. Belok Kanan',
  instruksi: 'Maju 80 langkah, putar kanan 90 derajat, lalu maju 80 langkah lagi — buat si Pensil belok.',
  periksaStruktur(ast, { hitungAst }) {
    if (hitungAst(ast, 'maju') < 2) return { lulus: false, pesan: 'Butuh dua blok "maju": sebelum dan sesudah belok.' }
    if (hitungAst(ast, 'putar') === 0) return { lulus: false, pesan: 'Belum ada blok "putar kanan" atau "putar kiri" di antaranya.' }
    return { lulus: true, pesan: 'Ada maju dan putar!' }
  },
  periksaHasil(panggung) {
    if (dekat(panggung.statistik.totalPutar, 0)) {
      return { lulus: false, pesan: 'Arah si Pensil belum berubah sama sekali.' }
    }
    if (panggung.statistik.jarakTotal < 140) {
      return { lulus: false, pesan: 'Si Pensil belum berjalan cukup jauh (dua kali maju 80 langkah).' }
    }
    return { lulus: true, pesan: 'Si Pensil berhasil belok!' }
  },
}

export const misiGarisPena = {
  id: 'tk2-03-pena',
  judul: '3. Coret Garis',
  instruksi: 'Turunkan pena, lalu maju 150 langkah supaya si Pensil meninggalkan garis di panggung.',
  periksaStruktur(ast, { hitungAst, cariAst }) {
    if (hitungAst(ast, 'pena') === 0) return { lulus: false, pesan: 'Belum ada blok "pena" untuk menurunkannya.' }
    const p = cariAst(ast, 'pena')
    if (!p.turun) return { lulus: false, pesan: 'Blok "pena" masih diatur ke "naik" — ganti ke "turun".' }
    if (hitungAst(ast, 'maju') === 0) return { lulus: false, pesan: 'Belum ada blok "maju" setelah pena diturunkan.' }
    return { lulus: true, pesan: 'Pena turun dan ada blok maju!' }
  },
  periksaHasil(panggung) {
    if (panggung.statistik.jarakTotal < 100) {
      return { lulus: false, pesan: 'Si Pensil belum berjalan cukup jauh untuk meninggalkan garis.' }
    }
    return { lulus: true, pesan: 'Garis berhasil dibuat!' }
  },
}

// Misi 4 (persegi, memakai "ulangi") sudah ditulis & diuji di milestone 2.1.
export const misiPerseguiUlangi = misiPersegi

export const misiSegitiga = {
  id: 'tk2-05-segitiga',
  judul: '5. Gambar Segitiga',
  instruksi: 'Pakai "ulangi 3 kali": di dalamnya, maju 100 langkah lalu putar kanan 120 derajat.',
  periksaStruktur(ast, { hitungAst, cariAst }) {
    const u = cariAst(ast, 'ulangi')
    if (!u) return { lulus: false, pesan: 'Belum ada blok "ulangi". Segitiga butuh perulangan 3 kali.' }
    if (u.n !== 3) return { lulus: false, pesan: `Blok "ulangi" diatur ${u.n} kali — segitiga butuh tepat 3 kali.` }
    if (hitungAst(u.isi, 'maju') === 0 || hitungAst(u.isi, 'putar') === 0) {
      return { lulus: false, pesan: 'Di dalam "ulangi" harus ada blok "maju" dan "putar".' }
    }
    return { lulus: true, pesan: 'Ulangi 3 kali dengan maju dan putar, tepat untuk segitiga!' }
  },
  periksaHasil(panggung) {
    const s = panggung.sprite
    if (!dekat(s.x, 0, 5) || !dekat(s.y, 0, 5)) {
      return { lulus: false, pesan: 'Si Pensil belum kembali ke titik awal — segitiga belum tertutup.' }
    }
    if (!dekat(Math.abs(panggung.statistik.totalPutar), 360, 8)) {
      return { lulus: false, pesan: 'Total putarannya belum 360° (3 kali putar 120°).' }
    }
    return { lulus: true, pesan: 'Segitiga berhasil digambar!' }
  },
}

export const misiPantulSelamanya = {
  id: 'tk2-06-pantul',
  judul: '6. Bola Memantul',
  instruksi: 'Pakai "ulangi selamanya": di dalamnya, maju 6 langkah lalu "jika di tepi, pantul".',
  periksaStruktur(ast, { hitungAst, cariAst }) {
    const s = cariAst(ast, 'selamanya')
    if (!s) return { lulus: false, pesan: 'Belum ada blok "ulangi selamanya".' }
    if (hitungAst(s.isi, 'maju') === 0) return { lulus: false, pesan: 'Di dalam "ulangi selamanya" harus ada blok "maju".' }
    if (hitungAst(s.isi, 'pantul') === 0) {
      return { lulus: false, pesan: 'Di dalam "ulangi selamanya" harus ada blok "jika di tepi, pantul".' }
    }
    return { lulus: true, pesan: 'Ulangi selamanya dengan maju dan pantul, siap memantul!' }
  },
  periksaHasil(panggung) {
    if (panggung.statistik.pantul < 1) {
      return { lulus: false, pesan: 'Si Pensil belum pernah memantul dari tepi panggung.' }
    }
    return { lulus: true, pesan: 'Bolanya berhasil memantul!' }
  },
}

export const misiSapa = {
  id: 'tk2-07-katakan',
  judul: '7. Sapa Teman',
  instruksi: 'Pakai blok "katakan" untuk menampilkan tulisan "Halo!" selama 2 detik.',
  periksaStruktur(ast, { hitungAst }) {
    if (hitungAst(ast, 'katakan') === 0) return { lulus: false, pesan: 'Belum ada blok "katakan".' }
    return { lulus: true, pesan: 'Ada blok katakan!' }
  },
  periksaHasil() {
    // "katakan" mengosongkan balonnya sendiri setelah jeda selesai (dengan
    // sengaja, lihat interpreter.js), jadi keadaan akhir panggung tidak lagi
    // menyimpan buktinya. Cukup diperiksa di lapis struktur.
    return { lulus: true, pesan: 'Sapaan berhasil dijalankan!' }
  },
}

export const misiSkor = {
  id: 'tk2-08-skor',
  judul: '8. Skor Bertambah',
  instruksi: 'Atur variabel "skor" ke 0, lalu di dalam "ulangi 5 kali" ubah skor sebanyak 1, lalu tampilkan skor.',
  periksaStruktur(ast, { hitungAst, cariAst }) {
    if (hitungAst(ast, 'var_atur') === 0) return { lulus: false, pesan: 'Belum ada blok "atur ... ke ..." untuk memulai skor dari 0.' }
    const u = cariAst(ast, 'ulangi')
    if (!u || hitungAst(u.isi, 'var_ubah') === 0) {
      return { lulus: false, pesan: 'Blok "ubah ... sebanyak ..." harus ada di dalam "ulangi".' }
    }
    if (hitungAst(ast, 'var_tampil') === 0) return { lulus: false, pesan: 'Belum ada blok "tampilkan skor".' }
    return { lulus: true, pesan: 'Skor diatur, diubah dalam ulangi, dan ditampilkan!' }
  },
  periksaHasil(panggung, vars) {
    const nilai = [...vars.values()][0]
    if (nilai !== 5) return { lulus: false, pesan: `Skor akhirnya ${nilai ?? 0}, seharusnya 5 (bertambah 1 sebanyak 5 kali).` }
    if (!panggung.skor) return { lulus: false, pesan: 'Skor belum ditampilkan di panggung.' }
    return { lulus: true, pesan: 'Skor bertambah dan tampil di panggung!' }
  },
}

export const misiKendaliTombol = {
  id: 'tk2-09-tombol',
  judul: '9. Kendalikan dengan Tombol',
  instruksi: 'Di dalam "ulangi selamanya", pakai "jika tombol panah kanan ditekan maka maju 5 langkah".',
  periksaStruktur(ast, { hitungAst, cariAst }) {
    const s = cariAst(ast, 'selamanya')
    if (!s) return { lulus: false, pesan: 'Belum ada blok "ulangi selamanya".' }
    const j = cariAst(s.isi, 'jika') || cariAst(s.isi, 'jika_lain')
    if (!j) return { lulus: false, pesan: 'Di dalam "ulangi selamanya" harus ada blok "jika".' }
    if (!j.kondisi || j.kondisi.t !== 'tombol_ditekan') {
      return { lulus: false, pesan: 'Kondisi di blok "jika" harus "tombol ... ditekan".' }
    }
    if (hitungAst(j.isi, 'maju') === 0) return { lulus: false, pesan: 'Di dalam blok "jika" harus ada blok "maju".' }
    return { lulus: true, pesan: 'Kendali tombol siap dipakai!' }
  },
  periksaHasil() {
    return { lulus: true, pesan: 'Strukturnya sudah benar — coba tekan tombol saat menjalankan!' }
  },
}

export const misiWarnaWarni = {
  id: 'tk2-10-warna',
  judul: '10. Pena Warna-Warni',
  instruksi: 'Gambar dua garis dengan warna pena berbeda: ganti "warna pena" di antara dua blok "maju".',
  periksaStruktur(ast, { hitungAst }) {
    if (hitungAst(ast, 'warna') < 2) return { lulus: false, pesan: 'Butuh minimal 2 blok "warna pena" yang berbeda warna.' }
    if (hitungAst(ast, 'maju') < 2) return { lulus: false, pesan: 'Butuh minimal 2 blok "maju" untuk menggambar dua garis.' }
    return { lulus: true, pesan: 'Dua warna dan dua garis, siap!' }
  },
  periksaHasil(panggung) {
    if (panggung.statistik.jarakTotal < 40) return { lulus: false, pesan: 'Garisnya masih terlalu pendek.' }
    return { lulus: true, pesan: 'Gambar warna-warni berhasil!' }
  },
}

export const misiDeteksiWarna = {
  id: 'tk2-11-deteksi-warna',
  judul: '11. Berhenti di Warna',
  instruksi: 'Di dalam "ulangi selamanya", pakai "jika menyentuh warna ... maka katakan \'Sampai!\'".',
  periksaStruktur(ast, { cariAst, hitungAst }) {
    const s = cariAst(ast, 'selamanya')
    if (!s) return { lulus: false, pesan: 'Belum ada blok "ulangi selamanya".' }
    const j = cariAst(s.isi, 'jika') || cariAst(s.isi, 'jika_lain')
    if (!j || !j.kondisi || j.kondisi.t !== 'menyentuh_warna') {
      return { lulus: false, pesan: 'Kondisi di blok "jika" harus "menyentuh warna".' }
    }
    if (hitungAst(j.isi, 'katakan') === 0) return { lulus: false, pesan: 'Di dalam blok "jika" harus ada blok "katakan".' }
    return { lulus: true, pesan: 'Sensor warna siap dipakai!' }
  },
  periksaHasil() {
    return { lulus: true, pesan: 'Strukturnya sudah benar!' }
  },
}

export const misiBintang = {
  id: 'tk2-12-bintang',
  judul: '12. Proyek Bebas: Bintang',
  instruksi: 'Gambar bintang bersudut lima: "ulangi 5 kali" berisi maju 120 langkah dan putar kanan 144 derajat.',
  periksaStruktur(ast, { cariAst, hitungAst }) {
    const u = cariAst(ast, 'ulangi')
    if (!u || u.n !== 5) return { lulus: false, pesan: 'Butuh blok "ulangi 5 kali".' }
    if (hitungAst(u.isi, 'maju') === 0 || hitungAst(u.isi, 'putar') === 0) {
      return { lulus: false, pesan: 'Di dalam "ulangi 5 kali" harus ada "maju" dan "putar".' }
    }
    return { lulus: true, pesan: 'Ulangi 5 kali siap membentuk bintang!' }
  },
  periksaHasil(panggung) {
    if (!dekat(Math.abs(panggung.statistik.totalPutar), 720, 10)) {
      return { lulus: false, pesan: 'Total putarannya belum 720° (5 kali putar 144°) — ciri khas bintang.' }
    }
    return { lulus: true, pesan: 'Bintang berhasil digambar!' }
  },
}

export const MISI_TINGKAT_2 = [
  misiMajuSederhana,
  misiBerputar,
  misiGarisPena,
  misiPerseguiUlangi,
  misiSegitiga,
  misiPantulSelamanya,
  misiSapa,
  misiSkor,
  misiKendaliTombol,
  misiWarnaWarni,
  misiDeteksiWarna,
  misiBintang,
]
