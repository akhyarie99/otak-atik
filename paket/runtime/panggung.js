// Game API dan panggung — milestone 1.2.
// Tanpa Blockly, tanpa Vue. Editor DAN pemutar hasil ekspor memakai kelas
// yang sama ini (aturan tetap #6): jangan menyalin logika ini ke tempat lain.
import { hexKeRgb, keLayar, normalisasiSudut, pantulkanDiTepi } from './geometri.js'

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

    // Status tombol dilacak di sini (bukan di interpreter) supaya blok
    // kondisi "tombol ditekan" bisa dibaca kapan saja, termasuk dari
    // konsol, tanpa bergantung pada event handler paralel.
    this.tombolDitekan = new Set()
    this._pasangDengarTombol()

    this._audioCtx = null

    this.aturUlang()
  }

  _pasangDengarTombol() {
    if (typeof window === 'undefined') return
    window.addEventListener('keydown', (e) => this.tombolDitekan.add(e.key))
    window.addEventListener('keyup', (e) => this.tombolDitekan.delete(e.key))
  }

  aturUlang() {
    this.sprite = {
      x: 0,
      y: 0,
      arah: 90,
      penaTurun: false,
      warna: WARNA_PENA_AWAL,
      tebal: 4,
      ucap: '',
      tampak: true,
      ukuran: 100,
      kostum: 'pensil',
    }
    this.statistik = { totalPutar: 0, pantul: 0, jarakTotal: 0 }
    this.skor = null // {nama, nilai} — diatur lewat tampilkanSkor()
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

  // --- Game API tambahan — milestone 1.4, dipakai blok gerak/tampilan/
  //     suara/kondisi lanjutan. ---

  arahkanKe(derajat) {
    this.sprite.arah = normalisasiSudut(derajat)
    this.gambar()
  }

  aturTampil(tampak) {
    this.sprite.tampak = tampak
    this.gambar()
  }

  gantiUkuran(persen) {
    this.sprite.ukuran = Math.max(10, Math.min(400, persen))
    this.gambar()
  }

  gantiKostum(nama) {
    this.sprite.kostum = nama
    this.gambar()
  }

  tampilkanSkor(nama, nilai) {
    this.skor = { nama, nilai }
    this.gambar()
  }

  sembunyikanSkor() {
    this.skor = null
    this.gambar()
  }

  apakahTombolDitekan(tombol) {
    return this.tombolDitekan.has(tombol)
  }

  // Menyentuh warna: sampel piksel lapisan pena di posisi sprite sekarang,
  // dibandingkan dengan warna target dalam toleransi kecil (anti-aliasing).
  menyentuhWarna(warnaHex) {
    const [px, py] = keLayar(this.sprite.x, this.sprite.y, this.lebar, this.tinggi)
    const bx = Math.round(Math.max(0, Math.min(this.lebar - 1, px)))
    const by = Math.round(Math.max(0, Math.min(this.tinggi - 1, py)))
    let data
    try {
      data = this.pctx.getImageData(bx, by, 1, 1).data
    } catch {
      return false
    }
    if (data[3] === 0) return false // transparan = tidak ada goresan di sana
    const target = hexKeRgb(warnaHex)
    if (!target) return false
    const jarak = Math.abs(data[0] - target.r) + Math.abs(data[1] - target.g) + Math.abs(data[2] - target.b)
    return jarak < 60
  }

  // Model satu sprite (aturan tetap milestone 1.2). Sensor sprite-ke-sprite
  // menunggu dukungan multi-sprite di fase lanjut — sengaja selalu false,
  // bukan bug, supaya blok tetap bisa dipakai tanpa membingungkan anak.
  menyentuhSprite() {
    return false
  }

  mainkanBunyi(nama) {
    const FREKUENSI = { pop: 660, ting: 990, kring: 440 }
    const f = FREKUENSI[nama] ?? 660
    try {
      this._audioCtx = this._audioCtx || new (window.AudioContext || window.webkitAudioContext)()
      const ctx = this._audioCtx
      const osc = ctx.createOscillator()
      const gain = ctx.createGain()
      osc.frequency.value = f
      osc.type = 'sine'
      gain.gain.setValueAtTime(0.15, ctx.currentTime)
      gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.18)
      osc.connect(gain).connect(ctx.destination)
      osc.start()
      osc.stop(ctx.currentTime + 0.18)
    } catch {
      // Perangkat tanpa Web Audio, atau kebijakan autoplay memblokir —
      // diamkan saja, jangan sampai menghentikan program anak.
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
    const s = (sprite.penaTurun ? 1 : 1.1) * (sprite.ukuran / 100)
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
    if (sprite.kostum === 'bulat') {
      ctx.fillStyle = sprite.warna
      ctx.beginPath()
      ctx.arc(0, -6, 16, 0, Math.PI * 2)
      ctx.fill()
      ctx.fillStyle = '#fff'
      ctx.beginPath()
      ctx.arc(-4, -8, 3.4, 0, Math.PI * 2)
      ctx.arc(4, -8, 3.4, 0, Math.PI * 2)
      ctx.fill()
      ctx.fillStyle = '#232B4D'
      ctx.beginPath()
      ctx.arc(-4, -8.6, 1.7, 0, Math.PI * 2)
      ctx.arc(4, -8.6, 1.7, 0, Math.PI * 2)
      ctx.fill()
      ctx.restore()
      return
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

  gambarSkor() {
    if (!this.skor) return
    const { ctx } = this
    const teks = `${this.skor.nama}: ${this.skor.nilai}`
    ctx.save()
    ctx.font = '700 13px "Baloo 2", sans-serif'
    const w = ctx.measureText(teks).width + 20
    ctx.fillStyle = 'rgba(35,43,77,0.85)'
    if (ctx.roundRect) {
      ctx.beginPath()
      ctx.roundRect(10, 10, w, 26, 8)
      ctx.fill()
    } else {
      ctx.fillRect(10, 10, w, 26)
    }
    ctx.fillStyle = '#fff'
    ctx.textBaseline = 'middle'
    ctx.fillText(teks, 20, 23)
    ctx.restore()
  }

  gambar() {
    const { ctx, lebar: L, tinggi: T } = this
    ctx.clearRect(0, 0, L, T)
    this.gambarPetak()
    ctx.drawImage(this.lapisPena, 0, 0)
    if (this.sprite.tampak) this.gambarSprite()
    this.gambarBalon()
    this.gambarSkor()
  }
}
