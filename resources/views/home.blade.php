@extends('layouts.app')

@section('content')
<section class="grid gap-6 lg:grid-cols-2" x-data="{ reveal: false }" x-init="setTimeout(() => reveal = true, 120)">
    <div class="glass-panel p-8 transition duration-700" :class="reveal ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
        <p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-amber-700">Sewa Gelanggang Premium</p>
        <h1 class="text-4xl font-bold leading-tight text-slate-900 sm:text-5xl">Booking lapangan olahraga jadi cepat, jelas, dan modern.</h1>
        <p class="mt-4 text-slate-600">Cek ketersediaan real-time, pilih jam favorit, lalu kelola semua riwayat sewa dari satu dashboard.</p>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="/gelanggang" class="btn-brand">Lihat Gelanggang</a>
            <a href="/register" class="btn-muted">Buat Akun</a>
        </div>
    </div>
    <div class="overflow-hidden rounded-2xl shadow-xl">
        <img src="https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=1200&q=80" alt="Arena olahraga" class="h-full min-h-72 w-full object-cover">
    </div>
</section>

<section class="mt-10">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-2xl font-bold">Gelanggang Unggulan</h2>
        <a href="/gelanggang" class="text-sm font-semibold text-teal-700 hover:text-teal-800">Lihat semua</a>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        @forelse($featured as $item)
            <article class="glass-panel p-4">
                <p class="mb-1 text-xs font-semibold uppercase text-slate-500">{{ $item->jenis }}</p>
                <h3 class="text-lg font-bold">{{ $item->nama }}</h3>
                <p class="mt-2 text-sm text-slate-600 line-clamp-2">{{ $item->deskripsi }}</p>
                <div class="mt-3 flex items-center justify-between">
                    <span class="font-semibold text-teal-700">Rp {{ number_format($item->harga_per_jam, 0, ',', '.') }}/jam</span>
                    <a href="/gelanggang/{{ $item->id }}" class="text-sm font-semibold text-slate-700 hover:text-teal-700">Detail</a>
                </div>
            </article>
        @empty
            <p class="glass-panel p-4 text-slate-600">Belum ada data gelanggang. Jalankan seeding/migrasi dulu.</p>
        @endforelse
    </div>
</section>
@endsection
