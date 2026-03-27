<?php

use App\Http\Controllers\CetakPengajuanServiceController;
use App\Http\Controllers\ExportPengajuansController;
use App\Http\Controllers\ExportPenggunaanMobilController;
use App\Http\Controllers\ExportPenggunaanTeknisiController;
use App\Http\Controllers\ExportServiceController;
use App\Http\Controllers\ExportRequestTeknisiController;
use App\Models\Pengajuan;
use App\Models\PengajuanBiayaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalPanel;
/*
|--------------------------------------------------------------------------
| Halaman utama (dashboard pilih sistem)
|--------------------------------------------------------------------------
|
| /  => landing page untuk memilih sistem:
|      - /web    : RAB Online
|      - /base   : Sistem Gudang / QC
|      - /pickup : Sistem Pickup
|      - /qc     : Sistem QC Barang
|
*/

Route::get('/', function () {
    $panels = PortalPanel::active()
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();

    return view('landing', compact('panels'));
})->name('landing');

/*
|--------------------------------------------------------------------------
| ROUTES PDF & EXPORT RAB ONLINE
|--------------------------------------------------------------------------
*/

// Preview PDF Pengajuan RAB
Route::get('/pengajuan/{pengajuan}/pdf', function (Pengajuan $pengajuan) {
    $view = match ((int) $pengajuan->tipe_rab_id) {
        1 => 'pdf.pengajuan',   // Barang Intern
        2 => 'pdf.dinas',       // Perjalanan Dinas
        3 => 'pdf.kegiatan',    // Marcomm Event/Kegiatan
        4 => 'pdf.promosi',     // Marcomm Promosi
        5 => 'pdf.kebutuhan',   // Marcomm Kebutuhan
        6 => 'pdf.biaya',       // Biaya Service
        default => abort(404, 'Template PDF tidak tersedia untuk tipe ini.'),
    };

    // Tentukan orientasi
    $orientation = match ((int) $pengajuan->tipe_rab_id) {
        3 => 'landscape', // kegiatan
        4 => 'landscape', // promosi
        5 => 'landscape', // kebutuhan
        default => 'portrait', // pengajuan & dinas
    };

    $pdf = Pdf::loadView($view, compact('pengajuan'))
        ->setPaper('a4', $orientation);

    $filename = str_replace(['/', '\\'], '_', $pengajuan->no_rab);

    return $pdf->stream("RAB_{$filename}.pdf");
})->name('pengajuan.pdf.preview');

// Download PDF Pengajuan RAB
Route::get('/pengajuan/{pengajuan}/download-pdf', function (Pengajuan $pengajuan) {
    $mode = strtolower((string) request()->query('mode', '')); // ?mode=user

    $view = match ((int) $pengajuan->tipe_rab_id) {
        1 => 'pdf.pengajuan',
        2 => 'pdf.dinas',
        3 => 'pdf.kegiatan',
        4 => 'pdf.promosi',
        5 => 'pdf.kebutuhan',
        6 => $mode === 'user' ? 'pdf.user' : 'pdf.biaya',  // default biaya
        default => abort(404, 'Template PDF tidak tersedia untuk tipe ini.'),
    };

    $orientation = match ((int) $pengajuan->tipe_rab_id) {
        3, 4, 5 => 'landscape',
        default => 'portrait',
    };

    // Optional: validasi "kota" di server juga (biar double guard)
    $status = optional(Auth::user())->status;
    if (!$status || empty($status->kota)) {
        return back()->with('error', 'Isi nama kota terlebih dahulu di profil.');
    }

    $pdf = Pdf::loadView($view, compact('pengajuan'))
        ->setPaper('a4', $orientation);

    $filename = str_replace(['/', '\\'], '_', (string) $pengajuan->no_rab);

    return $pdf->download("RAB_{$filename}.pdf");
})->name('pengajuan.pdf.download');

/*
|--------------------------------------------------------------------------
| EXPORT PENGAJUAN
|--------------------------------------------------------------------------
*/

Route::get('/exports/pengajuans/all', [ExportPengajuansController::class, 'all'])
    ->name('exports.pengajuans.all');

Route::get('/exports/pengajuans/filtered', [ExportPengajuansController::class, 'filtered'])
    ->name('exports.pengajuans.filtered');

/*
|--------------------------------------------------------------------------
| EXPORT PENGGUNAAN MOBIL
|--------------------------------------------------------------------------
*/

Route::get('/exports/penggunaan-mobil/all', [ExportPenggunaanMobilController::class, 'all'])
    ->name('exports.penggunaan_mobil.all');

Route::get('/exports/penggunaan-mobil/filtered', [ExportPenggunaanMobilController::class, 'filtered'])
    ->name('exports.penggunaan_mobil.filtered');

