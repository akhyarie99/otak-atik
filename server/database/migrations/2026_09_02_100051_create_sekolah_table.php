<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tenant. Satu baris di sini = satu sekolah yang berlangganan.
    // Basis data tunggal dengan kolom tenant_id (aturan tetap #5 &
    // rencana-build.md 9.6) — bukan satu basis data per sekolah.
    public function up(): void
    {
        Schema::create('sekolah', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode_sekolah', 12)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sekolah');
    }
};
