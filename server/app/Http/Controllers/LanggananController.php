<?php

namespace App\Http\Controllers;

use App\Models\Langganan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// Langganan — milestone 7.2 (PRD 9.1, 9.2, 9.4). Paket Guru gratis tidak
// pernah lewat sini; ini cuma untuk sekolah yang mengambil paket berbayar
// (Sekolah/Yayasan), mulai dari masa uji coba satu semester penuh.
class LanggananController extends Controller
{
    public function tampilkan(Request $request): Response
    {
        $keanggotaan = $request->attributes->get('keanggotaan_aktif');
        $sekolah = $keanggotaan->sekolah;
        $langganan = $sekolah->langgananAktif();

        return Inertia::render('Langganan/Index', [
            'paketSekarang' => $sekolah->paket->value,
            'langganan' => $langganan ? [
                'status' => $langganan->status,
                'mulai_pada' => $langganan->mulai_pada->toDateString(),
                'berakhir_pada' => $langganan->berakhir_pada->toDateString(),
            ] : null,
            'tagihan' => $sekolah->langganan()->with('tagihan')->get()->pluck('tagihan')->flatten()->sortByDesc('id')->values()->map(fn ($t) => [
                'id' => $t->id,
                'nomor_faktur' => $t->nomor_faktur,
                'jumlah_format' => $t->jumlahFormat(),
                'status' => $t->status,
                'metode' => $t->metode,
                'jatuh_tempo' => $t->jatuh_tempo->toDateString(),
                'midtrans_va_nomor' => $t->midtrans_va_nomor,
                'midtrans_bank' => $t->midtrans_bank,
            ]),
        ]);
    }

    // Mulai masa uji coba (PRD 9.4: "satu semester penuh") untuk paket
    // berbayar pilihan. BELUM menagih apa pun — trial gratis penuh dulu;
    // tagihan pertama baru muncul saat trial mendekati/lewat berakhir
    // (lihat Console/Commands/PerpanjangLangganan).
    public function mulai(Request $request): \Illuminate\Http\RedirectResponse
    {
        $keanggotaan = $request->attributes->get('keanggotaan_aktif');
        abort_unless($keanggotaan->peran->bolehKelolaSekolah(), 403);

        $data = $request->validate(['paket' => ['required', 'in:sekolah,yayasan']]);
        $sekolah = $keanggotaan->sekolah;

        abort_if($sekolah->langgananAktif()?->berfungsiPenuh(), 422, 'Sekolah ini sudah punya langganan yang berjalan.');

        Langganan::create([
            'tenant_id' => $sekolah->id,
            'paket' => $data['paket'],
            'status' => 'percobaan',
            'mulai_pada' => now()->toDateString(),
            'berakhir_pada' => now()->addMonths(6)->toDateString(), // satu semester (PRD 9.4)
        ]);
        $sekolah->update(['paket' => $data['paket']]);

        return redirect()->route('langganan.tampilkan')->with('status', 'Masa uji coba dimulai — enam bulan fungsi penuh, gratis.');
    }
}
