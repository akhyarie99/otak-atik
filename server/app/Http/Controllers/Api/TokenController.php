<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Menerbitkan token Sanctum untuk editor (SPA Vite yang berdiri
// sendiri, bukan bagian dari Inertia) memakai API sinkron karya —
// dipanggil dari sesi Inertia yang sudah login, jadi otentikasinya
// tetap lewat sesi biasa (aturan tetap #5 tidak berubah: keanggotaan
// aktif saat token diminta yang menentukan tenant token ini).
class TokenController extends Controller
{
    public function terbitkan(Request $request)
    {
        $keanggotaan = $request->attributes->get('keanggotaan_aktif');
        abort_unless($keanggotaan, 422, 'Pilih sekolah aktif dulu sebelum membuka editor.');

        // Token lama untuk keanggotaan yang sama dicabut dulu — satu
        // token aktif per keanggotaan, supaya token lama yang lupa
        // dibuang tidak menumpuk selamanya.
        $namaToken = "karya-sync:keanggotaan:{$keanggotaan->id}";
        $request->user()->tokens()->where('name', $namaToken)->delete();

        $token = $request->user()->createToken($namaToken, ['karya:sinkron']);

        return response()->json(['token' => $token->plainTextToken]);
    }
}