/*
|--------------------------------------------------------------------------
| EXPORT PENGGUNAAN TEKNISI
|--------------------------------------------------------------------------
*/

Route::get('/exports/penggunaan-teknisi/all', [ExportPenggunaanTeknisiController::class, 'all'])
    ->name('exports.penggunaan_teknisi.all');

Route::get('/exports/penggunaan-teknisi/filtered', [ExportPenggunaanTeknisiController::class, 'filtered'])
    ->name('exports.penggunaan_teknisi.filtered');

/*
|--------------------------------------------------------------------------
| EXPORT SERVICE
|--------------------------------------------------------------------------
*/

Route::get('/exports/services/all', [ExportServiceController::class, 'exportAll'])
    ->name('exports.services.all')
    ->middleware('auth');

Route::match(['get', 'post'], '/exports/services/filtered', [ExportServiceController::class, 'exportFiltered'])
    ->name('exports.services.filtered')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| PDF PENGAJUAN BIAYA SERVICE
|--------------------------------------------------------------------------
*/

// PDF Preview
Route::get('/pengajuan-biaya-service/{pengajuan}/pdf', function (PengajuanBiayaService $pengajuan) {
    $view = request()->query('tipe') === 'internal'
        ? 'pdf.service_internal'
        : 'pdf.service_pelanggan';

    $pdf = Pdf::loadView($view, compact('pengajuan'))
        ->setPaper('a4', 'portrait');

    $filename = "SERVICE_" . str_replace(['/', '\\'], '_', $pengajuan->id);

    return $pdf->stream("{$filename}.pdf");
})->name('pengajuan_biaya_service.pdf.preview')->middleware('auth');

// PDF Download
Route::get('/pengajuan-biaya-service/{pengajuan}/download-pdf', function (PengajuanBiayaService $pengajuan) {
    $view = request()->query('tipe') === 'internal'
        ? 'pdf.service_internal'
        : 'pdf.service_pelanggan';

    $pdf = Pdf::loadView($view, compact('pengajuan'))
        ->setPaper('a4', 'portrait');

    $filename = "SERVICE_" . str_replace(['/', '\\'], '_', $pengajuan->id);

    return $pdf->download("{$filename}.pdf");
})->name('pengajuan_biaya_service.pdf.download')->middleware('auth');

/*
|--------------------------------------------------------------------------
| EXPORT PENGAJUAN BIAYA SERVICE
|--------------------------------------------------------------------------
*/

Route::get('/exports/pengajuan-biaya-service/all', [CetakPengajuanServiceController::class, 'all'])
    ->name('exports.pengajuan_biaya_service.all')
    ->middleware('auth');

Route::match(['get', 'post'], '/exports/pengajuan-biaya-service/filtered', [CetakPengajuanServiceController::class, 'filtered'])
    ->name('exports.pengajuan_biaya_service.filtered')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| EXPORT REQUEST TEKNISI (XLSX)
|--------------------------------------------------------------------------
*/

Route::get('/exports/request-teknisi/all', [ExportRequestTeknisiController::class, 'exportAll'])
    ->name('exports.request_teknisi.all')
    ->middleware('auth');

Route::match(['get', 'post'], '/exports/request-teknisi/filtered', [ExportRequestTeknisiController::class, 'exportFiltered'])
    ->name('exports.request_teknisi.filtered')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| QC LABELS
|--------------------------------------------------------------------------
*/

Route::get('/qc/labels/{ids}', function ($ids) {
    $taskIds = explode(',', $ids);
    $tasks = \App\Models\QcTask::whereIn('id', $taskIds)->with('technician')->get();

    // Auto-update status to 'passed' if currently 'completed'
    // This removes it from the "Tugas QC Selesai" list
    foreach ($tasks as $task) {
        if ($task->status === 'completed') {
            $task->update(['status' => 'printed']);
        }
    }

    $criteria = \App\Models\QcCriteria::all();

    $labels = [];
    foreach ($tasks as $task) {
        $checklist = \App\Models\QcTaskCriteria::where('qc_task_id', $task->id)
            ->get()
            ->pluck('is_checked', 'qc_criteria_id')
            ->toArray();

        // Get scanned serial number directly from task record
        $firstSN = $task->scanned_serial_number;

        // Generate scannable QR Code for Serial Number
        $snValue = $firstSN ?? 'NO-SN';
        $barcodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->generate($snValue);

        // Generate WhatsApp QR using SimpleSoftwareIO QR Code
        $whatsappUrl = 'https://wa.me/628112945094';
        $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(150)->generate($whatsappUrl);

        $labels[] = [
            'task' => $task,
            'checklist' => $checklist,
            'barcode' => $barcodeSvg,
            'qr_whatsapp' => $qrSvg,
        ];
    }

    return view('filament.qc.labels.qc-label', compact('labels', 'criteria'));
})->name('qc.labels.print')->middleware('auth');
