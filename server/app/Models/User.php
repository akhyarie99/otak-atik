<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    // Akun (identitas) — peran & sekolah ada di Keanggotaan, bukan di
    // sini (PRD 6.8). Satu akun bisa punya banyak keanggotaan.
    public function keanggotaan(): HasMany
    {
        return $this->hasMany(Keanggotaan::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'nama_panggilan',
        'pin_hash',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'pin_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // pin_hash SENGAJA tidak pakai cast 'hashed' (yang otomatis
            // pakai cost bcrypt penuh, ~250ms/hash) — PIN cuma 4 digit,
            // ruang kuncinya kecil, cost tinggi tidak menambah keamanan
            // berarti (lihat ImportSiswaController) tapi membuat impor
            // ratusan siswa jadi lambat sekali. Perlindungan sungguhan
            // ada di pembatasan percobaan login (throttle), bukan cost
            // hash. Di-hash manual dengan cost rendah di tempat PIN dibuat.
        ];
    }
}
