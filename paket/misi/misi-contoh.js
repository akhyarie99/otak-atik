// Contoh misi untuk membuktikan mesin misi bekerja (milestone 2.1).
// Isi 12 misi tingkat 2 yang sesungguhnya adalah pekerjaan milestone 2.2.

const dekat = (a, b, toleransi = 3) => Math.abs(a - b) <= toleransi

export const misiPersegi = {
  id: 'tk2-persegi',
  judul: 'Gambar Persegi',
  instruksi: 'Buat si Pensil menggambar persegi memakai blok "ulangi" — jangan menempel blok "maju" satu-satu.',
  periksaStruktur(ast, { hitungAst, runTerpanjang }) {
    const jumlahUlangi = hitungAst(ast, 'ulangi') + hitungAst(ast, 'selamanya')
    if (jumlahUlangi === 0) {
      return {
        lulus: false,
        pesan: 'Di dalam programmu belum ada blok "ulangi". Coba pakai perulangan, bukan menempel blok "maju" empat kali.',
      }
    }
    if (runTerpanjang(ast, 'maju') >= 4) {
      return {
        lulus: false,
        pesan: 'Ada 4 blok "maju" ditempel berturut-turut di luar "ulangi". Ganti dengan satu blok "ulangi 4 kali".',
      }
    }
    if (hitungAst(ast, 'maju') === 0) {
      return { lulus: false, pesan: 'Belum ada blok "maju" di dalam "ulangi".' }
    }
    return { lulus: true, pesan: 'Struktur programmu memakai perulangan, bagus!' }
  },
  periksaHasil(panggung) {
    const s = panggung.sprite
    if (!dekat(s.x, 0) || !dekat(s.y, 0)) {
      return { lulus: false, pesan: 'Si Pensil belum kembali ke titik awal — periksa jumlah langkah dan besar putaran.' }
    }
    if (panggung.statistik.pantul > 0) {
      return { lulus: false, pesan: 'Si Pensil menabrak tepi panggung. Buat perseginya lebih kecil.' }
    }
    if (!dekat(Math.abs(panggung.statistik.totalPutar), 360, 5)) {
      return { lulus: false, pesan: 'Putaran totalnya belum genap satu putaran penuh (360°). Persegi butuh 4 kali putar 90°.' }
    }
    return { lulus: true, pesan: 'Persegi berhasil digambar!' }
  },
}
