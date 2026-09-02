<?php

namespace App\Enums;

// Tujuh peran dari PRD 6.8. Peran melekat pada KEANGGOTAAN, bukan akun —
// satu akun bisa punya banyak keanggotaan dengan peran berbeda-beda di
// sekolah berbeda-beda (mis. guru di sekolah A sekaligus orang tua di
// sekolah B). "Tamu" sengaja tidak ada di sini — tamu tidak punya akun
// sama sekali (PRD: "Tanpa akun").
enum Peran: string
{
    case Siswa = 'siswa';
    case OrangTua = 'orang_tua';
    case Guru = 'guru';
    case AdminSekolah = 'admin_sekolah';
    case PenulisKonten = 'penulis_konten';
    case AdminPlatform = 'admin_platform';

    public function label(): string
    {
        return match ($this) {
            self::Siswa => 'Siswa',
            self::OrangTua => 'Orang tua / wali',
            self::Guru => 'Guru',
            self::AdminSekolah => 'Admin sekolah',
            self::PenulisKonten => 'Penulis konten',
            self::AdminPlatform => 'Admin platform',
        };
    }

    // Peran platform: keanggotaan-nya tidak terikat sekolah_id (lihat
    // diagram PRD 6.8 — "keanggotaan(platform, peran=penulis konten)").
    public function levelPlatform(): bool
    {
        return $this === self::PenulisKonten || $this === self::AdminPlatform;
    }
}
