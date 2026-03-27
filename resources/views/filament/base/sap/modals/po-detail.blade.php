{{-- resources/views/filament/base/sap/modals/po-detail.blade.php --}}

<div class="space-y-4">
    @if (! $header)
        <div class="text-danger-600">
            Purchase Order tidak ditemukan di SAP.
        </div>
    @else
        {{-- HEADER --}}
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="space-y-1">
                <div><span class="font-medium">DocEntry:</span> {{ $header['DocEntry'] ?? '-' }}</div>
                <div><span class="font-medium">DocNum (PO No):</span> {{ $header['DocNum'] ?? '-' }}</div>
                <div><span class="font-medium">Vendor Code:</span> {{ $header['CardCode'] ?? '-' }}</div>
                <div><span class="font-medium">Vendor Name:</span> {{ $header['CardName'] ?? '-' }}</div>
            </div>
            <div class="space-y-1">
                <div><span class="font-medium">Doc Date:</span> {{ $header['DocDate'] ?? '-' }}</div>
                <div><span class="font-medium">Delivery Date:</span> {{ $header['DocDueDate'] ?? '-' }}</div>
                <div><span class="font-medium">Total:</span> {{ $header['DocTotal'] ?? '-' }}</div>
                <div><span class="font-medium">Status:</span>
                    {{ ($header['DocStatus'] ?? '') === 'O' ? 'Open' : 'Closed' }}
                </div>
            </div>
        </div>

        {{-- DETAIL LINES --}}
        <h3 class="mt-4 font-semibold text-sm">Detail Items</h3>
        <div class="border rounded-xl overflow-hidden">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                    <tr>
                        <th class="px-2 py-1 text-left">Line</th>
                        <th class="px-2 py-1 text-left">Item Code</th>
                        <th class="px-2 py-1 text-left">Description</th>
                        <th class="px-2 py-1 text-right">Qty</th>
                        <th class="px-2 py-1 text-right">Price</th>
                        <th class="px-2 py-1 text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lines as $row)
                        <tr class="border-t">
                            <td class="px-2 py-1">{{ $row['LineNum'] ?? '' }}</td>
                            <td class="px-2 py-1">{{ $row['ItemCode'] ?? '' }}</td>
                            <td class="px-2 py-1">{{ $row['Dscription'] ?? '' }}</td>
                            <td class="px-2 py-1 text-right">{{ $row['Quantity'] ?? '' }}</td>
                            <td class="px-2 py-1 text-right">{{ $row['Price'] ?? '' }}</td>
                            <td class="px-2 py-1 text-right">{{ $row['LineTotal'] ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-2 py-2 text-center">
                                Tidak ada detail item.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
