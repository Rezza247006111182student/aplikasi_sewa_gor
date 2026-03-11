<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Gelanggang;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $maksTanggal = now()->addDays(3)->toDateString();

        $validated = $request->validate([
            'gelanggang_id' => ['required', 'exists:gelanggangs,id'],
            'tanggal' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.$maksTanggal],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'catatan' => ['nullable', 'string'],
        ]);

        $gelanggang = Gelanggang::findOrFail($validated['gelanggang_id']);

        $konflik = Booking::query()
            ->where('gelanggang_id', $gelanggang->id)
            ->whereDate('tanggal', $validated['tanggal'])
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($query) use ($validated) {
                $query->where('jam_mulai', '<', $validated['jam_selesai'])
                    ->where('jam_selesai', '>', $validated['jam_mulai']);
            })
            ->exists();

        if ($konflik) {
            return response()->json([
                'message' => 'Jam yang dipilih sudah terisi. Silakan pilih slot lain.',
            ], 422);
        }

        $mulai = Carbon::parse($validated['tanggal'].' '.$validated['jam_mulai']);
        $selesai = Carbon::parse($validated['tanggal'].' '.$validated['jam_selesai']);

        if ($mulai->lt(now())) {
            return response()->json([
                'message' => 'Waktu booking tidak boleh kurang dari waktu saat ini.',
            ], 422);
        }

        // Use absolute minute difference to prevent negative duration values.
        $durasiMenit = $mulai->diffInMinutes($selesai, true);
        $durasiJam = max(1, (int) ceil($durasiMenit / 60));
        $total = $durasiJam * (float) $gelanggang->harga_per_jam;

        $booking = Booking::create([
            'user_id' => auth('api')->id(),
            'gelanggang_id' => $gelanggang->id,
            'tanggal' => $validated['tanggal'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'durasi_jam' => $durasiJam,
            'total_harga' => $total,
            'status' => 'pending',
            'catatan' => $validated['catatan'] ?? null,
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'metode' => 'transfer',
            'jumlah' => $booking->total_harga,
            'status' => 'unpaid',
        ]);

        return response()->json([
            'message' => 'Booking berhasil dibuat.',
            'data' => $booking->load(['gelanggang', 'payment']),
        ], 201);
    }

    public function history(): JsonResponse
    {
        Booking::finalizeExpiredConfirmed();

        $bookings = Booking::query()
            ->with(['gelanggang', 'payment'])
            ->where('user_id', auth('api')->id())
            ->latest()
            ->get();

        return response()->json($bookings);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $booking = Booking::query()
            ->where('user_id', auth('api')->id())
            ->findOrFail($id);

        if ($booking->status === 'cancelled') {
            return response()->json([
                'message' => 'Booking sudah dibatalkan sebelumnya.',
            ], 422);
        }

        $validated = $request->validate([
            'alasan_cancel' => ['nullable', 'string'],
        ]);

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'alasan_cancel' => $validated['alasan_cancel'] ?? null,
        ]);

        return response()->json([
            'message' => 'Booking berhasil dibatalkan.',
            'data' => $booking,
        ]);
    }
}
