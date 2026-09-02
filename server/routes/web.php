<?php

use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Auth\SiswaAuthController;
use App\Http\Controllers\ImportSiswaController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\LkpdController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgresController;
use App\Http\Controllers\SekolahController;
use App\Http\Controllers\TugasController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/masuk-siswa', [SiswaAuthController::class, 'tampilkanForm'])->middleware('guest')->name('siswa.login.form');
// PIN 4 digit sengaja di-hash murah (lihat User::casts) — throttle di
// sini yang jadi pertahanan utama terhadap tebak-PIN, bukan cost hash.
Route::post('/masuk-siswa', [SiswaAuthController::class, 'login'])->middleware(['guest', 'throttle:6,1'])->name('siswa.login');

// LKPD publik dengan sengaja — bahan ajar, bukan data siswa (PRD 8),
// jadi guru bisa cetak/bagikan tautannya tanpa harus login dulu.
Route::get('/lkpd/{misiId}', [LkpdController::class, 'tampilkan'])->name('lkpd.tampilkan');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/sekolah', [SekolahController::class, 'buat'])->name('sekolah.buat');
    Route::get('/pilih-sekolah', [SekolahController::class, 'pilih'])->name('sekolah.pilih');
    Route::post('/pilih-sekolah', [SekolahController::class, 'aktifkanPilihan'])->name('sekolah.pilih.aktifkan');

    Route::resource('kelas', KelasController::class)->only(['index', 'store']);
    Route::post('/kelas/{kelas}/impor/pratinjau', [ImportSiswaController::class, 'pratinjau'])->name('kelas.impor.pratinjau');
    Route::post('/kelas/{kelas}/impor/proses', [ImportSiswaController::class, 'proses'])->name('kelas.impor.proses');

    Route::get('/editor', function () {
        return Inertia::render('Editor', ['editorUrl' => config('app.editor_url')]);
    })->name('editor');
    Route::post('/api-token', [TokenController::class, 'terbitkan'])->name('api-token');

    Route::resource('tugas', TugasController::class)->only(['index', 'store']);
    Route::get('/kelas/{kelas}/progres', [ProgresController::class, 'tampilkan'])->name('progres.tampilkan');
    Route::get('/kelas/{kelas}/progres/csv', [ProgresController::class, 'eksporCsv'])->name('progres.csv');
});

require __DIR__.'/auth.php';
