<!DOCTYPE html>
<html lang="en" class="h-full" id="html-root">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Sistem Internal SAP</title>

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

        <main class="flex-1 flex flex-col items-center justify-center p-4">
            <div class="max-w-md w-full text-center space-y-6">
                <!-- Error Code Badge -->
                <div
                    class="inline-flex items-center justify-center h-24 w-24 rounded-full bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 ring-1 ring-primary-100 dark:ring-primary-900/40">
                    <span class="text-3xl font-bold">@yield('code')</span>
                </div>

                <!-- Error Title & Message -->
                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">
                        @yield('title')
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        @yield('message')
                    </p>
                </div>

                <!-- Action Button -->
                <div class="pt-4">
                    <a href="{{ url('/') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-primary-600 text-white text-sm font-medium px-6 py-2.5 shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 transition-all hover:shadow-primary-500/25">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Kembali ke Beranda
                    </a>
                </div>
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