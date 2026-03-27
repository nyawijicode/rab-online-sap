<div class="space-y-4">
    @if (! $qc || ! ($qc['header'] ?? null))
        <div class="text-danger-600">
            Data QC tidak ditemukan di SAP.
        </div>
    @else
        @php
            $header  = $qc['header'];
            $details = $qc['details'] ?? [];
            $serials = $qc['serials'] ?? [];
        @endphp

        {{-- HEADER --}}
        <div class="grid grid-cols-4 gap-4 text-sm">
            <div><span class="font-medium">QC No:</span> {{ $header['DocNum'] ?? '-' }}</div>
            <div><span class="font-medium">GRPO No:</span> {{ $header['U_GRPO_NO'] ?? '-' }}</div>
            <div><span class="font-medium">Branch:</span> {{ $header['U_BRANCH'] ?? '-' }}</div>
            <div><span class="font-medium">Status:</span> {{ $header['Status'] ?? '-' }}</div>
        </div>

        {{-- DETAIL ITEMS --}}
        <h3 class="mt-4 font-semibold text-sm">Detail Items</h3>
        <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden w-full">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                    <tr>
                        <th class="px-2 py-1 text-left">Item Code</th>
                        <th class="px-2 py-1 text-left">Description</th>
                        <th class="px-2 py-1 text-left">Warehouse</th>
                        <th class="px-2 py-1 text-right">Qty QC</th>
                        <th class="px-2 py-1 text-right">Qty Lolos</th>
                        <th class="px-2 py-1 text-right">Qty Tidak Lolos</th>
                        <th class="px-2 py-1 text-left">Teknisi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($details as $row)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="px-2 py-1">{{ $row['U_ITEMCODE'] ?? '-' }}</td>
                            <td class="px-2 py-1">
                                {{ $row['U_ITEMNAME'] ?? '-' }}
                            </td>
                            <td class="px-2 py-1">{{ $row['U_WHS'] ?? '-' }}</td>
                            <td class="px-2 py-1 text-right">{{ $row['U_GRPO_QTY'] ?? 0 }}</td>
                            <td class="px-2 py-1 text-right">{{ $row['U_QTY_LOLOS'] ?? 0 }}</td>
                            <td class="px-2 py-1 text-right">{{ $row['U_QTY_TLOLOS'] ?? 0 }}</td>
                            <td class="px-2 py-1">{{ $row['U_TEKNISI'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-2 py-2 text-center">
                                Tidak ada detail item.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- SERIAL / BATCH --}}
        <h3 class="mt-6 font-semibold text-sm">Serial / Batch</h3>
        <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden w-full">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                    <tr>
                        <th class="px-2 py-1 text-left">Item Code</th>
                        <th class="px-2 py-1 text-left">Serial No</th>
                        <th class="px-2 py-1 text-left">Description</th>
                        <th class="px-2 py-1 text-right">Qty</th>
                        <th class="px-2 py-1 text-left">Gudang</th>
                        <th class="px-2 py-1 text-left">Teknisi</th>
                        <th class="px-2 py-1 text-left">Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($serials as $s)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="px-2 py-1">{{ $s['U_ITEMCODE'] ?? '-' }}</td>
                            <td class="px-2 py-1">{{ $s['U_SNBN'] ?? '-' }}</td>
                            <td class="px-2 py-1">
                                {{ $s['U_ITEMNAME'] ?? '-' }}
                            </td>
                            <td class="px-2 py-1 text-right">{{ $s['U_QTY'] ?? 0 }}</td>
                            <td class="px-2 py-1">{{ $s['U_WHS'] ?? '-' }}</td>
                            <td class="px-2 py-1">{{ $s['U_TEKNISI'] ?? '-' }}</td>
                            <td class="px-2 py-1">{{ $s['U_REMARKS'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-2 py-2 text-center">
                                Tidak ada serial/batch.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
