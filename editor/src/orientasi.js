// Kunci lanskap saat bermain — milestone 3.2 (PRD 6.2: "Saat memainkan
// karya, layar dikunci lanskap"). Hanya masuk akal di layar sempit (HP);
// dan API-nya sendiri tidak didukung semua browser (Safari iOS misalnya
// tidak punya Screen Orientation API sama sekali) — jadi semua langkah
// dibungkus try/catch dan gagal secara diam-diam, tidak pernah
// menghentikan permainan anak hanya karena tidak bisa mengunci layar.
const LEBAR_HP_MAKS = 768

export async function kuncilLanskapUntukBermain() {
  if (typeof window === 'undefined' || window.innerWidth > LEBAR_HP_MAKS) return
  try {
    if (document.documentElement.requestFullscreen && !document.fullscreenElement) {
      await document.documentElement.requestFullscreen()
    }
  } catch {
    // Butuh gestur pengguna langsung di sejumlah browser — kalau ditolak,
    // tetap coba kunci orientasi saja di bawah.
  }
  try {
    await screen.orientation?.lock?.('landscape')
  } catch {
    // Tidak didukung atau ditolak browser — diamkan, jangan hentikan game.
  }
}

export function lepasKunciLanskap() {
  try {
    screen.orientation?.unlock?.()
  } catch {
    // aman diabaikan
  }
}
