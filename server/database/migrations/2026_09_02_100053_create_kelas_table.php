<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Model contoh pertama yang memakai BelongsToTenant — pembuktian
    // nyata bahwa scope tenant bekerja (milestone 4.1). Kelas siswa
    // sungguhan (daftar siswa, impor Excel) adalah pekerjaan milestone 4.2.
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('nama');
            $table->string('tahun_ajaran', 9); // mis. "2026/2027"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
