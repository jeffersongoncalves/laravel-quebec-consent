<?php

namespace JeffersonGoncalves\QuebecConsent\Http;

use JeffersonGoncalves\Privacy\Consent\ConsentController as BaseConsentController;
use JeffersonGoncalves\QuebecConsent\ConsentManager;

/**
 * Endpoint for front-end banners: POST {purpose, granted}.
 */
class ConsentController extends BaseConsentController
{
    protected string $manager = ConsentManager::class;
}
