<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tugas: guru menugaskan satu misi (id string dari paket/misi, mis.
    // "tk2-05-segitiga" — bukan tabel misi tersendiri, isinya dikirim
    // bersama kode, bukan data yang diubah user) ke satu kelas dengan
    // tenggat opsional (PRD 6.7).
    public function up(): void
    {
        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('sekolah')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('diberikan_oleh')->constrained('keanggotaan')->cascadeOnDelete();
            $table->string('misi_id');
            $table->unsignedTinyInteger('tingkat')->default(2);
            $table->timestamp('tenggat')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas');
    }
};
