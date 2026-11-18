<?php

namespace JeffersonGoncalves\QuebecConsent;

use JeffersonGoncalves\Privacy\Consent\ConsentManager as BaseConsentManager;
use JeffersonGoncalves\QuebecConsent\Events\ConsentRecorded;
use JeffersonGoncalves\QuebecConsent\Models\ConsentRecord;

/**
 * Append-only consent ledger. A subject is an Eloquent model (a user) or a string anonymous id (a visitor).
 */
class ConsentManager extends BaseConsentManager
{
    protected string $config = 'quebec-consent';

    protected string $model = ConsentRecord::class;

    protected string $event = ConsentRecorded::class;
}
