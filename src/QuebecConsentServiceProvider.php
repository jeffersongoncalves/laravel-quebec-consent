<?php

namespace JeffersonGoncalves\QuebecConsent;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QuebecConsentServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-quebec-consent')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
