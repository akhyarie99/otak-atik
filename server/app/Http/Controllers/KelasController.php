<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Support\KodeGenerator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KelasController extends Controller
{
    public function index(Request $request): Response
    {
        // Kelola kelas = tindakan staf sekolah, bukan siswa atau orang
        // tua (PRD lampiran — orang tua "hanya anaknya sendiri").
        abort_unless($request->attributes->get('keanggotaan_aktif')->peran->bolehKelolaSekolah(), 403);

        // Sengaja tidak menulis tenant_id manual di sini — TenantScope
        // (BelongsToTenant) yang menyaring otomatis lewat TenantContext
        // aktif punya request ini (aturan tetap #5).
        $kelas = Kelas::withCount('anggota')->latest()->get();

        return Inertia::render('Kelas/Index', ['kelas' => $kelas]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $keanggotaan = $request->attributes->get('keanggotaan_aktif');
        abort_unless($keanggotaan->peran->bolehKelolaSekolah(), 403);

        $sekolah = $keanggotaan->sekolah;

        // Langganan hanya-baca (milestone 7.2, PRD 9.4) — dicek LEBIH
        // DULU daripada kuota: pesannya beda ("langganan habis" vs
        // "kuota tercapai"), guru perlu tahu yang mana.
        if ($sekolah->hanyaBaca()) {
            return back()->withErrors([
                'kuota' => 'Langganan sekolah ini sudah berakhir (mode hanya-baca) — kelas dan karya lama tetap bisa dipakai, tapi tidak bisa menambah kelas baru. Perpanjang dulu.',
            ]);
        }

        // Kuota kelas (milestone 7.1, PRD 9.3) — "yang diblokir hanya
        // penambahan baru", jadi ditolak di SINI (sebelum kelas baru
        // dibuat), bukan mengunci kelas yang sudah ada.
        if (! $sekolah->bolehTambahKelas()) {
            return back()->withErrors([
                'kuota' => "Paket {$sekolah->paket->label()} sudah mencapai batas {$sekolah->batasKelas()} kelas. Hubungi admin untuk meningkatkan paket.",
            ]);
        }

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'tahun_ajaran' => ['required', 'string', 'max:9'],
        ]);

        do {
            $kode = KodeGenerator::acak(6);
        } while (Kelas::withoutGlobalScopes()->where('kode_kelas', $kode)->exists());

        Kelas::create([...$data, 'kode_kelas' => $kode]);

        return redirect()->route('kelas.index');
    }
}
