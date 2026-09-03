<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Models\Karya;
use App\Models\Keanggotaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

// Galeri kelas & sekolah — milestone 5.1 (PRD 6.6): "Hadiah terkuat
// adalah karyanya dimainkan orang lain." Tidak ada kolom komentar bebas
// di mana pun di sini — hanya reaksi terpilih (lihat ReaksiController).
class GaleriController extends Controller
{
    private function keanggotaanAktif(Request $request): Keanggotaan
    {
        return $request->attributes->get('keanggotaan_aktif');
    }

    private function karyaTerlihat()
    {
        return Karya::where('status_publikasi', '!=', 'privat')->where('disembunyikan_oleh_guru', false);
    }

    private function format($karya): array
    {
        return $karya->map(function (Karya $k) {
            // Rantai penuh sampai karya asal — bukan cuma induk langsung
            // — supaya atribusi "dibuat dari karya X oleh Y" tetap benar
            // walau sudah remix-dari-remix beberapa lapis (PRD 6.6).
            $rantai = $k->remix_dari_karya_id
                ? $k->rantaiRemix()->map(fn (Karya $r) => [
                    'id' => $r->id,
                    'judul' => $r->judul,
                    'pembuat' => $r->keanggotaan->user->nama_panggilan ?? $r->keanggotaan->user->name,
                ])->values()->all()
                : null;

            return [
                'id' => $k->id,
                'judul' => $k->judul,
                'pembuat' => $k->keanggotaan->user->nama_panggilan ?? $k->keanggotaan->user->name,
                'status_publikasi' => $k->status_publikasi,
                'dipublikasikan_pada' => $k->dipublikasikan_pada?->toIso8601String(),
                'jumlah_reaksi' => $k->reaksi_count ?? $k->reaksi()->count(),
                'remix_dari' => $k->remix_dari_karya_id,
                'rantai_remix' => $rantai,
            ];
        })->values()->all();
    }

    public function index(Request $request): Response
    {
        $keanggotaan = $this->keanggotaanAktif($request);
        // Galeri untuk siswa (karyanya sendiri + teman) dan staf
        // (pandangan moderasi) — BUKAN untuk orang tua. Permission
        // matrix PRD: kolom "orang tua" kosong untuk "melihat karya
        // sekelas"; orang tua punya halamannya sendiri (/orang-tua/progres).
        abort_if($keanggotaan->peran === Peran::OrangTua, 403);
        $isGuru = $keanggotaan->peran->bolehKelolaSekolah();

        $queryKelas = $this->karyaTerlihat();
        if ($isGuru) {
            // Guru di MVP ini mengelola seluruh sekolah (belum ada
            // pivot guru↔kelas — lihat ProgresController), jadi tab
            // "kelas" baginya adalah pandangan moderasi semua kelas di
            // tenant-nya, bukan cuma satu kelas seperti siswa.
            $queryKelas->where('status_publikasi', 'kelas');
        } else {
            // "Teman sekelas" = anggota kelas yang sama dengan
            // keanggotaan siswa yang sedang aktif.
            $kelasIds = $keanggotaan->kelas()->pluck('kelas.id');
            $temanSekelasIds = DB::table('kelas_anggota')->whereIn('kelas_id', $kelasIds)->pluck('keanggotaan_id');
            $queryKelas->whereIn('keanggotaan_id', $temanSekelasIds);
        }

        $galeriKelas = $queryKelas
            ->with('keanggotaan.user')
            ->withCount('reaksi')
            ->latest('dipublikasikan_pada')
            ->get();

        $galeriSekolah = $this->karyaTerlihat()
            ->where('status_publikasi', 'sekolah')
            ->with('keanggotaan.user')
            ->withCount('reaksi')
            ->latest('dipublikasikan_pada')
            ->get();

        $karyaSaya = Karya::where('keanggotaan_id', $keanggotaan->id)->get();

        return Inertia::render('Galeri/Index', [
            'galeriKelas' => $this->format($galeriKelas),
            'galeriSekolah' => $this->format($galeriSekolah),
            'karyaSaya' => $this->format($karyaSaya),
            'isGuru' => $isGuru,
        ]);
    }

