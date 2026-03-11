<?php

namespace App\Http\Controllers;

use App\Models\Gelanggang;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FrontendController extends Controller
{
    // Menampilkan halaman utama beserta gelanggang unggulan.
    public function home(): View
    {
        $featured = collect();

        if (Schema::hasTable('gelanggangs')) {
            $featured = Gelanggang::query()
                ->where('status', 'aktif')
                ->latest()
                ->take(3)
                ->get();
        }

        return view('home', compact('featured'));
    }

    // Menampilkan daftar seluruh gelanggang untuk pengunjung.
    public function gelanggangIndex(): View
    {
        $gelanggangs = Gelanggang::query()
            ->latest()
            ->get();

        return view('gelanggang.index', compact('gelanggangs'));
    }

    // Menampilkan detail satu gelanggang beserta gambar dan jadwalnya.
    public function gelanggangShow(int $id): View
    {
        $gelanggang = Gelanggang::with(['images', 'jadwalOperasional'])->findOrFail($id);

        return view('gelanggang.show', compact('gelanggang'));
    }

    // Menampilkan halaman login.
    public function login(): View
    {
        return view('auth.login');
    }

    // Menampilkan halaman registrasi.
    public function register(): View
    {
        return view('auth.register');
    }

    // Menampilkan dashboard penyewaan milik member.
    public function dashboardUser(): View
    {
        return view('dashboard.user');
    }

    // Menampilkan dashboard utama admin.
    public function dashboardAdmin(): View
    {
        return view('dashboard.admin');
    }

    // Menampilkan halaman manajemen data penyewaan admin.
    public function adminPenyewaan(): View
    {
        return view('admin.bookings');
    }

    // Menampilkan halaman kelola gelanggang untuk admin.
    public function adminGelanggang(): View
    {
        return view('admin.gelanggang');
    }
}
