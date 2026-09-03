<?php

namespace App\Console\Commands;

use App\Support\DaftarTabel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Cadangan harian — milestone 7.3 (PRD 9.6: "Cadangan harian dengan
// pemulihan titik waktu, dan uji pemulihan terjadwal"). Cadangan LOGIS
// murni PHP (baca setiap tabel lewat query builder, tulis JSON) — bukan
// shell out ke mysqldump: portabel lintas driver (MySQL di produksi,
// SQLite di uji otomatis) dan tidak bergantung binary yang belum tentu
// ada di PATH server produksi.
//
// disk 'local' (storage/app/backups) — BUKAN disk publik, cadangan
// berisi seluruh data sekolah (nama anak dkk, PRD 8) dan tidak boleh
// bisa diunduh siapa pun lewat URL langsung.
class CadanganJalankan extends Command
{
    protected $signature = 'cadangan:jalankan';

    protected $description = 'Buat cadangan JSON dari semua tabel database yang sedang aktif (PRD 9.6)';

    public function handle(): int
    {
        $namaDb = DaftarTabel::namaDatabaseAktif();
        $tabel = DaftarTabel::namaSaja();

        $data = [
            '_meta' => [
                'database' => $namaDb,
                'dibuat_pada' => now()->toIso8601String(),
                'jumlah_tabel' => count($tabel),
            ],
        ];

        foreach ($tabel as $nama) {
            $data[$nama] = DB::table($nama)->get()->map(fn ($baris) => (array) $baris)->all();
        }

        $namaBerkas = 'backups/cadangan-'.now()->format('Y-m-d_His').'.json';
        Storage::disk('local')->put($namaBerkas, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info("Cadangan dibuat: {$namaBerkas} ({$namaDb}, ".count($tabel)." tabel).");

        return self::SUCCESS;
    }
}
