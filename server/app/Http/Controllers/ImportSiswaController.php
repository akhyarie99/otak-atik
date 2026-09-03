<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Models\Kelas;
use App\Models\Keanggotaan;
use App\Models\User;
use App\Support\KodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

// Impor daftar siswa dari Excel/CSV, dua langkah (PRD 6.8 & 9.5):
//   1. pratinjau() — baca berkas, kembalikan judul kolom + beberapa
//      baris contoh, supaya guru bisa memetakan kolom mana yang
//      berisi nama sebelum apa pun disimpan.
//   2. proses() — pakai pemetaan itu untuk benar-benar membuat akun
//      siswa (satu per baris), dengan PIN acak per anak.
//
// Data yang diimpor SENGAJA hanya nama panggilan (PRD 8: privasi anak
// seminimal mungkin) — kolom lain di berkas asal diabaikan walau ada.
class ImportSiswaController extends Controller
{
    private const MAKS_PRATINJAU = 10;

    public function pratinjau(Request $request, Kelas $kelas)
    {
        abort_unless($request->attributes->get('keanggotaan_aktif')->peran->bolehKelolaSekolah(), 403);

        $request->validate([
            'berkas' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        $token = (string) Str::uuid();
        $path = $request->file('berkas')->storeAs('impor-sementara', $token.'.'.$request->file('berkas')->extension());

        $baris = Excel::toCollection(null, Storage::path($path))->first();
        $header = $baris->first()?->map(fn ($v) => trim((string) $v))->toArray() ?? [];
        $isi = $baris->slice(1);

        return response()->json([
            'token' => $token,
            'header' => array_values($header),
            'pratinjau' => $isi->take(self::MAKS_PRATINJAU)->values()->toArray(),
            'total_baris' => $isi->count(),
        ]);
    }

    public function proses(Request $request, Kelas $kelas)
    {
        abort_unless($request->attributes->get('keanggotaan_aktif')->peran->bolehKelolaSekolah(), 403);

        $data = $request->validate([
            'token' => ['required', 'uuid'],
            'kolom_nama' => ['required', 'integer', 'min:0'],
        ]);

        $path = collect(Storage::files('impor-sementara'))
            ->first(fn ($p) => str_starts_with(basename($p), $data['token']));

        abort_unless($path, 422, 'Berkas pratinjau sudah kedaluwarsa, unggah ulang.');

        $baris = Excel::toCollection(null, Storage::path($path))->first();
        $isi = $baris->slice(1); // baris pertama = judul kolom

        $dibuat = [];

        DB::transaction(function () use ($isi, $data, $kelas, &$dibuat) {
            foreach ($isi as $baris) {
                $nama = trim((string) ($baris[$data['kolom_nama']] ?? ''));
                if ($nama === '') {
                    continue;
                }

                $pin = KodeGenerator::pin();

                $user = User::create([
                    'name' => $nama,
                    'nama_panggilan' => $nama,
                    // Cost rendah dengan sengaja — lihat catatan di User::casts().
                    'pin_hash' => Hash::make($pin, ['rounds' => 4]),
                ]);

                $keanggotaan = Keanggotaan::create([
                    'user_id' => $user->id,
                    'sekolah_id' => $kelas->tenant_id,
                    'peran' => Peran::Siswa,
                    'aktif' => true,
                ]);

                $kelas->anggota()->attach($keanggotaan->id);

                $dibuat[] = ['nama_panggilan' => $nama, 'pin' => $pin];
            }
        });

        Storage::delete($path);

        return response()->json([
            'jumlah_dibuat' => count($dibuat),
            'siswa' => $dibuat,
            'kode_kelas' => $kelas->kode_kelas,
        ]);
    }
}
