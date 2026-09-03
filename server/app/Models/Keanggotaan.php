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

    protected $fillable = ['user_id', 'sekolah_id', 'peran', 'aktif', 'anak_keanggotaan_id', 'izin_publikasi_luar_sekolah'];

    protected $casts = [
        'peran' => Peran::class,
        'aktif' => 'boolean',
        'izin_publikasi_luar_sekolah' => 'boolean',
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

    // Hanya berarti untuk keanggotaan peran=orang_tua — menunjuk ke
    // keanggotaan (peran=siswa) anaknya (PRD 6.8).
    public function anak(): BelongsTo
    {
        return $this->belongsTo(Keanggotaan::class, 'anak_keanggotaan_id');
    }
}
