<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Peran melekat pada keanggotaan, bukan akun (PRD 6.8 — keputusan
    // arsitektur inti). sekolah_id NULL berarti keanggotaan level
    // platform (penulis konten, admin platform), bukan milik satu sekolah.
    public function up(): void
    {
        Schema::create('keanggotaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->cascadeOnDelete();
            $table->string('peran');
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'sekolah_id', 'peran']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keanggotaan');
    }
};
