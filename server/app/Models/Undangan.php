<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Undangan extends Model
{
    /** @use HasFactory<\Database\Factories\UndanganFactory> */
    use HasFactory, BelongsToTenant;

    protected $table = 'undangan';

    protected $fillable = [
        'tenant_id', 'siswa_keanggotaan_id', 'dibuat_oleh', 'token',
        'nomor_whatsapp', 'kadaluarsa_pada', 'dipakai_pada',
    ];

    protected $casts = [
        'kadaluarsa_pada' => 'datetime',
        'dipakai_pada' => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Keanggotaan::class, 'siswa_keanggotaan_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Keanggotaan::class, 'dibuat_oleh');
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'tenant_id');
    }

    public function berlaku(): bool
    {
        return ! $this->dipakai_pada && $this->kadaluarsa_pada->isFuture();
    }
}
