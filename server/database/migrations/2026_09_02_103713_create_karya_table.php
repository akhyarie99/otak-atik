<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Satu baris = karya aktif satu siswa (MVP milestone 4.3 — satu
    // karya per keanggotaan; manajemen banyak karya per siswa menyusul
    // kalau dibutuhkan). project_json menyimpan snapshot TERBARU (yang
    // menang di "tulisan terakhir menang") untuk baca cepat; riwayat
    // penuh ada di karya_versi.
    public function up(): void
    {
        Schema::create('karya', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('sekolah')->cascadeOnDelete();
            $table->foreignId('keanggotaan_id')->unique()->constrained('keanggotaan')->cascadeOnDelete();
            $table->string('judul')->default('Karyaku');
            $table->json('project_json');
            $table->timestamp('client_updated_at'); // jam PERANGKAT yang menulis, dasar "tulisan terakhir menang"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karya');
    }
};
