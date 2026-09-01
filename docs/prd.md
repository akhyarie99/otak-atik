# PRD — Otak-atik

Platform belajar coding blok visual untuk sekolah Indonesia, jenjang SD kelas 1 sampai SMA.

| | |
|---|---|
| Nama kerja | Otak-atik |
| Pemilik produk | Akhyar — SmartEduGame |
| Versi dokumen | 1.2 — model peran, matriks izin, dan lapisan SaaS ditambahkan |
| Status | Draf untuk validasi ke guru dan siswa |
| Prototipe rujukan | `otak-atik.html` (editor fase 1), `game-uji.html` (contoh ekspor) |

---

## 1. Masalah

Scratch sudah dipakai luas dan gratis, tapi tiga hal membuatnya sulit dipakai serius di sekolah Indonesia:

1. **Tidak ada kurikulum.** Scratch memberi kanvas kosong. Guru yang tidak bisa coding tidak tahu harus menyuruh anak melakukan apa, dan tidak punya cara menilai.
2. **Tidak ada jalur naik jenjang.** Anak yang lulus dari blok harus pindah ke alat lain sama sekali. Banyak yang berhenti di titik itu.
3. **Tidak dirancang untuk HP dan koneksi buruk.** Mayoritas sekolah sasaran tidak punya lab komputer yang layak; yang ada adalah HP milik anak atau orang tua.

Otak-atik menutup ketiganya: satu alat dari kelas 1 sampai kelas 12, dengan kurikulum berjenjang, penilaian otomatis yang bisa dipercaya guru, dan berjalan di HP maupun offline.

## 2. Pengguna

| Peran | Kebutuhan utama | Ukuran keberhasilan bagi mereka |
|---|---|---|
| **Siswa SD 1–3** | Bisa membuat sesuatu bergerak tanpa harus lancar membaca | "Aku bikin ini!" — ada yang bisa ditunjukkan ke orang tua |
| **Siswa SD 4–6** | Membuat game yang bisa dimainkan teman | Karyanya tayang dan dimainkan orang lain |
| **Siswa SMP** | Merasa naik kelas, bukan main mainan anak kecil | Bisa melihat kode aslinya dan mengerti |
| **Siswa SMA** | Keterampilan yang terasa nyata untuk masa depan | Bisa menulis kode teks, bukan hanya blok |
| **Guru** | Bahan ajar siap pakai, penilaian tidak manual | Bisa mengajar coding tanpa bisa coding |
| **Orang tua** | Tahu anaknya belajar apa, bukan sekadar main HP | Bisa melihat karya anaknya dan ikut bangga |
| **Kepala sekolah / yayasan** | Bukti program berjalan | Laporan progres per kelas, karya siswa yang bisa dipamerkan |

## 3. Tujuan dan metrik

| Tujuan | Metrik | Target 6 bulan |
|---|---|---|
| Anak cepat merasa berhasil | Waktu dari buka aplikasi ke karya pertama yang jalan | < 10 menit (median) |
| Belajar mandiri, bukan disuapi | % misi selesai tanpa guru turun tangan | > 70% |
| Guru sanggup mengajar | % guru yang menuntaskan 1 siklus tugas tanpa bantuan teknis | > 80% |
| Karya benar-benar terbit | Karya dipublikasikan per kelas per bulan | ≥ 15 |
| Terpakai berkelanjutan | Kelas aktif mingguan / kelas terdaftar | > 50% |

**Bukan tujuan (fase awal):** menyaingi kelengkapan fitur Scratch, mendukung 3D, kolaborasi waktu nyata, dan marketplace konten pihak ketiga.

---

## 4. Prinsip desain

Lima aturan yang mengikat semua keputusan tampilan dan interaksi.

**1. Karya dulu, teori belakangan.** Anak harus melihat sesuatu bergerak dalam menit pertama. Penjelasan konsep datang setelah ia penasaran, bukan sebelumnya.

**2. Tampilan ikut dewasa bersama anaknya.** Ini pembeda visual utama. Anak SMA malu memakai antarmuka yang sama dengan adiknya di kelas 1. Maka UI berubah bentuk per tingkat — bukan hanya isinya:

