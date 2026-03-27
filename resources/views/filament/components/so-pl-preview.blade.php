@php
use Illuminate\Support\Str;

$rabPath = $record->upload_file_rab;
$spPath = $record->upload_file_sp;
$npwpPath = $record->upload_file_npwp;

$rabUrl = $rabPath ? asset('storage/' . $rabPath) : null;
$spUrl = $spPath ? asset('storage/' . $spPath) : null;
$npwpUrl = $npwpPath ? asset('storage/' . $npwpPath) : null;

// Pilih file utama buat di-preview (prioritas: RAB -> SP -> NPWP)
$previewUrl = $rabUrl ?? $spUrl ?? $npwpUrl;
@endphp

<div class="space-y-4">

    {{-- ====================== --}}
    {{-- Info Singkat SO PL     --}}
    {{-- ====================== --}}
    <div class="border rounded-md p-4 bg-white shadow-sm dark:bg-gray-900 dark:border-gray-700">
        <h2 class="text-sm font-semibold mb-2 text-gray-800 dark:text-gray-100">
            Informasi Pengajuan SO PL
        </h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2 text-sm">
            <div>
                <dt class="font-medium text-gray-600 dark:text-gray-300">Nama Dinas</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $record->nama_dinas ?? '-' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-600 dark:text-gray-300">No SO (PL)</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $record->no_so_pl ?? '-' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-600 dark:text-gray-300">Nama PIC</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $record->nama_pic ?? '-' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-600 dark:text-gray-300">Nomor PIC</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $record->nomor_pic ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    {{-- ================================== --}}
    {{-- Daftar Dokumen Lampiran Utama      --}}
    {{-- ================================== --}}
    <div class="border rounded-md p-4 bg-white shadow-sm dark:bg-gray-900 dark:border-gray-700">
        <h2 class="text-sm font-semibold mb-3 text-gray-800 dark:text-gray-100">
            Dokumen Lampiran
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            {{-- Upload File RAB --}}
            <div class="border rounded-md p-3 bg-gray-50 dark:bg-gray-800/80 dark:border-gray-700">
                <div class="flex items-start gap-2">
                    <div class="text-xl">📄</div>
                    <div class="flex-1">
                        <div class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                            Upload File RAB
                        </div>
                        @if ($rabUrl)
                        <div class="mt-1 text-xs text-gray-600 dark:text-gray-300 break-all">
                            {{ basename($rabPath) }}
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <a href="{{ $rabUrl }}" target="_blank"
                                class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md border
                                          border-blue-600 text-blue-600 hover:bg-blue-50
                                          dark:border-blue-400 dark:text-blue-300 dark:hover:bg-blue-950/40">
                                Lihat / Download
                            </a>
                        </div>
                        @else
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Belum ada file diupload.
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Upload File SP --}}
            <div class="border rounded-md p-3 bg-gray-50 dark:bg-gray-800/80 dark:border-gray-700">
                <div class="flex items-start gap-2">
                    <div class="text-xl">📄</div>
                    <div class="flex-1">
                        <div class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                            Upload File SP
                        </div>
                        @if ($spUrl)
                        <div class="mt-1 text-xs text-gray-600 dark:text-gray-300 break-all">
                            {{ basename($spPath) }}
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <a href="{{ $spUrl }}" target="_blank"
                                class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md border
                                          border-blue-600 text-blue-600 hover:bg-blue-50
                                          dark:border-blue-400 dark:text-blue-300 dark:hover:bg-blue-950/40">
                                Lihat / Download
                            </a>
                        </div>
                        @else
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Belum ada file diupload.
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Upload File NPWP --}}
            <div class="border rounded-md p-3 bg-gray-50 dark:bg-gray-800/80 dark:border-gray-700">
                <div class="flex items-start gap-2">
                    <div class="text-xl">📄</div>
                    <div class="flex-1">
                        <div class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                            Upload File NPWP
                        </div>
                        @if ($npwpUrl)
                        <div class="mt-1 text-xs text-gray-600 dark:text-gray-300 break-all">
                            {{ basename($npwpPath) }}
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <a href="{{ $npwpUrl }}" target="_blank"
                                class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md border
                                          border-blue-600 text-blue-600 hover:bg-blue-50
                                          dark:border-blue-400 dark:text-blue-300 dark:hover:bg-blue-950/40">
                                Lihat / Download
                            </a>
                        </div>
                        @else
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Belum ada file diupload.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================= --}}
    {{-- Preview Semua Dokumen   --}}
    {{-- ======================= --}}

    @php
    $files = [
    'Upload File RAB' => $rabUrl,
    'Upload File SP' => $spUrl,
    'Upload File NPWP' => $npwpUrl,
    ];
    @endphp

    @foreach ($files as $label => $url)
    @if ($url)
    <div class="border rounded-md p-4 bg-white shadow-sm dark:bg-gray-900 dark:border-gray-700 mt-4">

        <h2 class="text-sm font-semibold mb-2 text-gray-800 dark:text-gray-100">
            Preview {{ $label }} ({{ basename(parse_url($url, PHP_URL_PATH)) }})
        </h2>

        @php
        $path = parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        @endphp

        {{-- Jika PDF → preview embed --}}
        @if ($ext === 'pdf')
        <div class="w-full bg-white dark:bg-gray-900 rounded-md shadow dark:shadow-md" style="height: 75vh;">
            <object data="{{ $url }}" type="application/pdf" class="w-full h-full rounded-md">
                <div class="p-4 text-center text-sm text-gray-700 dark:text-gray-200">
                    PDF tidak bisa ditampilkan.
                    <a href="{{ $url }}" class="text-blue-600 underline dark:text-blue-400" target="_blank">
                        Download PDF
                    </a>
                </div>
            </object>
        </div>

        {{-- Jika gambar → tampilkan <img> --}}
        @elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
        <div class="w-full bg-white dark:bg-gray-900 rounded-md shadow dark:shadow-md flex justify-center">
            <img src="{{ $url }}"
                alt="{{ $label }}"
                class="max-h-[75vh] w-auto rounded-md object-contain">
        </div>
        <div class="mt-2 text-center">
            <a href="{{ $url }}" class="text-blue-600 underline dark:text-blue-400" target="_blank">
                Buka / Download Gambar
            </a>
        </div>

        {{-- Selain itu → hanya tombol download --}}
        @else
        <div class="p-3 text-center text-sm text-gray-700 dark:text-gray-200">
            File tidak bisa di-preview.
            <a href="{{ $url }}" class="text-blue-600 underline dark:text-blue-400" target="_blank">
                Download File
            </a>
        </div>
        @endif

    </div>
    @endif
    @endforeach

</div>