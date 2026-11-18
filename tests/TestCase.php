<?php

namespace JeffersonGoncalves\QuebecConsent\Tests;

use JeffersonGoncalves\QuebecConsent\QuebecConsentServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            QuebecConsentServiceProvider::class,
        ];
    }
}
