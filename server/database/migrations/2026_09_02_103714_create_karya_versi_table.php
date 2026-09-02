<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Riwayat versi, tambah-saja (append-only) — PRD 6.5: "riwayat versi
    // yang bisa dikembalikan". Tidak punya tenant_id sendiri: dilindungi
    // transitif lewat karya_id (karya SELALU diambil lewat Karya, yang
    // sudah tersaring TenantScope, sebelum menyentuh versi-nya).
    public function up(): void
    {
        Schema::create('karya_versi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karya_id')->constrained('karya')->cascadeOnDelete();
            $table->json('project_json');
            $table->timestamp('client_updated_at');
            $table->timestamps();

            $table->index(['karya_id', 'client_updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karya_versi');
    }
};
