<?php

// Batas bawaan per paket — milestone 7.1 (PRD 9.1 & 9.3). Sekolah.batas_kelas/
// batas_siswa (kolom, bukan di sini) meng-override nilai di bawah kalau
// diisi manual (paket Sekolah "harga berjenjang menurut jumlah siswa aktif"
// — dinegosiasikan per sekolah, bukan satu angka tetap untuk semua).
//
// Aset (penyimpanan per siswa, ukuran per aset) BELUM ditegakkan — belum
// ada fitur unggah aset sama sekali di aplikasi ini (siswa cuma memakai
// pustaka warna/bunyi bawaan), jadi menegakkan kuota untuk sesuatu yang
// tidak bisa diunggah anak hanya kode mati. Kolomnya tetap didokumentasikan
// di sini supaya siap dipakai begitu fitur unggah aset benar-benar ada.
return [
    'guru' => [
        'label' => 'Guru (gratis)',
        'kelas' => 1,
        'siswa' => 35,
        'karya_per_siswa' => 20,
        'penyimpanan_aset_per_siswa_mb' => 5,
        'ukuran_aset_mb' => 2,
    ],
    'sekolah' => [
        'label' => 'Sekolah (tahunan)',
        'kelas' => null, // tak terbatas
        'siswa' => null, // "sesuai jenjang harga" — lihat Sekolah.batas_siswa
        'karya_per_siswa' => null,
        'penyimpanan_aset_per_siswa_mb' => 25,
        'ukuran_aset_mb' => 2,
        // ANGKA SEMENTARA (placeholder) — belum ada keputusan harga
        // sungguhan (butuh sekolah pertama yang mau bayar, lihat
        // rencana-build.md). Cukup untuk mensimulasikan siklus penagihan
        // penuh (milestone 7.2), BUKAN harga jual final.
        'harga_tahunan' => 3_000_000,
    ],
    'yayasan' => [
        'label' => 'Yayasan (tahunan)',
        'kelas' => null,
        'siswa' => null,
        'karya_per_siswa' => null,
        'penyimpanan_aset_per_siswa_mb' => 25,
        'ukuran_aset_mb' => 2,
        'harga_tahunan' => 10_000_000, // ANGKA SEMENTARA — lihat catatan di atas
    ],
];
