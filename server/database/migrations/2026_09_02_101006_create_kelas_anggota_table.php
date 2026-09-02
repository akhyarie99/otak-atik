<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Menghubungkan satu Keanggotaan ke satu/banyak Kelas. Dipakai untuk
    // siswa (biasanya satu kelas) MAUPUN guru (bisa mengajar/wali di
    // lebih dari satu kelas, PRD 6.8: "satu kelas boleh punya lebih
    // dari satu guru... dengan izin setara" — berlaku juga sebaliknya).
    public function up(): void
    {
        Schema::create('kelas_anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('keanggotaan_id')->constrained('keanggotaan')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['kelas_id', 'keanggotaan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_anggota');
    }
};
