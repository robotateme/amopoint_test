<?php

namespace Application\Auth\Query\AuthenticateToken;

use Application\Auth\JwtTokenService;
use Application\Auth\StatsCredentials;

final readonly class AuthenticateTokenHandler
{
    public function __construct(
        private StatsCredentials $credentials,
        private JwtTokenService $tokens,
    ) {}

    public function handle(AuthenticateTokenQuery $query): bool
    {
        $subject = $this->tokens->validate($query->token);

        return $subject !== null && hash_equals($this->credentials->subject(), $subject);
    }
}
