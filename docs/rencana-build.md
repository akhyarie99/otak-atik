# Rencana build — Otak-atik

Pemecahan PRD menjadi milestone yang bisa dikerjakan satu per satu oleh Claude Code.
**7 fase, 21 milestone.**

Ukuran memakai satuan hari kerja untuk satu orang yang dibantu Claude Code:
**S** = 1–2 hari · **M** = 3–5 hari · **L** = 1–2 minggu

---

## Cara memakai dokumen ini

Satu milestone = satu sesi Claude Code = satu pull request. Jangan menggabungkan dua milestone dalam satu sesi; konteksnya jadi terlalu besar dan hasilnya menurun.

Untuk setiap sesi, berikan tiga hal: `CLAUDE.md` (sudah ada di akar repo, dibaca otomatis), PRD sebagai rujukan, dan **satu** blok milestone dari dokumen ini. Kolom "selesai bila" adalah kriteria terima — minta Claude Code memverifikasinya sendiri sebelum menutup sesi.

---

## Struktur repo

```
otak-atik/
├─ CLAUDE.md                  ← aturan tetap, dibaca Claude Code otomatis
├─ docs/
│  ├─ prd.md
│  └─ rencana-build.md
├─ paket/
│  ├─ runtime/                ← mesin: AST, interpreter, Game API, canvas
│  │                            tanpa Blockly, tanpa Vue. Dipakai editor
│  │                            DAN game hasil ekspor.
│  ├─ blok/                   ← definisi blok, toolbox per tingkat, AST builder
│  └─ misi/                   ← mesin misi + pemeriksa dua lapis
├─ editor/                    ← Vue 3 + Blockly, mode kanvas & mode kartu
├─ pemutar/                   ← template game mandiri (< 15 KB)
└─ server/                    ← Laravel 12 + Inertia
```

Pemisahan `paket/runtime` adalah keputusan yang menentukan. Editor dan game hasil ekspor **wajib** memakai mesin yang sama. Kalau keduanya punya salinan sendiri, cepat atau lambat karya anak akan berjalan berbeda di editor dan di hasil ekspornya.

---

## Fase 1 — Inti editor dan runtime

Tujuan: program blok berjalan di panggung, aman, dan bisa disorot per langkah.

| # | Milestone | Keluaran | Selesai bila | Ukuran |
|---|---|---|---|---|
| 1.1 | Kerangka repo | Monorepo, Vite, Vue 3, Blockly di-host sendiri (bukan CDN), tata letak tiga panel | `npm run dev` menampilkan editor kosong yang bisa dipakai menyeret blok contoh | S |
| 1.2 | Game API dan panggung | Canvas 480×360, sprite, arah, lapisan pena, tabrakan tepi, balon bicara | Semua fungsi API bisa dipanggil dari konsol dan terlihat hasilnya di panggung | M |
| 1.3 | AST dan interpreter | Format AST, interpreter generator, jatah langkah per frame, sorot blok aktif | "ulangi selamanya" berisi blok kosong dijalankan 60 detik, tombol Berhenti tetap responsif | M |
| 1.4 | 30 blok tingkat 2 | Definisi blok, toolbox berkategori, generator kode JavaScript, panel kode | Setiap blok punya padanan AST dan padanan baris kode; tidak ada blok tanpa keduanya | M |

---

## Fase 2 — Konten dan ekspor

Tujuan: anak bisa menyelesaikan misi dan membawa pulang karyanya.

| # | Milestone | Keluaran | Selesai bila | Ukuran |
|---|---|---|---|---|
| 2.1 | Mesin misi | Kerangka misi, pemeriksa struktur (baca AST), pemeriksa hasil (jalankan lalu periksa keadaan) | Misi persegi menolak empat blok maju berturut-turut meski gambarnya benar | M |
| 2.2 | Isi tingkat 2 | 12 misi berjenjang, 3 template game, teks petunjuk kegagalan yang spesifik | Anak uji coba menyelesaikan misi 1–6 tanpa penjelasan lisan | L |
| 2.3 | Simpan dan ekspor | `project.json` (bagian `blockly` + `program`), buka berkas, ekspor HTML mandiri | Game hasil ekspor jalan dari berkas lokal tanpa jaringan; ukuran < 15 KB | M |

