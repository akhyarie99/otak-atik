<?php

// Cermin id+judul 12 misi tingkat 2 dari paket/misi/isi-tingkat-2.js —
// isi misi (pemeriksa, dsb) tetap satu-satunya sumber kebenaran di
// paket/misi (dipakai editor); daftar ini HANYA untuk label di ruang
// guru (dropdown tugas, papan progres) dan bahan ajar/LKPD (milestone
// 4.4). Kalau paket/misi berubah, perbarui daftar ini juga.
return [
    'tingkat_2' => [
        [
            'id' => 'tk2-01-maju',
            'judul' => '1. Jalan-Jalan',
            'tujuan' => 'Anak mengenal blok "maju" dan memahami bahwa satu blok = satu perintah.',
            'langkah_mengajar' => [
                'Tunjukkan panggung kosong: "Si Pensil belum bisa apa-apa sampai kita kasih perintah."',
                'Ajak anak menemukan sendiri blok "ketika bendera diklik" di kategori Kejadian, lalu blok "maju" di kategori Gerak.',
                'Biarkan anak mengubah angka di blok "maju" dan menekan Jalankan berkali-kali — biarkan mereka penasaran, jangan langsung kasih jawaban.',
            ],
            'lkpd' => 'Gambarkan di kotak ini kira-kira sejauh mana si Pensil berjalan kalau angkanya 200. Coba tebak dulu sebelum menjalankan program.',
        ],
        [
            'id' => 'tk2-02-putar',
            'judul' => '2. Belok Kanan',
            'tujuan' => 'Anak memahami bahwa arah si Pensil berubah permanen setelah blok "putar", memengaruhi kemana blok "maju" berikutnya berjalan.',
            'langkah_mengajar' => [
                'Tanyakan: "Kalau kamu berjalan lurus lalu berputar 90 derajat, kamu menghadap ke mana?" — minta anak memperagakan dengan badan sendiri.',
                'Minta anak menyusun maju-putar-maju, lalu tebak posisi akhir sebelum menekan Jalankan.',
            ],
            'lkpd' => 'Coba ganti "putar kanan" menjadi "putar kiri". Apa yang berubah dari jalur si Pensil?',
        ],
        [
            'id' => 'tk2-03-pena',
            'judul' => '3. Coret Garis',
            'tujuan' => 'Anak memahami bahwa "pena turun" adalah kondisi yang menyala terus sampai diubah, bukan tindakan sekali saja.',
            'langkah_mengajar' => [
                'Tunjukkan bedanya menggambar dengan pensil diangkat vs ditempel ke kertas.',
                'Minta anak mencoba "pena naik" di tengah jalan dan amati garis yang terputus.',
            ],
            'lkpd' => 'Gambar satu garis putus-putus: turunkan pena, maju, naikkan pena, maju lagi, turunkan pena lagi.',
        ],
        [
            'id' => 'tk2-persegi',
            'judul' => '4. Gambar Persegi',
            'tujuan' => 'Anak beralih dari menempel blok berulang-ulang ke memakai blok "ulangi" — inti berpikir komputasional (perulangan).',
            'langkah_mengajar' => [
                'Sengaja biarkan satu-dua anak mencoba menempel 4x blok "maju"+"putar" secara manual dulu — misi ini akan MENOLAKNYA meski gambarnya benar. Jelaskan kenapa: "Kita mau kamu belajar cara yang bisa dipakai lagi untuk gambar apa pun, bukan cuma persegi."',
                'Tunjukkan blok "ulangi 4 kali" sebagai kotak yang "mengingat" isi di dalamnya.',
            ],
            'lkpd' => 'Berapa kali "ulangi" dan berapa derajat "putar" kalau kamu mau gambar segi enam (6 sisi)?',
        ],
        [
            'id' => 'tk2-05-segitiga',
            'judul' => '5. Gambar Segitiga',
            'tujuan' => 'Anak menerapkan pola perulangan ke bentuk baru dan mulai melihat pola angka (360 dibagi jumlah sisi).',
            'langkah_mengajar' => [
                'Jangan kasih tahu angka 120 derajat langsung — minta anak mencoba angka lain dulu dan lihat hasilnya tidak menutup.',
                'Kalau anak sudah selesai misi 4, tanyakan: "Ada pola nggak antara jumlah sisi dan derajat putarnya?"',
            ],
            'lkpd' => 'Isi tabel: segitiga = ... derajat, persegi = 90 derajat, segi lima = ... derajat. Ada pola apa?',
        ],
        [
            'id' => 'tk2-06-pantul',
            'judul' => '6. Bola Memantul',
            'tujuan' => 'Anak mengenal "ulangi selamanya" sebagai program yang terus hidup, dan blok "jika di tepi, pantul" sebagai reaksi otomatis.',
            'langkah_mengajar' => [
                'Ini titik penting: perulangan sekarang TIDAK PUNYA angka, jalan terus sampai tombol Berhenti ditekan. Tunjukkan tombol Berhenti dulu sebelum menjalankan.',
                'Tanyakan apa yang terjadi kalau blok "jika di tepi, pantul" dihapus (si Pensil akan berjalan lurus keluar panggung).',
            ],
            'lkpd' => 'Amati satu menit penuh: berapa kali kira-kira si Pensil memantul?',
        ],
        [
            'id' => 'tk2-07-katakan',
            'judul' => '7. Sapa Teman',
            'tujuan' => 'Anak memahami blok "katakan" sebagai cara program berkomunikasi ke pemain, bukan sekadar hiasan.',
            'langkah_mengajar' => [
                'Minta anak membuat si Pensil menyapa dengan nama mereka sendiri.',
                'Diskusikan: kapan sebuah game butuh "bicara" ke pemainnya? (skor, instruksi, ucapan selamat)',
            ],
            'lkpd' => 'Tulis 3 kalimat berbeda yang bisa dikatakan si Pensil di awal, tengah, dan akhir sebuah cerita.',
        ],
        [
            'id' => 'tk2-08-skor',
            'judul' => '8. Skor Bertambah',
            'tujuan' => 'Anak mengenal konsep variabel sebagai "kotak penyimpan angka" yang bisa berubah nilainya.',
            'langkah_mengajar' => [
                'Analogikan variabel dengan papan skor pertandingan bola — angkanya berubah tapi papannya itu-itu saja.',
                'Minta anak mengubah "ubah skor sebanyak 1" jadi angka lain dan amati bedanya.',
            ],
            'lkpd' => 'Kalau "ulangi 5 kali" diganti "ulangi 10 kali", skor akhirnya jadi berapa? Tebak dulu, lalu buktikan.',
        ],
        [
            'id' => 'tk2-09-tombol',
            'judul' => '9. Kendalikan dengan Tombol',
            'tujuan' => 'Anak menggabungkan "ulangi selamanya" dan "jika" untuk membuat program yang MERESPONS pemain — inti dari sebuah game.',
            'langkah_mengajar' => [
                'Ini misi yang paling terasa seperti "bikin game sungguhan" — beri waktu lebih lama, jangan buru-buru.',
                'Kalau anak kesulitan, tanya balik: "Kapan si Pensil harus bergerak? Cuma waktu itu saja, atau terus-terusan?"',
            ],
            'lkpd' => 'Tambahkan satu blok "jika" lagi untuk tombol panah kiri, supaya si Pensil bisa mundur.',
        ],
        [
            'id' => 'tk2-10-warna',
            'judul' => '10. Pena Warna-Warni',
            'tujuan' => 'Anak bereksperimen bebas dengan properti visual (warna) sambil tetap memakai konsep gerak yang sudah dikuasai.',
            'langkah_mengajar' => [
                'Misi ini lebih longgar — dorong anak berkreasi, bukan mengejar satu jawaban benar.',
                'Pamerkan hasil 2-3 anak ke seluruh kelas kalau sempat.',
            ],
            'lkpd' => 'Gambar bebas apa saja memakai minimal 3 warna berbeda.',
        ],
        [
            'id' => 'tk2-11-deteksi-warna',
            'judul' => '11. Berhenti di Warna',
            'tujuan' => 'Anak mengenal sensor (blok Kondisi) sebagai "mata" program yang membaca keadaan panggung, bukan cuma menjalankan perintah membabi buta.',
            'langkah_mengajar' => [
                'Minta anak menggambar area berwarna dulu (pakai misi 10), baru menyusun sensornya.',
                'Ini konsep abstrak — kalau anak masih bingung, itu wajar, boleh diulang di sesi lain.',
            ],
            'lkpd' => 'Apa bedanya blok "jika" di misi ini dengan blok "jika" di misi 9 (tombol)? Sama-sama "jika", tapi kondisinya beda apa?',
        ],
        [
            'id' => 'tk2-12-bintang',
            'judul' => '12. Proyek Bebas: Bintang',
            'tujuan' => 'Misi penutup tingkat 2 — anak menggabungkan perulangan dan sudut non-90-derajat secara mandiri, sebagai bukti penguasaan konsep, bukan sekadar niru.',
            'langkah_mengajar' => [
                'Ini pengujian akhir — biarkan anak mengerjakan semandiri mungkin, guru cukup mendampingi.',
                'Kalau ada waktu lebih, tantang anak membuat bentuk bintang lain (7 sudut, dst.) sendiri.',
            ],
            'lkpd' => 'Setelah bintang jadi, coba ubah "putar kanan 144" jadi angka lain. Bentuk apa yang muncul?',
        ],
    ],
];
