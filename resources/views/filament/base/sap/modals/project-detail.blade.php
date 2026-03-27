{{-- resources/views/filament/base/sap/modals/project-detail.blade.php --}}

<div class="space-y-4">
    @if (! $project)
        <div class="text-danger-600">
            Project tidak ditemukan di SAP.
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="space-y-1">
                <div>
                    <span class="font-medium">Project Code:</span>
                    {{ $project['PrjCode'] ?? '-' }}
                </div>
                <div>
                    <span class="font-medium">Project Name:</span>
                    {{ $project['PrjName'] ?? '-' }}
                </div>
            </div>

            <div class="space-y-1">
                <div>
                    <span class="font-medium">Valid From:</span>
                    {{ $project['ValidFrom'] ?? '-' }}
                </div>
                <div>
                    <span class="font-medium">Valid To:</span>
                    {{ $project['ValidTo'] ?? '-' }}
                </div>
                <div>
                    <span class="font-medium">Active:</span>
                    @php
                        $active = strtoupper($project['Active'] ?? '') === 'Y';
                    @endphp
                    <span class="{{ $active ? 'text-green-600' : 'text-red-600' }}">
                        {{ $active ? 'Yes' : 'No' }}
                    </span>
                </div>
            </div>
        </div>
    @endif
</div>
