<?php

namespace App\Enums;

// 3 paket dari PRD 9.1. Batas kuota masing-masing ada di config/paket.php,
// bukan di sini — enum ini cuma daftar nilai yang sah.
enum Paket: string
{
    case Guru = 'guru';
    case Sekolah = 'sekolah';
    case Yayasan = 'yayasan';

    public function label(): string
    {
        return config("paket.{$this->value}.label", $this->value);
    }
}
