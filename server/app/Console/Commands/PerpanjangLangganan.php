<?php

namespace App\Console\Commands;

use App\Models\Langganan;
use App\Models\Tagihan;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

// Milestone 7.2 (PRD 9.2 & 9.4). Dijalankan harian (lihat routes/console.php)
// — idempoten, aman dijalankan berkali-kali: cuma bertindak kalau memang
// ada perubahan yang perlu (tagihan perpanjangan belum ada, atau status
// belum sesuai jarak hari dari berakhir_pada).
class PerpanjangLangganan extends Command
{
    protected $signature = 'langganan:perpanjang';

    protected $description = 'Buat tagihan perpanjangan H-60 dan pindahkan status langganan (aktif -> tenggang -> hanya_baca) sesuai PRD 9.4';

    public function handle(): void
    {
        $sekarang = now()->startOfDay();

        Langganan::withoutGlobalScopes()
            ->whereIn('status', ['percobaan', 'aktif', 'tenggang'])
            ->each(function (Langganan $langganan) use ($sekarang) {
                $hariSampaiBerakhir = $sekarang->diffInDays($langganan->berakhir_pada, false);
                $hariLewat = -$hariSampaiBerakhir; // positif kalau sudah lewat berakhir_pada

                // H-60 (atau lebih dekat) & belum ada tagihan untuk periode
                // BERIKUTNYA (persis sehari setelah berakhir_pada sekarang)
                // -> buat sekarang, supaya guru punya waktu bayar sebelum
                // langganan benar-benar habis. Dicek lewat periode_mulai
                // yang PERSIS, bukan "lebih besar dari tanggal mulai
                // langganan" — itu akan terus cocok ke tagihan siklus lama
                // yang sudah lunas begitu langganan diperpanjang lebih dari
                // sekali (bug nyata yang ketahuan saat menulis skenario uji
                // siklus penuh, lihat TagihanSiklusTest).
                if ($hariSampaiBerakhir <= 60) {
                    $periodeMulaiBerikutnya = $langganan->berakhir_pada->copy()->addDay();
                    $sudahAdaTagihanBerikutnya = Tagihan::withoutGlobalScopes()
                        ->where('langganan_id', $langganan->id)
                        ->whereDate('periode_mulai', $periodeMulaiBerikutnya)
                        ->exists();

                    if (! $sudahAdaTagihanBerikutnya) {
                        $this->buatTagihanPerpanjangan($langganan);
                        $this->info("Tagihan perpanjangan dibuat untuk langganan #{$langganan->id}.");
                    }
                }

                // Belum lewat sama sekali -> tidak ada perubahan status.
                if ($hariLewat <= 0) return;

                if ($hariLewat > 30 && $langganan->status !== 'hanya_baca') {
                    $langganan->update(['status' => 'hanya_baca']);
                    $this->warn("Langganan #{$langganan->id} masuk hanya-baca (lewat {$hariLewat} hari, masa tenggang 30 hari habis).");
                } elseif ($hariLewat <= 30 && $langganan->status !== 'tenggang') {
                    $langganan->update(['status' => 'tenggang']);
                    $this->warn("Langganan #{$langganan->id} masuk masa tenggang (lewat {$hariLewat} hari dari berakhir_pada).");
                }
            });
    }

    private function buatTagihanPerpanjangan(Langganan $langganan): void
    {
        $harga = config("paket.{$langganan->paket}.harga_tahunan");
        $periodeMulai = $langganan->berakhir_pada->copy()->addDay();
        $periodeSelesai = $periodeMulai->copy()->addYear()->subDay();

        Tagihan::withoutGlobalScopes()->create([
            'tenant_id' => $langganan->tenant_id,
            'langganan_id' => $langganan->id,
            'nomor_faktur' => 'INV-'.now()->format('Y').'-'.strtoupper(Str::random(8)),
            'jumlah' => $harga,
            'status' => 'menunggu',
            'periode_mulai' => $periodeMulai,
            'periode_selesai' => $periodeSelesai,
            'jatuh_tempo' => $langganan->berakhir_pada,
        ]);
    }
}
