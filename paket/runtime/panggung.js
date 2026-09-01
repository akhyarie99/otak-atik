// Game API dan panggung — milestone 1.2.
// Tanpa Blockly, tanpa Vue. Editor DAN pemutar hasil ekspor memakai kelas
// yang sama ini (aturan tetap #6): jangan menyalin logika ini ke tempat lain.
import { keLayar, pantulkanDiTepi, normalisasiSudut } from './geometri.js'

// Aturan tetap: panggung selalu 480×360, diskalakan lewat CSS agar posisi
// karya sama di semua layar.
export const LEBAR_PANGGUNG = 480
export const TINGGI_PANGGUNG = 360

const WARNA_PENA_AWAL = '#2F6FED'

export class Panggung {
  constructor(kanvas) {
    this.kanvas = kanvas
    this.ctx = kanvas.getContext('2d')
    this.lebar = kanvas.width || LEBAR_PANGGUNG
    this.tinggi = kanvas.height || TINGGI_PANGGUNG

    this.lapisPena = document.createElement('canvas')
    this.lapisPena.width = this.lebar
    this.lapisPena.height = this.tinggi
    this.pctx = this.lapisPena.getContext('2d')

    this.aturUlang()
  }

  aturUlang() {
    this.sprite = { x: 0, y: 0, arah: 90, penaTurun: false, warna: WARNA_PENA_AWAL, tebal: 4, ucap: '' }
    this.statistik = { totalPutar: 0, pantul: 0, jarakTotal: 0 }
    this.pctx.clearRect(0, 0, this.lebar, this.tinggi)
    this.gambar()
  }

  // --- Game API: dipanggil satu langkah oleh interpreter (milestone 1.3),
  //     dan sengaja bisa dipanggil langsung dari konsol untuk diuji. ---

  maju(n) {
    const r = (this.sprite.arah * Math.PI) / 180
    this.garisKe(this.sprite.x + Math.sin(r) * n, this.sprite.y + Math.cos(r) * n)
    this.statistik.jarakTotal += Math.abs(n)
  }

  garisKe(nx, ny) {
    if (this.sprite.penaTurun) {
      const a = keLayar(this.sprite.x, this.sprite.y, this.lebar, this.tinggi)
      const b = keLayar(nx, ny, this.lebar, this.tinggi)
      this.pctx.strokeStyle = this.sprite.warna
      this.pctx.lineWidth = this.sprite.tebal
      this.pctx.lineCap = 'round'
      this.pctx.beginPath()
      this.pctx.moveTo(a[0], a[1])
      this.pctx.lineTo(b[0], b[1])
      this.pctx.stroke()
    }
    this.sprite.x = nx
    this.sprite.y = ny
    this.gambar()
  }

  putar(d) {
    this.sprite.arah = normalisasiSudut(this.sprite.arah + d)
    this.statistik.totalPutar += d
    this.gambar()
  }

  pergiKe(x, y) {
    this.garisKe(x, y)
  }

  penaTurun(turun) {
    this.sprite.penaTurun = turun
    this.gambar()
  }

  warnaPena(warna) {
    this.sprite.warna = warna
  }

  hapusGambar() {
    this.pctx.clearRect(0, 0, this.lebar, this.tinggi)
    this.gambar()
  }

  pantulTepi() {
    const hasil = pantulkanDiTepi(this.sprite, this.lebar, this.tinggi)
    this.sprite.x = hasil.x
    this.sprite.y = hasil.y
    this.sprite.arah = hasil.arah
    if (hasil.kena) this.statistik.pantul++
    this.gambar()
    return hasil.kena
  }

  katakan(teks) {
    this.sprite.ucap = teks
    this.gambar()
  }

  ucapkan(teks) {
    if (!('speechSynthesis' in window)) return null
    try {
      const u = new SpeechSynthesisUtterance(String(teks))
      u.lang = 'id-ID'
      u.rate = 0.95
      speechSynthesis.cancel()
      speechSynthesis.speak(u)
      return u
    } catch {
      return null
    }
  }

  // --- Render. Dipanggil ulang otomatis di akhir tiap fungsi API di atas,
  //     sehingga hasilnya langsung terlihat tanpa perlu utas animasi
  //     terpisah — itu baru datang bersama interpreter di milestone 1.3. ---

