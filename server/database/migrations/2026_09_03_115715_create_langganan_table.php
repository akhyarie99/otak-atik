<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Milestone 7.2 (PRD 9.2 & 9.4). SATU baris aktif per sekolah pada
    // satu waktu (paket Guru gratis tidak pernah punya baris di sini sama
    // sekali — cuma paket berbayar yang berlangganan). Status:
    //   percobaan  — masa uji coba semester penuh, fungsi utuh, gratis.
    //   aktif      — sudah bayar, fungsi utuh.
    //   tenggang   — masa langganan habis, 30 hari fungsi TETAP utuh.
    //   hanya_baca — tenggang habis juga: karya tetap bisa dilihat/
    //                dimainkan/diunduh, tapi tidak ada penambahan baru
    //                (PRD 9.4 — data TIDAK PERNAH dihapus karena tunggakan).
    public function up(): void
    {
        Schema::create('langganan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('paket', 20);
            $table->string('status', 20)->default('percobaan');
            $table->date('mulai_pada');
            $table->date('berakhir_pada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('langganan');
    }
};
