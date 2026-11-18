<?php

namespace JeffersonGoncalves\QuebecConsent\Enums;

enum ConsentAction: string
{
    case Granted = 'granted';
    case Revoked = 'revoked';
}
