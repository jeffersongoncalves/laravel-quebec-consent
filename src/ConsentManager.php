<?php

namespace JeffersonGoncalves\QuebecConsent;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use JeffersonGoncalves\QuebecConsent\Enums\ConsentAction;
use JeffersonGoncalves\QuebecConsent\Events\ConsentRecorded;
use JeffersonGoncalves\QuebecConsent\Models\ConsentRecord;

/**
 * A subject is an Eloquent model (a user) or a string anonymous id (a visitor).
 */
class ConsentManager
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function grant(Model|string $subject, string $purpose, ?string $version = null, ?string $source = null, array $metadata = []): ConsentRecord
    {
        $expiresAfter = $this->purpose($purpose)['expires_after'] ?? null;

        return $this->record($subject, $purpose, ConsentAction::Granted, $version ?? $this->currentVersion($purpose), $source, $metadata, [
            'expires_at' => is_string($expiresAfter) ? now()->add(CarbonInterval::make($expiresAfter)) : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function revoke(Model|string $subject, string $purpose, ?string $source = null, array $metadata = []): ConsentRecord
    {
        return $this->record($subject, $purpose, ConsentAction::Revoked, $this->currentVersion($purpose), $source, $metadata);
    }

    /**
     * Latest decision is a grant, not expired, and for the required terms version
     * (the configured current version when none is given).
     */
    public function has(Model|string $subject, string $purpose, ?string $version = null): bool
    {
        $latest = $this->latest($subject, $purpose);
        $required = $version ?? $this->currentVersion($purpose);

        return $latest !== null
            && $latest->isActive()
            && ($required === null || $latest->version === $required);
    }

    public function latest(Model|string $subject, string $purpose): ?ConsentRecord
    {
        return $this->query($subject)->where('purpose', $purpose)->latest('id')->first();
    }

    /**
     * @return Collection<int, ConsentRecord>
     */
    public function history(Model|string $subject, ?string $purpose = null): Collection
    {
        return $this->query($subject)
            ->when($purpose !== null, fn (Builder $query) => $query->where('purpose', $purpose))
            ->oldest('id')
            ->get();
    }

    /**
     * @return Builder<ConsentRecord>
     */
    public function query(Model|string $subject): Builder
    {
        $query = ConsentRecord::query();

        if (is_string($subject)) {
            return $query->where('anonymous_id', $subject);
        }

        return $query->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', (string) $subject->getKey());
    }

    /**
     * Carry a visitor's decisions over to the account they log into. The ledger stays append-only:
     * for each purpose, the visitor's latest decision is copied onto the subject as a new record
     * (source "claimed", original proof kept) unless the subject already decided after it.
     *
     * @return Collection<int, ConsentRecord> the records created
     */
    public function claim(string $anonymousId, Model $subject): Collection
    {
        $claimed = new Collection;

        $latest = $this->query($anonymousId)->orderByDesc('id')->get()->unique('purpose');

        foreach ($latest as $record) {
            $own = $this->latest($subject, $record->purpose);

            // Ids grow with time. A claimed record counts as of the decision it copied, so a later
            // visitor decision still wins over an earlier claim; claiming the same one twice is a no-op.
            $ownOrder = $own === null ? null
                : ($own->getAttribute('source') === 'claimed' ? (int) ($own->metadata['claimed_record_id'] ?? $own->getKey()) : (int) $own->getKey());

            if ($ownOrder !== null && $ownOrder >= (int) $record->getKey()) {
                continue;
            }

            $claimed->push($this->record($subject, $record->purpose, $record->action, $record->version, 'claimed', [
                'anonymous_id' => $anonymousId,
                'claimed_record_id' => $record->getKey(),
            ], [
                'expires_at' => $record->expires_at,
                'ip_address' => $record->getAttribute('ip_address'),
                'user_agent' => $record->getAttribute('user_agent'),
            ]));
        }

        return $claimed;
    }

    public function currentVersion(string $purpose): ?string
    {
        $version = $this->purpose($purpose)['version'] ?? null;

        return is_string($version) ? $version : null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $attributes
     */
    protected function record(Model|string $subject, string $purpose, ConsentAction $action, ?string $version, ?string $source, array $metadata, array $attributes = []): ConsentRecord
    {
        $request = config('quebec-consent.store_request', true) ? request() : null;

        $record = ConsentRecord::create([
            ...(is_string($subject)
                ? ['anonymous_id' => $subject]
                : ['subject_type' => $subject->getMorphClass(), 'subject_id' => (string) $subject->getKey()]),
            'purpose' => $purpose,
            'version' => $version,
            'action' => $action,
            'source' => $source,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'metadata' => $metadata === [] ? null : $metadata,
            ...$attributes,
        ]);

        event(new ConsentRecorded($record));

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    protected function purpose(string $purpose): array
    {
        $purposes = (array) config('quebec-consent.purposes', []);

        if ($purposes === []) {
            return [];
        }

        if (! array_key_exists($purpose, $purposes)) {
            throw new InvalidArgumentException("Unknown consent purpose [{$purpose}]. Add it to quebec-consent.purposes.");
        }

        return (array) $purposes[$purpose];
    }
}
