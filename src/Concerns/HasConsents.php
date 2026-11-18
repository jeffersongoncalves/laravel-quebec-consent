<?php

namespace JeffersonGoncalves\QuebecConsent\Concerns;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Privacy\Consent\Concerns\HasConsents as BaseHasConsents;
use JeffersonGoncalves\Privacy\Consent\ConsentManager as BaseConsentManager;
use JeffersonGoncalves\QuebecConsent\ConsentManager;
use JeffersonGoncalves\QuebecConsent\Models\ConsentRecord;

/**
 * @mixin Model
 */
trait HasConsents
{
    use BaseHasConsents;

    protected static function consentManager(): BaseConsentManager
    {
        return app(ConsentManager::class);
    }

    protected static function consentRecordModel(): string
    {
        return ConsentRecord::class;
    }
}
