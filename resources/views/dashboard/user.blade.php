@extends('layouts.app')

@section('content')
<section class="space-y-6" x-data="userDashboard()" x-init="loadHistory()">
    <article class="glass-panel p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Sewaan Saya</h1>
                <p class="text-sm text-slate-500">Lacak semua status penyewaan gelanggang Anda secara praktis.</p>
            </div>
            <div class="flex gap-2">
                <a class="btn-brand" href="/gelanggang">Booking Baru</a>
            </div>
        </div>
    </article>

    <section class="grid gap-4 sm:grid-cols-3">
        <article class="glass-panel p-5">
            <p class="text-sm text-slate-500">Total Booking</p>
            <p class="mt-1 text-3xl font-bold text-slate-900" x-text="bookings.length"></p>
        </article>
        <article class="glass-panel p-5">
            <p class="text-sm text-slate-500">Aktif (Pending/Confirmed)</p>
            <p class="mt-1 text-3xl font-bold text-teal-700" x-text="activeCount()"></p>
        </article>
        <article class="glass-panel p-5">
            <p class="text-sm text-slate-500">Selesai</p>
            <p class="mt-1 text-3xl font-bold text-emerald-700" x-text="doneCount()"></p>
        </article>
    </section>

    <article class="glass-panel p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-bold">Riwayat Penyewaan</h2>
            <div class="flex gap-2">
                <input type="text" x-model="search" placeholder="Cari kode, gelanggang, status..." class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                <button class="btn-muted" @click="loadHistory">Refresh</button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" x-show="filteredBookings().length">
            <template x-for="item in filteredBookings()" :key="item.id">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <p class="font-bold text-slate-900" x-text="item.gelanggang?.nama || '-'"></p>
                        <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="statusClass(item.status)" x-text="statusLabel(item.status)"></span>
                    </div>
                    <p class="text-xs uppercase tracking-[0.12em] text-slate-500" x-text="item.kode_booking"></p>

                    <div class="mt-3 space-y-1 text-sm text-slate-600">
                        <p><span class="font-semibold">Tanggal:</span> <span x-text="formatDate(item.tanggal)"></span></p>
                        <p><span class="font-semibold">Jam:</span> <span x-text="formatTimeRange(item.jam_mulai, item.jam_selesai)"></span></p>
                        <p><span class="font-semibold">Total:</span> <span x-text="formatRupiah(item.total_harga)"></span></p>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <button class="rounded-lg bg-rose-100 px-3 py-1.5 text-sm font-semibold text-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="!canCancel(item.status)"
                            @click="cancel(item.id)">
                            Batalkan
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-6 text-center" x-show="!filteredBookings().length">
            <p class="font-semibold text-slate-700">Belum ada riwayat sewaan.</p>
            <p class="mt-1 text-sm text-slate-500">Mulai booking gelanggang favorit Anda sekarang.</p>
            <a class="btn-brand mt-4 inline-block" href="/gelanggang">Cari Gelanggang</a>
        </div>
    </article>

    <p class="text-sm" :class="error ? 'text-rose-700' : 'text-emerald-700'" x-text="message"></p>
</section>

<script>
    function userDashboard() {
        return {
            bookings: [],
            search: '',
            message: '',
            error: false,
            async loadHistory() {
                this.error = false;
                this.message = '';
                try {
                    this.bookings = await window.api('/api/booking/history');
                } catch (e) {
                    this.error = true;
                    this.message = e.message + ' Silakan login ulang.';
                }
            },
            formatRupiah(value) {
                return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
            },
            activeCount() {
                return this.bookings.filter((item) => ['pending', 'confirmed'].includes(item.status)).length;
            },
            doneCount() {
                return this.bookings.filter((item) => item.status === 'selesai').length;
            },
            formatDate(value) {
                if (!value) return '-';
                const d = new Date(value);
                if (Number.isNaN(d.getTime())) {
                    return String(value).slice(0, 10);
                }
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${day}`;
            },
            formatTime(value) {
                if (!value) return '-';
                return String(value).slice(0, 5);
            },
            formatTimeRange(start, end) {
                return `${this.formatTime(start)} - ${this.formatTime(end)}`;
            },
            filteredBookings() {
                const q = this.search.trim().toLowerCase();
                if (!q) return this.bookings;

                return this.bookings.filter((item) => {
                    const haystack = [
                        item.kode_booking,
                        item.gelanggang?.nama,
                        item.status,
                        this.formatDate(item.tanggal),
                        this.formatTime(item.jam_mulai),
                        this.formatTime(item.jam_selesai),
                    ].join(' ').toLowerCase();

                    return haystack.includes(q);
                });
            },
            canCancel(status) {
                return ['pending', 'confirmed'].includes(status);
            },
            statusLabel(status) {
                const map = {
                    pending: 'Pending',
                    confirmed: 'Confirmed',
                    cancelled: 'Cancelled',
                    selesai: 'Selesai',
                };
                return map[status] || status;
            },
            statusClass(status) {
                if (status === 'confirmed') return 'bg-emerald-100 text-emerald-700';
                if (status === 'pending') return 'bg-amber-100 text-amber-700';
                if (status === 'cancelled') return 'bg-rose-100 text-rose-700';
                if (status === 'selesai') return 'bg-teal-100 text-teal-700';
                return 'bg-slate-100 text-slate-700';
            },
            async cancel(id) {
                this.error = false;
                this.message = '';
                try {
                    await window.api(`/api/booking/${id}/cancel`, {
                        method: 'PATCH',
                        body: JSON.stringify({ alasan_cancel: 'Dibatalkan oleh user' }),
                    });
                    this.message = 'Booking dibatalkan.';
                    await this.loadHistory();
                } catch (e) {
                    this.error = true;
                    this.message = e.message;
                }
            },
        }
    }
</script>
@endsection
