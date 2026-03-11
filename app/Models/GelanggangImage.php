<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GelanggangImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'gelanggang_id',
        'path',
        'urutan',
    ];

    public function gelanggang(): BelongsTo
    {
        return $this->belongsTo(Gelanggang::class);
    }
}
