<?php

use App\Http\Controllers\Api\AdminBookingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\GelanggangController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::get('/gelanggang', [GelanggangController::class, 'index']);
Route::get('/gelanggang/{id}', [GelanggangController::class, 'show']);
Route::get('/gelanggang/{id}/jadwal', [GelanggangController::class, 'jadwal']);

Route::middleware('auth:api')->group(function (): void {
    Route::post('/booking', [BookingController::class, 'store']);
    Route::get('/booking/history', [BookingController::class, 'history']);
    Route::patch('/booking/{id}/cancel', [BookingController::class, 'cancel']);

    Route::middleware('admin')->group(function (): void {
        Route::get('/admin/stats', [AdminBookingController::class, 'stats']);
        Route::get('/admin/bookings', [AdminBookingController::class, 'index']);
        Route::patch('/admin/bookings/{id}/status', [AdminBookingController::class, 'updateStatus']);

        Route::post('/gelanggang', [GelanggangController::class, 'store']);
        Route::put('/gelanggang/{id}', [GelanggangController::class, 'update']);
        Route::delete('/gelanggang/{id}', [GelanggangController::class, 'destroy']);
    });
});
