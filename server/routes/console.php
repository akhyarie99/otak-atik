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
