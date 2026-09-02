// Panggung tiruan tanpa canvas/DOM untuk uji paket/misi — memakai
// matematika murni yang sama dengan paket/runtime/panggung.js supaya
// periksaHasil() bisa diuji di Node tanpa jsdom+canvas asli.
import { normalisasiSudut, pantulkanDiTepi } from '@otak-atik/runtime'

const LEBAR = 480
const TINGGI = 360

export function panggungTiruan() {
  const p = {
    sprite: { x: 0, y: 0, arah: 90, penaTurun: false, ucap: '', tampak: true, ukuran: 100, kostum: 'pensil' },
    statistik: { totalPutar: 0, pantul: 0, jarakTotal: 0 },
    skor: null,
    aturUlang() {
      p.sprite = { x: 0, y: 0, arah: 90, penaTurun: false, ucap: '', tampak: true, ukuran: 100, kostum: 'pensil' }
      p.statistik = { totalPutar: 0, pantul: 0, jarakTotal: 0 }
      p.skor = null
    },
    maju(n) {
      const r = (p.sprite.arah * Math.PI) / 180
      p.pergiKe(p.sprite.x + Math.sin(r) * n, p.sprite.y + Math.cos(r) * n)
      p.statistik.jarakTotal += Math.abs(n)
    },
    pergiKe(x, y) {
      p.sprite.x = x
      p.sprite.y = y
    },
    putar(d) {
      p.sprite.arah = normalisasiSudut(p.sprite.arah + d)
      p.statistik.totalPutar += d
    },
    arahkanKe(d) {
      p.sprite.arah = normalisasiSudut(d)
    },
    pantulTepi() {
      const hasil = pantulkanDiTepi(p.sprite, LEBAR, TINGGI)
      p.sprite.x = hasil.x
      p.sprite.y = hasil.y
      p.sprite.arah = hasil.arah
      if (hasil.kena) p.statistik.pantul++
    },
    penaTurun(t) {
      p.sprite.penaTurun = t
    },
    warnaPena() {},
    hapusGambar() {},
    katakan(t) {
      p.sprite.ucap = t
    },
    ucapkan: () => null,
    aturTampil(t) {
      p.sprite.tampak = t
    },
    gantiUkuran(n) {
      p.sprite.ukuran = n
    },
    gantiKostum(n) {
      p.sprite.kostum = n
    },
    mainkanBunyi() {},
    apakahTombolDitekan: () => false,
    menyentuhWarna: () => false,
    menyentuhSprite: () => false,
    tampilkanSkor(nama, nilai) {
      p.skor = { nama, nilai }
    },
    sembunyikanSkor() {
      p.skor = null
    },
  }
  return p
}
