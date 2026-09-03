<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

// Milestone 7.3 (PRD 9.6 — cadangan & pemulihan). SENGAJA TIDAK memakai
// Schema::getTables() — di server MySQL bersama (satu instance dipakai
// banyak proyek lain di mesin dev ini), API itu bisa mengembalikan tabel
// dari SKEMA LAIN juga, bukan cuma database Otak-atik sendiri (dibuktikan
// langsung sebelum menulis kode ini: puluhan tabel proyek tak berkaitan
// ikut muncul). Query di bawah SELALU disaring eksplisit ke database yang
// SEDANG AKTIF (DATABASE() / nama file SQLite) — cadangan/pemulihan tidak
// boleh pernah menyentuh data proyek lain di server yang sama.
class DaftarTabel
{
    public static function semua(): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
        }

        // mysql/mariadb — table_schema = DATABASE() memastikan HANYA
        // database yang sedang dipakai koneksi ini, bukan skema lain.
        return DB::select(
            'SELECT table_name AS name FROM information_schema.tables WHERE table_schema = DATABASE()'
        );
    }

    public static function namaSaja(): array
    {
        return array_map(fn ($t) => $t->name, self::semua());
    }

    public static function namaDatabaseAktif(): string
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return (string) DB::connection()->getDatabaseName(); // ':memory:' atau path berkas
        }

        return DB::selectOne('SELECT DATABASE() AS db')->db;
    }
}
