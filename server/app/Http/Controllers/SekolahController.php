<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Models\Keanggotaan;
use App\Models\Sekolah;
use App\Support\KodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

// Onboarding sekolah minimal — memilih paket & pembayaran adalah
// pekerjaan fase 7 (SaaS), belum di sini. Ini cukup untuk satu sekolah
// nyata mulai memakai Otak-atik (tujuan fase 4).
class SekolahController extends Controller
{
    public function buat(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $keanggotaan = DB::transaction(function () use ($data, $request) {
            $sekolah = Sekolah::create([
                'nama' => $data['nama'],
                'kode_sekolah' => $this->kodeSekolahUnik(),
            ]);

            return Keanggotaan::create([
                'user_id' => $request->user()->id,
                'sekolah_id' => $sekolah->id,
                'peran' => Peran::AdminSekolah,
                'aktif' => true,
            ]);
        });

        $request->session()->put('keanggotaan_aktif_id', $keanggotaan->id);

        return redirect()->route('dashboard');
    }

    public function pilih(Request $request): Response
    {
        $daftar = $request->user()->keanggotaan()->with('sekolah')->where('aktif', true)->get();

        return Inertia::render('Sekolah/Pilih', ['keanggotaan' => $daftar]);
    }

    public function aktifkanPilihan(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate(['keanggotaan_id' => ['required', 'integer']]);

        $milik = $request->user()->keanggotaan()->where('id', $data['keanggotaan_id'])->exists();
        abort_unless($milik, 403);

        $request->session()->put('keanggotaan_aktif_id', $data['keanggotaan_id']);

        return redirect()->route('dashboard');
    }

    private function kodeSekolahUnik(): string
    {
        do {
            $kode = KodeGenerator::acak(6);
        } while (Sekolah::where('kode_sekolah', $kode)->exists());

        return $kode;
    }
}
