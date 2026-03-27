<?php

namespace App\Exports;

use App\Exports\Sheets\RequestTeknisiMainSheet;
use App\Exports\Sheets\RequestTeknisiReportsSheet;
use App\Models\RequestTeknisi;
use App\Models\RequestTeknisiReport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RequestTeknisiAllExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        $mainSheet = new RequestTeknisiMainSheet(
            RequestTeknisi::with(['user', 'teknisi', 'teknisis'])
                ->orderByDesc('created_at')
                ->get(),
            'Data Request Teknisi'
        );

        // ALL: **tanpa filter** supaya selalu tampil
        $reportsSheet = new RequestTeknisiReportsSheet(
            RequestTeknisiReport::with(['requestTeknisi', 'request', 'user'])
                ->orderBy('request_teknisi_id')
                ->orderBy('id')
                ->get(),
            'RequestTeknisi Reports'
        );

        return [$mainSheet, $reportsSheet];
    }
}
