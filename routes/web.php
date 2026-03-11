<?php

use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontendController::class, 'home']);
Route::get('/gelanggang', [FrontendController::class, 'gelanggangIndex']);
Route::get('/gelanggang/{id}', [FrontendController::class, 'gelanggangShow']);

Route::get('/login', [FrontendController::class, 'login'])->name('login');
Route::get('/register', [FrontendController::class, 'register'])->name('register');

Route::get('/dashboard', [FrontendController::class, 'dashboardUser']);
Route::get('/admin', [FrontendController::class, 'dashboardAdmin']);
Route::get('/admin/penyewaan', [FrontendController::class, 'adminPenyewaan']);
Route::get('/admin/gelanggang', [FrontendController::class, 'adminGelanggang']);
