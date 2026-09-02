<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MisiPercobaan;
use Illuminate\Http\Request;

// Dipanggil editor setiap kali siswa menekan "Periksa misi" (lulus atau
// tidak) — dasar papan progres & ekspor nilai guru (milestone 4.4).
// Sengaja tidak menolak kalau gagal (siswa boleh berlatih tanpa akun
// tersambung, mis. tamu) — endpoint ini hanya dipanggil kalau editor
// sedang tersambung ke sesi (lihat sinkronAwan.js).
class MisiPercobaanController extends Controller
{
    public function catat(Request $request)
    {
        $data = $request->validate([
            'misi_id' => ['required', 'string'],
            'lulus' => ['required', 'boolean'],
        ]);

        $keanggotaan = $request->attributes->get('keanggotaan_aktif');

        MisiPercobaan::create([
            'keanggotaan_id' => $keanggotaan->id,
            'misi_id' => $data['misi_id'],
            'lulus' => $data['lulus'],
        ]);

        return response()->noContent();
    }
}
