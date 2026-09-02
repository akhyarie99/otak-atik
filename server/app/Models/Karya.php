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

    protected $fillable = [
        'tenant_id', 'keanggotaan_id', 'judul', 'project_json', 'client_updated_at',
        'status_publikasi', 'disembunyikan_oleh_guru', 'dipublikasikan_pada', 'remix_dari_karya_id',
    ];

    protected $casts = [
        'project_json' => 'array',
        'client_updated_at' => 'datetime',
        'disembunyikan_oleh_guru' => 'boolean',
        'dipublikasikan_pada' => 'datetime',
    ];

    public function keanggotaan(): BelongsTo
    {
        return $this->belongsTo(Keanggotaan::class);
    }

    public function versi(): HasMany
    {
        return $this->hasMany(KaryaVersi::class)->latest('client_updated_at');
    }

    public function remixDari(): BelongsTo
    {
        return $this->belongsTo(Karya::class, 'remix_dari_karya_id');
    }

    public function remixTurunan(): HasMany
    {
        return $this->hasMany(Karya::class, 'remix_dari_karya_id');
    }

    public function reaksi(): HasMany
    {
        return $this->hasMany(Reaksi::class);
    }

    // Terlihat teman sekelas/sekolah hanya bila dipublikasikan DAN guru
    // belum menyembunyikannya — "guru bisa menyembunyikan, tidak bisa
    // mengubah" (CLAUDE.md, privasi anak).
    public function terlihat(): bool
    {
        return $this->status_publikasi !== 'privat' && ! $this->disembunyikan_oleh_guru;
    }
}
