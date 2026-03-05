<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\ReportRepository;
use App\Services\ReportService;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ReportRepository::class, function ($app) {
            return new ReportRepository();
        });
        
        $this->app->bind(ReportService::class, function ($app) {
            return new ReportService($app->make(ReportRepository::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure Socialite for Windows SSL issues
        $this->app->extend('socialite', function ($socialite, $app) {
            $socialite->extend('google', function () use ($app) {
                $config = $app['config']['services.google'];
                
                $provider = new \Laravel\Socialite\Two\GoogleProvider(
                    $app['request'],
                    $config['client_id'],
                    $config['client_secret'],
                    $config['redirect']
                );
                
                // Set HTTP client with SSL verification option
                $provider->setHttpClient(new \GuzzleHttp\Client([
                    'verify' => $config['ssl_verify'] ?? true,
                ]));
                
                return $provider;
            });
            
            return $socialite;
        });
    }
}
