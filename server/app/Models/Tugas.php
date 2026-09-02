<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tugas extends Model
{
    /** @use HasFactory<\Database\Factories\TugasFactory> */
    use HasFactory, BelongsToTenant;

    protected $table = 'tugas';

    protected $fillable = ['tenant_id', 'kelas_id', 'diberikan_oleh', 'misi_id', 'tingkat', 'tenggat'];

    protected $casts = [
        'tenggat' => 'datetime',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Keanggotaan::class, 'diberikan_oleh');
    }
}
