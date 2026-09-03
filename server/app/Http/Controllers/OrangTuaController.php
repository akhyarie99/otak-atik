<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Models\Keanggotaan;
use App\Models\MisiPercobaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// Pandangan orang tua — milestone 5.3 (PRD 6.8 & lampiran): "Orang tua
// tidak bisa melihat siswa lain, termasuk peringkat atau perbandingan.
// Hanya anaknya sendiri." keanggotaan_aktif DI SINI adalah keanggotaan
// orang_tua-nya sendiri (dipilih lewat /pilih-sekolah kalau orang tua
// itu tertaut ke lebih dari satu anak — pola yang sama dipakai guru
// multi-sekolah, tidak perlu mekanisme baru).
class OrangTuaController extends Controller
{
    private function anakDariKeanggotaanAktif(Request $request): Keanggotaan
    {
        $keanggotaan = $request->attributes->get('keanggotaan_aktif');
        abort_unless($keanggotaan->peran === Peran::OrangTua, 403);
        abort_unless($keanggotaan->anak_keanggotaan_id, 404);

        // Keanggotaan tidak memakai TenantScope — verifikasi tangan ini
        // yang mencegah anak dari sekolah lain terlihat lewat ID
        // langsung sekalipun (aturan tetap #5, jalur non-Eloquent-scope).
        $anak = Keanggotaan::with('user')->findOrFail($keanggotaan->anak_keanggotaan_id);
        abort_unless($anak->sekolah_id === $keanggotaan->sekolah_id, 404);

        return $anak;
    }

    public function progres(Request $request): Response
    {
        $anak = $this->anakDariKeanggotaanAktif($request);

        $daftarMisi = collect(config('misi.tingkat_2'));
        $percobaan = MisiPercobaan::where('keanggotaan_id', $anak->id)->orderBy('created_at')->get();

        $misi = $daftarMisi->map(function ($m) use ($percobaan) {
            $log = $percobaan->where('misi_id', $m['id'])->values();
            $indeksLulus = $log->search(fn ($p) => $p->lulus);

            return [
                'misi_id' => $m['id'],
                'judul' => $m['judul'],
                'jumlah_percobaan' => $log->count(),
                'lulus' => $indeksLulus !== false,
                'percobaan_ke' => $indeksLulus !== false ? $indeksLulus + 1 : null,
            ];
        });

        return Inertia::render('OrangTua/Progres', [
            'anak' => ['nama_panggilan' => $anak->user->nama_panggilan ?? $anak->user->name],
            'misi' => $misi->values(),
            'izinPublikasiLuarSekolah' => $anak->izin_publikasi_luar_sekolah,
        ]);
    }

    public function ubahIzinPublikasi(Request $request): RedirectResponse
    {
        $anak = $this->anakDariKeanggotaanAktif($request);

        $data = $request->validate(['izin' => ['required', 'boolean']]);
        $anak->update(['izin_publikasi_luar_sekolah' => $data['izin']]);

        return back();
    }
}
