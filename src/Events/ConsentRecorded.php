<?php

namespace JeffersonGoncalves\QuebecConsent\Events;

use JeffersonGoncalves\Privacy\Consent\ConsentRecorded as BaseConsentRecorded;

/**
 * Fired after each grant or revoke ($record is a JeffersonGoncalves\QuebecConsent\Models\ConsentRecord).
 */
class ConsentRecorded extends BaseConsentRecorded {}
