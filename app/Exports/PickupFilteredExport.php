<?php

namespace App\Exports;

use App\Exports\Sheets\PickupMainSheet;
use App\Models\Pickup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PickupFilteredExport implements WithMultipleSheets
{
    public function __construct(
        protected array $rawFilters = [],
        protected ?string $search = null
    ) {}

    protected function normalizeFilters(array $raw): array
    {
        $raw = array_filter($raw, fn ($v) => !($v === null || $v === '' || $v === []));
        $out = [];

        foreach ($raw as $key => $val) {
            if (is_array($val) && Arr::has($val, 'value')) { $out[$key] = $val['value']; continue; }
            if (is_array($val) && Arr::has($val, 'state') && !is_array($val['state'])) { $out[$key] = $val['state']; continue; }

            $c = is_array($val) && Arr::has($val, 'state') && is_array($val['state']) ? $val['state'] : $val;
            if (is_array($c) && (Arr::has($c, 'from') || Arr::has($c, 'until'))) {
                if ($c['from'] ?? null) $out["{$key}_from"] = $c['from'];
                if ($c['until'] ?? null) $out["{$key}_until"] = $c['until'];
                continue;
            }

            if (is_array($val) && Arr::has($val, 'state') && is_array($val['state'])) { $out[$key] = array_values($val['state']); continue; }
            if (is_array($val)) { $out[$key] = array_values($val); continue; }

            $out[$key] = $val;
        }

        return $out;
    }

    protected function applyFilters(Builder $q, array $filters): void
    {
        foreach ($filters as $name => $value) {
            if ($value === null || $value === '' || $value === []) continue;

            switch ($name) {
                case 'status':
                case 'cabang_id':
                case 'perusahaan_id':
                    if (is_array($value)) {
                        $q->whereIn($name, $value);
                    } else {
                        $q->where($name, $value);
                    }
                    break;

                case 'pickup_date_from':
                    $q->whereDate('pickup_date', '>=', $value);
                    break;
                case 'pickup_date_until':
                    $q->whereDate('pickup_date', '<=', $value);
                    break;
            }
        }
    }

    public function sheets(): array
    {
        $filters = $this->normalizeFilters($this->rawFilters);
        $q = Pickup::query()
            ->with(['perusahaan', 'creator', 'updater', 'items'])
            ->withSum('items', 'pickup_quantity');

        // Role-based visibility
        if (!auth()->user()->hasAnyRole(['superadmin', 'logistik', 'purchasing'])) {
            $q->where(function ($qq) {
                $qq->where('created_by', auth()->id())
                  ->orWhere('cabang_pic_user_id', auth()->id());
            });
        }

        // Global search
        if ($s = trim((string) $this->search)) {
            $q->where(function ($qq) use ($s) {
                $qq->where('po_number', 'like', "%{$s}%")
                   ->orWhere('vendor_name', 'like', "%{$s}%")
                   ->orWhere('customer_name', 'like', "%{$s}%")
                   ->orWhere('no_resi', 'like', "%{$s}%");
            });
        }

        $this->applyFilters($q, $filters);

        $data = $q->latest()->get();

        return [
            new PickupMainSheet($data, 'Data Pickup Terfilter'),
        ];
    }
}
