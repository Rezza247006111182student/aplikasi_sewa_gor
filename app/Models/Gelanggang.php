<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gelanggang extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'jenis',
        'deskripsi',
        'harga_per_jam',
        'kapasitas',
        'fasilitas',
        'status',
        'foto_utama',
    ];

    protected $casts = [
        'fasilitas' => 'array',
        'harga_per_jam' => 'decimal:2',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(GelanggangImage::class);
    }

    public function jadwalOperasional(): HasMany
    {
        return $this->hasMany(JadwalOperasional::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
