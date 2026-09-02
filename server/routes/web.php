<?php

use App\Http\Controllers\Auth\SiswaAuthController;
use App\Http\Controllers\ImportSiswaController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SekolahController;
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
});

require __DIR__.'/auth.php';
