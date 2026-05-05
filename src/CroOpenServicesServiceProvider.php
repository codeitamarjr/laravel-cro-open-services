<?php

namespace Codeitamarjr\LaravelCroOpenServices;

use Illuminate\Support\ServiceProvider;

class CroOpenServicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cro-open-services.php', 'cro-open-services');

        $this->app->singleton(CroOpenServicesClient::class, function ($app) {
            $config = $app['config']->get('cro-open-services', []);

            return new CroOpenServicesClient(
                email: (string) ($config['email'] ?? ''),
                key: (string) ($config['key'] ?? ''),
            );
        });

        $this->app->alias(CroOpenServicesClient::class, 'cro-open-services');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/cro-open-services.php' => config_path('cro-open-services.php'),
            ], 'cro-open-services-config');
        }
    }
}
