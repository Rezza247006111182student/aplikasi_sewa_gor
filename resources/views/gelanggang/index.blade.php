@extends('layouts.app')

@section('content')
<section x-data='gelanggangListPage(@json($gelanggangs))'>
    <h1 class="text-3xl font-bold">Daftar Gelanggang</h1>
    <p class="mt-2 text-slate-600">Pilih lapangan terbaik sesuai cabang olahraga dan jam favorit Anda.</p>

    <div class="mt-4 max-w-md">
        <input type="text" x-model="search" placeholder="Cari nama, jenis, atau status gelanggang..." class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <template x-for="item in filtered()" :key="item.id">
            <article class="glass-panel overflow-hidden">
                <img :src="item.foto_utama || placeholder" :alt="item.nama" class="h-44 w-full object-cover">
                <div class="p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-lg font-bold" x-text="item.nama"></h2>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold"
                            :class="item.status === 'aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                            x-text="item.status === 'aktif' ? 'Tersedia' : 'Penuh'">
                        </span>
                    </div>
                    <p class="text-sm text-slate-600" x-text="truncate(item.deskripsi || '-', 90)"></p>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="font-semibold text-teal-700" x-text="'Rp ' + Number(item.harga_per_jam || 0).toLocaleString('id-ID') + '/jam'"></span>
                        <a :href="`/gelanggang/${item.id}`" class="btn-brand text-sm">Cek Detail</a>
                    </div>
                </div>
            </article>
        </template>
    </div>

    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white/70 p-4 text-center text-sm text-slate-500" x-show="!filtered().length">
        Data gelanggang tidak ditemukan.
    </div>
</section>

<script>
    function gelanggangListPage(initialItems) {
        return {
            search: '',
            placeholder: 'https://images.unsplash.com/photo-1526676037777-05a232554f77?auto=format&fit=crop&w=1000&q=80',
            items: initialItems || [],
            truncate(text, max) {
                const s = String(text || '');
                return s.length > max ? s.slice(0, max) + '...' : s;
            },
            filtered() {
                const q = this.search.trim().toLowerCase();
                if (!q) return this.items;

                return this.items.filter((item) => {
                    const haystack = [item.nama, item.jenis, item.status, item.deskripsi]
                        .join(' ')
                        .toLowerCase();
                    return haystack.includes(q);
                });
            },
        };
    }
</script>
@endsection
