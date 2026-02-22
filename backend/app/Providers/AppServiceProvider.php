<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AnalyticsService;
use App\Services\ExportService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Analytics Service
        $this->app->singleton(AnalyticsService::class, function ($app) {
            return new AnalyticsService();
        });

        // Register Export Service
        $this->app->singleton(ExportService::class, function ($app) {
            return new ExportService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

