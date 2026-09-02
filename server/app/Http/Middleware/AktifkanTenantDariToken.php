<?php

namespace App\Http\Middleware;

use App\Models\Keanggotaan;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Padanan AktifkanTenant (routes/web.php, berbasis sesi) untuk rute API
// bertoken Sanctum: token editor "tahu" keanggotaan mana yang diwakilinya
// lewat namanya sendiri (lihat TokenController), bukan lewat sesi PHP —
// wajar untuk klien yang berdiri sendiri (editor Vite terpisah dari
// Inertia). Sama seperti versi sesi: gagal tertutup kalau token tidak
// bisa dipetakan ke keanggotaan yang valid (aturan tetap #5).
class AktifkanTenantDariToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();
        $keanggotaanId = $token && preg_match('/^karya-sync:keanggotaan:(\d+)$/', $token->name, $m) ? (int) $m[1] : null;

        $keanggotaan = $keanggotaanId
            ? Keanggotaan::where('id', $keanggotaanId)->where('user_id', $request->user()->id)->first()
            : null;

        if ($keanggotaan) {
            app(TenantContext::class)->aktifkan($keanggotaan->sekolah_id);
            $request->attributes->set('keanggotaan_aktif', $keanggotaan);
        }

        return $next($request);
    }
}
