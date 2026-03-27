<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pilih Sistem – Portal Internal</title>

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center">

    <div class="absolute inset-0 opacity-20 pointer-events-none bg-[radial-gradient(circle_at_top,_#38bdf8,_transparent_60%),_radial-gradient(circle_at_bottom,_#6366f1,_transparent_60%)]"></div>

    <div class="relative z-10 w-full max-w-4xl px-4">
        <div class="bg-slate-900/70 border border-slate-700 shadow-2xl rounded-3xl p-8 md:p-10 backdrop-blur">

            {{-- Header --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold tracking-tight">
                        Portal Sistem Internal
                    </h1>
                    <p class="text-slate-400 mt-1">
                        Silakan pilih sistem yang ingin kamu gunakan.
                    </p>
                </div>

                <div class="flex items-center gap-2 text-sm text-slate-400">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                    <span>Server status: <span class="font-semibold text-emerald-300">Online</span></span>
                </div>
            </div>

            {{-- Grid pilihan sistem --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">

                {{-- RAB Online --}}
                <a href="{{ url('/web') }}"
                   class="group relative overflow-hidden rounded-2xl border border-slate-700 bg-slate-800/60 p-5 md:p-6
                          hover:border-sky-400 hover:bg-slate-800 transition-all duration-200 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="h-10 w-10 rounded-2xl bg-sky-500/10 flex items-center justify-center">
                                    <span class="text-xl">🧾</span>
                                </div>
                                <h2 class="text-lg font-semibold">
                                    RAB Online
                                </h2>
                            </div>
                            <span class="text-[11px] px-2 py-1 rounded-full bg-sky-500/10 text-sky-300 border border-sky-500/30">
                                Main System
                            </span>
                        </div>
                        <p class="text-sm text-slate-400">
                            Pengajuan RAB, approval, monitoring, dan laporan keuangan proyek.
                        </p>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-xs text-slate-400">
                        <span>Path: <span class="font-mono text-slate-200">/web</span></span>
                        <span class="inline-flex items-center gap-1 group-hover:text-sky-300">
                            Masuk
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 -translate-x-0.5 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </span>
                    </div>
                </a>

                {{-- Sistem Gudang / QC Gudang --}}
                <a href="{{ url('/base') }}"
                   class="group relative overflow-hidden rounded-2xl border border-slate-700 bg-slate-800/60 p-5 md:p-6
                          hover:border-emerald-400 hover:bg-slate-800 transition-all duration-200 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="h-10 w-10 rounded-2xl bg-emerald-500/10 flex items-center justify-center">
                                    <span class="text-xl">📦</span>
                                </div>
                                <h2 class="text-lg font-semibold">
                                    Sistem Gudang / QC
                                </h2>
                            </div>
                            <span class="text-[11px] px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/30">
                                Warehouse
                            </span>
                        </div>
                        <p class="text-sm text-slate-400">
                            QC gudang, stok, penerimaan barang, dan monitoring per cabang/perusahaan.
                        </p>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-xs text-slate-400">
                        <span>Path: <span class="font-mono text-slate-200">/base</span></span>
                        <span class="inline-flex items-center gap-1 group-hover:text-emerald-300">
                            Masuk
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 -translate-x-0.5 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </span>
                    </div>
                </a>

                {{-- Sistem Pickup --}}
                <a href="{{ url('/pickup') }}"
                   class="group relative overflow-hidden rounded-2xl border border-slate-700 bg-slate-800/60 p-5 md:p-6
                          hover:border-amber-400 hover:bg-slate-800 transition-all duration-200 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="h-10 w-10 rounded-2xl bg-amber-500/10 flex items-center justify-center">
                                    <span class="text-xl">🚚</span>
                                </div>
                                <h2 class="text-lg font-semibold">
                                    Sistem Pickup
                                </h2>
                            </div>
                            <span class="text-[11px] px-2 py-1 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/30">
                                Logistics
                            </span>
                        </div>
                        <p class="text-sm text-slate-400">
                            Ambil list PO dropship dari SAP, pengaturan vendor, jadwal pickup, dan ekspedisi.
                        </p>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-xs text-slate-400">
                        <span>Path: <span class="font-mono text-slate-200">/pickup</span></span>
                        <span class="inline-flex items-center gap-1 group-hover:text-amber-300">
                            Masuk
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 -translate-x-0.5 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </span>
                    </div>
                </a>

                {{-- Sistem QC Barang --}}
                <a href="{{ url('/qc') }}"
                   class="group relative overflow-hidden rounded-2xl border border-slate-700 bg-slate-800/60 p-5 md:p-6
                          hover:border-rose-400 hover:bg-slate-800 transition-all duration-200 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="h-10 w-10 rounded-2xl bg-rose-500/10 flex items-center justify-center">
                                    <span class="text-xl">🔍</span>
                                </div>
                                <h2 class="text-lg font-semibold">
                                    Sistem QC Barang
                                </h2>
                            </div>
                            <span class="text-[11px] px-2 py-1 rounded-full bg-rose-500/10 text-rose-300 border border-rose-500/30">
                                Quality
                            </span>
                        </div>
                        <p class="text-sm text-slate-400">
                            Distribusi tugas QC per teknisi, input hasil QC, dan tracking status per SN.
                        </p>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-xs text-slate-400">
                        <span>Path: <span class="font-mono text-slate-200">/qc</span></span>
                        <span class="inline-flex items-center gap-1 group-hover:text-rose-300">
                            Masuk
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 -translate-x-0.5 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </span>
                    </div>
                </a>

            </div>

            {{-- Footer --}}
            <div class="mt-8 text-white text-xs text-slate-500 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <span>© {{ date('Y') }} Staff IT || Solusi Arya Prima</span>
                <span>v1.0 · Multi-module: RAB · Gudang · Pickup · QC</span>
            </div>
        </div>
    </div>
</body>
</html>
