<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;

class PSGCServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure HTTP client for PSGC API
        Http::macro('psgc', function () {
            return Http::baseUrl('https://psgc.gitlab.io/api')
                ->acceptJson()
                ->timeout(30);
        });
    }
}