<?php

namespace Ccharz\LaravelEpcQr;

use Illuminate\Support\ServiceProvider;

class LaravelEpcQrServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->bind('epcqr', function ($app) {
            return new LaravelEpcQr($app['files']);
        });
    }
}
