<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\QuebecConsent\Enums\ConsentAction;
use JeffersonGoncalves\QuebecConsent\Events\ConsentRecorded;
use JeffersonGoncalves\QuebecConsent\Facades\QuebecConsent;
use JeffersonGoncalves\QuebecConsent\Models\ConsentRecord;
use JeffersonGoncalves\QuebecConsent\Tests\Fixtures\User;

function user(): User
{
    return User::create(['name' => 'Maria']);
}

it('grants and revokes, keeping the full history', function () {
    Event::fake([ConsentRecorded::class]);
    $user = user();

    expect($user->hasConsent('marketing'))->toBeFalse();

    $user->grantConsent('marketing', source: 'signup');
    expect($user->hasConsent('marketing'))->toBeTrue();

    $user->revokeConsent('marketing');
    expect($user->hasConsent('marketing'))->toBeFalse()
        ->and(QuebecConsent::history($user, 'marketing')->pluck('action')->all())->toBe([ConsentAction::Granted, ConsentAction::Revoked])
        ->and($user->consents)->toHaveCount(2);

    Event::assertDispatchedTimes(ConsentRecorded::class, 2);
});

it('invalidates consent when the terms version is bumped', function () {
    config()->set('quebec-consent.purposes', ['marketing' => ['version' => 'v1']]);
    $user = user();
    $user->grantConsent('marketing');

    expect($user->hasConsent('marketing'))->toBeTrue()
        ->and($user->consents()->first()->version)->toBe('v1');

    config()->set('quebec-consent.purposes.marketing.version', 'v2');

    expect($user->hasConsent('marketing'))->toBeFalse();
});

it('expires consent after the configured period', function () {
    config()->set('quebec-consent.purposes', ['marketing' => ['expires_after' => '1 year']]);
    $user = user();
    $user->grantConsent('marketing');

    $this->travel(13)->months();

    expect($user->hasConsent('marketing'))->toBeFalse();
});

it('rejects unknown purposes once purposes are configured', function () {
    config()->set('quebec-consent.purposes', ['marketing' => []]);

    user()->grantConsent('newsletter');
})->throws(InvalidArgumentException::class);

it('records anonymous visitors by id', function () {
    QuebecConsent::grant('visitor-1', 'analytics');

    expect(QuebecConsent::has('visitor-1', 'analytics'))->toBeTrue()
        ->and(QuebecConsent::has('visitor-2', 'analytics'))->toBeFalse();
});

it('is append-only but can be anonymized', function () {
    $record = user()->grantConsent('marketing');

    expect(fn () => $record->update(['action' => ConsentAction::Revoked]))->toThrow(LogicException::class)
        ->and(fn () => $record->delete())->toThrow(LogicException::class);

    $record->refresh()->anonymizePersonalData();

    expect($record->fresh()->ip_address)->toBeNull()
        ->and($record->fresh()->action)->toBe(ConsentAction::Granted);
});

it('stores guest choices from the endpoint with a cookie id', function () {
    $response = $this->withoutMiddleware(VerifyCsrfToken::class)
        ->post('/quebec/consent', ['purpose' => 'analytics', 'granted' => true])
        ->assertNoContent()
        ->assertCookie('quebec_consent_id');

    $id = $response->getCookie('quebec_consent_id')->getValue();
    $record = ConsentRecord::sole();

    expect($record->anonymous_id)->toBe($id)
        ->and($record->ip_address)->toBe('127.0.0.1')
        ->and($record->source)->toBe('route');

    // Returning visitor keeps the same id.
    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->withCookie('quebec_consent_id', $id)
        ->post('/quebec/consent', ['purpose' => 'analytics', 'granted' => false])
        ->assertNoContent();

    expect(QuebecConsent::has($id, 'analytics'))->toBeFalse()
        ->and(QuebecConsent::history($id))->toHaveCount(2);
});

it('claims a visitor\'s latest decisions for the user, once', function () {
    QuebecConsent::grant('visitor-1', 'analytics');
    QuebecConsent::revoke('visitor-1', 'analytics');
    QuebecConsent::grant('visitor-1', 'marketing');
    $user = user();
    $user->grantConsent('newsletter');

    $claimed = QuebecConsent::claim('visitor-1', $user);

    expect($claimed)->toHaveCount(2)
        ->and($user->hasConsent('analytics'))->toBeFalse()
        ->and($user->hasConsent('marketing'))->toBeTrue()
        ->and(QuebecConsent::latest($user, 'marketing'))
        ->source->toBe('claimed')
        ->metadata->toMatchArray(['anonymous_id' => 'visitor-1'])
        ->ip_address->toBe('127.0.0.1')
        ->and(QuebecConsent::history('visitor-1'))->toHaveCount(3)
        ->and(QuebecConsent::claim('visitor-1', $user))->toHaveCount(0);
});

it('keeps the user\'s own later decision over the visitor\'s', function () {
    QuebecConsent::grant('visitor-1', 'marketing');
    $user = user();
    $user->revokeConsent('marketing');

    expect(QuebecConsent::claim('visitor-1', $user))->toHaveCount(0)
        ->and($user->hasConsent('marketing'))->toBeFalse();
});

it('orders claims by the original decision, across visitor ids', function () {
    QuebecConsent::grant('device-a', 'analytics');
    QuebecConsent::revoke('device-b', 'analytics');
    $user = user();

    QuebecConsent::claim('device-a', $user);
    expect($user->hasConsent('analytics'))->toBeTrue();

    // device-b decided after device-a: its revoke wins even though it is claimed later.
    expect(QuebecConsent::claim('device-b', $user))->toHaveCount(1)
        ->and($user->hasConsent('analytics'))->toBeFalse()
        ->and(QuebecConsent::claim('device-a', $user))->toHaveCount(0)
        ->and($user->hasConsent('analytics'))->toBeFalse();
});

it('claims the cookie id on login', function () {
    QuebecConsent::grant('visitor-1', 'analytics');
    $user = user();

    Route::middleware('web')->post('/test-login', function () use ($user) {
        Auth::login($user);

        return response()->noContent();
    });

    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->withCookie('quebec_consent_id', 'visitor-1')
        ->post('/test-login')
        ->assertNoContent();

    expect($user->hasConsent('analytics'))->toBeTrue();
});

it('stores logged-in choices on the user', function () {
    $user = user();

    $this->actingAs($user)
        ->withoutMiddleware(VerifyCsrfToken::class)
        ->post('/quebec/consent', ['purpose' => 'analytics', 'granted' => false])
        ->assertNoContent();

    expect($user->consents()->sole()->action)->toBe(ConsentAction::Revoked);
});