| Tingkat | Karakter visual |
|---|---|
| 1 (SD 1–3) | Blok besar dan bulat, ikon mendominasi, teks minimal, maskot aktif memandu, warna cerah penuh |
| 2 (SD 4–6) | Blok sedang, teks + ikon, panggung lebih besar, maskot muncul seperlunya |
| 3 (SMP) | Blok rapat, panel kode muncul berdampingan (baca saja), palet lebih tenang |
| 4 (SMA) | Tata letak menyerupai editor kode sungguhan, tema gelap tersedia, blok jadi opsional |

**3. Satu tempat untuk kegembiraan, sisanya tenang.** Panggung dan momen "karyaku terbit" adalah tempat animasi, warna, dan suara. Panel lain — daftar tugas, dashboard guru — dibuat sunyi. Aplikasi anak yang meriah di setiap sudut melelahkan dan memperlambat perangkat murah.

**4. Hadiah berupa penonton, bukan lencana.** Bintang dan lencana cepat kehilangan makna. Hadiah terkuat adalah karyanya dimainkan orang lain. Galeri kelas adalah fitur motivasi utama, bukan pelengkap.

**5. Tidak ada anak yang dipermalukan.** Tanpa papan peringkat antar-siswa, tanpa waktu pengerjaan yang diadu, tanpa tanda merah besar. Kegagalan misi ditulis sebagai petunjuk langkah berikutnya, bukan vonis.

### 4.1 Bahasa visual

- **Warna kategori blok tetap sama di semua tingkat** — biru gerak, hijau kontrol, ungu pena, kuning kejadian, magenta tampilan, oranye suara. Ini memori otot; jangan pernah diubah.
- **Tipografi:** Baloo 2 untuk judul dan tombol (bulat, ramah), Plus Jakarta Sans untuk teks (buatan Tokotype, karakter Indonesia), JetBrains Mono untuk panel kode.
- **Maskot:** si Pensil. Ia adalah sprite yang digerakkan anak sekaligus pemandu. Di tingkat 1 ia bicara dan menunjuk; di tingkat 4 ia hanya jadi sprite biasa.
- **Panggung** bergaya kertas bergaris dengan petak halus — anak mengenali "buku tulis", dan petak memudahkan mengajarkan koordinat di tingkat 3.
- **Gerak** hanya sebagai umpan balik aksi: blok menempel dengan bunyi klik, misi tuntas dengan satu animasi pendek, karya terbit dengan satu perayaan. Tidak ada animasi yang berjalan sendiri.
- **Suara:** efek klik/tempel blok, satu nada sukses, dan TTS Bahasa Indonesia yang membacakan label blok saat disentuh (wajib di tingkat 1). Semua bisa dimatikan — kelas yang ramai butuh mode senyap.

### 4.2 Aksesibilitas dan perangkat murah

- Sasaran perangkat terendah: Android 4 GB RAM, Chrome, layar 5,5 inci.
- Target sentuh minimal 44×44 px; di tingkat 1 minimal 56 px.
- Kontras teks memenuhi WCAG AA. Warna tidak pernah jadi satu-satunya pembawa makna — kategori blok selalu punya ikon.
- Menghormati `prefers-reduced-motion`.
- Panggung tetap 480×360 dan diskalakan, agar posisi karya sama di semua layar.

---

## 5. Peta tingkat

| Tingkat | Jenjang | Konsep | Blok | Karya akhir |
|---|---|---|---|---|
| **1** | SD 1–3 | Urutan, kejadian, ulangi N kali | ~12, dominan ikon | Animasi dan cerita interaktif |
| **2** | SD 4–6 | Variabel, kondisi, input, tabrakan, skor | ~30 | Game arcade sederhana |
| **3** | SMP | Fungsi buatan sendiri, daftar, operator logika, acak, koordinat | ~50 + panel kode baca-saja | Platformer, mesin kuis |
| **4** | SMA | Objek, event handler, data JSON, algoritma dasar | Editor teks penuh, blok opsional | Aplikasi web dan game ber-state |

