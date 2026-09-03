<?php

namespace App\Models;

use App\Enums\Paket;
use App\Enums\Peran;
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

    protected $fillable = ['nama', 'kode_sekolah', 'paket', 'batas_kelas', 'batas_siswa'];

    protected $casts = [
        'paket' => Paket::class,
    ];

    public function keanggotaan(): HasMany
    {
        return $this->hasMany(Keanggotaan::class);
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'tenant_id');
    }

    // --- Kuota (milestone 7.1, PRD 9.1 & 9.3) ---
    // batas_kelas/batas_siswa kolom = override manual; null = pakai
    // config/paket.php. Batas itu sendiri null = tak terbatas.

    public function batasKelas(): ?int
    {
        return $this->batas_kelas ?? config("paket.{$this->paket->value}.kelas");
    }

    public function batasSiswa(): ?int
    {
        return $this->batas_siswa ?? config("paket.{$this->paket->value}.siswa");
    }

    public function batasKaryaPerSiswa(): ?int
    {
        return config("paket.{$this->paket->value}.karya_per_siswa");
    }

    public function jumlahSiswaAktif(): int
    {
        return $this->keanggotaan()->where('peran', Peran::Siswa)->where('aktif', true)->count();
    }

    // Berapa slot siswa yang masih tersisa sebelum kuota tercapai. null =
    // tak terbatas (dipakai ImportSiswaController untuk memotong impor,
    // bukan menolak semuanya — lihat PRD 9.3: "yang diblokir hanya
    // penambahan baru", anak yang sudah ada tidak pernah terkunci).
    public function sisaSlotSiswa(): ?int
    {
        $batas = $this->batasSiswa();
        if ($batas === null) return null;

        return max(0, $batas - $this->jumlahSiswaAktif());
    }

    public function bolehTambahKelas(): bool
    {
        $batas = $this->batasKelas();

        return $batas === null || $this->kelas()->count() < $batas;
    }
}
