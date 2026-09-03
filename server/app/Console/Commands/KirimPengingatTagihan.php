<?php

namespace App\Console\Commands;

use App\Enums\Peran;
use App\Models\Tagihan;
use App\Services\FonnteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Milestone 7.2 (PRD 9.2): "Pengingat perpanjangan lewat surel dan
// WhatsApp (Fonnte) pada H-60, H-30, dan H-7." Dijalankan harian.
// Idempoten lewat Tagihan.pengingat_terkirim — tiap ambang cuma dikirim
// SEKALI per tagihan, aman dijalankan berkali-kali sehari.
class KirimPengingatTagihan extends Command
{
    protected $signature = 'tagihan:kirim-pengingat';

    protected $description = 'Kirim pengingat H-60/30/7 untuk tagihan yang belum lunas (PRD 9.2)';

    private const AMBANG = [60, 30, 7];

    public function handle(FonnteService $fonnte): void
    {
        $sekarang = now()->startOfDay();

        Tagihan::withoutGlobalScopes()
            ->where('status', 'menunggu')
            ->each(function (Tagihan $tagihan) use ($sekarang, $fonnte) {
                $hariSampaiJatuhTempo = $sekarang->diffInDays($tagihan->jatuh_tempo, false);
                $terkirim = $tagihan->pengingat_terkirim ?? [];

                foreach (self::AMBANG as $ambang) {
                    $kunci = "h{$ambang}";
                    if (in_array($kunci, $terkirim, true)) continue;
                    if ($hariSampaiJatuhTempo > $ambang) continue; // belum waktunya

                    $this->kirimSatu($tagihan, $ambang, $fonnte);
                    $terkirim[] = $kunci;
                    $tagihan->update(['pengingat_terkirim' => $terkirim]);
                    $this->info("Pengingat H-{$ambang} terkirim untuk tagihan {$tagihan->nomor_faktur}.");
                }
            });
    }

    private function kirimSatu(Tagihan $tagihan, int $ambang, FonnteService $fonnte): void
    {
        $sekolah = $tagihan->sekolah;
        $pesan = "Pengingat: tagihan {$tagihan->nomor_faktur} ({$tagihan->jumlahFormat()}) untuk {$sekolah->nama} jatuh tempo {$tagihan->jatuh_tempo->translatedFormat('d F Y')}.";

        $emailAdmin = $sekolah->keanggotaan()
            ->whereIn('peran', [Peran::AdminSekolah->value, Peran::Guru->value])
            ->with('user')->first()?->user?->email;

        if ($emailAdmin) {
            try {
                Mail::raw($pesan, fn ($m) => $m->to($emailAdmin)->subject("Pengingat tagihan — jatuh tempo H-{$ambang}"));
            } catch (\Throwable $e) {
                Log::warning('KirimPengingatTagihan: gagal kirim surel.', ['pesan_galat' => $e->getMessage()]);
            }
        }

        if ($sekolah->nomor_whatsapp) {
            $fonnte->kirim($sekolah->nomor_whatsapp, $pesan);
        }
    }
}
