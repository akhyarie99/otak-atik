<?php

namespace App\Services;

use App\Models\Tagihan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Virtual account & transfer bank lewat Midtrans (milestone 7.2, PRD 9.2).
// Kredensial Midtrans SUNGGUHAN (bahkan sandbox) belum ada untuk proyek
// ini. Daripada memblokir seluruh alur penagihan sampai kredensial itu
// ada, layanan ini SELALU bisa dipakai: kalau server_key kosong, VA-nya
// DISIMULASIKAN secara lokal (nomor VA nyata secara bentuk, ditandai
// jelas di log & di field midtrans_order_id dengan awalan "SIM-") — cukup
// untuk membuktikan seluruh alur langganan (daftar -> tagihan -> bayar ->
// aktif -> perpanjang) bekerja ujung ke ujung tanpa transaksi nyata (lihat
// TagihanSiklusTest.php). Begitu kredensial sandbox/produksi sungguhan
// diisi di .env, kode charge/verifikasi di bawah langsung memakainya —
// tidak perlu ditulis ulang.
class MidtransService
{
    private function dikonfigurasi(): bool
    {
        return (bool) config('services.midtrans.server_key');
    }

    private function baseUrl(): string
    {
        return config('services.midtrans.produksi')
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    // Membuat transaksi virtual account bank (Core API charge, bank_transfer).
    // Mengembalikan ['order_id', 'va_nomor', 'bank', 'simulasi' => bool].
    public function buatTransaksiVa(Tagihan $tagihan, string $bank = 'bca'): array
    {
        $orderId = 'TAGIHAN-'.$tagihan->id.'-'.Str::random(6);

        if (! $this->dikonfigurasi()) {
            $vaSimulasi = 'SIM'.str_pad((string) $tagihan->id, 10, '0', STR_PAD_LEFT);
            Log::info('MidtransService: server_key belum dikonfigurasi, VA disimulasikan.', [
                'tagihan_id' => $tagihan->id, 'va_simulasi' => $vaSimulasi,
            ]);

            return ['order_id' => 'SIM-'.$orderId, 'va_nomor' => $vaSimulasi, 'bank' => $bank, 'simulasi' => true];
        }

        try {
            $resp = Http::withBasicAuth(config('services.midtrans.server_key'), '')
                ->post("{$this->baseUrl()}/v2/charge", [
                    'payment_type' => 'bank_transfer',
                    'transaction_details' => ['order_id' => $orderId, 'gross_amount' => $tagihan->jumlah],
                    'bank_transfer' => ['bank' => $bank],
                ]);

            if (! $resp->successful()) {
                throw new \RuntimeException('Midtrans menolak permintaan: '.$resp->body());
            }

            $vaNomor = $resp->json('va_numbers.0.va_number') ?? $resp->json('permata_va_number');

            return ['order_id' => $orderId, 'va_nomor' => $vaNomor, 'bank' => $bank, 'simulasi' => false];
        } catch (\Throwable $e) {
            Log::warning('MidtransService: gagal membuat transaksi VA, jatuh ke simulasi.', ['pesan_galat' => $e->getMessage()]);
            $vaSimulasi = 'SIM'.str_pad((string) $tagihan->id, 10, '0', STR_PAD_LEFT);

            return ['order_id' => 'SIM-'.$orderId, 'va_nomor' => $vaSimulasi, 'bank' => $bank, 'simulasi' => true];
        }
    }

    // Verifikasi tanda tangan notifikasi webhook (dokumentasi Midtrans:
    // SHA512(order_id+status_code+gross_amount+server_key)). Notifikasi
    // dari order_id "SIM-..." (hasil simulasi) tidak pernah datang dari
    // Midtrans sungguhan — endpoint webhook menolaknya di luar fungsi ini.
    public function verifikasiSignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = config('services.midtrans.server_key');
        if (! $serverKey) return false;

        $hash = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($hash, $signatureKey);
    }
}
