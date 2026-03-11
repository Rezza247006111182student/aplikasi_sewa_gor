@extends('layouts.app')
@php($hideNavbar = true)

@section('content')
<div class="auth-stage">
<section class="auth-shell" x-data="registerApp()">
    <aside class="relative hidden min-h-155 overflow-hidden bg-teal-800 p-10 text-white lg:block">
        <div class="absolute -right-16 top-20 h-48 w-48 rounded-full bg-amber-300/25 blur-2xl"></div>
        <div class="absolute -left-12 bottom-10 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
        <p class="relative z-10 text-sm uppercase tracking-[0.24em] text-amber-200">Welcome Sign</p>
        <h1 class="relative z-10 mt-4 text-4xl font-bold leading-tight">Gabung sekarang dan booking lapangan tanpa ribet.</h1>
        <p class="relative z-10 mt-4 max-w-sm text-teal-100">Buat akun member untuk akses jadwal real-time, reservasi cepat, dan histori transaksi.</p>
        <figure class="relative z-10 mt-8 overflow-hidden rounded-2xl border border-white/30 bg-white/10 shadow-xl">
            <img src="https://images.unsplash.com/photo-1624526267942-ab0ff8a3e972?auto=format&fit=crop&w=1200&q=80" alt="Lapangan badminton" class="h-52 w-full object-cover">
        </figure>
    </aside>

    <div class="auth-panel">
        <div class="mb-5 rounded-2xl bg-teal-800/95 p-4 text-white lg:hidden">
            <p class="text-xs uppercase tracking-[0.2em] text-amber-200">Welcome Sign</p>
            <p class="mt-2 text-sm text-teal-100">Gabung sekarang dan booking lapangan tanpa ribet.</p>
        </div>

        <h2 class="text-2xl font-bold text-slate-900">Register Akun</h2>
        <p class="mt-1 text-sm text-slate-500">Lengkapi data di bawah untuk membuat akun.</p>

        <form class="mt-6 space-y-3" @submit.prevent="submit">
            <input type="text" x-model="name" class="auth-input" placeholder="Nama lengkap" required>
            <input type="email" x-model="email" class="auth-input" placeholder="Email" required>
            <input type="text" x-model="phone" class="auth-input" placeholder="No. HP">
            <input type="password" x-model="password" class="auth-input" placeholder="Password minimal 8 karakter" required>
            <input type="password" x-model="password_confirmation" class="auth-input" placeholder="Konfirmasi password" required>
            <button class="btn-brand w-full" type="submit">Register</button>
        </form>

        <p class="mt-3 text-sm" :class="error ? 'text-rose-700' : 'text-emerald-700'" x-text="message"></p>
        <p class="mt-5 text-xs text-slate-500">Sudah punya akun? <a class="auth-switch-link" href="/login">Pindah ke Login</a></p>
    </div>
</section>
</div>

<script>
    function registerApp() {
        return {
            name: '',
            email: '',
            phone: '',
            password: '',
            password_confirmation: '',
            message: '',
            error: false,
            async submit() {
                this.error = false;
                this.message = '';
                try {
                    const data = await window.api('/api/auth/register', {
                        method: 'POST',
                        body: JSON.stringify({
                            name: this.name,
                            email: this.email,
                            phone: this.phone,
                            password: this.password,
                            password_confirmation: this.password_confirmation,
                        }),
                    });

                    window.authToken.set(data.token);
                    this.message = 'Registrasi berhasil. Mengarahkan...';
                    window.location.href = '/dashboard';
                } catch (e) {
                    this.error = true;
                    this.message = e.message;
                }
            }
        }
    }
</script>
@endsection