**Aturan yang mengikat seluruh jenjang:** blok berbahasa Indonesia, tapi kode yang dihasilkan **JavaScript standar** dengan API runtime yang sama dari kelas 1 sampai kelas 12. Tidak ada bahasa pemrograman berbahasa Indonesia — itu jalan buntu yang merugikan anak saat ia keluar dari aplikasi ini.

Peralihan tingkat 3 ke 4 tidak boleh mendadak. Di SMP, panel kode muncul **hanya bisa dibaca** dan berubah langsung saat anak menyusun blok. Di SMA, panel itu bisa diedit dua arah.

---

## 6. Kebutuhan fungsional

### 6.1 Editor blok — mode kanvas (layar ≥ 768 px)

- Palet berkategori, tarik-lepas bebas, sambungan magnetis, batalkan/ulangi, zoom, tempat sampah.
- Blok yang sedang berjalan menyala saat program dieksekusi.
- Angka diisi lewat kolom pada blok, bukan blok bayangan yang harus diseret — jauh lebih ramah sentuh.
- Palet dibatasi per tingkat; blok tingkat atas tidak muncul sebelum dibuka.

### 6.2 Editor blok — mode kartu (HP)

Ini pekerjaan yang paling menentukan jangkauan produk, dan yang paling mudah diremehkan.

- Program tampil sebagai daftar kartu vertikal, bukan kanvas bebas.
- Blok ditambahkan dengan **ketuk** dari laci, bukan diseret dari jauh.
- Urutan diubah dengan seret satu sumbu (atas–bawah) atau tombol panah.
- Sarang (isi perulangan) ditandai indentasi dan garis penghubung, otomatis.
- Di belakang layar menulis ke struktur yang sama persis dengan mode kanvas, sehingga anak bisa mulai di HP rumah dan lanjut di lab sekolah.
- Saat memainkan karya, layar dikunci lanskap.

### 6.3 Runtime

- Eksekusi bertahap: satu blok satu langkah, dengan jatah langkah per frame.
- **Setiap putaran perulangan wajib melepas kendali ke browser.** Ini syarat mutlak: tanpa ini, satu blok "ulangi selamanya" akan membekukan HP anak. Sudah terbukti di prototipe.
- Kecepatan bisa diatur: lambat (untuk memahami alur), normal, kilat.
- Kesalahan runtime ditangkap dan disampaikan sebagai kalimat ramah anak, bukan pesan teknis.

### 6.4 Misi dan penilaian

Setiap misi diperiksa dua lapis, dan keduanya harus lulus:

1. **Cara mengerjakan** — membaca struktur program. Contoh: misi "gambar persegi" menolak anak yang menempel empat blok maju berturut-turut, meski gambarnya sempurna, karena tujuan misinya adalah perulangan.
2. **Hasil di panggung** — menjalankan program lalu memeriksa keadaan akhir (posisi, total putaran, jumlah garis, skor).

Kegagalan selalu disertai petunjuk spesifik ("Di dalam 'ulangi' belum ada blok putar"), bukan sekadar "salah". Guru bisa melihat percobaan ke berapa anak berhasil — data ini lebih berguna daripada nilai akhir.

Target isi: minimal 12 misi per tingkat, plus 3 template game yang tinggal dimodifikasi. Kanvas kosong membuat anak macet; kerangka yang sudah jalan membuat anak berani mengubah.

### 6.5 Simpan, buka, ekspor

- **Autosave lokal** ke IndexedDB setiap beberapa detik. Wajib — ini yang menyelamatkan karya saat listrik mati atau tab tertutup.
- **Sinkron ke server** saat ada koneksi. Konflik diselesaikan dengan tulisan terakhir menang, dengan riwayat versi yang bisa dikembalikan.
- **Ekspor game mandiri**: satu berkas HTML berisi program dan mesin kecil, tanpa Blockly, tanpa tautan ke internet. Prototipe menghasilkan berkas 4,9 KB yang berjalan sepenuhnya offline.
- **Publikasi ke SmartEduGame** lewat iframe dengan kontrak `postMessage` yang sudah ada, sehingga karya anak masuk ke platform yang sama dengan game buatan tim.

### 6.6 Galeri karya