**Titik uji pertama ke anak.** Jangan lanjut ke fase 3 sebelum tiga anak SD kelas 5 mencoba ini. Catat detik ke berapa mereka bingung, bukan apakah mereka berhasil.

---

## Fase 3 — Mode kartu untuk HP

Tujuan: anak yang hanya punya HP bisa ikut. Ini bagian dengan risiko tertinggi di seluruh proyek.

| # | Milestone | Keluaran | Selesai bila | Ukuran |
|---|---|---|---|---|
| 3.1 | Tampilan kartu | Daftar kartu vertikal, tambah blok lewat ketuk, urut dengan seret satu sumbu, indentasi sarang otomatis | Program yang dibuat di mode kartu terbuka identik di mode kanvas, dan sebaliknya | L |
| 3.2 | Penyetelan perangkat | Target sentuh ≥ 56 px, kunci lanskap saat bermain, uji di HP 4 GB | Anak menyelesaikan misi 2 di HP tanpa bantuan | M |

---

## Fase 4 — Backend, kelas, dan ruang guru

Tujuan: satu kelas nyata bisa berjalan satu semester.

| # | Milestone | Keluaran | Selesai bila | Ukuran |
|---|---|---|---|---|
| 4.1 | Fondasi tenant | Laravel 12 + Inertia, model akun–keanggotaan–peran, global scope tenant | Uji otomatis membuktikan kueri lintas tenant selalu kosong, termasuk lewat ID langsung | M |
| 4.2 | Akun dan kelas | Login guru/admin, login siswa (kode kelas + PIN), kelas, daftar siswa, impor Excel | Impor 300 siswa dari berkas contoh berhasil dengan pratinjau dan pemetaan kolom | L |
| 4.3 | Simpan awan | Autosave IndexedDB, sinkron saat online, riwayat versi, penyelesaian konflik | Tab ditutup paksa saat anak bekerja, karya utuh saat dibuka lagi di perangkat lain | M |
| 4.4 | Ruang guru | Tugas dan tenggat, papan progres per siswa dan per misi, ekspor nilai, LKPD cetak | Guru yang tidak bisa coding menuntaskan satu siklus tugas tanpa dibantu | L |

**Titik uji kedua.** Jalankan satu kelas nyata di sini, sebelum menambah jenjang lain.

---

## Fase 5 — Galeri dan orang tua

Tujuan: karya anak punya penonton. Ini mesin motivasi produk, bukan pelengkap.

| # | Milestone | Keluaran | Selesai bila | Ukuran |
|---|---|---|---|---|
| 5.1 | Galeri | Galeri kelas dan sekolah, mainkan karya teman, reaksi terpilih (tanpa komentar bebas), sembunyikan oleh guru | Karya bisa dimainkan teman sekelas dalam satu ketukan dari daftar | M |
| 5.2 | Remix | Salin karya teman untuk dimodifikasi, atribusi otomatis ke pembuat asli | Rantai remix terlacak sampai karya asal | S |
| 5.3 | Orang tua | Undangan, akun orang tua, lihat progres anak sendiri, izin publikasi keluar sekolah, notifikasi WhatsApp | Orang tua hanya bisa melihat anaknya; uji akses ke siswa lain gagal | M |

---

## Fase 6 — Jenjang lain

Tujuan: rentang SD 1 sampai SMA lengkap.

