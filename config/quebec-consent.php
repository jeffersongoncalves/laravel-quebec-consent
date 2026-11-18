<?php

return [

    'table' => 'quebec_consents',

    /*
     * Known purposes and the current version of their terms. Ask for each purpose separately, in
     * clear and simple terms (s. 14); consent lasts only as long as the purpose needs. When set, unknown
     * purposes are rejected, and bumping a version invalidates older consents
     * (has() returns false until the subject consents again).
     *
     * 'marketing' => ['version' => '2026-11', 'expires_after' => '1 year'],
     */
    'purposes' => [],

    /*
     * Store IP address and user agent as proof of consent (Law 25 s. 14: consent must be manifest, free, enlightened and given for specific purposes; keep the proof).
     */
    'store_request' => true,

    /*
     * Optional endpoint for front-end banners: POST {purpose, granted}.
     * Guests are identified by an encrypted cookie holding a random id.
     */
    'route' => [
        'enabled' => false,
        'path' => 'quebec/consent',
        'middleware' => ['web', 'throttle:60,1'],
        'cookie' => 'quebec_consent_id',
        // On login, copy the visitor's latest decisions (by cookie id) onto the user.
        'claim_on_login' => true,
    ],

];
