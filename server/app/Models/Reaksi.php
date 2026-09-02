<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaksi extends Model
{
    /** @use HasFactory<\Database\Factories\ReaksiFactory> */
    use HasFactory, BelongsToTenant;

    protected $table = 'reaksi';

    protected $fillable = ['tenant_id', 'karya_id', 'keanggotaan_id', 'jenis'];

    // Pilihan tetap — BUKAN kolom bebas (PRD 6.6). Ditegakkan di
    // controller lewat validasi `in:`, kolomnya sendiri cuma string pendek.
    public const JENIS_TERSEDIA = ['suka', 'keren', 'lucu', 'kreatif'];

    public function karya(): BelongsTo
    {
        return $this->belongsTo(Karya::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Keanggotaan::class, 'keanggotaan_id');
    }
}
