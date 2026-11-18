<?php

namespace JeffersonGoncalves\QuebecConsent\Models;

use JeffersonGoncalves\Privacy\Consent\ConsentRecord as BaseConsentRecord;
use JeffersonGoncalves\Quebec\Attributes\PersonalData;
use JeffersonGoncalves\Quebec\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\Quebec\Contracts\HasPersonalData;
use JeffersonGoncalves\QuebecConsent\Enums\ConsentAction;

/**
 * One immutable consent event. The ledger is append-only: a new decision is a new row.
 * Only the personal data columns may change, so the person can still be anonymized.
 *
 * @property ConsentAction $action
 */
#[PersonalData('ip_address', purpose: 'Proof of consent (s. 14)')]
#[PersonalData('user_agent', purpose: 'Proof of consent (s. 14)')]
class ConsentRecord extends BaseConsentRecord implements HasPersonalData
{
    use InteractsWithPersonalData;

    protected string $actionEnum = ConsentAction::class;

    public function getTable(): string
    {
        return config('quebec-consent.table', 'quebec_consents');
    }
}
