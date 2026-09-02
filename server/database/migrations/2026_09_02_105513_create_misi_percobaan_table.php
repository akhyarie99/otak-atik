<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Setiap kali siswa menekan "Periksa misi" (lulus ATAU tidak) dicatat
    // di sini — PRD 6.4: "Guru bisa melihat percobaan ke berapa anak
    // berhasil — data ini lebih berguna daripada nilai akhir." Nomor
    // percobaan dihitung dari urutan baris (created_at), tidak disimpan
    // sebagai kolom terpisah supaya tidak ada dua sumber kebenaran.
    public function up(): void
    {
        Schema::create('misi_percobaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('sekolah')->cascadeOnDelete();
            $table->foreignId('keanggotaan_id')->constrained('keanggotaan')->cascadeOnDelete();
            $table->string('misi_id');
            $table->boolean('lulus');
            $table->timestamps();

            $table->index(['keanggotaan_id', 'misi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('misi_percobaan');
    }
};
