<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Tenant. Sekolah TIDAK memakai BelongsToTenant — tabel ini sendiri
// yang jadi acuan tenant_id bagi model lain, bukan pemilik tenant_id.
class Sekolah extends Model
{
    /** @use HasFactory<\Database\Factories\SekolahFactory> */
    use HasFactory;

    protected $table = 'sekolah';

    protected $fillable = ['nama', 'kode_sekolah'];

    public function keanggotaan(): HasMany
    {
        return $this->hasMany(Keanggotaan::class);
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'tenant_id');
    }
}
