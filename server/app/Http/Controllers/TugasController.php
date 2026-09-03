<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Tugas;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TugasController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->attributes->get('keanggotaan_aktif')->peran->bolehKelolaSekolah(), 403);

        $kelas = Kelas::orderBy('nama')->get(['id', 'nama']);
        $tugas = Tugas::with('kelas:id,nama')->latest()->get();

        return Inertia::render('Tugas/Index', [
            'kelas' => $kelas,
            'tugas' => $tugas,
            'daftarMisi' => config('misi.tingkat_2'),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless($request->attributes->get('keanggotaan_aktif')->peran->bolehKelolaSekolah(), 403);

        $data = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'misi_id' => ['required', 'string'],
            'tenggat' => ['nullable', 'date'],
        ]);

        Tugas::create([
            ...$data,
            'diberikan_oleh' => $request->attributes->get('keanggotaan_aktif')->id,
            'tingkat' => 2,
        ]);

        return redirect()->route('tugas.index');
    }
}
