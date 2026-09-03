<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Milestone 7.1 (PRD 9.1 & 9.3): paket menentukan batas bawaan (lihat
    // config/paket.php) — batas_kelas/batas_siswa di sini HANYA override
    // manual (mis. harga berjenjang paket Sekolah dinegosiasikan per
    // sekolah), null berarti "pakai batas bawaan paket-nya".
    public function up(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->string('paket', 20)->default('guru')->after('kode_sekolah');
            $table->unsignedInteger('batas_kelas')->nullable()->after('paket');
            $table->unsignedInteger('batas_siswa')->nullable()->after('batas_kelas');
        });
    }

    public function down(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->dropColumn(['paket', 'batas_kelas', 'batas_siswa']);
        });
    }
};
