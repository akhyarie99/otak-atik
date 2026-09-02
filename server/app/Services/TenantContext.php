<?php

namespace App\Services;

// Menyimpan sekolah (tenant) mana yang aktif untuk request saat ini.
// Diisi middleware dari keanggotaan yang dipilih user setelah login
// (milestone 4.2) — untuk sekarang diisi manual lewat aktifkan(), mis.
// dari test atau dari console command admin platform.
//
// Didaftarkan sebagai singleton di AppServiceProvider supaya satu
// instance dipakai di seluruh request/proses yang sama.
class TenantContext
{
    private ?int $sekolahId = null;

    public function aktifkan(?int $sekolahId): void
    {
        $this->sekolahId = $sekolahId;
    }

    public function id(): ?int
    {
        return $this->sekolahId;
    }

    public function aktif(): bool
    {
        return $this->sekolahId !== null;
    }

    public function bersihkan(): void
    {
        $this->sekolahId = null;
    }
}
