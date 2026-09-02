<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

// LKPD (Lembar Kerja Peserta Didik) cetak per misi — PRD 6.7: "Bahan
// ajar per misi: tujuan pembelajaran, langkah mengajar, dan LKPD yang
// bisa dicetak. Guru yang tidak bisa coding harus tetap sanggup
// mengajar dengan ini." Halaman Blade polos (bukan Inertia) — ini
// dokumen cetak, bukan bagian alur aplikasi SPA.
class LkpdController extends Controller
{
    public function tampilkan(string $misiId): View
    {
        $misi = collect(config('misi.tingkat_2'))->firstWhere('id', $misiId);
        abort_if(! $misi, 404);

        return view('lkpd', ['misi' => $misi]);
    }
}