    public function mainkan(Request $request, Karya $karya): View
    {
        // Karya sudah tersaring tenant lewat TenantScope pada route model
        // binding (aturan tetap #5) — di sini tinggal cek boleh dilihat:
        // milik sendiri, atau memang sudah diterbitkan & tidak disembunyikan.
        $keanggotaan = $request->attributes->get('keanggotaan_aktif');
        $pemilik = $keanggotaan && $karya->keanggotaan_id === $keanggotaan->id;
        abort_unless($pemilik || $karya->terlihat(), 404);

        // SATU mesin yang sama dengan editor & ekspor (aturan tetap #6) —
        // dibundel dari paket/runtime lewat pemutar/build.mjs, dibaca apa
        // adanya di sini, bukan disalin ulang.
        $motorJs = file_get_contents(base_path('../pemutar/motor.min.js'));

        return view('mainkan', [
            'judul' => $karya->judul,
            'motorJs' => $motorJs,
            'programJson' => json_encode($karya->project_json),
        ]);
    }

    // Salin karya teman untuk dimodifikasi, atribusi otomatis ke pembuat
    // asli lewat remix_dari_karya_id (milestone 5.2, PRD 6.6). Hasil
    // remix mulai privat — anak boleh utak-atik dulu sebelum menerbitkan.
    public function remix(Request $request, Karya $karya): \Illuminate\Http\RedirectResponse
    {
        abort_unless($karya->terlihat(), 404);

        $keanggotaan = $this->keanggotaanAktif($request);

        $remix = Karya::create([
            'keanggotaan_id' => $keanggotaan->id,
            'judul' => $karya->judul,
            'project_json' => $karya->project_json,
            'client_updated_at' => now(),
            'status_publikasi' => 'privat',
            'remix_dari_karya_id' => $karya->id,
        ]);

        // client_updated_at terbaru => editor (yang selalu memuat karya
        // TERBARU milik keanggotaan aktif, lihat KaryaController) langsung
        // membuka hasil remix ini saat dibuka berikutnya.
        return redirect()->route('editor');
    }

    public function terbitkan(Request $request, Karya $karya): \Illuminate\Http\RedirectResponse
    {
        $keanggotaan = $this->keanggotaanAktif($request);
        abort_unless($karya->keanggotaan_id === $keanggotaan->id, 403);

        $data = $request->validate(['status' => ['required', 'in:privat,kelas']]);

        $karya->update([
            'status_publikasi' => $data['status'],
            'dipublikasikan_pada' => $data['status'] === 'privat' ? null : ($karya->dipublikasikan_pada ?? now()),
        ]);

        return back();
    }

    // Ke galeri sekolah perlu persetujuan guru (PRD lampiran, catatan △)
    // — makanya jalur terpisah dari terbitkan() milik siswa sendiri.
    public function promosikanSekolah(Request $request, Karya $karya): \Illuminate\Http\RedirectResponse
    {
        $keanggotaan = $this->keanggotaanAktif($request);
        abort_unless($keanggotaan->peran->bolehKelolaSekolah(), 403);
        abort_if($karya->status_publikasi === 'privat', 422, 'Karya harus diterbitkan ke kelas lebih dulu.');

        $karya->update(['status_publikasi' => 'sekolah']);

        return back();
    }

    public function sembunyikan(Request $request, Karya $karya): \Illuminate\Http\RedirectResponse
    {
        $keanggotaan = $this->keanggotaanAktif($request);
        abort_unless($keanggotaan->peran->bolehKelolaSekolah(), 403);

        // Guru cuma bisa MENYEMBUNYIKAN, bukan mengubah isi karya —
        // "Karya adalah milik anak" (CLAUDE.md, privasi anak).
        $karya->update(['disembunyikan_oleh_guru' => true]);

        return back();
    }

    public function tampilkanKembali(Request $request, Karya $karya): \Illuminate\Http\RedirectResponse
    {
        $keanggotaan = $this->keanggotaanAktif($request);
        abort_unless($keanggotaan->peran->bolehKelolaSekolah(), 403);

        $karya->update(['disembunyikan_oleh_guru' => false]);

        return back();
    }
}
