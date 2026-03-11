@extends('layouts.app')

@section('content')
<section class="glass-panel p-6" x-data="adminBookingsPage()" x-init="loadBookings()">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">List Penyewaan User</h1>
        <div class="flex items-center gap-2">
            <input type="text" x-model="search" placeholder="Cari kode, user, gelanggang..." class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
            <select x-model="statusFilter" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="cancelled">Cancelled</option>
                <option value="selesai">Selesai</option>
            </select>
            <button class="btn-muted" @click="loadBookings">Refresh</button>
        </div>
    </div>

    <div class="overflow-x-auto" style="overflow-y: visible;">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-slate-500">
                    <th class="py-2 pr-4">Kode</th>
                    <th class="py-2 pr-4">User</th>
                    <th class="py-2 pr-4">Gelanggang</th>
                    <th class="py-2 pr-4">Tanggal</th>
                    <th class="py-2 pr-4">Jam</th>
                    <th class="py-2 pr-4">Status</th>
                    <th class="py-2 pr-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="item in filteredBookings()" :key="item.id">
                    <tr class="border-b border-slate-100">
                        <td class="py-2 pr-4" x-text="item.kode_booking"></td>
                        <td class="py-2 pr-4" x-text="item.user?.name"></td>
                        <td class="py-2 pr-4" x-text="item.gelanggang?.nama"></td>
                        <td class="py-2 pr-4" x-text="formatDate(item.tanggal)"></td>
                        <td class="py-2 pr-4" x-text="item.jam_mulai + ' - ' + item.jam_selesai"></td>
                        <td class="py-2 pr-4">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="statusClass(item.status)" x-text="statusLabel(item.status)"></span>
                            <p class="mt-1 text-[11px] text-slate-500" x-show="isUserCancelled(item)">Dibatalkan user</p>
                        </td>
                        <td class="py-2 pr-4">
                            <div class="relative inline-block" x-data="{ open: false }">
                                <button type="button"
                                    class="rounded-lg border border-slate-300 bg-white p-2 text-slate-600 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="availableActions(item).length === 0"
                                    @click="open = !open">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                    </svg>
                                </button>

                                <div x-show="open"
                                    x-cloak
                                    @click.outside="open = false"
                                    class="absolute right-0 top-full z-40 mt-2 w-40 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                    <template x-for="action in availableActions(item)" :key="action.status">
                                        <button type="button"
                                            class="block w-full px-3 py-2 text-left text-xs font-semibold transition hover:bg-slate-50"
                                            :class="action.class"
                                            @click="setStatus(item.id, action.status); open = false"
                                            x-text="action.label">
                                        </button>
                                    </template>
                                    <p class="px-3 py-2 text-xs text-slate-500" x-show="availableActions(item).length === 0">Tidak ada aksi</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white/70 p-4 text-center text-sm text-slate-500" x-show="!filteredBookings().length">
        Data penyewaan tidak ditemukan.
    </div>

    <p class="mt-4 text-sm" :class="error ? 'text-rose-700' : 'text-emerald-700'" x-text="message"></p>
</section>

<script>
    function adminBookingsPage() {
        return {
            bookings: [],
            statusFilter: '',
            search: '',
            message: '',
            error: false,
            async loadBookings() {
                this.error = false;
                this.message = '';
                try {
                    const query = this.statusFilter ? `?status=${encodeURIComponent(this.statusFilter)}` : '';
                    this.bookings = await window.api(`/api/admin/bookings${query}`);
                } catch (e) {
                    this.error = true;
                    this.message = e.message + ' Pastikan login sebagai admin.';
                }
            },
            async setStatus(id, status) {
                this.error = false;
                this.message = '';
                try {
                    await window.api(`/api/admin/bookings/${id}/status`, {
                        method: 'PATCH',
                        body: JSON.stringify({ status }),
                    });
                    this.message = 'Status booking diperbarui.';
                    await this.loadBookings();
                } catch (e) {
                    this.error = true;
                    this.message = e.message;
                }
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
            isUserCancelled(item) {
                return item.status === 'cancelled' && !!item.alasan_cancel;
            },
            availableActions(item) {
                if (this.isUserCancelled(item) || item.status === 'selesai') {
                    return [];
                }

                if (item.status === 'pending') {
                    return [
                        { status: 'confirmed', label: 'Approve', class: 'text-emerald-700' },
                        { status: 'cancelled', label: 'Reject', class: 'text-rose-700' },
                    ];
                }

                if (item.status === 'confirmed') {
                    return [
                        { status: 'selesai', label: 'Tandai Selesai', class: 'text-teal-700' },
                        { status: 'cancelled', label: 'Batalkan', class: 'text-rose-700' },
                    ];
                }

                return [];
            },
            filteredBookings() {
                const q = this.search.trim().toLowerCase();
                if (!q) return this.bookings;

                return this.bookings.filter((item) => {
                    const haystack = [
                        item.kode_booking,
                        item.user?.name,
                        item.gelanggang?.nama,
                        item.status,
                        item.tanggal,
                        item.jam_mulai,
                        item.jam_selesai,
                    ].join(' ').toLowerCase();

                    return haystack.includes(q);
                });
            },
        };
    }
</script>
@endsection
