<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Notifikasi WhatsApp lewat Fonnte (PRD 6.8 & 9.2). Token Fonnte SUNGGUHAN
// belum tersedia untuk proyek ini — daripada memblokir fitur undangan
// orang tua sampai kredensial itu ada, layanan ini no-op dengan aman
// (dicatat ke log, tidak melempar galat) kalau token belum diisi.
// Guru tetap melihat & bisa membagikan tautan undangan secara manual
// terlepas dari berhasil tidaknya pengiriman WhatsApp (lihat UndanganController).
class FonnteService
{
    public function kirim(string $nomorTujuan, string $pesan): bool
    {
        $token = config('services.fonnte.token');

        if (! $token) {
            Log::info('FonnteService: token belum dikonfigurasi, pesan tidak dikirim.', [
                'tujuan' => $nomorTujuan,
            ]);

            return false;
        }

        try {
            $resp = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->post(config('services.fonnte.url'), [
                    'target' => $nomorTujuan,
                    'message' => $pesan,
                ]);

            return $resp->successful();
        } catch (\Throwable $e) {
            Log::warning('FonnteService: gagal mengirim pesan.', ['pesan_galat' => $e->getMessage()]);

            return false;
        }
    }
}