- Galeri kelas (terlihat sekelas) dan galeri sekolah (terlihat sesekolah). Publik ke internet luas hanya lewat persetujuan guru.
- Teman bisa memainkan dan memberi apresiasi. **Tidak ada kolom komentar bebas** — hanya reaksi terpilih. Ini keputusan sadar untuk menghindari perundungan dan beban moderasi.
- Guru bisa menyembunyikan karya apa pun seketika.
- Anak bisa "remix": menyalin karya teman untuk dimodifikasi, dengan atribusi otomatis ke pembuat asli.

### 6.7 Ruang guru

- Buat kelas, bagikan kode kelas, kelola daftar siswa.
- Berikan tugas: pilih tingkat, pilih misi, tetapkan tenggat.
- Papan progres: per siswa dan per misi, mana yang tuntas, mana yang macet, di misi mana kelas paling banyak tersendat.
- Ekspor nilai ke format yang bisa disalin ke rapor.
- Bahan ajar per misi: tujuan pembelajaran, langkah mengajar, dan LKPD yang bisa dicetak. Guru yang tidak bisa coding harus tetap sanggup mengajar dengan ini.

### 6.8 Akun, peran, dan multi-tenant

**Keputusan arsitektur:** peran melekat pada **keanggotaan**, bukan pada akun. Satu orang bisa menjadi guru di SD A sekaligus orang tua siswa di SMP B, dan itu harus jadi satu akun dengan dua keanggotaan — bukan dua akun. Salah di sini akan menyakitkan untuk diperbaiki setelah ada data nyata.

```
akun (identitas)  ──┬── keanggotaan(sekolah A, peran=guru, kelas=[4A, 4B])
                    ├── keanggotaan(sekolah B, peran=orang tua, anak=[Nadia])
                    └── keanggotaan(platform, peran=penulis konten)
```

**Tujuh peran:**

| Peran | Cara masuk | Lingkup | Alasan keberadaannya |
|---|---|---|---|
| **Tamu** | Tanpa akun | Perangkat sendiri | Anak dan guru harus bisa mencoba tanpa mendaftar. Karya tersimpan lokal, bisa diklaim setelah punya akun. |
| **Siswa** | Kode kelas + nama + PIN 4 angka | Kelasnya sendiri | Anak kelas 1 tidak boleh dipaksa mengetik alamat surel. |
| **Orang tua / wali** | Undangan dari sekolah | Anaknya sendiri | Pemberi izin publikasi ke luar sekolah, dan pendorong utama di jenjang SD. |
| **Guru** | Email + kata sandi | Kelas yang diampu | Satu kelas boleh punya lebih dari satu guru (guru TIK + wali kelas), dengan izin setara. |
| **Admin sekolah** | Email + kata sandi | Satu sekolah | Mengelola guru, kelas, tahun ajaran, dan data sekolah. |
| **Penulis konten** | Internal SmartEduGame | Pustaka misi | Menulis dan menerbitkan misi, template, dan LKPD. Pekerjaan pedagogis yang terpisah dari operasional sekolah. |
| **Admin platform** | Internal SmartEduGame | Semua tenant | Dukungan teknis dan penanganan insiden. |

**Aturan yang mengikat:**

- **Batas tenant bersifat mutlak.** Guru sekolah A tidak pernah bisa melihat data sekolah B, termasuk lewat tautan langsung atau tebakan ID.
- **Admin platform tidak boleh membaca karya atau data siswa secara diam-diam.** Akses hanya lewat mode impersonasi yang butuh alasan tertulis, berbatas waktu, tercatat di jejak audit, dan terlihat oleh admin sekolah.
- **Orang tua tidak bisa melihat siswa lain**, termasuk peringkat atau perbandingan. Hanya anaknya sendiri.
- **Guru tidak bisa mengubah karya siswa**, hanya menyembunyikan atau mengembalikannya. Karya adalah milik anak.

**Siklus hidup akun** — bagian yang paling sering terlewat sampai tahun ajaran kedua:

- **Kenaikan kelas.** Setiap tahun ajaran, siswa dipindah ke kelas baru secara massal. Karya lama ikut pindah bersama siswanya, bukan tertinggal di kelas lama.
- **Siswa pindah sekolah.** Akunnya dinonaktifkan di sekolah lama. Karyanya bisa diunduh sebagai berkas `.json`, tapi tidak otomatis berpindah tenant.
- **Alumni.** Setelah lulus, akun jadi hanya-baca. Karya tetap bisa dilihat dan diunduh pemiliknya.
- **Lupa PIN.** Direset oleh guru pengampu, bukan oleh admin platform. Ini kejadian mingguan di kelas 1–3, jadi harus dua ketukan.
- **Guru keluar.** Kelasnya wajib dialihkan ke guru lain sebelum akunnya dinonaktifkan — kelas tanpa guru tidak boleh ada.
- **Penghapusan data.** Admin sekolah bisa mengekspor dan menghapus seluruh data sekolah. Penghapusan siswa tunggal menghapus data pribadinya tapi menyisakan karya yang sudah di-remix orang lain dalam bentuk anonim.

Matriks izin lengkap ada di lampiran.

---

## 7. Arsitektur

```
Editor (Vue 3 + Blockly, dua mode tampilan)
        │
        ▼   project.json  ──►  IndexedDB (autosave, offline)
   AST program                        │
        │                             ▼
        ▼                    Laravel 12 + Inertia (multi-tenant)
   Interpreter bertahap               │
        │                             ▼
   Canvas 2D  ──► ekspor ──►  HTML mandiri (< 10 KB, offline)
                                      │
                                      ▼
                          SmartEduGame (iframe + postMessage)
```

**Keputusan teknis yang mengikat:**

- **Blockly (Apache 2.0)**, bukan fork Scratch. Bebas di-rebrand, bisa dibatasi per tingkat, jauh lebih ringan.
- **Dua format data yang dipisah**: `blockly` (susunan blok, untuk dibuka lagi di editor) dan `program` (AST, untuk dijalankan). Pemisahan ini membuat game lama tetap bisa diputar meski editornya nanti berubah.
- **Interpreter berbasis generator**, bukan `eval` terhadap kode hasil. Ini yang memungkinkan jalan bertahap, sorot blok aktif, dan pengaman loop.
- **Canvas 2D**, bukan Three.js, untuk semua tingkat awal. Ringan lebih penting daripada keren.
- Blockly **di-host sendiri**, tidak dari CDN, agar editor bisa jadi PWA yang berjalan offline.

**Target performa:**

| Ukuran | Target |
|---|---|
| Bundel editor (gzip) | < 1,5 MB |
| Mesin pemutar hasil ekspor | < 15 KB |
| Siap dipakai di HP 4 GB, jaringan 3G | < 5 detik |
| Laju gambar panggung | 60 fps di HP sasaran, 30 fps minimum |

---

## 8. Keamanan dan privasi anak

- Data pribadi siswa seminimal mungkin: nama panggilan dan kelas. Tanpa tanggal lahir, tanpa alamat, tanpa nomor telepon siswa.
- Sekolah adalah pemilik data; ekspor dan hapus data kelas tersedia untuk admin sekolah.
- Tidak ada iklan dan tidak ada pelacak pihak ketiga di area siswa.
- Tidak ada pesan bebas antar-siswa di mana pun dalam produk.
- Karya yang dipublikasikan ke luar sekolah butuh persetujuan guru secara eksplisit.

## 9. Model SaaS dan operasional

### 9.1 Paket

| Paket | Isi | Sasaran |
|---|---|---|
| **Guru** — gratis | 1 kelas, maksimal 35 siswa, semua tingkat, semua misi, galeri kelas. Tanpa dashboard sekolah dan tanpa laporan. | Guru yang mencoba sendiri sebelum sekolahnya ikut |
| **Sekolah** — tahunan | Kelas tak terbatas, dashboard sekolah, laporan, akun orang tua, ekspor nilai. Harga berjenjang menurut jumlah siswa aktif. | Sekolah dasar dan menengah |
| **Yayasan** — tahunan | Banyak sekolah dalam satu naungan, laporan lintas sekolah, sub-domain sendiri. | Yayasan dengan beberapa unit sekolah |