| # | Milestone | Keluaran | Selesai bila | Ukuran |
|---|---|---|---|---|
| 6.1 | Tingkat 1 | ~12 blok dominan ikon, TTS membacakan label saat disentuh, blok ≥ 56 px, tema cerah | Anak kelas 1 yang belum lancar membaca menyelesaikan misi pertama | L |
| 6.2 | Tingkat 3 | ~50 blok, fungsi buatan sendiri, daftar, koordinat, panel kode baca-saja berdampingan | Panel kode berubah langsung saat blok disusun | L |
| 6.3 | Tingkat 4 | Editor teks dua arah, tema gelap, tata letak menyerupai editor kode | Kode diedit sebagai teks lalu kembali ke blok tanpa kehilangan struktur | L |

Milestone 6.3 adalah yang paling sulit di seluruh proyek. Sinkronisasi dua arah blok–teks itu masalah yang berat; kalau jadwal ketat, versi pertama boleh satu arah saja (blok → teks, lalu teks mengambil alih permanen untuk karya itu).

---

## Fase 7 — SaaS dan kesiapan rilis

Tujuan: bisa dijual dan dioperasikan tanpa kamu jaga terus.

| # | Milestone | Keluaran | Selesai bila | Ukuran |
|---|---|---|---|---|
| 7.1 | Paket dan kuota | Paket Guru/Sekolah/Yayasan, batas kelas dan aset, penegakan kuota | Kuota tercapai memblokir penambahan baru tanpa mengunci karya yang ada | M |
| 7.2 | Penagihan | Midtrans (VA dan transfer), faktur dan kwitansi unduh, penandaan lunas manual, masa tenggang, pengingat H-60/30/7 | Satu siklus langganan penuh disimulasikan dari daftar sampai perpanjangan | L |
| 7.3 | Operasional | PWA offline penuh, cadangan harian, uji pemulihan, pemantauan galat, halaman status | Pemulihan dari cadangan diuji nyata, bukan diasumsikan | M |

---

## Aturan tetap

Enam hal yang tidak boleh dilanggar milestone mana pun. Salin ke `CLAUDE.md` agar ikut terbaca setiap sesi.

1. **Setiap putaran perulangan wajib melepas kendali ke browser.** Tanpa ini, satu blok "ulangi selamanya" membekukan HP anak.
2. **Tidak ada `eval` terhadap kode hasil.** Eksekusi selalu lewat interpreter atas AST.
3. **Format AST hanya boleh ditambah, tidak diubah.** Game yang sudah diekspor harus tetap bisa diputar bertahun-tahun kemudian.
4. **Warna kategori blok tidak pernah berubah** di seluruh tingkat dan seluruh versi. Itu memori otot anak selama 12 tahun.
5. **Setiap kueri yang menyentuh data sekolah wajib melewati scope tenant**, dan itu diuji otomatis, bukan diandalkan pada kedisiplinan.
6. **Editor dan pemutar memakai satu mesin yang sama** dari `paket/runtime`. Dilarang menduplikasi logika runtime ke dalam template ekspor.

## Urutan yang tidak boleh dibalik

- Fase 2 sebelum fase 3 — mode kartu HP baru bermakna setelah ada misi yang layak dikerjakan di dalamnya.
- Fase 4 sebelum fase 5 — galeri tanpa kelas dan tanpa moderasi guru adalah risiko, bukan fitur.
- Fase 4 sebelum fase 6 — buktikan satu jenjang bekerja di kelas nyata sebelum membangun tiga jenjang lain.
- Fase 7 kapan saja setelah fase 4, tapi jangan sebelum ada sekolah yang mau membayar.

## Perkiraan kasar

| Fase | Ukuran |
|---|---|
| 1 | 3–4 minggu |
| 2 | 4–5 minggu |
| 3 | 3–4 minggu |
| 4 | 6–8 minggu |
| 5 | 3–4 minggu |
| 6 | 8–10 minggu |
| 7 | 5–6 minggu |
| **Total** | **8–10 bulan** untuk satu orang yang juga mengajar |

Fase 1–4 saja sudah menghasilkan produk yang bisa dipakai satu sekolah dengan sungguhan. Itu sekitar **4–5 bulan**, dan di titik itu kamu sudah punya sesuatu untuk ditunjukkan dan diuji harganya.
