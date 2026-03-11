@extends('layouts.app')
@php($hideNavbar = true)

@section('content')
<div class="auth-stage">
<section class="auth-shell" x-data="loginApp()">
    <aside class="relative hidden min-h-140 overflow-hidden bg-teal-800 p-10 text-white lg:block">
        <div class="absolute -right-16 top-20 h-48 w-48 rounded-full bg-amber-300/25 blur-2xl"></div>
        <div class="absolute -left-12 bottom-10 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
        <p class="relative z-10 text-sm uppercase tracking-[0.24em] text-amber-200">Welcome Back</p>
        <h1 class="relative z-10 mt-4 text-4xl font-bold leading-tight">Masuk dan mulai booking gelanggang favoritmu.</h1>
        <p class="relative z-10 mt-4 max-w-sm text-teal-100">Pantau jadwal, lakukan reservasi cepat, dan cek histori sewa dari dashboard Anda.</p>
        <figure class="relative z-10 mt-8 overflow-hidden rounded-2xl border border-white/30 bg-white/10 shadow-xl">
            <img src="https://images.unsplash.com/photo-1521412644187-c49fa049e84d?auto=format&fit=crop&w=1200&q=80" alt="Lapangan futsal indoor" class="h-48 w-full object-cover">
        </figure>
    </aside>

    <div class="auth-panel">
        <div class="mb-5 rounded-2xl bg-teal-800/95 p-4 text-white lg:hidden">
            <p class="text-xs uppercase tracking-[0.2em] text-amber-200">Welcome Back</p>
            <p class="mt-2 text-sm text-teal-100">Masuk dan mulai booking gelanggang favoritmu.</p>
        </div>

        <h2 class="text-2xl font-bold text-slate-900">Login Akun</h2>
        <p class="mt-1 text-sm text-slate-500">Silakan masuk menggunakan email dan password.</p>

        <form class="mt-6 space-y-3" @submit.prevent="submit">
            <input type="email" x-model="email" class="auth-input" placeholder="Email" required>
            <input type="password" x-model="password" class="auth-input" placeholder="Password" required>
            <button class="btn-brand w-full" type="submit">Login</button>
        </form>

        <p class="mt-3 text-sm" :class="error ? 'text-rose-700' : 'text-emerald-700'" x-text="message"></p>
        <p class="mt-5 text-xs text-slate-500">Belum punya akun? <a class="auth-switch-link" href="/register">Pindah ke Register</a></p>
    </div>
</section>
</div>

<script>
    function loginApp() {
        return {
            email: '',
            password: '',
            message: '',
            error: false,
            async submit() {
                this.error = false;
                this.message = '';

                try {
                    const data = await window.api('/api/auth/login', {
                        method: 'POST',
                        body: JSON.stringify({
                            email: this.email,
                            password: this.password,
                        }),
                    });

                    window.authToken.set(data.token);
                    this.message = 'Login berhasil. Mengarahkan...';

                    if (data.user.role === 'admin') {
                        window.location.href = '/admin';
                        return;
                    }

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