Paket Guru gratis bukan sekadar pemasaran — ia adalah jalur masuk yang sesungguhnya. Sekolah di Indonesia jarang membeli perangkat lunak karena presentasi penjualan; mereka membeli karena satu guru sudah memakainya dan berhasil. Batas 1 kelas dipilih supaya guru bisa membuktikan nilainya, tapi tidak bisa memakai gratis untuk seluruh sekolah.

### 9.2 Penagihan — tahunan, bukan bulanan

Ini menyesuaikan kenyataan sekolah Indonesia, bukan kebiasaan SaaS global:

- **Siklus tahunan mengikuti tahun ajaran**, karena anggaran sekolah dan yayasan disusun tahunan. Langganan bulanan akan ditolak bagian keuangan.
- **Transfer bank dan virtual account lewat Midtrans**, bukan langganan kartu kredit otomatis. Sekolah membayar lewat bendahara, dan hampir tidak ada yang memakai kartu.
- **Faktur dan kwitansi resmi wajib tersedia untuk diunduh**, lengkap dengan NPWP dan data sekolah. Tanpa ini, pembayaran tidak bisa dipertanggungjawabkan dan kesepakatan batal di menit terakhir.
- **Purchase order dan pembayaran manual** harus didukung: admin platform bisa menandai tenant sebagai lunas setelah transfer masuk.
- **Pengingat perpanjangan** lewat surel dan WhatsApp (Fonnte) pada H-60, H-30, dan H-7.

### 9.3 Kuota dan batas

| Batas | Paket Guru | Paket Sekolah |
|---|---|---|
| Kelas | 1 | tak terbatas |
| Siswa aktif | 35 | sesuai jenjang harga |
| Karya per siswa | 20 | tak terbatas |
| Penyimpanan aset per siswa | 5 MB | 25 MB |
| Ukuran satu aset | 2 MB | 2 MB |

Berkas program itu sangat kecil — beberapa kilobyte per karya. **Pendorong biaya sebenarnya adalah aset**: gambar dan suara yang diunggah anak. Karena itu batasnya ada pada aset, bukan pada jumlah karya, dan pustaka aset bawaan dibagikan lintas tenant sehingga tidak digandakan per sekolah.

Saat kuota tercapai, anak tetap bisa bekerja pada karya yang ada. Yang diblokir hanya penambahan baru, dengan pesan yang ditujukan ke guru, bukan ke anak.

### 9.4 Uji coba dan masa tenggang

- **Uji coba sekolah: satu semester penuh**, bukan 14 hari. Siklus belajar coding di sekolah diukur dalam bulan; uji coba dua minggu tidak membuktikan apa pun dan hanya menghasilkan penolakan.
- **Setelah masa langganan habis**: 30 hari masa tenggang dengan fungsi penuh, lalu berubah menjadi **hanya-baca** — karya tetap bisa dilihat, dimainkan, dan diunduh, tapi tidak bisa dibuat baru.
- **Data tidak pernah dihapus karena tunggakan.** Ini karya anak. Penghapusan hanya atas permintaan tertulis admin sekolah.

### 9.5 Pendaftaran dan orientasi sekolah

Langkah yang paling menentukan berhasil-tidaknya adopsi:

1. Admin sekolah mendaftar, memilih paket, mendapat kode sekolah.
2. **Impor daftar siswa dari Excel atau CSV**, dengan pemetaan kolom dan pratinjau sebelum disimpan. Tidak ada guru yang mau mengetik 300 nama — kalau langkah ini sulit, sekolah berhenti di sini.
3. Guru diundang lewat surel atau tautan, memilih kelas yang diampu.
4. Kelas menerima kode kelas dan kartu PIN yang bisa dicetak.
5. Kelas pertama dijalankan dengan misi bawaan tanpa perlu menyiapkan apa pun.

Target: dari mendaftar sampai satu kelas benar-benar berjalan, kurang dari 30 menit tanpa bantuan tim.

### 9.6 Isolasi tenant dan operasional

