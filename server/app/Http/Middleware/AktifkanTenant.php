<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Mengisi TenantContext dari keanggotaan yang aktif di sesi user yang
// login. Satu akun bisa punya banyak keanggotaan (PRD 6.8) — kalau
// cuma satu, langsung dipakai; kalau lebih dari satu (mis. guru di dua
// sekolah), user harus memilih dulu lewat halaman pilih-sekolah.
class AktifkanTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $keanggotaanId = $request->session()->get('keanggotaan_aktif_id');
        $keanggotaan = $keanggotaanId
            ? $user->keanggotaan()->where('id', $keanggotaanId)->where('aktif', true)->first()
            : null;

        if (! $keanggotaan) {
            $daftar = $user->keanggotaan()->where('aktif', true)->get();

            if ($daftar->count() === 1) {
                $keanggotaan = $daftar->first();
                $request->session()->put('keanggotaan_aktif_id', $keanggotaan->id);
            } elseif ($daftar->count() > 1 && ! $request->routeIs('sekolah.pilih*')) {
                return redirect()->route('sekolah.pilih');
            }
        }

        if ($keanggotaan) {
            app(TenantContext::class)->aktifkan($keanggotaan->sekolah_id);
            $request->attributes->set('keanggotaan_aktif', $keanggotaan);
        }

        return $next($request);
    }
}
