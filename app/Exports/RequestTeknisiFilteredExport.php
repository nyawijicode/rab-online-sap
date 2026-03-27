<?php

namespace App\Exports;

use App\Exports\Sheets\RequestTeknisiMainSheet;
use App\Exports\Sheets\RequestTeknisiReportsSheet;
use App\Models\RequestTeknisi;
use App\Models\RequestTeknisiReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RequestTeknisiFilteredExport implements WithMultipleSheets
{
    public function __construct(
        protected array $rawFilters = [],
        protected ?string $search = null
    ) {}

    /** Normalizer yang menangkap varian state Filament (value/state, daterange, multiselect, ternary) */
    protected function normalizeFilters(array $raw): array
    {
        $raw = array_filter($raw, fn ($v) => !($v === null || $v === '' || $v === []));
        $out = [];

        foreach ($raw as $key => $val) {
            // SelectFilter { value: ... } / { state: ... }
            if (is_array($val) && Arr::has($val, 'value')) { $out[$key] = $val['value']; continue; }
            if (is_array($val) && Arr::has($val, 'state') && !is_array($val['state'])) { $out[$key] = $val['state']; continue; }

            // DateRange {from|start, until|end} (kadang di bawah state)
            $c = is_array($val) && Arr::has($val, 'state') && is_array($val['state']) ? $val['state'] : $val;
            if (is_array($c) && (Arr::has($c, 'from') || Arr::has($c, 'start') || Arr::has($c, 'until') || Arr::has($c, 'end'))) {
                $from  = $c['from']  ?? $c['start'] ?? null;
                $until = $c['until'] ?? $c['end']   ?? null;
                if ($from)  $out["{$key}_from"]  = $from;
                if ($until) $out["{$key}_until"] = $until;
                continue;
            }

            // Ternary 'true'/'false'
            if (is_string($val) && ($val === 'true' || $val === 'false')) { $out[$key] = $val === 'true'; continue; }

            // Multiselect (langsung array atau di bawah state)
            if (is_array($val) && Arr::has($val, 'state') && is_array($val['state'])) { $out[$key] = array_values($val['state']); continue; }
            if (is_array($val)) { $out[$key] = array_values($val); continue; }

            // Scalar biasa
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
                case 'cabang':
                case 'user_id':
                case 'teknisi_id': // legacy
                    $q->where($name, $value);
                    break;

                // Rentang tanggal
                case 'tanggal_pelaksanaan_from':  $q->whereDate('tanggal_pelaksanaan', '>=', $value); break;
                case 'tanggal_pelaksanaan_until': $q->whereDate('tanggal_pelaksanaan', '<=', $value); break;
                case 'tanggal_penjadwalan_from':  $q->whereDate('tanggal_penjadwalan', '>=', $value); break;
                case 'tanggal_penjadwalan_until': $q->whereDate('tanggal_penjadwalan', '<=', $value); break;

                // Alias yang mungkin kamu pakai di UI
                case 'request_from':  $q->whereDate('tanggal_pelaksanaan', '>=', $value); break;
                case 'request_until': $q->whereDate('tanggal_pelaksanaan', '<=', $value); break;
                case 'jadwal_from':   $q->whereDate('tanggal_penjadwalan', '>=', $value); break;
                case 'jadwal_until':  $q->whereDate('tanggal_penjadwalan', '<=', $value); break;

                // Multiselect teknisi via pivot
                case 'teknisi_pivot_id':
                case 'teknisi_ids':
                    $ids = array_filter(array_map('intval', (array) $value));
                    if (!empty($ids)) {
                        $q->where(function (Builder $qq) use ($ids) {
                            $qq->whereIn('teknisi_id', $ids)
                               ->orWhereHas('teknisis', fn (Builder $q3) => $q3->whereIn('users.id', $ids));
                        });
                    }
                    break;

                // Contoh ternary
                case 'has_rab':
                    if ($value === true)  $q->whereNotNull('no_rab')->where('no_rab', '!=', '');
                    if ($value === false) $q->where(fn ($qq) => $qq->whereNull('no_rab')->orWhere('no_rab', ''));
                    break;

                default:
                    // abaikan yang tidak dikenali
                    break;
            }
        }
    }

    protected function baseQuery(array &$normalizedOut = null): Builder
    {
        $filters = $this->normalizeFilters($this->rawFilters);
        $normalizedOut = $filters;

        Log::info('RT FilteredExport normalized', ['filters' => $filters, 'search' => $this->search]);

        $q = RequestTeknisi::query()->with(['user', 'teknisi', 'teknisis']);

        // Global search
        $s = trim((string) $this->search ?? '');
        if ($s !== '') {
            $q->where(function (Builder $qq) use ($s) {
                $qq->where('no_request', 'like', "%{$s}%")
                   ->orWhere('id_paket', 'like', "%{$s}%")
                   ->orWhere('nama_dinas', 'like', "%{$s}%")
                   ->orWhere('nama_kontak', 'like', "%{$s}%")
                   ->orWhere('no_telepon', 'like', "%{$s}%")
                   ->orWhere('jenis_pekerjaan', 'like', "%{$s}%")
                   ->orWhere('cabang', 'like', "%{$s}%")
                   ->orWhere('status', 'like', "%{$s}%")
                   ->orWhere('no_rab', 'like', "%{$s}%");
            });
        }

        $this->applyFilters($q, $filters);

        return $q;
    }

    public function sheets(): array
    {
        $normalized = [];
        $query = $this->baseQuery($normalized);

        $data = $query->orderByDesc('created_at')->get();

        // ⛑️ Hard fallback: jika hasil kosong → pakai ALL (permintaanmu: “harusnya sama yang download semua”)
        if ($data->isEmpty()) {
            Log::info('RT FilteredExport fallback -> ALL (result empty).', ['normalized' => $normalized, 'search' => $this->search]);

            $data = RequestTeknisi::with(['user', 'teknisi', 'teknisis'])
                ->orderByDesc('created_at')
                ->get();
        }

        $mainSheet  = new RequestTeknisiMainSheet($data, 'Data Request Teknisi');
        $requestIds = $mainSheet->getRequestIds();

        $reports = RequestTeknisiReport::with(['requestTeknisi', 'request', 'user'])
            ->when(!empty($requestIds), fn ($qq) => $qq->whereIn('request_teknisi_id', $requestIds))
            ->orderBy('request_teknisi_id')->orderBy('id')
            ->get();

        // Jika main fallback ke ALL, tapi $requestIds kebetulan kosong (edge case), maka tampilkan semua reports
        if ($reports->isEmpty() && !$data->isEmpty() && empty($requestIds)) {
            $reports = RequestTeknisiReport::with(['requestTeknisi', 'request', 'user'])
                ->orderBy('request_teknisi_id')->orderBy('id')->get();
        }

        return [
            new RequestTeknisiMainSheet($data, 'Data Request Teknisi'),
            new RequestTeknisiReportsSheet($reports, 'RequestTeknisi Reports'),
        ];
    }
}
