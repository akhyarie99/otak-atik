<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Services\TenantContext;

// Tempel trait ini di setiap model yang menyimpan data milik sekolah
// (kelas, siswa, karya, tugas, dst — aturan tetap #5). Otomatis:
//   1. Menambahkan TenantScope ke semua kueri lewat model ini.
//   2. Mengisi tenant_id dari TenantContext aktif saat baris baru dibuat,
//      supaya tidak ada tempat penulis kode bisa lupa mengisinya sendiri.
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (! $model->isDirty('tenant_id') && ! $model->tenant_id) {
                $model->tenant_id = app(TenantContext::class)->id();
            }
        });
    }
}
