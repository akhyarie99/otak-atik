<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karya extends Model
{
    /** @use HasFactory<\Database\Factories\KaryaFactory> */
    use HasFactory, BelongsToTenant;

    protected $table = 'karya';

    protected $fillable = ['tenant_id', 'keanggotaan_id', 'judul', 'project_json', 'client_updated_at'];

    protected $casts = [
        'project_json' => 'array',
        'client_updated_at' => 'datetime',
    ];

    public function keanggotaan(): BelongsTo
    {
        return $this->belongsTo(Keanggotaan::class);
    }

    public function versi(): HasMany
    {
        return $this->hasMany(KaryaVersi::class)->latest('client_updated_at');
    }
}
