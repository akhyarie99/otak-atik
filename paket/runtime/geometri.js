// Fungsi murni untuk geometri panggung — tanpa DOM, mudah diuji.
// Dipakai oleh panggung.js dan siapa pun yang butuh logika yang sama
// tanpa canvas (mis. pemeriksa misi di paket/misi, fase 2).

// Sistem koordinat panggung: origin di tengah, sumbu y ke atas (seperti Scratch/matematika).
// keLayar mengubahnya ke koordinat piksel canvas (origin kiri-atas, y ke bawah).
export function keLayar(x, y, lebar, tinggi) {
  return [lebar / 2 + x, tinggi / 2 - y]
}

export function normalisasiSudut(d) {
  return ((d % 360) + 360) % 360
}

// Memantulkan posisi/arah saat sprite melewati tepi panggung.
// marginSprite menyisakan ruang agar gambar sprite tidak terpotong tepi.
export function pantulkanDiTepi({ x, y, arah }, lebar, tinggi, marginSprite = 22) {
  const bx = lebar / 2 - marginSprite
  const by = tinggi / 2 - marginSprite
  let nx = x
  let ny = y
  let narah = arah
  let kena = false

  if (nx > bx) {
    nx = bx
    narah = -narah
    kena = true
  } else if (nx < -bx) {
    nx = -bx
    narah = -narah
    kena = true
  }

  if (ny > by) {
    ny = by
    narah = 180 - narah
    kena = true
  } else if (ny < -by) {
    ny = -by
    narah = 180 - narah
    kena = true
  }

  if (kena) narah = normalisasiSudut(narah)

  return { x: nx, y: ny, arah: narah, kena }
}
