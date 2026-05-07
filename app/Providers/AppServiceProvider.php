<?php

namespace App\Providers;

use App\Services\Rh\RhClientHttp;
use App\Services\Rh\RhClientInterface;
use App\Services\Rh\RhClientMock;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RhClientInterface::class, function ($app) {
            $config = $app['config']->get('gpt.rh');

            if ($config['use_mock'] ?? true) {
                return new RhClientMock;
            }

            return new RhClientHttp(
                baseUrl: $config['url'],
                token: $config['token'] ?? null,
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
