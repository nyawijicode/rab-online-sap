<!DOCTYPE html>
<html lang="en" class="h-full" id="html-root">

<head>
    <meta charset="UTF-8">
    <title>Sistem Internal SAP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        // Konfigurasi Tailwind (opsional)
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#ef4444',
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        },
                    },
                },
            },
            darkMode: 'class',
        };
    </script>

    {{-- Set tema awal (dark/light) --}}
    <script>
        (function () {
            const storedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const html = document.documentElement;

            if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }
        })();
    </script>
</head>

<body class="h-full bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 antialiased">
    @php
        use Illuminate\Support\Str;
        // supaya aman kalau $panels belum dikirim dari route
        $panels = $panels ?? collect();
    @endphp

    <div class="min-h-screen flex flex-col">

        {{-- Navbar --}}
        <header
            class="border-b border-slate-200/70 dark:border-slate-800/80 bg-white/80 dark:bg-slate-950/80 backdrop-blur">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div
                        class="h-9 w-9 rounded-2xl bg-primary-600 text-white flex items-center justify-center font-bold shadow-sm">
                        SI
                    </div>
                    <div>
                        <div class="text-sm font-semibold tracking-wide uppercase text-slate-700 dark:text-slate-200">
                            Sistem Internal SAP
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            Premmiere Group
                        </div>
                    </div>
                </div>

                {{-- Right side: Dark/Light toggle --}}
                <div class="flex items-center gap-3">
                    <button id="theme-toggle" type="button"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 dark:border-slate-700/80 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 bg-white/80 dark:bg-slate-900/80 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        <span id="theme-icon-sun" class="hidden">
                            {{-- Sun icon --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="4"></circle>
                                <path d="M12 2v2" />
                                <path d="M12 20v2" />
                                <path d="m4.93 4.93 1.41 1.41" />
                                <path d="m17.66 17.66 1.41 1.41" />
                                <path d="M2 12h2" />
                                <path d="M20 12h2" />
                                <path d="m6.34 17.66-1.41 1.41" />
                                <path d="m19.07 4.93-1.41 1.41" />
                            </svg>
                        </span>
                        <span id="theme-icon-moon" class="hidden">
                            {{-- Moon icon --}}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                            </svg>
                        </span>
                        <span id="theme-label" class="hidden sm:inline">Tema</span>
                    </button>
                </div>
            </div>
        </header>

        {{-- Hero + cards --}}
        <main class="flex-1">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
                {{-- Hero section --}}
                <div class="grid gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] items-center">
                    <div class="space-y-6">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-primary-100/80 dark:border-primary-900/60 bg-primary-50/70 dark:bg-primary-950/40 px-3 py-1 text-xs font-medium text-primary-700 dark:text-primary-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-primary-500 animate-pulse"></span>
                            Internal Access Portal
                        </div>

                        <div class="space-y-3">
                            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-semibold tracking-tight">
                                Satu pintu untuk
                                <span class="text-primary-600 dark:text-primary-400">semua sistem internal</span>
                            </h1>
                            <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 max-w-xl">
                                Pilih modul sesuai kebutuhan: RAB Perjalanan Dinas, Form Pengajuan, Pickup & QC, dan
                                panel lainnya.
                                Akses diatur oleh Tim IT, jika ada perubahan hubungi Tim IT.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a href="https://wa.me/6289676310137" target="_blank"
                                class="inline-flex items-center gap-2 rounded-full bg-primary-600 text-white text-xs sm:text-sm font-medium px-4 py-2 shadow-sm hover:bg-primary-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50 dark:focus-visible:ring-offset-slate-950 transition">
                                Hubungi Tim IT
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M5 12h14"></path>
                                    <path d="m12 5 7 7-7 7"></path>
                                </svg>
                            </a>

                            <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                Terhubung dengan SAP & sistem internal lainnya
                            </div>
                        </div>
                    </div>

                    {{-- Right side: glass card --}}
                    <div class="relative">
                        <div
                            class="absolute inset-0 rounded-3xl bg-gradient-to-br from-primary-500/10 via-primary-500/5 to-emerald-400/10 blur-3xl -z-10">
                        </div>

                        <div
                            class="rounded-3xl border border-slate-200/70 dark:border-slate-800/80 bg-white/80 dark:bg-slate-950/80 shadow-xl shadow-slate-900/5 dark:shadow-black/40 p-5 sm:p-6 space-y-5 backdrop-blur">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Ringkasan Akses
                                    </p>
                                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                        Panel utama yang tersedia
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-200 border border-emerald-100/60 dark:border-emerald-900 px-3 py-1 text-[11px] font-semibold">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Aktif
                                </span>
                            </div>

                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Akses modul diatur oleh Tim IT, jika ada perubahan hubungi Tim IT.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Grid kartu akses panel (DINAMIS dari DB) --}}
                <section class="mt-12 sm:mt-16">
                    <h2 class="text-sm sm:text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">
                        Pilih Panel
                    </h2>

                    @if ($panels->isEmpty())
                        <div
                            class="rounded-2xl border border-dashed border-slate-300/80 dark:border-slate-700/80 bg-white/70 dark:bg-slate-950/70 p-6 text-center text-sm text-slate-500 dark:text-slate-400">
                            Belum ada panel yang dikonfigurasi. Tambahkan panel di menu
                            <span class="font-semibold">Portal Panel</span> pada Panel Admin.
                        </div>
                    @else
                        <div class="grid gap-4 sm:gap-5 md:grid-cols-2 lg:grid-cols-4">
                            @foreach ($panels as $panel)
                                <a href="{{ url($panel->url) }}"
                                    class="group rounded-2xl border border-slate-200/70 dark:border-slate-800/80 bg-white/80 dark:bg-slate-950/80 p-4 sm:p-5 flex flex-col justify-between hover:border-primary-400/80 hover:-translate-y-0.5 hover:shadow-md hover:shadow-primary-900/10 dark:hover:shadow-black/40 transition">
                                    <div class="flex items-center justify-between gap-3">
                                        <div
                                            class="h-9 w-9 rounded-xl bg-primary-100 text-primary-700 dark:bg-primary-950/60 dark:text-primary-700 flex items-center justify-center text-[11px] font-semibold">
                                            {{ Str::upper(Str::substr($panel->code, 0, 2)) }}
                                        </div>
                                        @if ($panel->badge)
                                            <span
                                                class="inline-flex items-center rounded-full bg-slate-50 text-slate-700 dark:bg-slate-900/80 dark:text-slate-200 border border-slate-200/70 dark:border-slate-700/80 px-2.5 py-0.5 text-[11px] font-medium text-right">
                                                {{ $panel->badge }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="mt-4 space-y-1.5">
                                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                            {{ $panel->name }}
                                        </h3>
                                        @if ($panel->description)
                                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                                {{ $panel->description }}
                                            </p>
                                        @endif
                                    </div>
                                    <div
                                        class="mt-3 flex items-center gap-1 text-xs font-medium text-primary-600 dark:text-primary-300">
                                        Masuk panel
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-3.5 w-3.5 group-hover:translate-x-0.5 transition-transform"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M5 12h14"></path>
                                            <path d="m12 5 7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-slate-200/70 dark:border-slate-800/80 bg-white/80 dark:bg-slate-950/80">
            <div
                class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-2">
                <p class="text-[11px] text-slate-500 dark:text-slate-500">
                    &copy; {{ date('Y') }} Premmiere Group | Sistem Internal SAP
                </p>
                <p class="text-[11px] text-slate-500 dark:text-slate-500">
                    Akses diatur oleh Tim IT |
                    <a href="https://wa.me/6289676310137" target="_blank" style="color: grey; text-decoration:none;"
                        onmouseover="this.style.color='red'" onmouseout="this.style.color='grey'">
                        jika ada perubahan hubungi Tim IT
                    </a>
                </p>
            </div>
        </footer>
    </div>

    <script>
        // Toggle tema (dark / light)
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('theme-toggle');
            const html = document.documentElement;
            const iconSun = document.getElementById('theme-icon-sun');
            const iconMoon = document.getElementById('theme-icon-moon');

            function syncIcons() {
                const isDark = html.classList.contains('dark');
                if (isDark) {
                    iconMoon.classList.add('hidden');
                    iconSun.classList.remove('hidden');
                } else {
                    iconSun.classList.add('hidden');
                    iconMoon.classList.remove('hidden');
                }
            }

            btn?.addEventListener('click', function () {
                const isDark = html.classList.toggle('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                syncIcons();
            });

            syncIcons();
        });
    </script>
</body>

</html>