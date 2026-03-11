<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalOperasional extends Model
{
    use HasFactory;

    protected $table = 'jadwal_operasional';

    public $timestamps = false;

    protected $fillable = [
        'gelanggang_id',
        'hari',
        'jam_buka',
        'jam_tutup',
        'is_libur',
    ];

    protected $casts = [
        'is_libur' => 'boolean',
    ];

    public function gelanggang(): BelongsTo
    {
        return $this->belongsTo(Gelanggang::class);
    }
}
