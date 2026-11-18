<?php

namespace JeffersonGoncalves\QuebecConsent\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\QuebecConsent\QuebecConsent
 */
class QuebecConsent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-quebec-consent';
    }
}
