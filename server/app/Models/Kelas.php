<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Kelas satu sekolah. kode_kelas dipakai siswa untuk masuk (kode kelas +
// nama panggilan + PIN, PRD 6.8) — dicetak di kartu, bukan diketik dari
// ingatan surel/kata sandi.
class Kelas extends Model
{
    /** @use HasFactory<\Database\Factories\KelasFactory> */
    use HasFactory, BelongsToTenant;

    protected $table = 'kelas';

    protected $fillable = ['tenant_id', 'nama', 'tahun_ajaran', 'kode_kelas'];

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'tenant_id');
    }

    public function anggota(): BelongsToMany
    {
        return $this->belongsToMany(Keanggotaan::class, 'kelas_anggota');
    }
}
