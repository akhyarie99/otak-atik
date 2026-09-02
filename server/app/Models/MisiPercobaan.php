<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MisiPercobaan extends Model
{
    /** @use HasFactory<\Database\Factories\MisiPercobaanFactory> */
    use HasFactory, BelongsToTenant;

    protected $table = 'misi_percobaan';

    protected $fillable = ['tenant_id', 'keanggotaan_id', 'misi_id', 'lulus'];

    protected $casts = [
        'lulus' => 'boolean',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Keanggotaan::class, 'keanggotaan_id');
    }
}
