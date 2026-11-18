<?php

namespace JeffersonGoncalves\QuebecConsent\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use JeffersonGoncalves\QuebecConsent\ConsentManager;

class ConsentController
{
    public function __invoke(Request $request, ConsentManager $consents): Response
    {
        $purposes = array_keys((array) config('quebec-consent.purposes', []));

        $data = $request->validate([
            'purpose' => ['required', 'string', 'max:100', ...($purposes === [] ? [] : [Rule::in($purposes)])],
            'granted' => ['required', 'boolean'],
        ]);

        $cookie = (string) config('quebec-consent.route.cookie', 'quebec_consent_id');
        $user = $request->user();
        $subject = $user instanceof Model ? $user : ($request->cookie($cookie) ?: (string) Str::uuid());
        $purpose = (string) $data['purpose'];

        $request->boolean('granted')
            ? $consents->grant($subject, $purpose, source: 'route')
            : $consents->revoke($subject, $purpose, source: 'route');

        $response = response()->noContent();

        if (is_string($subject)) {
            $response->withCookie(cookie()->forever($cookie, $subject));
        }

        return $response;
    }
}
