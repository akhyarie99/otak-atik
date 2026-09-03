<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Undangan orang tua (PRD 6.8: "Orang tua / wali — Undangan dari
    // sekolah"). Token dibuat guru, ditautkan ke SATU keanggotaan siswa.
    // nomor_whatsapp cuma dipakai sekali untuk mengirim tautan (FonnteService)
    // — bukan data anak, tidak dipakai lagi setelah undangan terpakai/kedaluwarsa.
    public function up(): void
    {
        Schema::create('undangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('sekolah')->cascadeOnDelete();
            $table->foreignId('siswa_keanggotaan_id')->constrained('keanggotaan')->cascadeOnDelete();
            $table->foreignId('dibuat_oleh')->constrained('keanggotaan')->cascadeOnDelete();
            $table->string('token', 40)->unique();
            $table->string('nomor_whatsapp')->nullable();
            $table->timestamp('kadaluarsa_pada');
            $table->timestamp('dipakai_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('undangan');
    }
};
