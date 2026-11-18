<?php

namespace JeffersonGoncalves\QuebecConsent\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use JeffersonGoncalves\QuebecConsent\ConsentManager;
use JeffersonGoncalves\QuebecConsent\Models\ConsentRecord;

/**
 * @mixin Model
 */
trait HasConsents
{
    /**
     * @return MorphMany<ConsentRecord, $this>
     */
    public function consents(): MorphMany
    {
        return $this->morphMany(ConsentRecord::class, 'subject');
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function grantConsent(string $purpose, ?string $version = null, ?string $source = null, array $metadata = []): ConsentRecord
    {
        return app(ConsentManager::class)->grant($this, $purpose, $version, $source, $metadata);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function revokeConsent(string $purpose, ?string $source = null, array $metadata = []): ConsentRecord
    {
        return app(ConsentManager::class)->revoke($this, $purpose, $source, $metadata);
    }

    public function hasConsent(string $purpose, ?string $version = null): bool
    {
        return app(ConsentManager::class)->has($this, $purpose, $version);
    }
}
