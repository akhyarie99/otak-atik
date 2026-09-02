<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KaryaVersi extends Model
{
    /** @use HasFactory<\Database\Factories\KaryaVersiFactory> */
    use HasFactory;

    protected $table = 'karya_versi';

    protected $fillable = ['karya_id', 'project_json', 'client_updated_at'];

    protected $casts = [
        'project_json' => 'array',
        'client_updated_at' => 'datetime',
    ];

    public function karya(): BelongsTo
    {
        return $this->belongsTo(Karya::class);
    }
}
