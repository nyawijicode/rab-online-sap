<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Pengajuan;
use App\Models\Service;
use App\Observers\PengajuanObserver;
use App\Observers\ServiceObserver;
use App\Http\Responses\LogoutResponse;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use App\Services\Sap\HanaOdbcConnector;
use App\Services\Sap\SapHanaService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);
        // Singleton connector ODBC
        $this->app->singleton(HanaOdbcConnector::class, function () {
            return new HanaOdbcConnector(
                env('HANA_DB_URL'),
                PHP_OS_FAMILY === 'Windows'
                    ? env('HANA_ODBC_DRIVER', '{HDBODBC}')
                    : env('HANA_ODBC_DRIVER_LINUX', '{HDBODBC}')
            );
        });

        // Service SAP
        $this->app->singleton(SapHanaService::class, function ($app) {
            return new SapHanaService($app->make(HanaOdbcConnector::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Daftarkan observer untuk auto-check expired pengajuan
        Pengajuan::observe(PengajuanObserver::class);
        Service::observe(ServiceObserver::class);
        \App\Models\RequestTeknisi::observe(\App\Observers\RequestTeknisiObserver::class);
    }
}
