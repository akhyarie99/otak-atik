<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\MisiPercobaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Inertia\Inertia;
use Inertia\Response;

// Papan progres per siswa & per misi (PRD 6.7) — "Guru bisa melihat
// progres per siswa dan per misi, mana yang tuntas, mana yang macet, di
// misi mana kelas paling banyak tersendat." dan (PRD 6.4) "Guru bisa
// melihat percobaan ke berapa anak berhasil — data ini lebih berguna
// daripada nilai akhir."
class ProgresController extends Controller
{
    private function susunProgres(Kelas $kelas): array
    {
        $siswaList = $kelas->anggota()->with('user')->where('peran', 'siswa')->get();
        $daftarMisi = collect(config('misi.tingkat_2'));

        $percobaan = MisiPercobaan::whereIn('keanggotaan_id', $siswaList->pluck('id'))
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($p) => $p->keanggotaan_id.'|'.$p->misi_id);

        $baris = $siswaList->map(function ($siswa) use ($daftarMisi, $percobaan) {
            $perMisi = $daftarMisi->map(function ($misi) use ($siswa, $percobaan) {
                $log = $percobaan->get($siswa->id.'|'.$misi['id'], collect());
                $indeksLulus = $log->search(fn ($p) => $p->lulus);

                return [
                    'misi_id' => $misi['id'],
                    'jumlah_percobaan' => $log->count(),
                    'lulus' => $indeksLulus !== false,
                    'percobaan_ke' => $indeksLulus !== false ? $indeksLulus + 1 : null,
                ];
            });

            return [
                'keanggotaan_id' => $siswa->id,
                'nama_panggilan' => $siswa->user->nama_panggilan ?? $siswa->user->name,
                'misi' => $perMisi,
            ];
        });

        return ['siswa' => $baris, 'daftarMisi' => $daftarMisi->values()];
    }

    public function tampilkan(Kelas $kelas): Response
    {
        return Inertia::render('Progres/Index', [
            'kelas' => $kelas->only('id', 'nama'),
            ...$this->susunProgres($kelas),
        ]);
    }

    public function eksporCsv(Kelas $kelas)
    {
        $data = $this->susunProgres($kelas);
        $namaMisi = $data['daftarMisi']->pluck('judul');

        $baris = [];
        $baris[] = array_merge(['Nama panggilan'], $namaMisi->all());
        foreach ($data['siswa'] as $siswa) {
            $kolom = [$siswa['nama_panggilan']];
            foreach ($siswa['misi'] as $m) {
                $kolom[] = $m['lulus'] ? "Lulus (percobaan ke-{$m['percobaan_ke']})" : ($m['jumlah_percobaan'] > 0 ? 'Mencoba, belum lulus' : 'Belum dicoba');
            }
            $baris[] = $kolom;
        }

        $csv = implode("\r\n", array_map(
            fn ($kolom) => implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', $v).'"', $kolom)),
            $baris
        ));

        $namaBerkas = 'nilai-'.str($kelas->nama)->slug().'.csv';

        return ResponseFacade::make("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$namaBerkas}\"",
        ]);
    }
}
