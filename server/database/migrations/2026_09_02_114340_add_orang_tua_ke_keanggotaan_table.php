<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Milestone 5.3 (PRD 6.8): keanggotaan peran=orang_tua menunjuk ke
    // keanggotaan ANAKnya lewat kolom ini — bukan tabel baru — supaya
    // pola "satu akun, banyak keanggotaan, peran melekat di keanggotaan"
    // yang sudah dipakai guru/siswa tetap konsisten dipakai orang tua.
    // izin_publikasi_luar_sekolah melekat di keanggotaan SISWA (bukan
    // orang tua) karena itu adalah properti anaknya, diubah oleh orang
    // tua yang tertaut (PRD lampiran: "Menerbitkan ke luar sekolah —
    // perlu izin orang tua yang tercatat").
    public function up(): void
    {
        Schema::table('keanggotaan', function (Blueprint $table) {
            $table->foreignId('anak_keanggotaan_id')->nullable()->after('peran')
                ->constrained('keanggotaan')->nullOnDelete();
            $table->boolean('izin_publikasi_luar_sekolah')->default(false)->after('anak_keanggotaan_id');
        });
    }

    public function down(): void
    {
        Schema::table('keanggotaan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anak_keanggotaan_id');
            $table->dropColumn('izin_publikasi_luar_sekolah');
        });
    }
};
