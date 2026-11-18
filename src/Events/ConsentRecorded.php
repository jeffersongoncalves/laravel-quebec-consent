<?php

namespace JeffersonGoncalves\QuebecConsent\Events;

use JeffersonGoncalves\QuebecConsent\Models\ConsentRecord;

class ConsentRecorded
{
    public function __construct(public ConsentRecord $record) {}
}
