@extends('layouts.app')

@section('content')
@php
    $galleryImages = collect([$gelanggang->foto_utama])
        ->merge($gelanggang->images->pluck('path'))
        ->filter()
        ->unique()
        ->values();

    if ($galleryImages->isEmpty()) {
        $galleryImages = collect([
            'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=80',
        ]);
    }
@endphp

<section class="grid gap-6 lg:grid-cols-3" x-data="bookingApp({{ $gelanggang->id }}, @js($galleryImages->all()))" x-init="init()">
    <article class="glass-panel p-6 lg:col-span-2">
        <div class="relative mb-4 overflow-hidden rounded-2xl">
            <img :src="currentImage()" alt="{{ $gelanggang->nama }}" class="h-64 w-full rounded-2xl object-cover sm:h-80">

            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-linear-to-t from-slate-950/30 to-transparent"></div>

            <button type="button"
                class="absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-lg font-bold text-slate-700 shadow-sm transition hover:bg-white"
                @click="prevImage()"
                aria-label="Gambar sebelumnya">
                &#8249;
            </button>
            <button type="button"
                class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-lg font-bold text-slate-700 shadow-sm transition hover:bg-white"
                @click="nextImage()"
                aria-label="Gambar berikutnya">
                &#8250;
            </button>

            <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-2">
                <template x-for="(image, index) in galleryImages" :key="index">
                    <button type="button"
                        class="h-2.5 w-2.5 rounded-full transition"
                        :class="activeImageIndex === index ? 'bg-white shadow-sm' : 'bg-white/45'"
                        @click="goToImage(index)"
                        :aria-label="`Lihat gambar ${index + 1}`"></button>
                </template>
            </div>
        </div>

        <div class="mb-5 grid grid-cols-4 gap-2 sm:grid-cols-5" x-show="galleryImages.length > 1">
            <template x-for="(image, index) in galleryImages" :key="`thumb-${index}`">
                <button type="button" class="overflow-hidden rounded-xl border-2 transition"
                    :class="activeImageIndex === index ? 'border-teal-600' : 'border-transparent'"
                    @click="goToImage(index)">
                    <img :src="image" alt="Thumbnail gelanggang" class="h-16 w-full object-cover sm:h-20">
                </button>
            </template>
        </div>

        <h1 class="text-3xl font-bold">{{ $gelanggang->nama }}</h1>
        <p class="mt-2 text-slate-600">{{ $gelanggang->deskripsi }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach(($gelanggang->fasilitas ?? []) as $fasilitas)
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">{{ $fasilitas }}</span>
            @endforeach
        </div>

        <div class="mt-6">
            <h2 class="text-xl font-bold">Cek Jadwal</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                <input type="date" x-model="tanggal" class="rounded-xl border border-slate-300 bg-white px-3 py-2">
                <button class="btn-brand" @click="cekJadwal">Refresh</button>
            </div>
            <template x-if="jadwal.length">
                <div class="mt-4 space-y-2">
                    <template x-for="(slot, index) in jadwal" :key="index">
                        <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
                            <strong x-text="slot.jam_mulai + ' - ' + slot.jam_selesai"></strong>
                            <span class="ml-2 rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700" x-text="slot.status"></span>
                        </div>
                    </template>
                </div>
            </template>
            <p class="mt-3 text-sm text-slate-500" x-show="!jadwal.length">Belum ada booking di tanggal ini.</p>
        </div>
    </article>

    <aside class="glass-panel p-6">
        <h2 class="text-xl font-bold">Form Booking</h2>
        <p class="mt-1 text-sm text-slate-500">Wajib login dulu untuk booking.</p>
        <form class="mt-4 space-y-3" @submit.prevent="submitBooking">
            <div>
                <label class="mb-1 block text-sm font-medium">Tanggal</label>
                <input type="date" x-model="form.tanggal" :min="minDate" :max="maxDate" class="w-full rounded-xl border border-slate-300 px-3 py-2" required>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Jam Mulai</label>
                    <input type="time" x-model="form.jam_mulai" class="w-full rounded-xl border border-slate-300 px-3 py-2" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Jam Selesai</label>
                    <input type="time" x-model="form.jam_selesai" class="w-full rounded-xl border border-slate-300 px-3 py-2" required>
                </div>
            </div>
            <textarea x-model="form.catatan" class="w-full resize-none rounded-xl border border-slate-300 px-3 py-2" rows="3" placeholder="Catatan tambahan"></textarea>
            <button class="btn-brand w-full" type="submit">Konfirmasi Booking</button>
        </form>

        <p class="mt-3 text-sm font-medium" :class="error ? 'text-rose-700' : 'text-emerald-700'" x-text="message"></p>
    </aside>
</section>

<script>
    function bookingApp(gelanggangId, galleryImages = []) {
        return {
            tanggal: new Date().toISOString().slice(0, 10),
            minDate: new Date().toISOString().slice(0, 10),
            maxDate: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
            galleryImages,
            activeImageIndex: 0,
            slideInterval: null,
            jadwal: [],
            form: {
                tanggal: new Date().toISOString().slice(0, 10),
                jam_mulai: '',
                jam_selesai: '',
                catatan: '',
            },
            message: '',
            error: false,
            async init() {
                this.startAutoSlide();
                await this.cekJadwal();
            },
            currentImage() {
                return this.galleryImages[this.activeImageIndex] || this.galleryImages[0] || '';
            },
            goToImage(index) {
                this.activeImageIndex = index;
                this.restartAutoSlide();
            },
            nextImage() {
                if (!this.galleryImages.length) {
                    return;
                }

                this.activeImageIndex = (this.activeImageIndex + 1) % this.galleryImages.length;
                this.restartAutoSlide();
            },
            prevImage() {
                if (!this.galleryImages.length) {
                    return;
                }

                this.activeImageIndex = (this.activeImageIndex - 1 + this.galleryImages.length) % this.galleryImages.length;
                this.restartAutoSlide();
            },
            startAutoSlide() {
                if (this.galleryImages.length <= 1) {
                    return;
                }

                this.slideInterval = window.setInterval(() => {
                    this.activeImageIndex = (this.activeImageIndex + 1) % this.galleryImages.length;
                }, 3000);
            },
            restartAutoSlide() {
                if (this.slideInterval) {
                    window.clearInterval(this.slideInterval);
                    this.slideInterval = null;
                }

                this.startAutoSlide();
            },
            async cekJadwal() {
                try {
                    const data = await window.api(`/api/gelanggang/${gelanggangId}/jadwal?tanggal=${this.tanggal}`);
                    this.jadwal = data.booked_slots || [];
                } catch (e) {
                    this.message = e.message;
                    this.error = true;
                }
            },
            async submitBooking() {
                this.error = false;
                this.message = '';
                try {
                    const payload = {
                        gelanggang_id: gelanggangId,
                        ...this.form,
                    };
                    await window.api('/api/booking', {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    });
                    this.message = 'Booking berhasil dibuat.';
                    this.form.jam_mulai = '';
                    this.form.jam_selesai = '';
                    this.form.catatan = '';
                    await this.cekJadwal();
                } catch (e) {
                    this.message = e.message;
                    this.error = true;
                }
            }
        };
    }
</script>
@endsection
