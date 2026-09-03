<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Milestone 7.2 (PRD 9.2) — jalan harian, keduanya idempoten (aman
// dijalankan berkali-kali; lihat masing-masing command).
Schedule::command('langganan:perpanjang')->daily();
Schedule::command('tagihan:kirim-pengingat')->daily();

// Milestone 7.3 (PRD 9.6) — cadangan harian. Pemulihan (cadangan:pulihkan)
// SENGAJA tidak dijadwalkan — destruktif, cuma dijalankan manual saat
// benar-benar perlu memulihkan.
Schedule::command('cadangan:jalankan')->daily();
