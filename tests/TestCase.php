<?php

namespace JeffersonGoncalves\QuebecConsent\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JeffersonGoncalves\PestPluginDatabaseMigrations\LoadsPackageMigrations;
use JeffersonGoncalves\Quebec\QuebecServiceProvider;
use JeffersonGoncalves\QuebecConsent\QuebecConsentServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use LoadsPackageMigrations;

    protected function getPackageProviders($app): array
    {
        return [
            QuebecServiceProvider::class,
            QuebecConsentServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('quebec-consent.route.enabled', true);
        $app['config']->set('quebec-consent.route.middleware', ['web']);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadPackageMigrations(QuebecConsentServiceProvider::class);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }
}
