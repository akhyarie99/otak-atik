<?php

use App\Http\Controllers\Api\KaryaController;
use Illuminate\Support\Facades\Route;

// Sinkron karya editor <-> server (milestone 4.3). AktifkanTenantDariToken
// memetakan token Sanctum ke keanggotaan yang benar — lihat catatan di
// middleware itu, ini padanan AktifkanTenant untuk klien API/token.
Route::middleware(['auth:sanctum', \App\Http\Middleware\AktifkanTenantDariToken::class])->prefix('karya')->group(function () {
    Route::get('/mutakhir', [KaryaController::class, 'tampilkan'])->name('api.karya.tampilkan');
    Route::put('/mutakhir', [KaryaController::class, 'simpan'])->name('api.karya.simpan');
    Route::get('/mutakhir/versi', [KaryaController::class, 'versi'])->name('api.karya.versi');
    Route::post('/mutakhir/versi/{versi}/pulihkan', [KaryaController::class, 'pulihkan'])->name('api.karya.pulihkan');
});
