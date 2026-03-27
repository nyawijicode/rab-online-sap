<?php

namespace App\Http\Controllers;

use App\Exports\RequestTeknisiAllExport;
use App\Exports\RequestTeknisiFilteredExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportRequestTeknisiController extends Controller
{
    public function exportAll()
    {
        $filename = 'request-teknisi-all-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        /** @var BinaryFileResponse $response */
        $response = Excel::download(new RequestTeknisiAllExport, $filename);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        return $response;
    }

    public function exportFiltered(Request $request)
    {
        $filtersRaw = $request->input('filters', []);
        $search     = $request->input('search');

        // robust decode
        if (is_string($filtersRaw)) {
            $filtersRaw = trim($filtersRaw);
            if ($filtersRaw === 'null' || $filtersRaw === 'undefined' || $filtersRaw === '') {
                $filters = [];
            } else {
                $decoded = json_decode($filtersRaw, true);
                $filters = json_last_error() === JSON_ERROR_NONE ? ($decoded ?: []) : [];
            }
        } else {
            $filters = is_array($filtersRaw) ? $filtersRaw : [];
        }

        Log::info('Export Filtered RequestTeknisi (raw)', ['filtersRaw' => $filtersRaw, 'filtersParsed' => $filters, 'search' => $search]);

        $filename = 'request-teknisi-filtered-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        /** @var BinaryFileResponse $response */
        $response = Excel::download(new RequestTeknisiFilteredExport($filters, $search), $filename);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        return $response;
    }
}
