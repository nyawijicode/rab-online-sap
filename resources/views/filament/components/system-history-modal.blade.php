<div class="space-y-6 p-2">
    @if ($record->histories->count() > 0)
        <ol class="relative border-l border-gray-200 dark:border-gray-700 ml-3">
            @foreach ($record->histories()->latest()->get() as $history)
                <li class="mb-8 ml-10">
                    <span
                        class="absolute flex items-center justify-center w-8 h-8 rounded-full -left-4 ring-4 ring-white dark:ring-gray-900 bg-gray-100 text-gray-500">
                        @svg('heroicon-o-clock', 'w-5 h-5')
                    </span>

                    <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        <div class="items-center justify-between mb-3 sm:flex">
                            <time class="mb-1 text-xs font-normal text-gray-400 sm:order-last sm:mb-0">
                                <br> {{ $history->created_at->locale('id')->isoFormat('dddd, D MMMM Y, HH:mm') }}
                            </time>
                            <div class="text-sm font-normal text-gray-500 dark:text-gray-300">
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    {{ $history->user->name ?? 'Sistem' }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="p-3 text-sm italic font-normal text-gray-500 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                            {{ $history->description }}
                        </div>

                        @if ($history->old_value || $history->new_value)
                            <div class="mt-3 flex items-center gap-2 text-xs">
                                <span
                                    class="font-bold text-gray-700 dark:text-gray-200">{{ ucfirst(str_replace('_', ' ', $history->field)) }}:</span>
                                <span
                                    class="line-through text-red-500 bg-red-50 px-1 rounded">{{ $history->old_value ?: '(kosong)' }}</span>
                                <span class="text-gray-400">&rarr;</span>
                                <span
                                    class="font-medium text-green-600 bg-green-50 px-1 rounded">{{ $history->new_value ?: '(kosong)' }}</span>
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @else
        <div class="flex flex-col items-center justify-center py-8 text-gray-500">
            @svg('heroicon-o-archive-box', 'w-12 h-12 mb-2 text-gray-300')
            <p>Belum ada riwayat aktivitas.</p>
        </div>
    @endif
</div>