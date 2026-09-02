<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Fase 5: galeri butuh siswa bisa punya LEBIH dari satu karya (satu
    // yang dipublikasikan tetap bisa dilihat teman sambil siswa mulai
    // karya baru yang lain) — unique(keanggotaan_id) dari milestone 4.3
    // dilepas. remix_dari_karya_id melacak rantai remix (milestone 5.2).
    public function up(): void
    {
        Schema::table('karya', function (Blueprint $table) {
            // MySQL menolak drop index unik yang masih dipakai FK tanpa
            // index pengganti — tambah index biasa dulu, baru lepas yang unik.
            $table->index('keanggotaan_id');
        });
        Schema::table('karya', function (Blueprint $table) {
            $table->dropUnique(['keanggotaan_id']);

            $table->string('status_publikasi')->default('privat')->after('judul'); // privat|kelas|sekolah
            $table->boolean('disembunyikan_oleh_guru')->default(false)->after('status_publikasi');
            $table->timestamp('dipublikasikan_pada')->nullable()->after('disembunyikan_oleh_guru');
            $table->foreignId('remix_dari_karya_id')->nullable()->after('dipublikasikan_pada')
                ->constrained('karya')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('karya', function (Blueprint $table) {
            $table->dropConstrainedForeignId('remix_dari_karya_id');
            $table->dropColumn(['status_publikasi', 'disembunyikan_oleh_guru', 'dipublikasikan_pada']);
            $table->dropIndex(['keanggotaan_id']);
            $table->unique('keanggotaan_id');
        });
    }
};
