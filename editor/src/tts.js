// TTS "baca label saat disentuh" — milestone 6.1 (PRD 4.1, wajib di
// tingkat 1). Terpisah dari paket/runtime/panggung.js:ucapkan(), yang
// membacakan teks HASIL KARYA anak saat program berjalan di panggung —
// ini membaca NAMA BLOK saat anak menjelajah toolbox/kartu di EDITOR,
// supaya anak yang belum lancar membaca tetap tahu blok mana yang dia
// pegang. "Semua bisa dimatikan" (PRD 4.1) — status bisu disimpan di
// localStorage per perangkat, bertahan lintas sesi editor.
const KUNCI_BISU = 'otak-atik:tts-bisu'

export function ttsBisu() {
  try {
    return localStorage.getItem(KUNCI_BISU) === '1'
  } catch {
    return false
  }
}

export function aturTtsBisu(bisu) {
  try {
    localStorage.setItem(KUNCI_BISU, bisu ? '1' : '0')
  } catch {
    // Penyimpanan tidak tersedia (mis. mode privat) — aman diabaikan,
    // TTS tetap menyala default per sesi.
  }
}

export function bicarakan(teks) {
  if (!teks || ttsBisu()) return
  if (typeof window === 'undefined' || !window.speechSynthesis) return
  try {
    window.speechSynthesis.cancel() // batalkan ucapan sebelumnya — anak sering menyentuh cepat berturut-turut
    const u = new SpeechSynthesisUtterance(teks)
    u.lang = 'id-ID'
    u.rate = 0.95
    window.speechSynthesis.speak(u)
  } catch {
    // Web Speech API tidak didukung di sebagian browser lama — diamkan,
    // bukan galat yang menghentikan anak memakai editor.
  }
}
