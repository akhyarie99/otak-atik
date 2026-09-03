<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // Kontak penagihan (milestone 7.2, PRD 9.2: "Pengingat perpanjangan
    // lewat surel dan WhatsApp"). Nullable & opsional — pengingat tetap
    // jalan lewat surel admin sekolah kalau ini kosong.
    public function up(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->string('nomor_whatsapp')->nullable()->after('batas_siswa');
        });
    }

    public function down(): void
    {
        Schema::table('sekolah', function (Blueprint $table) {
            $table->dropColumn('nomor_whatsapp');
        });
    }
};