  gambarPetak() {
    const { ctx, lebar: L, tinggi: T } = this
    ctx.save()
    ctx.strokeStyle = '#EFF2F9'
    ctx.lineWidth = 1
    for (let x = 0; x <= L; x += 40) {
      ctx.beginPath()
      ctx.moveTo(x + 0.5, 0)
      ctx.lineTo(x + 0.5, T)
      ctx.stroke()
    }
    for (let y = 0; y <= T; y += 40) {
      ctx.beginPath()
      ctx.moveTo(0, y + 0.5)
      ctx.lineTo(L, y + 0.5)
      ctx.stroke()
    }
    ctx.strokeStyle = '#DFE5F3'
    ctx.beginPath()
    ctx.moveTo(L / 2 + 0.5, 0)
    ctx.lineTo(L / 2 + 0.5, T)
    ctx.stroke()
    ctx.beginPath()
    ctx.moveTo(0, T / 2 + 0.5)
    ctx.lineTo(L, T / 2 + 0.5)
    ctx.stroke()
    ctx.restore()
  }

  gambarSprite() {
    const { ctx, sprite } = this
    const p = keLayar(sprite.x, sprite.y, this.lebar, this.tinggi)
    ctx.save()
    ctx.translate(p[0], p[1])
    ctx.rotate((sprite.arah * Math.PI) / 180)
    const s = sprite.penaTurun ? 1 : 1.1
    ctx.scale(s, s)
    if (!sprite.penaTurun) {
      ctx.save()
      ctx.globalAlpha = 0.12
      ctx.fillStyle = '#232B4D'
      ctx.beginPath()
      ctx.ellipse(0, 6, 13, 5, 0, 0, Math.PI * 2)
      ctx.fill()
      ctx.restore()
    }
    ctx.fillStyle = '#E8C08A'
    ctx.beginPath()
    ctx.moveTo(0, -28)
    ctx.lineTo(-9, -12)
    ctx.lineTo(9, -12)
    ctx.closePath()
    ctx.fill()
    ctx.fillStyle = '#3A3F52'
    ctx.beginPath()
    ctx.moveTo(0, -28)
    ctx.lineTo(-3.4, -21)
    ctx.lineTo(3.4, -21)
    ctx.closePath()
    ctx.fill()
    ctx.fillStyle = sprite.warna
    ctx.fillRect(-9, -12, 18, 28)
    ctx.fillStyle = '#C9CFE2'
    ctx.fillRect(-9, 16, 18, 4)
    ctx.fillStyle = '#F2A0B4'
    ctx.fillRect(-9, 20, 18, 8)
    ctx.fillStyle = '#fff'
    ctx.beginPath()
    ctx.arc(-4, -2, 3.4, 0, Math.PI * 2)
    ctx.arc(4, -2, 3.4, 0, Math.PI * 2)
    ctx.fill()
    ctx.fillStyle = '#232B4D'
    ctx.beginPath()
    ctx.arc(-4, -2.6, 1.7, 0, Math.PI * 2)
    ctx.arc(4, -2.6, 1.7, 0, Math.PI * 2)
    ctx.fill()
    ctx.restore()
  }

  gambarBalon() {
    const { ctx, sprite } = this
    if (!sprite.ucap) return
    const p = keLayar(sprite.x, sprite.y, this.lebar, this.tinggi)
    ctx.save()
    ctx.font = '600 14px "Plus Jakarta Sans", sans-serif'
    const teks = String(sprite.ucap)
    const w = Math.min(190, ctx.measureText(teks).width + 22)
    const x = Math.max(6, Math.min(this.lebar - w - 6, p[0] + 16))
    const y = Math.max(6, p[1] - 62)
    ctx.fillStyle = '#fff'
    ctx.strokeStyle = '#232B4D'
    ctx.lineWidth = 2
    ctx.beginPath()
    if (ctx.roundRect) ctx.roundRect(x, y, w, 34, 12)
    else ctx.rect(x, y, w, 34)
    ctx.fill()
    ctx.stroke()
    ctx.fillStyle = '#232B4D'
    ctx.textBaseline = 'middle'
    ctx.fillText(teks, x + 11, y + 18, w - 20)
    ctx.restore()
  }

  gambar() {
    const { ctx, lebar: L, tinggi: T } = this
    ctx.clearRect(0, 0, L, T)
    this.gambarPetak()
    ctx.drawImage(this.lapisPena, 0, 0)
    this.gambarSprite()
    this.gambarBalon()
  }
}
