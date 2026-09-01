# CLAUDE.md

Konteks tetap untuk repo Otak-atik. Dibaca otomatis di setiap sesi.

## Produk

Platform belajar coding blok visual untuk sekolah Indonesia, SD kelas 1 sampai SMA. Anak menyusun blok berbahasa Indonesia, melihatnya berjalan di panggung, lalu menerbitkan karyanya untuk dimainkan teman sekelas.

Rujukan lengkap: `docs/prd.md`. Rencana pengerjaan: `docs/rencana-build.md`.
Balas dan tulis komentar kode dalam Bahasa Indonesia.

## Tumpukan teknologi

| Bagian | Pilihan |
|---|---|
| Editor | Vue 3 + Vite, Blockly (Apache 2.0), di-host sendiri — **jangan** dari CDN |
| Renderer blok | `zelos` |
| Runtime | JavaScript murni, Canvas 2D, tanpa kerangka kerja |
| Server | Laravel 12 + Inertia.js |
| Basis data | MySQL, satu basis data, kolom `tenant_id` |
| Pembayaran | Midtrans (virtual account dan transfer bank) |
| WhatsApp | Fonnte |

Jangan menambah dependensi besar tanpa alasan yang ditulis. Three.js, state manager, dan UI kit tidak dipakai di fase awal — target perangkat terendah adalah Android 4 GB RAM.

## Peta folder

```
paket/runtime/   mesin: AST, interpreter, Game API, canvas.
                 Tanpa Blockly, tanpa Vue. Dipakai editor DAN pemutar.
paket/blok/      definisi blok, toolbox per tingkat, konversi blok → AST
paket/misi/      mesin misi dan pemeriksa dua lapis
editor/          Vue 3 + Blockly, mode kanvas dan mode kartu
pemutar/         template game mandiri hasil ekspor (< 15 KB)
server/          Laravel 12 + Inertia
```

## Aturan tetap

Enam hal yang tidak boleh dilanggar. Kalau sebuah permintaan tampak menuntut pelanggaran, hentikan dan tanyakan.

1. **Setiap putaran perulangan wajib `yield`.** Interpreter berbasis generator dengan jatah langkah per frame. Tanpa ini, satu blok "ulangi selamanya" membekukan HP anak.
2. **Tidak ada `eval` atau `new Function` terhadap kode hasil.** Eksekusi selalu lewat interpreter atas AST. Kode JavaScript yang ditampilkan di panel hanya untuk dibaca anak.
3. **Format AST hanya boleh ditambah, tidak diubah dan tidak dihapus.** Game yang sudah diekspor harus tetap bisa diputar bertahun-tahun kemudian. Penambahan jenis simpul wajib disertai nilai bawaan agar pemutar lama tidak pecah.
4. **Warna kategori blok konstan** di seluruh tingkat dan versi:
   kejadian `#F5B32E` · gerak `#2F6FED` · kontrol `#12A472` · pena `#7A4FD1` · tampilan `#D9407E` · suara `#EE6C2B`
5. **Setiap kueri yang menyentuh data sekolah wajib melewati scope tenant**, dengan uji otomatis yang membuktikan akses lintas tenant selalu kosong — termasuk lewat ID langsung.
6. **Editor dan pemutar memakai satu mesin dari `paket/runtime`.** Dilarang menyalin logika runtime ke dalam template ekspor.

## Cara kerja

- Satu milestone dari `docs/rencana-build.md` = satu sesi = satu pull request. Jangan mengerjakan dua sekaligus.
- Sebelum menutup sesi, verifikasi sendiri kolom "selesai bila" pada milestone tersebut dan laporkan hasilnya.
- Tulis uji untuk: pengaman loop, kesetaraan mode kanvas ↔ mode kartu, kompatibilitas AST lama, dan scope tenant. Empat ini adalah tempat kerusakan paling mahal.
- Kalau sebuah keputusan menyimpang dari PRD, tulis alasannya di deskripsi PR dan usulkan perubahan PRD — jangan diam-diam menyimpang.

## Pengguna yang harus diingat saat menulis antarmuka

- Anak kelas 1 belum lancar membaca. Ikon dan suara mendahului teks.
- Anak SMA malu memakai antarmuka anak kecil. Tampilan ikut dewasa per tingkat.
- Guru sasaran tidak bisa coding. Pesan galat ditulis sebagai petunjuk langkah berikutnya, bukan istilah teknis.
- Banyak anak hanya punya HP, dan koneksinya putus-nyambung.

## Privasi anak

Data pribadi siswa seminimal mungkin: nama panggilan dan kelas. Tanpa tanggal lahir, alamat, atau nomor telepon siswa. Tidak ada pesan bebas antar-siswa di mana pun. Tidak ada iklan dan pelacak pihak ketiga di area siswa. Karya adalah milik anak — guru bisa menyembunyikan, tidak bisa mengubah.
