<?php

namespace Tests\Feature;

use App\Models\Langganan;
use App\Models\Sekolah;
use App\Models\Tagihan;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Milestone 7.2 — jalur webhook Midtrans TERPISAH dari uji siklus penuh
// (TagihanSiklusTest), karena tanpa server_key sungguhan, VA yang dibuat
// selalu SIMULASI (order_id berawalan "SIM-") — webhook.php menolak order
// simulasi dengan sengaja (tidak pernah datang dari Midtrans sungguhan).
// Di sini server_key DIPAKSA ada lewat config() supaya jalur verifikasi
// tanda tangan sungguhan bisa dibuktikan bekerja, tanpa perlu akun
// Midtrans sungguhan (tanda tangannya cuma hash SHA512, bisa dihitung
// sendiri di kedua sisi).
class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function tagihanContoh(): Tagihan
    {
        $sekolah = Sekolah::factory()->create(['paket' => 'sekolah']);
        $langganan = Langganan::withoutGlobalScopes()->create([
            'tenant_id' => $sekolah->id, 'paket' => 'sekolah', 'status' => 'percobaan',
            'mulai_pada' => now(), 'berakhir_pada' => now()->addMonths(6),
        ]);

        return Tagihan::withoutGlobalScopes()->create([
            'tenant_id' => $sekolah->id, 'langganan_id' => $langganan->id,
            'nomor_faktur' => 'INV-TEST-0001', 'jumlah' => 3_000_000, 'status' => 'menunggu',
            'midtrans_order_id' => 'TAGIHAN-1-ABCDEF', // BUKAN "SIM-..." — mensimulasikan VA sungguhan dari Midtrans
            'periode_mulai' => now(), 'periode_selesai' => now()->addYear(), 'jatuh_tempo' => now()->addDays(7),
        ]);
    }

    private function signature(string $orderId, string $statusCode, string $grossAmount, string $serverKey): string
    {
        return hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
    }

    public function test_webhook_dengan_tanda_tangan_valid_menandai_tagihan_lunas(): void
    {
        config(['services.midtrans.server_key' => 'kunci-uji-rahasia']);
        $tagihan = $this->tagihanContoh();

        $payload = [
            'order_id' => $tagihan->midtrans_order_id,
            'status_code' => '200',
            'gross_amount' => '3000000.00',
            'transaction_status' => 'settlement',
        ];
        $payload['signature_key'] = $this->signature($payload['order_id'], $payload['status_code'], $payload['gross_amount'], 'kunci-uji-rahasia');

        $resp = $this->postJson('/midtrans/webhook', $payload);

        $resp->assertOk();
        $this->assertTrue($tagihan->fresh()->lunas());
        $this->assertSame('aktif', Langganan::withoutGlobalScopes()->find($tagihan->langganan_id)->status);
    }

    public function test_webhook_dengan_tanda_tangan_palsu_ditolak_tagihan_tetap_menunggu(): void
    {
        config(['services.midtrans.server_key' => 'kunci-uji-rahasia']);
        $tagihan = $this->tagihanContoh();

        $payload = [
            'order_id' => $tagihan->midtrans_order_id,
            'status_code' => '200',
            'gross_amount' => '3000000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'tanda-tangan-palsu-asal-tulis',
        ];

        $resp = $this->postJson('/midtrans/webhook', $payload);

        $resp->assertStatus(403);
        $this->assertFalse($tagihan->fresh()->lunas());
    }

    public function test_webhook_untuk_order_id_simulasi_ditolak(): void
    {
        config(['services.midtrans.server_key' => 'kunci-uji-rahasia']);

        $payload = [
            'order_id' => 'SIM-TAGIHAN-1-ABCDEF',
            'status_code' => '200',
            'gross_amount' => '3000000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'apapun',
        ];

        $resp = $this->postJson('/midtrans/webhook', $payload);

        $resp->assertStatus(422);
    }

    public function test_midtrans_service_mensimulasikan_va_saat_server_key_kosong(): void
    {
        config(['services.midtrans.server_key' => null]);
        $tagihan = $this->tagihanContoh();

        $hasil = app(MidtransService::class)->buatTransaksiVa($tagihan);

        $this->assertTrue($hasil['simulasi']);
        $this->assertStringStartsWith('SIM-', $hasil['order_id']);
        $this->assertNotEmpty($hasil['va_nomor']);
    }
}
