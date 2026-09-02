<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Karya;
use App\Models\KaryaVersi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Sinkron karya — milestone 4.3 (PRD 6.5): "Sinkron ke server saat ada
// koneksi. Konflik diselesaikan dengan tulisan terakhir menang, dengan
// riwayat versi yang bisa dikembalikan."
//
// "Karya" di sini SATU per keanggotaan (MVP) — cukup untuk membuktikan
// mekanisme sinkron/versi/konflik; banyak karya per siswa adalah
// perluasan lanjutan, bukan yang diuji milestone ini.
class KaryaController extends Controller
{
    private function keanggotaanId(Request $request): int
    {
        return $request->attributes->get('keanggotaan_aktif')->id;
    }

    public function tampilkan(Request $request)
    {
        $karya = Karya::where('keanggotaan_id', $this->keanggotaanId($request))->first();

        // 204 kalau belum ada karya — SENGAJA bukan response()->json(null),
        // yang di Laravel/Symfony menghasilkan body "{}" (objek kosong),
        // bukan literal "null". "{}" itu truthy di JavaScript, jadi klien
        // editor bisa salah kira karyanya sudah ada padahal belum.
        return $karya ? response()->json($this->format($karya)) : response()->noContent();
    }

    public function simpan(Request $request)
    {
        $data = $request->validate([
            'project' => ['required', 'array'],
            'client_updated_at' => ['required', 'date'],
            'judul' => ['nullable', 'string', 'max:255'],
        ]);

        $keanggotaanId = $this->keanggotaanId($request);
        $waktuKlien = Carbon::parse($data['client_updated_at']);

        $karya = DB::transaction(function () use ($request, $data, $keanggotaanId, $waktuKlien) {
            $karya = Karya::where('keanggotaan_id', $keanggotaanId)->lockForUpdate()->first();

            if (! $karya) {
                $karya = Karya::create([
                    'keanggotaan_id' => $keanggotaanId,
                    'judul' => $data['judul'] ?? 'Karyaku',
                    'project_json' => $data['project'],
                    'client_updated_at' => $waktuKlien,
                ]);
                KaryaVersi::create([
                    'karya_id' => $karya->id,
                    'project_json' => $data['project'],
                    'client_updated_at' => $waktuKlien,
                ]);

                return $karya;
            }

            // Tulisan terakhir menang: versi ini SELALU disimpan ke
            // riwayat (tidak ada yang hilang), tapi cuma jadi keadaan
            // "sekarang" kalau jamnya bukan yang paling tua.
            KaryaVersi::create([
                'karya_id' => $karya->id,
                'project_json' => $data['project'],
                'client_updated_at' => $waktuKlien,
            ]);

            if ($waktuKlien->gte($karya->client_updated_at)) {
                $karya->update([
                    'project_json' => $data['project'],
                    'client_updated_at' => $waktuKlien,
                    'judul' => $data['judul'] ?? $karya->judul,
                ]);
            }

            return $karya->fresh();
        });

        return response()->json($this->format($karya));
    }

    public function versi(Request $request)
    {
        $karya = Karya::where('keanggotaan_id', $this->keanggotaanId($request))->firstOrFail();

        return response()->json(
            $karya->versi()->get(['id', 'client_updated_at', 'created_at'])
        );
    }

    public function pulihkan(Request $request, KaryaVersi $versi)
    {
        $karya = Karya::where('keanggotaan_id', $this->keanggotaanId($request))->firstOrFail();

        // {versi} diikat implisit lewat ID saja (KaryaVersi tidak
        // tersaring TenantScope langsung) — verifikasi manual di sini
        // wajib, atau siswa sekolah lain bisa memulihkan versi yang
        // bukan miliknya lewat ID langsung (persis kasus yang dicegah
        // aturan tetap #5, cuma jalurnya beda: bukan Eloquent scope,
        // tapi pengecekan tangan di controller).
        abort_unless($versi->karya_id === $karya->id, 404);

        $karya->update([
            'project_json' => $versi->project_json,
            'client_updated_at' => now(),
        ]);
        KaryaVersi::create([
            'karya_id' => $karya->id,
            'project_json' => $versi->project_json,
            'client_updated_at' => $karya->client_updated_at,
        ]);

        return response()->json($this->format($karya->fresh()));
    }

    private function format(Karya $karya): array
    {
        return [
            'id' => $karya->id,
            'judul' => $karya->judul,
            'project' => $karya->project_json,
            'client_updated_at' => $karya->client_updated_at->toIso8601String(),
        ];
    }
}
