{{-- resources/views/filament/base/sap/modals/vendor-detail.blade.php --}}

<div class="space-y-4">
    @if (! $vendor)
        <div class="text-danger-600">
            Vendor tidak ditemukan di SAP.
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="space-y-1">
                <div><span class="font-medium">Code:</span>
                    {{ $vendor['CardCode'] ?? '-' }}
                </div>
                <div><span class="font-medium">Name:</span>
                    {{ $vendor['CardName'] ?? '-' }}
                </div>
                <div><span class="font-medium">Type:</span>
                    @php
                        $type = $vendor['CardType'] ?? null;
                        $typeText = match ($type) {
                            'S' => 'Supplier',
                            'C' => 'Customer',
                            'L' => 'Lead',
                            default => $type,
                        };
                    @endphp
                    {{ $typeText ?? '-' }}
                </div>
                <div><span class="font-medium">Group:</span>
                    {{ $vendor['GroupCode'] ?? '-' }}
                </div>
                <div><span class="font-medium">Currency:</span>
                    {{ $vendor['Currency'] ?? '-' }}
                </div>
            </div>

            <div class="space-y-1">
                <div><span class="font-medium">Phone 1:</span>
                    {{ $vendor['Phone1'] ?? '-' }}
                </div>
                <div><span class="font-medium">Mobile:</span>
                    {{ $vendor['Cellular'] ?? '-' }}
                </div>
                <div><span class="font-medium">Email:</span>
                    {{ $vendor['Email'] ?? '-' }}
                </div>
            </div>
        </div>
    @endif
</div>
