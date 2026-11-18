<?php

namespace JeffersonGoncalves\QuebecConsent;

use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\QuebecConsent\Http\ConsentController;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QuebecConsentServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('quebec-consent')
            ->hasConfigFile()
            ->hasMigrations(['create_quebec_consents_table']);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ConsentManager::class);
    }

    public function packageBooted(): void
    {
        if (! config('quebec-consent.route.enabled', false)) {
            return;
        }

        if (config('quebec-consent.route.claim_on_login', true)) {
            Event::listen(Login::class, function (Login $event): void {
                $id = request()->cookie((string) config('quebec-consent.route.cookie', 'quebec_consent_id'));

                if (is_string($id) && $id !== '' && $event->user instanceof Model) {
                    $this->app->make(ConsentManager::class)->claim($id, $event->user);
                }
            });
        }

        Route::middleware((array) config('quebec-consent.route.middleware', ['web']))
            ->post((string) config('quebec-consent.route.path', 'quebec/consent'), ConsentController::class)
            ->name('quebec-consent.store');
    }
}
