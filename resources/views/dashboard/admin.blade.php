@extends('layouts.app')

@section('content')
<section class="space-y-6" x-data="adminStatsPage()" x-init="loadStats()">
    <article class="glass-panel p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Dashboard Admin</h1>
                <p class="text-sm text-slate-500">Ringkasan statistik penyewaan gelanggang.</p>
            </div>
            <button class="btn-muted" @click="loadStats">Refresh Statistik</button>
        </div>
    </article>

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="glass-panel p-5">
            <p class="text-sm text-slate-500">Total Booking</p>
            <p class="mt-1 text-3xl font-bold text-slate-900" x-text="stats.total_bookings"></p>
        </article>
        <article class="glass-panel p-5">
            <p class="text-sm text-slate-500">Booking Pending</p>
            <p class="mt-1 text-3xl font-bold text-amber-700" x-text="stats.pending_bookings"></p>
        </article>
        <article class="glass-panel p-5">
            <p class="text-sm text-slate-500">Booking Confirmed</p>
            <p class="mt-1 text-3xl font-bold text-emerald-700" x-text="stats.confirmed_bookings"></p>
        </article>
        <article class="glass-panel p-5">
            <p class="text-sm text-slate-500">Total Gelanggang</p>
            <p class="mt-1 text-3xl font-bold text-teal-700" x-text="stats.total_gelanggang"></p>
        </article>
    </section>

    <section class="grid gap-4 lg:grid-cols-2">
        <article class="glass-panel p-6">
            <p class="text-sm text-slate-500">Omzet Booking (Confirmed + Selesai)</p>
            <p class="mt-2 text-3xl font-bold text-slate-900" x-text="formatCurrency(stats.omzet)"></p>
        </article>
        <article class="glass-panel p-6">
            <h2 class="text-lg font-bold">Aksi Cepat Admin</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                <a class="btn-brand" href="/admin/penyewaan">Lihat Penyewaan User</a>
                <a class="btn-muted" href="/admin/gelanggang">Kelola Gelanggang</a>
            </div>
        </article>
    </section>

    <p class="text-sm" :class="error ? 'text-rose-700' : 'text-emerald-700'" x-text="message"></p>
</section>

<script>
    function adminStatsPage() {
        return {
            stats: {
                total_bookings: 0,
                pending_bookings: 0,
                confirmed_bookings: 0,
                total_gelanggang: 0,
                omzet: 0,
            },
            message: '',
            error: false,
            async loadStats() {
                this.error = false;
                this.message = '';
                try {
                    this.stats = await window.api('/api/admin/stats');
                } catch (e) {
                    this.error = true;
                    this.message = e.message + ' Pastikan Anda login sebagai admin.';
                }
            },
            formatCurrency(value) {
                return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
            }
        }
    }
</script>
@endsection
