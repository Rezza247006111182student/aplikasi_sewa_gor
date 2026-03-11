<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_booking',
        'user_id',
        'gelanggang_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'durasi_jam',
        'total_harga',
        'status',
        'catatan',
        'cancelled_at',
        'alasan_cancel',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_harga' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            if (empty($booking->kode_booking)) {
                $booking->kode_booking = 'BK-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gelanggang(): BelongsTo
    {
        return $this->belongsTo(Gelanggang::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public static function finalizeExpiredConfirmed(): int
    {
        $now = now();
        $updated = 0;

        self::query()
            ->where('status', 'confirmed')
            ->get()
            ->each(function (Booking $booking) use ($now, &$updated): void {
                $tanggal = $booking->tanggal instanceof Carbon
                    ? $booking->tanggal->format('Y-m-d')
                    : (string) $booking->tanggal;

                $endAt = Carbon::parse($tanggal.' '.$booking->jam_selesai);
                if ($endAt->lte($now)) {
                    $booking->status = 'selesai';
                    $booking->save();
                    $updated++;
                }
            });

        return $updated;
    }
}
