<?php

namespace Application\Auth;

interface LoginRateLimiter
{
    public function attempt(string $key): LoginRateLimit;

    public function clear(string $key): void;
}
