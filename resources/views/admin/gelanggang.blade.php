@extends('layouts.app')

@section('content')
<section class="space-y-6" x-data="adminGelanggangPage()" x-init="loadGelanggang()">
    <article class="glass-panel p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold">Kelola Gelanggang</h1>
            <div class="flex flex-wrap gap-2">
                <input type="text" x-model="search" placeholder="Cari nama, jenis, status..." class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                <button class="btn-muted" @click="loadGelanggang">Refresh</button>
                <button class="btn-brand" @click="openCreateModal">Tambah Gelanggang</button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="item in filteredGelanggang()" :key="item.id">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <img :src="item.foto_utama || placeholder" alt="Gelanggang" class="mb-3 h-40 w-full rounded-xl object-cover">
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <h2 class="text-lg font-bold" x-text="item.nama"></h2>
                        <span class="rounded-full px-2 py-1 text-xs font-semibold"
                            :class="item.status === 'aktif' ? 'bg-emerald-100 text-emerald-700' : (item.status === 'maintenance' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-700')"
                            x-text="item.status"></span>
                    </div>
                    <p class="text-sm text-slate-500" x-text="item.jenis + ' | Rp ' + Number(item.harga_per_jam).toLocaleString('id-ID') + '/jam'"></p>
                    <p class="mt-2 min-h-10 text-sm text-slate-600 line-clamp-2" x-text="item.deskripsi || '-'" ></p>
                    <p class="mt-2 text-xs text-slate-500" x-text="'Fasilitas: ' + ((item.fasilitas || []).join(', ') || '-')"></p>
                    <div class="mt-4 flex gap-2">
                        <button class="rounded-lg bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-700" @click="openEditModal(item)">Edit</button>
                        <button class="rounded-lg bg-rose-100 px-3 py-1 text-sm font-semibold text-rose-700" @click="askDelete(item)">Hapus</button>
                    </div>
                </article>
            </template>
        </div>

        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white/70 p-4 text-center text-sm text-slate-500" x-show="!filteredGelanggang().length">
            Data gelanggang tidak ditemukan.
        </div>

        <p class="mt-4 text-sm" :class="error ? 'text-rose-700' : 'text-emerald-700'" x-text="message"></p>
    </article>

    <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/45 px-4" x-show="showFormModal" x-cloak>
        <div class="flex max-h-[92vh] w-full max-w-2xl flex-col rounded-3xl border border-white/60 bg-white shadow-2xl">
            <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-6 py-4">
                <h2 class="text-xl font-bold" x-text="isEdit ? 'Edit Gelanggang' : 'Tambah Gelanggang'"></h2>
                <button class="rounded-lg bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700" @click="closeFormModal">Tutup</button>
            </div>

            <form class="flex flex-1 flex-col overflow-hidden" @submit.prevent="submit">
                <div class="flex-1 overflow-y-auto">
                    <div class="grid gap-3 p-6 sm:grid-cols-2">
                        <input type="text" x-model="form.nama" class="w-full rounded-xl border border-slate-300 px-3 py-2 sm:col-span-2" placeholder="Nama" required>
                        <select x-model="form.jenis" class="w-full rounded-xl border border-slate-300 px-3 py-2" required>
                            <option value="">Pilih jenis</option>
                            <option value="badminton">Badminton</option>
                            <option value="basket">Basket</option>
                            <option value="futsal">Futsal</option>
                            <option value="tenis">Tenis</option>
                            <option value="voli">Voli</option>
                        </select>
                        <select x-model="form.status" class="w-full rounded-xl border border-slate-300 px-3 py-2" required>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                        <input type="number" x-model="form.harga_per_jam" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="Harga/jam" required>
                        <input type="number" x-model="form.kapasitas" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="Kapasitas" required>
                        <input type="text" x-model="form.fasilitas_input" class="w-full rounded-xl border border-slate-300 px-3 py-2 sm:col-span-2" placeholder="Fasilitas pisahkan koma">
                        <textarea x-model="form.deskripsi" class="w-full resize-none rounded-xl border border-slate-300 px-3 py-2 sm:col-span-2" rows="3" placeholder="Deskripsi"></textarea>

                        {{-- Foto Utama --}}
                        <div class="sm:col-span-2">
                            <p class="mb-1 text-sm font-semibold text-slate-700">Foto Utama</p>
                            <input type="file" @change="setImage($event)" accept="image/*" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <p class="mt-1 text-xs text-slate-500">jpg/png/webp, maks 4MB.</p>
                        </div>

                        {{-- Foto Galeri --}}
                        <div class="sm:col-span-2">
                            <p class="mb-1 text-sm font-semibold text-slate-700">Foto Galeri <span class="font-normal text-slate-500">(bisa lebih dari 1)</span></p>
                            <template x-if="form.existingImages.length">
                                <div class="mb-2 flex flex-wrap gap-2">
                                    <template x-for="(img, i) in form.existingImages" :key="img.id">
                                        <div class="relative">
                                            <img :src="img.path" class="h-16 w-16 rounded-lg border border-slate-200 object-cover">
                                            <button type="button" @click="removeExistingImage(i)"
                                                class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-rose-600 text-xs font-bold text-white">&times;</button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <input type="file" multiple accept="image/*" @change="setGaleri($event)" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <template x-if="galeriPreviews.length">
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <template x-for="(src, i) in galeriPreviews" :key="i">
                                        <img :src="src" class="h-16 w-16 rounded-lg border border-slate-200 object-cover">
                                    </template>
                                </div>
                            </template>
                            <p class="mt-1 text-xs text-slate-500">jpg/png/webp, maks 4MB per file.</p>
                        </div>

                        {{-- Jadwal Operasional --}}
                        <div class="sm:col-span-2">
                            <p class="mb-2 text-sm font-semibold text-slate-700">Jadwal Operasional</p>
                            <div class="space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <template x-for="(j, i) in form.jadwal" :key="j.hari">
                                    <div class="flex flex-wrap items-center gap-2 text-sm">
                                        <span class="w-14 capitalize font-medium text-slate-700" x-text="j.hari"></span>
                                        <label class="flex cursor-pointer items-center gap-1 text-slate-600">
                                            <input type="checkbox" x-model="j.is_libur" class="rounded">
                                            <span class="text-xs">Libur</span>
                                        </label>
                                        <input type="time" x-model="j.jam_buka" :disabled="j.is_libur"
                                            class="rounded-lg border border-slate-300 px-2 py-1 text-sm disabled:opacity-40">
                                        <span class="text-slate-400">–</span>
                                        <input type="time" x-model="j.jam_tutup" :disabled="j.is_libur"
                                            class="rounded-lg border border-slate-300 px-2 py-1 text-sm disabled:opacity-40">
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 gap-2 border-t border-slate-100 px-6 py-4">
                    <button class="btn-brand flex-1" type="submit" x-text="isEdit ? 'Update' : 'Tambah'"></button>
                    <button class="btn-muted" type="button" @click="closeFormModal">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/55 px-4" x-show="showDeleteModal" x-cloak>
        <div class="w-full max-w-md rounded-3xl border border-white/60 bg-linear-to-b from-white to-rose-50 p-6 shadow-2xl">
            <p class="text-xs uppercase tracking-[0.16em] text-rose-600">Konfirmasi Hapus</p>
            <h3 class="mt-2 text-xl font-bold text-slate-900">Hapus Gelanggang?</h3>
            <p class="mt-2 text-sm text-slate-600">Data <span class="font-semibold" x-text="deleteTarget?.nama || '-'" ></span> akan dihapus permanen.</p>

            <div class="mt-5 flex gap-2">
                <button class="btn-muted flex-1" @click="showDeleteModal = false">Batal</button>
                <button class="flex-1 rounded-xl bg-rose-600 px-4 py-2 font-semibold text-white transition hover:bg-rose-700" @click="confirmDelete">Ya, Hapus</button>
            </div>
        </div>
    </div>
</section>

<script>
    function adminGelanggangPage() {
        const DEFAULT_JADWAL = () => [
            { hari: 'senin',   jam_buka: '08:00', jam_tutup: '22:00', is_libur: false },
            { hari: 'selasa',  jam_buka: '08:00', jam_tutup: '22:00', is_libur: false },
            { hari: 'rabu',    jam_buka: '08:00', jam_tutup: '22:00', is_libur: false },
            { hari: 'kamis',   jam_buka: '08:00', jam_tutup: '22:00', is_libur: false },
            { hari: 'jumat',   jam_buka: '08:00', jam_tutup: '22:00', is_libur: false },
            { hari: 'sabtu',   jam_buka: '08:00', jam_tutup: '22:00', is_libur: false },
            { hari: 'minggu',  jam_buka: '08:00', jam_tutup: '22:00', is_libur: false },
        ];

        return {
            placeholder: 'https://images.unsplash.com/photo-1526676037777-05a232554f77?auto=format&fit=crop&w=1000&q=80',
            gelanggangs: [],
            search: '',
            isEdit: false,
            editId: null,
            showFormModal: false,
            showDeleteModal: false,
            deleteTarget: null,
            message: '',
            error: false,
            galeriPreviews: [],
            deletedImageIds: [],
            form: {
                nama: '',
                jenis: '',
                deskripsi: '',
                harga_per_jam: '',
                kapasitas: 10,
                status: 'aktif',
                fasilitas_input: '',
                foto_utama: null,
                galeri: [],
                existingImages: [],
                jadwal: DEFAULT_JADWAL(),
            },
            async loadGelanggang() {
                this.error = false;
                this.message = '';
                try {
                    this.gelanggangs = await window.api('/api/gelanggang');
                } catch (e) {
                    this.error = true;
                    this.message = e.message;
                }
            },
            filteredGelanggang() {
                const q = this.search.trim().toLowerCase();
                if (!q) return this.gelanggangs;

                return this.gelanggangs.filter((item) => {
                    const haystack = [
                        item.nama,
                        item.jenis,
                        item.status,
                        item.deskripsi,
                        (item.fasilitas || []).join(' '),
                    ].join(' ').toLowerCase();

                    return haystack.includes(q);
                });
            },
            openCreateModal() {
                this.resetForm();
                this.showFormModal = true;
            },
            openEditModal(item) {
                this.isEdit = true;
                this.editId = item.id;
                this.galeriPreviews = [];
                this.deletedImageIds = [];

                const hariList = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
                const apiJadwal = item.jadwal_operasional || [];
                const jadwal = hariList.map(hari => {
                    const found = apiJadwal.find(j => j.hari === hari);
                    if (found) {
                        return {
                            hari,
                            jam_buka: found.jam_buka ? found.jam_buka.slice(0, 5) : '08:00',
                            jam_tutup: found.jam_tutup ? found.jam_tutup.slice(0, 5) : '22:00',
                            is_libur: !!found.is_libur,
                        };
                    }
                    return { hari, jam_buka: '08:00', jam_tutup: '22:00', is_libur: false };
                });

                this.form = {
                    nama: item.nama || '',
                    jenis: item.jenis || '',
                    deskripsi: item.deskripsi || '',
                    harga_per_jam: item.harga_per_jam || '',
                    kapasitas: item.kapasitas || 10,
                    status: item.status || 'aktif',
                    fasilitas_input: (item.fasilitas || []).join(', '),
                    foto_utama: null,
                    galeri: [],
                    existingImages: (item.images || []).map(img => ({ id: img.id, path: img.path })),
                    jadwal,
                };
                this.showFormModal = true;
            },
            closeFormModal() {
                this.showFormModal = false;
            },
            setImage(event) {
                this.form.foto_utama = event.target.files?.[0] || null;
            },
            setGaleri(event) {
                const files = Array.from(event.target.files || []);
                this.form.galeri = files;
                this.galeriPreviews = files.map(f => URL.createObjectURL(f));
            },
            removeExistingImage(i) {
                const img = this.form.existingImages.splice(i, 1)[0];
                if (img?.id) this.deletedImageIds.push(img.id);
            },
            buildFormData() {
                const fasilitas = this.form.fasilitas_input
                    .split(',')
                    .map((item) => item.trim())
                    .filter(Boolean);

                const fd = new FormData();
                fd.append('nama', this.form.nama);
                fd.append('jenis', this.form.jenis);
                fd.append('deskripsi', this.form.deskripsi || '');
                fd.append('harga_per_jam', this.form.harga_per_jam);
                fd.append('kapasitas', this.form.kapasitas);
                fd.append('status', this.form.status);
                fd.append('fasilitas', JSON.stringify(fasilitas));

                if (this.form.foto_utama instanceof File) {
                    fd.append('foto_utama', this.form.foto_utama);
                }

                for (const file of this.form.galeri) {
                    fd.append('images[]', file);
                }

                fd.append('jadwal', JSON.stringify(this.form.jadwal));

                if (this.deletedImageIds.length) {
                    fd.append('delete_image_ids', JSON.stringify(this.deletedImageIds));
                }

                return fd;
            },
            async submit() {
                this.error = false;
                this.message = '';
                try {
                    const formData = this.buildFormData();
                    if (this.isEdit) {
                        formData.append('_method', 'PUT');
                        await window.api(`/api/gelanggang/${this.editId}`, {
                            method: 'POST',
                            body: formData,
                        });
                        this.message = 'Gelanggang berhasil diupdate.';
                    } else {
                        await window.api('/api/gelanggang', {
                            method: 'POST',
                            body: formData,
                        });
                        this.message = 'Gelanggang berhasil ditambahkan.';
                    }

                    this.showFormModal = false;
                    this.resetForm();
                    await this.loadGelanggang();
                } catch (e) {
                    this.error = true;
                    this.message = e.message;
                }
            },
            askDelete(item) {
                this.deleteTarget = item;
                this.showDeleteModal = true;
            },
            async confirmDelete() {
                if (!this.deleteTarget) {
                    return;
                }

                this.error = false;
                this.message = '';
                try {
                    await window.api(`/api/gelanggang/${this.deleteTarget.id}`, { method: 'DELETE' });
                    this.message = 'Gelanggang berhasil dihapus.';
                    this.showDeleteModal = false;
                    this.deleteTarget = null;
                    await this.loadGelanggang();
                } catch (e) {
                    this.error = true;
                    this.message = e.message;
                }
            },
            resetForm() {
                this.isEdit = false;
                this.editId = null;
                this.galeriPreviews = [];
                this.deletedImageIds = [];
                this.form = {
                    nama: '',
                    jenis: '',
                    deskripsi: '',
                    harga_per_jam: '',
                    kapasitas: 10,
                    status: 'aktif',
                    fasilitas_input: '',
                    foto_utama: null,
                    galeri: [],
                    existingImages: [],
                    jadwal: DEFAULT_JADWAL(),
                };
            },
        };
    }
</script>
@endsection
