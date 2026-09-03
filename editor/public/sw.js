// Service worker tulisan tangan untuk editor Otak-atik (milestone 7.3,
// PRD 9.6: "PWA offline penuh"). Sengaja tanpa vite-plugin-pwa (lihat
// CLAUDE.md — jangan menambah dependensi besar tanpa alasan).
//
// Berkas hasil build (JS/CSS) punya nama ber-hash yang tidak diketahui
// saat berkas ini ditulis, jadi tidak bisa di-precache dengan daftar
// tetap. Sebagai gantinya dipakai cache RUNTIME: setiap berkas yang
// berhasil diambil lewat jaringan disimpan ke cache, sehingga setelah
// satu kali kunjungan online, kunjungan berikutnya (termasuk saat
// koneksi putus) tetap bisa memuat editor secara penuh. Ini sesuai
// keluhan pengguna sasaran: "Banyak anak hanya punya HP, dan
// koneksinya putus-nyambung."

const CACHE_NAME = 'otakatik-editor-v1';
const CANGKANG_APP = ['/', '/manifest.webmanifest', '/icon.svg'];

self.addEventListener('install', (peristiwa) => {
  self.skipWaiting();
  peristiwa.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(CANGKANG_APP))
  );
});

self.addEventListener('activate', (peristiwa) => {
  peristiwa.waitUntil(
    caches
      .keys()
      .then((namaCache) =>
        Promise.all(
          namaCache
            .filter((nama) => nama !== CACHE_NAME)
            .map((nama) => caches.delete(nama))
        )
      )
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (peristiwa) => {
  const permintaan = peristiwa.request;

  // Hanya tangani GET satu-asal — permintaan lain (POST ke server
  // Laravel, dll.) dibiarkan lewat jaringan seperti biasa.
  if (permintaan.method !== 'GET' || new URL(permintaan.url).origin !== self.location.origin) {
    return;
  }

  // Navigasi halaman (reload / buka tab baru): coba jaringan dulu agar
  // versi terbaru selalu didapat saat online, baru jatuh ke cangkang
  // tersimpan kalau jaringan gagal (offline).
  if (permintaan.mode === 'navigate') {
    peristiwa.respondWith(
      fetch(permintaan)
        .then((respons) => {
          const salinan = respons.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put('/', salinan));
          return respons;
        })
        .catch(() => caches.match('/'))
    );
    return;
  }

  // Aset lain (JS/CSS/gambar hasil build): cache dulu, kalau tidak ada
  // baru ambil dari jaringan sambil menyimpannya untuk kunjungan
  // berikutnya yang offline.
  peristiwa.respondWith(
    caches.match(permintaan).then((tersimpan) => {
      if (tersimpan) return tersimpan;

      return fetch(permintaan).then((respons) => {
        if (respons.ok) {
          const salinan = respons.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(permintaan, salinan));
        }
        return respons;
      });
    })
  );
});
