<?php

namespace App\Models;

use App\Enums\Peran;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Peran melekat di sini, bukan di User (PRD 6.8). Satu akun (User) bisa
// punya banyak baris Keanggotaan dengan peran & sekolah berbeda-beda.
class Keanggotaan extends Model
{
    /** @use HasFactory<\Database\Factories\KeanggotaanFactory> */
    use HasFactory;

    protected $table = 'keanggotaan';

    protected $fillable = ['user_id', 'sekolah_id', 'peran', 'aktif'];

    protected $casts = [
        'peran' => Peran::class,
        'aktif' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kelas_anggota');
    }
}
