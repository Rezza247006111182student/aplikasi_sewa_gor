<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Gelanggang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    // Mengambil ringkasan statistik utama untuk dashboard admin.
    public function stats(): JsonResponse
    {
        Booking::finalizeExpiredConfirmed();

        $totalBookings = Booking::count();
        $pendingBookings = Booking::where('status', 'pending')->count();
        $confirmedBookings = Booking::where('status', 'confirmed')->count();
        $totalGelanggang = Gelanggang::count();
        $omzet = (float) Booking::query()
            ->whereIn('status', ['confirmed', 'selesai'])
            ->sum('total_harga');

        return response()->json([
            'total_bookings' => $totalBookings,
            'pending_bookings' => $pendingBookings,
            'confirmed_bookings' => $confirmedBookings,
            'total_gelanggang' => $totalGelanggang,
            'omzet' => $omzet,
        ]);
    }

    // Mengambil daftar booking admin dengan filter status opsional.
    public function index(Request $request): JsonResponse
    {
        Booking::finalizeExpiredConfirmed();

        $query = Booking::query()->with(['user', 'gelanggang', 'payment']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json($query->latest()->get());
    }

    // Memperbarui status booking sesuai aturan bisnis admin.
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled,selesai'],
        ]);

        $booking = Booking::findOrFail($id);

        $isUserCancelled = $booking->status === 'cancelled' && !empty($booking->alasan_cancel);
        if ($isUserCancelled) {
            return response()->json([
                'message' => 'Booking sudah dibatalkan oleh user dan tidak bisa diubah lagi.',
            ], 422);
        }

        if ($booking->status === 'selesai') {
            return response()->json([
                'message' => 'Booking yang sudah selesai tidak bisa diubah lagi.',
            ], 422);
        }

        $booking->status = $validated['status'];
        if ($validated['status'] === 'cancelled') {
            $booking->cancelled_at = now();
        }
        $booking->save();

        return response()->json([
            'message' => 'Status booking diperbarui.',
            'data' => $booking,
        ]);
    }
}
