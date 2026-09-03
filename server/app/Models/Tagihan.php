<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tagihan extends Model
{
    /** @use HasFactory<\Database\Factories\TagihanFactory> */
    use HasFactory, BelongsToTenant;

    protected $table = 'tagihan';

    protected $fillable = [
        'tenant_id', 'langganan_id', 'nomor_faktur', 'jumlah', 'status', 'metode',
        'midtrans_order_id', 'midtrans_va_nomor', 'midtrans_bank',
        'periode_mulai', 'periode_selesai', 'jatuh_tempo', 'lunas_pada',
        'ditandai_lunas_oleh', 'pengingat_terkirim', 'catatan',
    ];

    protected $casts = [
        'periode_mulai' => 'date',
        'periode_selesai' => 'date',
        'jatuh_tempo' => 'date',
        'lunas_pada' => 'datetime',
        'pengingat_terkirim' => 'array',
    ];

    public function langganan(): BelongsTo
    {
        return $this->belongsTo(Langganan::class);
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'tenant_id');
    }

    public function lunas(): bool
    {
        return $this->status === 'lunas';
    }

    // Rp 1.500.000 — dipakai faktur/kwitansi (milestone 7.2, PRD 9.2).
    public function jumlahFormat(): string
    {
        return 'Rp '.number_format($this->jumlah, 0, ',', '.');
    }
}
