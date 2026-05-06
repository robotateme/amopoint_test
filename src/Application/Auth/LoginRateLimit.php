<?php

namespace Application\Auth;

final readonly class LoginRateLimit
{
    public function __construct(
        public bool $allowed,
        public int $remainingAttempts,
        public int $retryAfterSeconds,
    ) {}
}
