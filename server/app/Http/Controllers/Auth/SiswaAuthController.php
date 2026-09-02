<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Peran;
use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

// Siswa masuk lewat kode kelas + nama panggilan + PIN 4 angka (PRD
// 6.8) — "anak kelas 1 tidak boleh dipaksa mengetik alamat surel".
class SiswaAuthController extends Controller
{
    public function tampilkanForm(): Response
    {
        return Inertia::render('Auth/LoginSiswa');
    }

    public function login(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'kode_kelas' => ['required', 'string'],
            'nama_panggilan' => ['required', 'string'],
            'pin' => ['required', 'digits:4'],
        ]);

        // Kode kelas unik lintas sekolah, jadi pencarian tahap ini
        // SENGAJA melewati TenantScope — belum ada tenant yang bisa
        // diaktifkan sebelum kelasnya sendiri ditemukan. Begitu
        // ketemu, konteksnya langsung diisi sebelum kueri berikutnya
        // (pencarian siswa), yang tetap wajib tersaring seperti biasa.
        $kelas = Kelas::withoutGlobalScopes()->where('kode_kelas', strtoupper($data['kode_kelas']))->first();

        if (! $kelas) {
            throw ValidationException::withMessages(['kode_kelas' => 'Kode kelas tidak ditemukan. Tanyakan ke gurumu.']);
        }

        app(TenantContext::class)->aktifkan($kelas->tenant_id);

        $keanggotaan = $kelas->anggota()
            ->where('peran', Peran::Siswa->value)
            ->whereHas('user', fn ($q) => $q->where('nama_panggilan', $data['nama_panggilan']))
            ->with('user')
            ->get()
            ->first(fn ($k) => $k->user && $k->user->pin_hash && Hash::check($data['pin'], $k->user->pin_hash));

        if (! $keanggotaan) {
            throw ValidationException::withMessages(['pin' => 'Nama atau PIN belum cocok. Coba lagi, atau minta gurumu mereset PIN.']);
        }

        Auth::login($keanggotaan->user);
        $request->session()->regenerate();
        $request->session()->put('keanggotaan_aktif_id', $keanggotaan->id);

        return redirect()->route('dashboard');
    }
}
