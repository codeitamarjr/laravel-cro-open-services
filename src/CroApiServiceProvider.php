<?php

namespace Codeitamarjr\LaravelCroApi;

use Illuminate\Support\ServiceProvider;

class CroApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cro-api.php', 'cro-api');

        $this->app->singleton(CroApiClient::class, function ($app) {
            $config = $app['config']->get('cro-api', []);

            return new CroApiClient(
                email: (string) ($config['email'] ?? ''),
                key: (string) ($config['key'] ?? ''),
                baseUrl: (string) ($config['base_url'] ?? 'https://services.cro.ie/cws'),
                httpTimeout: (int) ($config['http_timeout'] ?? 15),
                maxPerPage: (int) ($config['max_per_page'] ?? 100),
                rateLimitSleepSeconds: (int) ($config['rate_limit_sleep_seconds'] ?? 10),
                delayBetweenRequestsMs: (int) ($config['delay_between_requests_ms'] ?? 750),
            );
        });

        $this->app->alias(CroApiClient::class, 'cro-api');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cro-api.php' => config_path('cro-api.php'),
            ], 'cro-api-config');
        }
    }
}
