<?php

namespace Infrastructure\Auth;

use Application\Auth\LoginRateLimit;
use Application\Auth\LoginRateLimiter;
use Illuminate\Support\Carbon;

final class InMemorySlidingWindowLoginRateLimiter implements LoginRateLimiter
{
    /**
     * @var array<string, list<int>>
     */
    private array $timestampsByKey = [];

    public function __construct(
        private readonly int $maxAttempts,
        private readonly int $windowSeconds,
    ) {}

    public function attempt(string $key): LoginRateLimit
    {
        $nowMs = Carbon::now()->getTimestampMs();
        $windowMs = $this->windowSeconds * 1000;
        $threshold = $nowMs - $windowMs;

        $entries = array_values(array_filter(
            $this->timestampsByKey[$key] ?? [],
            static fn (int $timestamp): bool => $timestamp > $threshold,
        ));

        if (count($entries) >= $this->maxAttempts) {
            $oldest = $entries[0] ?? $nowMs;
            $retryAfterSeconds = max(1, (int) ceil(($windowMs - ($nowMs - $oldest)) / 1000));
            $this->timestampsByKey[$key] = $entries;

            return new LoginRateLimit(false, 0, $retryAfterSeconds);
        }

        $entries[] = $nowMs;
        $this->timestampsByKey[$key] = $entries;

        return new LoginRateLimit(true, max(0, $this->maxAttempts - count($entries)), 0);
    }

    public function clear(string $key): void
    {
        unset($this->timestampsByKey[$key]);
    }
}
