<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Model contoh pertama yang memakai BelongsToTenant (milestone 4.1) —
// daftar siswa & impor Excel sungguhan adalah pekerjaan milestone 4.2.
class Kelas extends Model
{
    /** @use HasFactory<\Database\Factories\KelasFactory> */
    use HasFactory, BelongsToTenant;

    protected $table = 'kelas';

    protected $fillable = ['tenant_id', 'nama', 'tahun_ajaran'];

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'tenant_id');
    }
}