- **Satu basis data dengan kolom `tenant_id`** dan global scope Laravel, bukan satu basis data per sekolah. Dengan ratusan sekolah, migrasi per-tenant jadi beban operasional yang tidak sepadan.
- Setiap kueri yang menyentuh data sekolah wajib melewati scope tenant. Ini diuji otomatis, bukan diandalkan pada kedisiplinan penulis kode.
- Sub-domain per sekolah bersifat opsional dan kosmetik; pemisahan data tetap di lapisan aplikasi.
- Cadangan harian dengan pemulihan titik waktu, dan uji pemulihan terjadwal — bukan hanya cadangan yang tidak pernah dicoba dikembalikan.
- Status layanan dan riwayat gangguan terbuka untuk sekolah.

### 9.7 Metrik SaaS

| Metrik | Kenapa penting |
|---|---|
| Sekolah aktif / sekolah berlangganan | Berlangganan tapi tidak dipakai = tidak akan diperpanjang |
| Konversi paket Guru → paket Sekolah | Menguji apakah jalur masuk lewat guru benar-benar bekerja |
| Perpanjangan tahunan | Metrik kesehatan utama; diukur per tahun ajaran, bukan per bulan |
| Kelas aktif per sekolah | Menunjukkan apakah pemakaian menyebar atau berhenti di satu guru |
| Biaya penyimpanan per siswa aktif | Menjaga margin saat jumlah karya bertumbuh |

---

## 10. Tahapan

| Fase | Isi | Keluaran yang bisa diuji |
|---|---|---|
| **1** | Runtime, Game API, AST, interpreter, editor kanvas, 30 blok tingkat 2 | Program blok berjalan di panggung dengan pengaman loop |
| **2** | Mesin misi, 12 misi tingkat 2, 3 template, simpan/buka, ekspor HTML mandiri | Anak SD 4–6 bisa membuat dan mengekspor game tanpa akun |
| **3** | Mode kartu untuk HP | Anak yang hanya punya HP bisa ikut |
| **4** | Laravel: tenant, akun, kelas, impor siswa, autosave, sinkron, tugas, papan progres guru | Satu kelas nyata berjalan satu semester penuh |
| **5** | Galeri kelas dan sekolah, remix, moderasi, akun orang tua | Karya terbit dan dimainkan teman sekelas |
| **6** | Tingkat 1, 3, dan 4: blok ikon, TTS, panel kode baca-saja, mode teks | Jalur SD 1 sampai SMA lengkap |
| **7** | Paket dan penagihan tahunan, kuota, PWA offline penuh, cadangan dan pemantauan | Siap dijual ke sekolah |

**Mulai dari tingkat 2, bukan tingkat 1.** Bloknya jadi fondasi yang tinggal disunat untuk tingkat 1 dan ditambah untuk tingkat 3; anaknya sudah lancar membaca sehingga uji konsep bersih; gurunya paling siap.

## 11. Risiko

| Risiko | Dampak | Penanganan |
|---|---|---|
| Mode kartu HP ternyata tetap sulit dipakai anak | Kehilangan mayoritas sekolah sasaran | Uji ke anak sejak fase 2, sebelum membangun sisanya |
| Ruang lingkup melebar mengejar kelengkapan Scratch | Tidak pernah rilis | Batas keras: 30 blok di fase 1, tidak boleh bertambah sampai ada kelas nyata memakainya |
| Guru tidak memakai karena tidak bisa coding | Produk mati meski teknisnya bagus | Bahan ajar dan LKPD adalah bagian produk, bukan pelengkap; uji ke guru yang benar-benar tidak bisa coding |
| Peralihan blok ke teks terlalu curam | Anak berhenti di SMP | Panel kode baca-saja muncul sejak tingkat 3, bukan mendadak di tingkat 4 |
| Perangkat sekolah terlalu lemah | Pemakaian macet di kelas | Uji di HP termurah yang benar-benar dipakai siswa, bukan di perangkat pengembang |

## 12. Pertanyaan terbuka

