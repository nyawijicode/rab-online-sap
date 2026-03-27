<?php

namespace App\Filament\Resources\PengajuanResource\Widgets;

use App\Models\Pengajuan;
use App\Models\PengajuanStatus;
use App\Models\Service;
use App\Models\RequestTeknisi;
use App\Models\PengajuanSoPl;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat as StatsOverviewWidgetStat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PengajuanStats extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    public array $cardYears = [];

    public function mount(): void
    {
        $currentYear = (string) date('Y');
        $this->cardYears = [
            'total'   => $currentYear,
            'tipe'    => $currentYear,
            'biaya'   => $currentYear,
            'service' => $currentYear,
            'teknisi' => $currentYear,
            'marcomm' => $currentYear,
            'sopl'    => $currentYear,
        ];
    }

    protected function getYearSelectHtml(string $key): HtmlString
    {
        $selectedYear = (string) ($this->cardYears[$key] ?? date('Y'));
        $options = '';
        $currentYear = (int) date('Y');
        for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
            $isSelected = ($selectedYear === (string) $y) ? 'selected' : '';
            $options .= "<option value='{$y}' {$isSelected}>{$y}</option>";
        }

        return new HtmlString("
            <div class='mt-2 flex items-center gap-2' 
                onclick='event.stopPropagation()' 
                onmousedown='event.stopPropagation()'
                x-on:click.stop.prevent='' 
                x-on:mousedown.stop=''>
                <span class='text-[10px] font-medium uppercase tracking-wider text-gray-500'>Tahun:</span>
                <select
                    wire:model.live='cardYears.{$key}'
                    wire:key='select-year-{$key}'
                    onclick='event.stopPropagation()'
                    onmousedown='event.stopPropagation()'
                    x-on:click.stop.prevent=''
                    x-on:mousedown.stop=''
                    x-on:change.stop=''
                    class='text-[11px] border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 focus:border-primary-500 dark:focus:border-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 rounded shadow-sm py-0 h-6 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'
                    style='padding-top: 0; padding-bottom: 0;'
                >
                    {$options}
                </select>
            </div>
        ");
    }

    protected function getTrendData($query, string $year, string $column = 'created_at', string $aggregate = 'count', string $sumColumn = 'total_biaya'): array
    {
        $data = (clone $query)
            ->select(
                DB::raw('MONTH(' . $column . ') as month'),
                $aggregate === 'count' ? DB::raw('count(*) as aggregate') : DB::raw('sum(' . $sumColumn . ') as aggregate')
            )
            ->whereYear($column, $year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('aggregate', 'month')
            ->all();

        $trend = [];
        for ($i = 1; $i <= 12; $i++) {
            $trend[] = (float) ($data[$i] ?? 0);
        }

        return $trend;
    }

    protected function getStats(): array
    {
        $user       = Auth::user();
        $role       = $user->getRoleNames()->first();

        $targetUrl      = url('/web/pengajuans');
        $semuaUrl       = url('/web/semua-pengajuan');
        $serviceUrl     = url('/web/request-service');
        $teknisiUrl     = url('/web/request-teknisi');
        $marcommUrl     = url('/web/request-marcomm');
        $soPlUrl        = url('/web/pengajuan-so-pls');

        // ---- id pengajuan yang relevan untuk user ini
        $createdIds   = Pengajuan::where('user_id', $user->id)->pluck('id')->toArray();
        $toApproveIds = PengajuanStatus::where('user_id', $user->id)->pluck('pengajuan_id')->toArray();
        $allIds       = array_unique(array_merge($createdIds, $toApproveIds));

        // Year values from each card
        $yearTotal   = $this->cardYears['total'];
        $yearTipe    = $this->cardYears['tipe'];
        $yearBiaya   = $this->cardYears['biaya'];
        $yearService = $this->cardYears['service'];
        $yearTeknisi = $this->cardYears['teknisi'];
        $yearMarcomm = $this->cardYears['marcomm'];
        $yearSopl    = $this->cardYears['sopl'];

        // ---- total pengajuan
        $totalPengajuanBase = $role === 'superadmin' ? Pengajuan::query() : Pengajuan::whereIn('id', $allIds);

        $totalPengajuan = (clone $totalPengajuanBase)->whereYear('created_at', $yearTotal)->count();
        $totalPengajuanAll = (clone $totalPengajuanBase)->count();
        $totalPengajuanTrend = $this->getTrendData($totalPengajuanBase, $yearTotal);

        // ---- pengajuan hari ini
        $pengajuanHariIni = (clone $totalPengajuanBase)->whereDate('created_at', Carbon::today())->count();

        // ---- tipe RAB terbanyak (Yearly)
        $subQuery = (clone $totalPengajuanBase)->select(
            'id',
            'user_id',
            'no_rab',
            DB::raw("SUBSTRING_INDEX(SUBSTRING_INDEX(no_rab, '/', 2), '/', -1) as kode")
        )->whereYear('created_at', $yearTipe)
            ->whereNull('deleted_at');
        $subSql = $subQuery->toSql();

        $topTipeRab = DB::table(DB::raw("({$subSql}) as pengajuans"))
            ->mergeBindings($subQuery->getQuery())
            ->join('tipe_rabs', 'pengajuans.kode', '=', 'tipe_rabs.kode')
            ->select('pengajuans.kode', 'tipe_rabs.nama as tipe_rab', DB::raw('COUNT(*) as total'))
            ->groupBy('pengajuans.kode', 'tipe_rabs.nama')
            ->orderByDesc('total')
            ->limit(1)
            ->first();

        // ---- tipe RAB terbanyak (All Time)
        $subQueryAll = (clone $totalPengajuanBase)->select(
            'id',
            'user_id',
            'no_rab',
            DB::raw("SUBSTRING_INDEX(SUBSTRING_INDEX(no_rab, '/', 2), '/', -1) as kode")
        )->whereNull('deleted_at');
        $subSqlAll = $subQueryAll->toSql();

        $topTipeRabAll = DB::table(DB::raw("({$subSqlAll}) as pengajuans"))
            ->mergeBindings($subQueryAll->getQuery())
            ->join('tipe_rabs', 'pengajuans.kode', '=', 'tipe_rabs.kode')
            ->select('pengajuans.kode', 'tipe_rabs.nama as tipe_rab', DB::raw('COUNT(*) as total'))
            ->groupBy('pengajuans.kode', 'tipe_rabs.nama')
            ->orderByDesc('total')
            ->limit(1)
            ->first();

        // ---- total biaya selesai / expired unlocked
        $biayaBase = (clone $totalPengajuanBase)->where(function ($q) {
            $q->where('status', 'selesai')
                ->orWhere(function ($q2) {
                    $q2->where('status', 'expired')->where('expired_unlocked', true);
                });
        });

        $totalBiaya = (clone $biayaBase)->whereYear('created_at', $yearBiaya)->sum('total_biaya');
        $totalBiayaAll = (clone $biayaBase)->sum('total_biaya');
        $totalBiayaTrend = $this->getTrendData($biayaBase, $yearBiaya, 'created_at', 'sum', 'total_biaya');

        // ================== STATS SERVICE ==================
        $privilegedRoles = ['superadmin', 'manager', 'koordinator teknisi', 'servis'];
        $canSeeAllServices = in_array($role, $privilegedRoles, true);

        $serviceBaseYear = Service::query()->whereYear('created_at', $yearService);
        $serviceBaseAll  = Service::query();
        if (! $canSeeAllServices) {
            $serviceBaseYear->where('user_id', $user->id);
            $serviceBaseAll->where('user_id', $user->id);
        }

        $totalServiceYear   = (clone $serviceBaseYear)->count();
        $totalServiceAll    = (clone $serviceBaseAll)->count();
        $serviceTrend       = $this->getTrendData($serviceBaseAll, $yearService);
        $serviceRequestYear = (clone $serviceBaseYear)->where('staging', 'request')->count();
        $serviceRequestAll  = (clone $serviceBaseAll)->where('staging', 'request')->count();

        $descTotal   = $canSeeAllServices ? 'Semua service di sistem' : 'Service yang Anda ajukan';
        $descRequest = $canSeeAllServices ? 'Semua service berstatus Request' : 'Service Anda berstatus Request';

        // ================== STATS REQUEST TEKNISI ==================
        $teknisiBaseYear = RequestTeknisi::query()->whereYear('created_at', $yearTeknisi);
        $teknisiBaseAll  = RequestTeknisi::query();
        if (! $canSeeAllServices) {
            $teknisiBaseYear->where('user_id', $user->id);
            $teknisiBaseAll->where('user_id', $user->id);
        }

        $totalTeknisiYear   = (clone $teknisiBaseYear)->count();
        $totalTeknisiAll    = (clone $teknisiBaseAll)->count();
        $teknisiTrend       = $this->getTrendData($teknisiBaseAll, $yearTeknisi);
        $teknisiRequestYear = (clone $teknisiBaseYear)->where('status', 'request')->count();
        $teknisiRequestAll  = (clone $teknisiBaseAll)->where('status', 'request')->count();

        $descTotalTeknisi   = $canSeeAllServices ? 'Semua request teknisi di sistem' : 'Request teknisi yang Anda ajukan';
        $descRequestTeknisi = $canSeeAllServices ? 'Semua request teknisi berstatus Request' : 'Request teknisi Anda berstatus Request';

        // ================== STATS REQUEST MARCOMM ==================
        $privilegedMarcomm = ['superadmin', 'marcomm'];

        $marcommBaseYear = \App\Models\RequestMarcomm::query()->whereYear('created_at', $yearMarcomm);
        $marcommBaseAll  = \App\Models\RequestMarcomm::query();

        if (! in_array($role, $privilegedMarcomm)) {
            $marcommBaseYear->where('user_id', $user->id);
            $marcommBaseAll->where('user_id', $user->id);
        }

        $totalMarcommYear   = (clone $marcommBaseYear)->count();
        $totalMarcommAll    = (clone $marcommBaseAll)->count();
        $marcommTrend       = $this->getTrendData($marcommBaseAll, $yearMarcomm);
        $marcommRequestYear = (clone $marcommBaseYear)->where('status', 'tunggu')->count();
        $marcommRequestAll  = (clone $marcommBaseAll)->where('status', 'tunggu')->count();

        $descTotalMarcomm   = in_array($role, $privilegedMarcomm)
            ? 'Semua Request Marcomm di sistem'
            : 'Request Marcomm yang Anda ajukan';

        $descRequestMarcomm = in_array($role, $privilegedMarcomm)
            ? 'Semua Request Marcomm berstatus Menunggu'
            : 'Request Marcomm Anda berstatus Menunggu';

        // ================== STATS PENGAJUAN SO PL ==================
        $soPlBaseYear = PengajuanSoPl::query()->whereYear('created_at', $yearSopl);
        $soPlBaseAll  = PengajuanSoPl::query();
        if ($role !== 'superadmin') {
            $soPlBaseYear->where('user_id', $user->id);
            $soPlBaseAll->where('user_id', $user->id);
        }

        $totalSoPlYear   = (clone $soPlBaseYear)->count();
        $totalSoPlAll    = (clone $soPlBaseAll)->count();
        $soplTrend       = $this->getTrendData($soPlBaseAll, $yearSopl);
        $soPlPendingYear = (clone $soPlBaseYear)->where('status', 'pending')->count();
        $soPlPendingAll  = (clone $soPlBaseAll)->where('status', 'pending')->count();

        $currentYear = Carbon::now()->year;

        // ============================================================

        $stats = [
            StatsOverviewWidgetStat::make('Total Pengajuan (' . $yearTotal . ')', $totalPengajuan)
                ->description(new HtmlString($totalPengajuanAll . ' (Semua Tahun) ' . $this->getYearSelectHtml('total')))
                ->chart($totalPengajuanTrend)
                ->color('info')
                ->url($semuaUrl)
                ->icon('heroicon-o-document-check'),

            StatsOverviewWidgetStat::make('Jumlah Pengajuan Hari Ini', $pengajuanHariIni)
                ->description('Pengajuan Status Menunggu (buat/minta approve) hari ini')
                ->color('warning')
                ->url($targetUrl)
                ->icon('heroicon-o-document-arrow-down'),

            StatsOverviewWidgetStat::make('Tipe RAB (' . $yearTipe . ')', $topTipeRab?->tipe_rab ?? 'Belum ada data')
                ->description(new HtmlString('Terbanyak: ' . ($topTipeRabAll?->tipe_rab ?? 'N/A') . $this->getYearSelectHtml('tipe')))
                ->color('info')
                ->url($targetUrl)
                ->icon('heroicon-o-document-text'),

            StatsOverviewWidgetStat::make('Total Biaya (' . $yearBiaya . ')', 'Rp ' . number_format($totalBiaya, 0, ',', '.'))
                ->description(new HtmlString('Semua Tahun: Rp ' . number_format($totalBiayaAll, 0, ',', '.') . $this->getYearSelectHtml('biaya')))
                ->chart($totalBiayaTrend)
                ->color('success')
                ->url($semuaUrl)
                ->icon('heroicon-o-currency-dollar'),

            // ==== SERVICE ====
            StatsOverviewWidgetStat::make('Total Service (' . $yearService . ')', $totalServiceYear)
                ->description(new HtmlString($totalServiceAll . ' (Semua Tahun) ' . $this->getYearSelectHtml('service')))
                ->chart($serviceTrend)
                ->color('primary')
                ->url($serviceUrl)
                ->icon('heroicon-o-wrench-screwdriver'),

            StatsOverviewWidgetStat::make('Service Request (' . $yearService . ')', $serviceRequestYear)
                ->description(new HtmlString($serviceRequestAll . ' (Semua Tahun) ' . $this->getYearSelectHtml('service')))
                ->color('warning')
                ->url($serviceUrl . '?tableFilters[staging][value]=request')
                ->icon('heroicon-o-clock'),

            // ==== REQUEST TEKNISI ====
            StatsOverviewWidgetStat::make('Total Request Teknisi (' . $yearTeknisi . ')', $totalTeknisiYear)
                ->description(new HtmlString($totalTeknisiAll . ' (Semua Tahun) ' . $this->getYearSelectHtml('teknisi')))
                ->chart($teknisiTrend)
                ->color('primary')
                ->url($teknisiUrl)
                ->icon('heroicon-o-wrench'),

            StatsOverviewWidgetStat::make('Request Teknisi (' . $yearTeknisi . ')', $teknisiRequestYear)
                ->description(new HtmlString($teknisiRequestAll . ' (Semua Tahun) ' . $this->getYearSelectHtml('teknisi')))
                ->color('warning')
                ->url($teknisiUrl . '?tableFilters[status][status]=request')
                ->icon('heroicon-o-clock'),

            // ==== REQUEST MARCOMM ====
            StatsOverviewWidgetStat::make('Total Request Marcomm (' . $yearMarcomm . ')', $totalMarcommYear)
                ->description(new HtmlString($totalMarcommAll . ' (Semua Tahun) ' . $this->getYearSelectHtml('marcomm')))
                ->chart($marcommTrend)
                ->color('primary')
                ->url($marcommUrl)
                ->icon('heroicon-o-megaphone'),

            StatsOverviewWidgetStat::make('Request Marcomm (' . $yearMarcomm . ')', $marcommRequestYear)
                ->description(new HtmlString($marcommRequestAll . ' (Semua Tahun) ' . $this->getYearSelectHtml('marcomm')))
                ->color('warning')
                ->url($marcommUrl . '?tableFilters[status][status]=tunggu')
                ->icon('heroicon-o-clock'),

            // ==== PENGAJUAN SO PL ====
            StatsOverviewWidgetStat::make('Total Pengajuan SO PL (' . $yearSopl . ')', $totalSoPlYear)
                ->description(new HtmlString($totalSoPlAll . ' (Semua Tahun) ' . $this->getYearSelectHtml('sopl')))
                ->chart($soplTrend)
                ->color('primary')
                ->url($soPlUrl)
                ->icon('heroicon-o-document-text'),

            StatsOverviewWidgetStat::make('SO PL Pending (' . $yearSopl . ')', $soPlPendingYear)
                ->description(new HtmlString($soPlPendingAll . ' (Semua Tahun) ' . $this->getYearSelectHtml('sopl')))
                ->color('warning')
                ->url($soPlUrl . '?tableFilters[status][value]=pending')
                ->icon('heroicon-o-clock'),
        ];

        return $stats;
    }
}
