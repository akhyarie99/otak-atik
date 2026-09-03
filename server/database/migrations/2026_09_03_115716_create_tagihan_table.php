<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Milestone 7.2 (PRD 9.2): satu baris = satu faktur, satu periode
    // langganan (mis. satu tahun ajaran). midtrans_* nullable karena
    // metode "transfer_manual" (PO/transfer bank) tidak pernah menyentuh
    // Midtrans sama sekali — ditandai lunas manual oleh admin platform.
    // pengingat_terkirim menyimpan penanda H-60/30/7 mana yang SUDAH
    // dikirim (JSON, mis. ["h60","h30"]) supaya perintah pengingat tidak
    // mengirim ulang setiap kali dijalankan.
    public function up(): void
    {
        Schema::create('tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('sekolah')->cascadeOnDelete();
            $table->foreignId('langganan_id')->constrained('langganan')->cascadeOnDelete();
            $table->string('nomor_faktur', 30)->unique();
            $table->unsignedBigInteger('jumlah'); // rupiah, bilangan bulat
            $table->string('status', 20)->default('menunggu'); // menunggu|lunas|kedaluwarsa|dibatalkan
            $table->string('metode', 20)->nullable(); // midtrans_va|transfer_manual
            $table->string('midtrans_order_id', 60)->nullable()->unique();
            $table->string('midtrans_va_nomor', 40)->nullable();
            $table->string('midtrans_bank', 20)->nullable();
            $table->date('periode_mulai');
            $table->date('periode_selesai');
            $table->date('jatuh_tempo');
            $table->timestamp('lunas_pada')->nullable();
            $table->foreignId('ditandai_lunas_oleh')->nullable()->constrained('keanggotaan')->nullOnDelete();
            $table->json('pengingat_terkirim')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan');
    }
};
