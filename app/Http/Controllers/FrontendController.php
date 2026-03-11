<?php

namespace App\Http\Controllers;

use App\Models\Gelanggang;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FrontendController extends Controller
{
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

    public function gelanggangIndex(): View
    {
        $gelanggangs = Gelanggang::query()
            ->latest()
            ->get();

        return view('gelanggang.index', compact('gelanggangs'));
    }

    public function gelanggangShow(int $id): View
    {
        $gelanggang = Gelanggang::with(['images', 'jadwalOperasional'])->findOrFail($id);

        return view('gelanggang.show', compact('gelanggang'));
    }

    public function login(): View
    {
        return view('auth.login');
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function dashboardUser(): View
    {
        return view('dashboard.user');
    }

    public function dashboardAdmin(): View
    {
        return view('dashboard.admin');
    }

    public function adminPenyewaan(): View
    {
        return view('admin.bookings');
    }

    public function adminGelanggang(): View
    {
        return view('admin.gelanggang');
    }
}
