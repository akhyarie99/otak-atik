<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\AktifkanTenant::class,
        ]);

        // AktifkanTenant WAJIB jalan sebelum SubstituteBindings — kalau
        // tidak, route model binding (mis. {kelas}) mencari modelnya
        // SEBELUM TenantContext aktif, dan TenantScope yang gagal
        // tertutup (aturan tetap #5) membuatnya selalu 404. append()
        // saja tidak menjamin urutan ini, jadi dipaksa eksplisit di sini.
        $middleware->priority([
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\AktifkanTenant::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
