<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Pemantauan galat — milestone 7.3 (PRD 9.6). Sengaja ringan: bukan
// layanan pemantauan pihak ketiga, hanya membaca ekor storage/logs/
// laravel.log langsung. Cukup untuk "ada yang tahu kalau ada yang
// rusak" tanpa menambah dependensi/biaya layanan eksternal di fase
// awal. Hanya admin_platform yang boleh melihat — bisa memuat jejak
// (stack trace) yang tidak pantas dilihat guru/sekolah.
class GalatController extends Controller
{
    private const MAKS_BARIS_DIBACA = 4000;

    private const MAKS_ENTRI_DITAMPILKAN = 100;

    public function tampilkan(Request $request): View
    {
        $adminPlatform = $request->user()->keanggotaan()
            ->where('peran', Peran::AdminPlatform)
            ->where('aktif', true)
            ->exists();
        abort_unless($adminPlatform, 404);

        return view('galat', ['entri' => $this->entriTerakhir()]);
    }

    private function entriTerakhir(): array
    {
        $berkas = storage_path('logs/laravel.log');
        if (! is_file($berkas)) {
            return [];
        }

        // Baca dari ekor berkas saja — laravel.log bisa tumbuh sangat
        // besar dan kita cuma perlu entri terbaru, bukan seluruh isi.
        $ukuran = filesize($berkas);
        $panjangBaca = min($ukuran, 2_000_000);
        $pegangan = fopen($berkas, 'r');
        fseek($pegangan, -$panjangBaca, SEEK_END);
        $isi = fread($pegangan, $panjangBaca);
        fclose($pegangan);

        $baris = explode("\n", $isi);
        $baris = array_slice($baris, -self::MAKS_BARIS_DIBACA);

        // Format baku Monolog Laravel: "[2026-09-03 10:00:00] local.ERROR: pesan {...}"
        // Entri baru dimulai dengan pola ini; baris lanjutan (jejak
        // tumpukan multi-baris) digabung ke entri sebelumnya.
        $entri = [];
        foreach ($baris as $baris1) {
            if (preg_match('/^\[(?<waktu>[\d\-: ]+)\] \w+\.(?<level>\w+): (?<pesan>.*)$/', $baris1, $m)) {
                $entri[] = [
                    'waktu' => $m['waktu'],
                    'level' => $m['level'],
                    'pesan' => $m['pesan'],
                ];
            } elseif ($entri && trim($baris1) !== '') {
                $terakhir = count($entri) - 1;
                $entri[$terakhir]['pesan'] .= "\n".$baris1;
            }
        }

        // Hanya level yang benar-benar menandakan gangguan — DEBUG/INFO
        // terlalu ramai untuk halaman ringkasan ini.
        $entri = array_values(array_filter(
            $entri,
            fn ($e) => in_array($e['level'], ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true)
        ));

        return array_slice(array_reverse($entri), 0, self::MAKS_ENTRI_DITAMPILKAN);
    }
}
