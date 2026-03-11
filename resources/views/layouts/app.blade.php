<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Sewa Gelanggang' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @if (!app()->runningUnitTests())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body>
    <div id="global-loading" class="loading-overlay hidden" aria-hidden="true">
        <div class="loading-card" role="status" aria-live="polite" aria-label="Memuat">
            <div class="loading-spinner"></div>
            <p class="loading-text">Memproses permintaan...</p>
        </div>
    </div>

    <div class="mx-auto max-w-6xl px-4 pb-16 pt-6 sm:px-6 lg:px-8" @if(empty($hideNavbar)) x-data="navbarState()" x-init="init()" @endif>
        @if(empty($hideNavbar))
            <header class="glass-panel mb-8 flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                <a href="/" class="text-xl font-bold tracking-tight text-teal-800">ArenaRent</a>
                <nav class="flex flex-wrap items-center gap-2 text-sm font-medium">
                    <a :class="navLinkClass(['/gelanggang'])" href="/gelanggang" x-cloak x-show="!isLoggedIn || userRole !== 'admin'">Gelanggang</a>

                    <a :class="navLinkClass(['/dashboard'], true)" href="/dashboard" x-cloak x-show="isLoggedIn && userRole === 'member'">Sewaan</a>
                    <a :class="navLinkClass(['/admin'], true)" href="/admin" x-cloak x-show="isLoggedIn && userRole === 'admin'">Dashboard Admin</a>
                    <a :class="navLinkClass(['/admin/penyewaan'], true)" href="/admin/penyewaan" x-cloak x-show="isLoggedIn && userRole === 'admin'">Penyewaan</a>
                    <a :class="navLinkClass(['/admin/gelanggang'])" href="/admin/gelanggang" x-cloak x-show="isLoggedIn && userRole === 'admin'">Kelola Gelanggang</a>

                    <a :class="navLinkClass(['/login'], true)" href="/login" x-cloak x-show="!isLoggedIn">Login</a>
                    <a :class="navLinkClass(['/register'], true)" href="/register" x-cloak x-show="!isLoggedIn">Register</a>

                    <button class="btn-muted" type="button" x-cloak x-show="isLoggedIn" @click="logout">Logout</button>
                </nav>
            </header>
        @endif

        <main>
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    <script>
        window.requestLoader = {
            activeRequests: 0,
            element: null,
            ensureElement() {
                if (!this.element) {
                    this.element = document.getElementById('global-loading');
                }

                return this.element;
            },
            show() {
                this.activeRequests += 1;
                const element = this.ensureElement();
                if (element) {
                    element.classList.remove('hidden');
                    element.setAttribute('aria-hidden', 'false');
                }
            },
            hide() {
                this.activeRequests = Math.max(0, this.activeRequests - 1);
                const element = this.ensureElement();
                if (element && this.activeRequests === 0) {
                    element.classList.add('hidden');
                    element.setAttribute('aria-hidden', 'true');
                }
            }
        };

        window.authToken = {
            set(token) {
                localStorage.setItem('jwt_token', token);
            },
            get() {
                return localStorage.getItem('jwt_token');
            },
            clear() {
                localStorage.removeItem('jwt_token');
            }
        };

        window.api = async (url, options = {}) => {
            const token = window.authToken.get();
            const headers = { Accept: 'application/json', ...(options.headers || {}) };
            const isFormData = options.body instanceof FormData;

            if (!isFormData && !headers['Content-Type']) {
                headers['Content-Type'] = 'application/json';
            }

            if (token) {
                headers.Authorization = `Bearer ${token}`;
            }

            window.requestLoader.show();

            try {
                const response = await fetch(url, {
                    ...options,
                    headers,
                });

                const contentType = response.headers.get('content-type') || '';
                const body = contentType.includes('application/json') ? await response.json() : await response.text();

                if (!response.ok) {
                    const firstValidationError = body?.errors && typeof body.errors === 'object'
                        ? Object.values(body.errors)[0]?.[0]
                        : null;
                    const message = firstValidationError || body?.message || 'Terjadi kesalahan.';
                    throw new Error(message);
                }

                return body;
            } finally {
                window.requestLoader.hide();
            }
        };

        function navbarState() {
            return {
                isLoggedIn: false,
                userRole: null,
                currentPath: window.location.pathname,
                normalizePath(path) {
                    if (!path) {
                        return '/';
                    }

                    if (path.length > 1 && path.endsWith('/')) {
                        return path.slice(0, -1);
                    }

                    return path;
                },
                isActive(paths, exact = false) {
                    const current = this.normalizePath(this.currentPath);
                    const list = Array.isArray(paths) ? paths : [paths];

                    return list.some((path) => {
                        const target = this.normalizePath(path);
                        if (exact) {
                            return current === target;
                        }

                        return current === target || (target !== '/' && current.startsWith(target + '/'));
                    });
                },
                navLinkClass(paths, exact = false) {
                    return this.isActive(paths, exact)
                        ? 'rounded-xl bg-teal-700 px-4 py-2 font-semibold text-white shadow-sm hover:bg-teal-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-300 focus-visible:ring-offset-2'
                        : 'btn-muted';
                },
                async init() {
                    this.currentPath = window.location.pathname;
                    const token = window.authToken.get();
                    if (!token) {
                        this.isLoggedIn = false;
                        this.userRole = null;
                        return;
                    }

                    try {
                        const data = await window.api('/api/auth/me');
                        const user = data.user || null;
                        this.isLoggedIn = !!user;
                        this.userRole = user?.role || null;
                    } catch (e) {
                        window.authToken.clear();
                        this.isLoggedIn = false;
                        this.userRole = null;
                    }
                },
                async logout() {
                    try {
                        await window.api('/api/auth/logout', { method: 'POST' });
                    } catch (e) {
                        // Tetap lanjut clear token agar UI konsisten.
                    }

                    window.authToken.clear();
                    this.isLoggedIn = false;
                    this.userRole = null;
                    window.location.href = '/login';
                }
            };
        }
    </script>
</body>
</html>
