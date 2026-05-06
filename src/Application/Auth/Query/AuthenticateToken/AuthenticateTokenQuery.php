<?php

namespace Application\Auth\Query\AuthenticateToken;

final readonly class AuthenticateTokenQuery
{
    public function __construct(
        public string $token,
    ) {}
}
