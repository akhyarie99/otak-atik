<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Langganan extends Model
{
    /** @use HasFactory<\Database\Factories\LanggananFactory> */
    use HasFactory, BelongsToTenant;

    protected $table = 'langganan';

    protected $fillable = ['tenant_id', 'paket', 'status', 'mulai_pada', 'berakhir_pada'];

    protected $casts = [
        'mulai_pada' => 'date',
        'berakhir_pada' => 'date',
    ];

    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class);
    }

    public function berfungsiPenuh(): bool
    {
        return in_array($this->status, ['percobaan', 'aktif', 'tenggang'], true);
    }

    public function hanyaBaca(): bool
    {
        return $this->status === 'hanya_baca';
    }
}
