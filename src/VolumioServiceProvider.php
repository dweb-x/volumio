<?php

namespace Dwebx\Volumio;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class VolumioServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('volumio')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Volumio::class, function ($app) {
            return new Volumio(
                config('volumio.base_url'),
                config('volumio.timeout'),
                config('volumio.retries'),
                config('volumio.http_options')
            );
        });

        $this->app->alias(Volumio::class, 'volumio');
    }
}
