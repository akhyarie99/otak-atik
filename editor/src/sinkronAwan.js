// Klien sinkron awan — milestone 4.3 (PRD 6.5). Bicara ke server lewat
// token Sanctum yang dikirim halaman induk (server/resources/js/Pages/
// Editor.vue) lewat postMessage saat editor ditempel di iframe di dalam
// sesi guru/siswa yang sudah login. Tanpa token, semua fungsi di sini
// aman dipanggil tapi tidak melakukan apa-apa (mode luring murni).

let token = null
let apiBase = null

export function konfigurasiSinkron({ token: t, apiBase: a }) {
  token = t
  apiBase = a
}

export function siapSinkron() {
  return !!(token && apiBase && navigator.onLine)
}

async function panggil(path, opsi = {}) {
  const res = await fetch(`${apiBase}/api/karya${path}`, {
    ...opsi,
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
      ...(opsi.body ? { 'Content-Type': 'application/json' } : {}),
      ...opsi.headers,
    },
  })
  if (!res.ok) throw new Error(`Sinkron gagal (${res.status})`)
  return res.status === 204 ? null : res.json()
}

// Kirim keadaan lokal ke server. Server yang memutuskan siapa menang
// (tulisan terakhir menang, berdasar clientUpdatedAt) dan selalu
// membalas keadaan yang SEHARUSNYA jadi acuan sekarang — kalau
// ternyata ada tulisan lain yang lebih baru, pemanggil harus
// menyesuaikan salinan lokalnya ke balasan ini (lihat App.vue).
export async function dorongKeAwan(project, clientUpdatedAt, judul) {
  return panggil('/mutakhir', {
    method: 'PUT',
    body: JSON.stringify({ project, client_updated_at: clientUpdatedAt, judul }),
  })
}

export async function tarikDariAwan() {
  return panggil('/mutakhir')
}

export async function daftarVersi() {
  return panggil('/mutakhir/versi')
}

export async function pulihkanVersi(idVersi) {
  return panggil(`/mutakhir/versi/${idVersi}/pulihkan`, { method: 'POST' })
}

// Dasar papan progres & ekspor nilai guru (milestone 4.4) — dicatat
// setiap kali "Periksa misi" ditekan, lulus atau tidak (PRD 6.4: nomor
// percobaan lebih berguna daripada nilai akhir).
export async function catatPercobaanMisi(misiId, lulus) {
  if (!siapSinkron()) return
  try {
    await fetch(`${apiBase}/api/misi/percobaan`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      body: JSON.stringify({ misi_id: misiId, lulus }),
    })
  } catch {
    // Luring atau gagal — tidak menghalangi anak tetap melihat hasil
    // periksa misinya sendiri, cuma guru yang tidak dapat catatannya.
  }
}
