<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $keanggotaan = $request->attributes->get('keanggotaan_aktif');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'keanggotaanAktif' => $keanggotaan
                ? ['id' => $keanggotaan->id, 'peran' => $keanggotaan->peran->value, 'sekolah' => $keanggotaan->sekolah?->nama]
                : null,
            'flash' => [
                'status' => $request->session()->get('status'),
                'tautanUndangan' => $request->session()->get('tautanUndangan'),
            ],
        ];
    }
}
