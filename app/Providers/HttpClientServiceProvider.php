<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;

class HttpClientServiceProvider extends ServiceProvider
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
        // Configure HTTP client for SSL handling on Windows
        Http::macro('withWindowsSSL', function () {
            return Http::withOptions([
                'verify' => env('HTTP_VERIFY_SSL', true),
            ]);
        });
    }
}