1. Titik harga per jenjang jumlah siswa: berapa angka yang masih masuk anggaran BOS atau yayasan untuk sekolah dasar swasta menengah?
2. Apakah kurikulum perlu dipetakan resmi ke Kurikulum Merdeka, dan siapa yang memvalidasinya?
3. Perlukah aplikasi Android terbungkus, atau PWA sudah cukup untuk pemasangan di layar utama?
4. Sekolah mana yang jadi mitra uji pertama, dan berapa kelas?
5. Siapa yang menulis 12 misi per tingkat beserta LKPD-nya — ini pekerjaan pedagogis, bukan teknis, dan volumenya besar.

---

## Lampiran — Matriks izin

Kolom: T = tamu, S = siswa, O = orang tua, G = guru, AS = admin sekolah, PK = penulis konten, AP = admin platform.
✓ = boleh, △ = boleh dengan syarat, kosong = tidak boleh.

| Tindakan | T | S | O | G | AS | PK | AP |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| Menyusun dan menjalankan karya sendiri | ✓ | ✓ | | ✓ | | ✓ | |
| Menyimpan karya ke server | | ✓ | | ✓ | | ✓ | |
| Mengekspor karya jadi berkas HTML | ✓ | ✓ | ✓ | ✓ | | ✓ | |
| Melihat karya sekelas | | ✓ | | ✓ | ✓ | | △ |
| Me-remix karya teman sekelas | | ✓ | | ✓ | | | |
| Menerbitkan ke galeri kelas | | ✓ | | ✓ | | | |
| Menerbitkan ke galeri sekolah | | △ | | ✓ | ✓ | | |
| Menerbitkan ke luar sekolah | | | △ | △ | ✓ | | |
| Menyembunyikan atau memulihkan karya siswa | | | | ✓ | ✓ | | △ |
| Mengubah isi karya siswa | | | | | | | |
| Membuat kelas dan mengelola daftar siswa | | | | ✓ | ✓ | | |
| Memberi tugas dan tenggat | | | | ✓ | | | |
| Melihat progres seluruh siswa di kelasnya | | | | ✓ | ✓ | | △ |
| Melihat progres anak sendiri | | | ✓ | ✓ | ✓ | | △ |
| Mengekspor nilai kelas | | | | ✓ | ✓ | | |
| Mereset PIN siswa | | | | ✓ | ✓ | | |
| Menaikkan kelas siswa antar tahun ajaran | | | | | ✓ | | |
| Mengelola akun guru sekolah | | | | | ✓ | | |
| Mengekspor atau menghapus data sekolah | | | | | ✓ | | |
| Menulis dan menerbitkan misi serta LKPD | | | | | | ✓ | |
| Mengubah tingkat dan daftar blok | | | | | | ✓ | |
| Membuat tenant sekolah baru | | | | | | | ✓ |
| Impersonasi akun | | | | | | | △ |

Catatan syarat (△):

- **Siswa menerbitkan ke galeri sekolah** — perlu persetujuan guru pengampu.
- **Menerbitkan ke luar sekolah** — perlu izin orang tua yang tercatat *dan* persetujuan guru. Admin sekolah bisa menetapkan kebijakan sekolah yang mematikan jalur ini sepenuhnya.
- **Admin platform melihat data siswa** — hanya lewat mode impersonasi berbatas waktu dengan alasan tertulis, tercatat di jejak audit, dan terlihat oleh admin sekolah.

Setiap tindakan pada baris yang menyentuh data siswa wajib meninggalkan jejak audit: siapa, kapan, tindakan apa, terhadap siapa.

---

## Lampiran — Blok tingkat 2 (acuan fase 1)

| Kategori | Blok |
|---|---|
| Kejadian | ketika bendera diklik; ketika tombol ditekan; ketika disentuh |
| Gerak | maju N langkah; putar kanan/kiri N derajat; pergi ke x y; jika di tepi pantul; arahkan ke |
| Kontrol | ulangi N kali; ulangi selamanya; tunggu N detik; jika … maka; jika … maka … kalau tidak |
| Kondisi | menyentuh warna; menyentuh sprite; tombol ditekan |
| Variabel | buat variabel; atur ke; ubah sebanyak; tampilkan skor |
| Pena | pena turun/naik; warna pena; hapus semua gambar |
| Tampilan | katakan … selama N detik; ganti kostum; sembunyi/tampil; ganti ukuran |
| Suara | mainkan bunyi; ucapkan teks |
