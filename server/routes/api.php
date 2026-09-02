<?php

use App\Http\Controllers\Api\KaryaController;
use App\Http\Controllers\Api\MisiPercobaanController;
use Illuminate\Support\Facades\Route;

// Sinkron karya editor <-> server (milestone 4.3). AktifkanTenantDariToken
// memetakan token Sanctum ke keanggotaan yang benar — lihat catatan di
// middleware itu, ini padanan AktifkanTenant untuk klien API/token.
Route::middleware(['auth:sanctum', \App\Http\Middleware\AktifkanTenantDariToken::class])->group(function () {
    Route::prefix('karya')->group(function () {
        Route::get('/mutakhir', [KaryaController::class, 'tampilkan'])->name('api.karya.tampilkan');
        Route::put('/mutakhir', [KaryaController::class, 'simpan'])->name('api.karya.simpan');
        Route::get('/mutakhir/versi', [KaryaController::class, 'versi'])->name('api.karya.versi');
        Route::post('/mutakhir/versi/{versi}/pulihkan', [KaryaController::class, 'pulihkan'])->name('api.karya.pulihkan');
    });

    // Milestone 4.4 — dasar papan progres & ekspor nilai guru.
    Route::post('/misi/percobaan', [MisiPercobaanController::class, 'catat'])->name('api.misi.catat');
});
