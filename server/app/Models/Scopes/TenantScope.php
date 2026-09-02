<?php

namespace App\Models\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

// Aturan tetap #5: setiap kueri yang menyentuh data sekolah wajib
// melewati scope tenant, dan itu diuji otomatis — bukan diandalkan pada
// kedisiplinan penulis kode. Scope ini otomatis menempel di setiap model
// yang memakai trait BelongsToTenant, termasuk saat dicari lewat ID
// langsung (find/where), karena Eloquent menerapkan global scope pada
// SEMUA kueri lewat model itu, bukan hanya yang eksplisit ditulis "all".
//
// GAGAL TERTUTUP: kalau tidak ada tenant aktif sama sekali (bug, lupa
// pasang middleware, dsb), kueri menghasilkan KOSONG, bukan menampilkan
// semua sekolah. Kebalikannya — gagal terbuka — adalah kebocoran data
// lintas sekolah, yang justru aturan ini dibuat untuk mencegahnya.
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
    }
}
