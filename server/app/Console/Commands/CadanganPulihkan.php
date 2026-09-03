<?php

namespace App\Console\Commands;

use App\Support\DaftarTabel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

// Pemulihan dari cadangan — milestone 7.3 (PRD 9.6). DESTRUKTIF DENGAN
// SENGAJA (mengosongkan lalu mengisi ulang tiap tabel dari berkas
// cadangan) — itu memang makna "pulihkan". Pengaman:
//   1. Menolak kalau nama database di berkas cadangan BEDA dari database
//      yang sedang aktif sekarang — mencegah cadangan sekolah lain (atau
//      proyek lain di server yang sama) ditumpahkan ke database yang salah.
//   2. --konfirmasi wajib (bukan default "ya") — perintah ini tidak boleh
//      pernah terpicu tidak sengaja lewat skrip lain.
class CadanganPulihkan extends Command
{
    protected $signature = 'cadangan:pulihkan {berkas} {--konfirmasi}';

    protected $description = 'Pulihkan database dari berkas cadangan JSON (PRD 9.6) — DESTRUKTIF, menimpa semua tabel';

    public function handle(): int
    {
        if (! $this->option('konfirmasi')) {
            $this->error('Perintah ini destruktif (menimpa seluruh tabel). Jalankan lagi dengan --konfirmasi.');

            return self::FAILURE;
        }

        $path = $this->argument('berkas');
        if (! Storage::disk('local')->exists($path)) {
            $this->error("Berkas cadangan tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $data = json_decode(Storage::disk('local')->get($path), true);
        $meta = $data['_meta'] ?? null;
        unset($data['_meta']);

        $namaDbAktif = DaftarTabel::namaDatabaseAktif();
        if (! $meta || $meta['database'] !== $namaDbAktif) {
            $dariMana = $meta['database'] ?? '(tidak diketahui)';
            $this->error("Cadangan ini dari database \"{$dariMana}\", database yang aktif sekarang \"{$namaDbAktif}\". Ditolak demi keamanan.");

            return self::FAILURE;
        }

        $tabelTersedia = DaftarTabel::namaSaja();
        $sisa = array_filter($data, fn ($nama) => in_array($nama, $tabelTersedia, true), ARRAY_FILTER_USE_KEY);
        foreach (array_diff(array_keys($data), array_keys($sisa)) as $namaAsing) {
            $this->warn("Tabel \"{$namaAsing}\" ada di cadangan tapi tidak ada di database sekarang — dilewati.");
        }

        // disableForeignKeyConstraints() TIDAK cukup diandalkan sendirian
        // — di SQLite, PRAGMA foreign_keys tidak berefek di tengah
        // transaksi (persis situasi RefreshDatabase saat uji otomatis),
        // jadi truncate/insert bisa tetap gagal karena urutan tabel belum
        // sesuai dependensi FK. Pengaman SUNGGUHANNYA: coba ulang tabel
        // yang gagal di putaran berikutnya sampai stabil — bekerja di
        // driver mana pun, tidak bergantung pragma itu benar-benar aktif.
        Schema::disableForeignKeyConstraints();
        try {
            $sisaPercobaan = count($sisa) + 1;
            while ($sisa && $sisaPercobaan-- > 0) {
                foreach ($sisa as $nama => $baris) {
                    try {
                        DB::table($nama)->truncate();
                        foreach (array_chunk($baris, 500) as $potongan) {
                            if ($potongan) DB::table($nama)->insert($potongan);
                        }
                        $this->line("  {$nama}: ".count($baris).' baris dipulihkan.');
                        unset($sisa[$nama]);
                    } catch (\Throwable $e) {
                        // Kemungkinan tabel lain yang jadi induknya (FK)
                        // belum dipulihkan — coba lagi putaran berikutnya.
                    }
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        if ($sisa) {
            $this->error('Tabel berikut gagal dipulihkan setelah beberapa kali coba (kemungkinan dependensi FK melingkar): '.implode(', ', array_keys($sisa)));

            return self::FAILURE;
        }

        $this->info('Pemulihan selesai dari cadangan '.($meta['dibuat_pada'] ?? $path).'.');

        return self::SUCCESS;
    }
}
