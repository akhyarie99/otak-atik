<?php

namespace App\Http\Controllers;

use App\Models\Karya;
use App\Models\Reaksi;
use Illuminate\Http\Request;

// Reaksi terpilih ke karya teman (PRD 6.6) — SENGAJA bukan kolom
// komentar bebas, untuk menghindari perundungan & beban moderasi.
// Satu reaksi per anak per karya (unique index) — mengirim lagi
// menggantikan jenis reaksi sebelumnya, bukan menumpuk baris baru.
class ReaksiController extends Controller
{
    public function simpan(Request $request, Karya $karya): \Illuminate\Http\RedirectResponse
    {
        $keanggotaan = $request->attributes->get('keanggotaan_aktif');
        abort_unless($karya->terlihat() || $karya->keanggotaan_id === $keanggotaan->id, 404);

        $data = $request->validate([
            'jenis' => ['required', 'in:'.implode(',', Reaksi::JENIS_TERSEDIA)],
        ]);

        Reaksi::updateOrCreate(
            ['karya_id' => $karya->id, 'keanggotaan_id' => $keanggotaan->id],
            ['jenis' => $data['jenis']]
        );

        return back();
    }

    public function hapus(Request $request, Karya $karya): \Illuminate\Http\RedirectResponse
    {
        $keanggotaan = $request->attributes->get('keanggotaan_aktif');

        Reaksi::where('karya_id', $karya->id)->where('keanggotaan_id', $keanggotaan->id)->delete();

        return back();
    }
}
