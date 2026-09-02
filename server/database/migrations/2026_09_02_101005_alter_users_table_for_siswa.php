<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Siswa masuk lewat kode kelas + nama panggilan + PIN 4 angka (PRD
    // 6.8), bukan surel — "anak kelas 1 tidak boleh dipaksa mengetik
    // alamat surel". email & password jadi nullable supaya baris users
    // yang sama bisa dipakai siswa (privasi minimal: nama panggilan
    // saja, PRD bagian 8) maupun guru/admin (surel+kata sandi).
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // unique-nya sudah ada dari migrasi dasar — jangan dideklarasikan
            // ulang di sini, MySQL menolak index unik dobel dengan nama sama.
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->string('nama_panggilan')->nullable()->after('name');
            $table->string('pin_hash')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nama_panggilan', 'pin_hash']);
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
