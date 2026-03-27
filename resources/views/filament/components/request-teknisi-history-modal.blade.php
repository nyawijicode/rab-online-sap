<div class="space-y-6 p-2">
    @php
        // Helper untuk menerjemahkan field/value lama jika diperlukan secara dinamis
        $transField = function ($field) {
            return match ($field) {
                'status' => 'Status',
                'tanggal_penjadwalan' => 'Jadwal Pelaksanaan',
                'final_status' => 'Keputusan Akhir',
                default => ucfirst(str_replace('_', ' ', $field)),
            };
        };

        // Helper icon berdasarkan field/tipe
        $getIcon = function ($history) {
            if ($history->description === 'Permintaan dibuat' || $history->description === 'Request created') {
                return 'heroicon-o-plus-circle';
            }
            if ($history->field === 'status') {
                return 'heroicon-o-arrow-path';
            }
            if ($history->field === 'tanggal_penjadwalan') {
                return 'heroicon-o-calendar';
            }
            if ($history->field === 'final_status') {
                return $history->new_value === 'ditolak' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle';
            }
            return 'heroicon-o-information-circle';
        };

        $getColor = function ($history) {
            if ($history->description === 'Permintaan dibuat') return 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300';
             if ($history->field === 'final_status') {
                return $history->new_value === 'ditolak'
                    ? 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300'
                    : 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300';
            }
            return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300';
        };
    @endphp

    @if ($record->histories->count() > 0)
        <ol class="relative border-l border-gray-200 dark:border-gray-700 ml-3">
            @foreach ($record->histories()->latest()->get() as $history)
                @php
                    $icon = $getIcon($history);
                    $colorClass = $getColor($history);
                @endphp
                <li class="mb-8 ml-10">
                    <span class="absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white dark:ring-gray-900 {{ $colorClass }}">
                        @svg($icon, 'w-5 h-5')
                    </span>
                    
                    <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        <div class="items-center justify-between mb-3 sm:flex">
                            <time class="mb-1 text-xs font-normal text-gray-400 sm:order-last sm:mb-0">
                               <br> {{ $history->created_at->locale('id')->isoFormat('dddd, D MMMM Y, HH:mm') }}
                            </time>
                            <div class="text-sm font-normal text-gray-500 dark:text-gray-300">
                                <span class="font-semibold text-gray-900 dark:text-white hover:underline">
                                    {{ $history->user->name ?? 'Sistem' }}
                                </span>
                            </div>
                        </div>
                        <div class="p-3 text-sm italic font-normal text-gray-500 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                            {{ $history->description }}
                        </div>

                        @if ($history->old_value || $history->new_value)
                            <div class="mt-3 flex items-center gap-2 text-xs">
                                @if ($history->field)
                                    <span class="font-bold text-gray-700 dark:text-gray-200">{{ $transField($history->field) }}:</span>
                                @endif
                                <span class="line-through text-red-500 bg-red-50 px-1 rounded">{{ $history->old_value ?: '(kosong)' }}</span>
                                <span class="text-gray-400">&rarr;</span>
                                <span class="font-medium text-green-600 bg-green-50 px-1 rounded">{{ $history->new_value ?: '(kosong)' }}</span>
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @else
        <div class="flex flex-col items-center justify-center py-8 text-gray-500">
            @svg('heroicon-o-archive-box', 'w-12 h-12 mb-2 text-gray-300')
            <p>Belum ada riwayat aktivitas yang tercatat.</p>
        </div>
    @endif
</div>