<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Models\Keanggotaan;
use App\Models\Undangan;
use App\Services\FonnteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

// Undangan orang tua — milestone 5.3 (PRD 6.8): "Orang tua / wali —
// Undangan dari sekolah." Guru membuat tautan bertoken untuk SATU
// siswa; orang tua yang membukanya (setelah masuk/daftar) mendapat
// keanggotaan baru berperan orang_tua yang tertaut ke anak itu saja.
class UndanganController extends Controller
{
    // Keanggotaan TIDAK memakai TenantScope (lihat model-nya) — route
    // model binding {siswa} di sini karena itu tidak otomatis tersaring
    // tenant, jadi pengecekan sekolah dilakukan tangan di bawah, sama
    // seperti KaryaController::pulihkan() untuk KaryaVersi.
    public function store(Request $request, Keanggotaan $siswa): RedirectResponse
    {
        $guru = $request->attributes->get('keanggotaan_aktif');
        abort_unless($guru->peran->bolehKelolaSekolah(), 403);
        abort_unless($siswa->peran === Peran::Siswa && $siswa->sekolah_id === $guru->sekolah_id, 404);

        $data = $request->validate([
            'nomor_whatsapp' => ['nullable', 'string', 'max:30'],
        ]);

        $undangan = Undangan::create([
            'tenant_id' => $guru->sekolah_id,
            'siswa_keanggotaan_id' => $siswa->id,
            'dibuat_oleh' => $guru->id,
            'token' => Str::random(32),
            'nomor_whatsapp' => $data['nomor_whatsapp'] ?? null,
            'kadaluarsa_pada' => now()->addDays(7),
        ]);

        $tautan = route('undangan.tampilkan', $undangan->token);

        // Kalau nomor diisi, coba kirim lewat WhatsApp (no-op aman kalau
        // Fonnte belum dikonfigurasi — lihat FonnteService). Guru tetap
        // melihat tautannya sendiri di bawah, jadi bisa dibagikan
        // manual apa pun hasil pengirimannya.
        if (! empty($data['nomor_whatsapp'])) {
            $namaSiswa = $siswa->user->nama_panggilan ?? $siswa->user->name;
            app(FonnteService::class)->kirim(
                $data['nomor_whatsapp'],
                "Sekolah mengundang Anda memantau progres belajar {$namaSiswa} di Otak-atik: {$tautan}"
            );
        }

        return back()->with('tautanUndangan', $tautan);
    }

    public function tampilkan(string $token): Response
    {
        $undangan = Undangan::withoutGlobalScopes()->where('token', $token)
            ->with(['siswa.user', 'sekolah'])->first();

        return Inertia::render('Undangan/Tampilkan', [
            'token' => $token,
            'berlaku' => $undangan?->berlaku() ?? false,
            'namaSiswa' => $undangan ? ($undangan->siswa->user->nama_panggilan ?? $undangan->siswa->user->name) : null,
            'namaSekolah' => $undangan?->sekolah?->nama,
        ]);
    }

    // Sengaja GET (bukan POST) dan dibungkus middleware auth: pengunjung
    // yang belum masuk otomatis dialihkan ke /login (URL ini tersimpan
    // sebagai "intended"), lalu balik lagi ke sini persis setelah masuk
    // ATAU daftar baru (lihat RegisteredUserController) — tanpa itu,
    // token undangan bisa hilang di tengah alur masuk/daftar.
    public function terima(Request $request, string $token): RedirectResponse
    {
        $undangan = Undangan::withoutGlobalScopes()->where('token', $token)->first();
        abort_if(! $undangan || ! $undangan->berlaku(), 410, 'Undangan ini sudah tidak berlaku.');

        $user = $request->user();
        $sudahTertaut = Keanggotaan::where('user_id', $user->id)
            ->where('anak_keanggotaan_id', $undangan->siswa_keanggotaan_id)
            ->exists();

        if (! $sudahTertaut) {
            $keanggotaan = Keanggotaan::create([
                'user_id' => $user->id,
                'sekolah_id' => $undangan->tenant_id,
                'peran' => Peran::OrangTua,
                'aktif' => true,
                'anak_keanggotaan_id' => $undangan->siswa_keanggotaan_id,
            ]);
            $request->session()->put('keanggotaan_aktif_id', $keanggotaan->id);
        }

        $undangan->update(['dipakai_pada' => now()]);

        return redirect()->route('dashboard')
            ->with('status', 'Undangan diterima — sekarang kamu bisa melihat progres belajar anakmu.');
    }
}
