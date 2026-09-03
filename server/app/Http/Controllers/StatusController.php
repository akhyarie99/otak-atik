<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

// Halaman status publik — milestone 7.3 (PRD 9.6: "Status layanan dan
// riwayat gangguan terbuka untuk sekolah"). Riwayat gangguan (log
// insiden dari waktu ke waktu) BELUM dibangun — itu perlu model &
// antarmuka pencatatan insiden tersendiri, di luar cakupan milestone
// ini; yang ada di sini adalah status SAAT INI, diperiksa nyata
// (bukan angka tetap), plus waktu cadangan terakhir sebagai bukti
// cadangan harian (PRD 9.6) sungguhan berjalan.
class StatusController extends Controller
{
    public function tampilkan(): View
    {
        $database = $this->cekDatabase();
        $cadanganTerakhir = $this->cadanganTerakhir();

        return view('status', [
            'database' => $database,
            'cadanganTerakhir' => $cadanganTerakhir,
            'operasional' => $database['baik'],
        ]);
    }

    private function cekDatabase(): array
    {
        $mulai = microtime(true);
        try {
            DB::select('SELECT 1');

            return ['baik' => true, 'ms' => round((microtime(true) - $mulai) * 1000, 1)];
        } catch (\Throwable $e) {
            return ['baik' => false, 'pesan' => 'Tidak bisa terhubung ke basis data.'];
        }
    }

    private function cadanganTerakhir(): ?array
    {
        $berkas = collect(Storage::disk('local')->files('backups'))->sortDesc()->first();
        if (! $berkas) return null;

        return [
            'nama' => basename($berkas),
            'waktu' => Storage::disk('local')->lastModified($berkas),
        ];
    }
}
