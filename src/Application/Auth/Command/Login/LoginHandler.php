<?php

namespace Application\Auth\Command\Login;

use Application\Auth\JwtTokenService;
use Application\Auth\StatsCredentials;
use RuntimeException;

final readonly class LoginHandler
{
    public function __construct(
        private StatsCredentials $credentials,
        private JwtTokenService $tokens,
    ) {}

    public function handle(LoginCommand $command): string
    {
        if (! $this->credentials->check($command->login, $command->password)) {
            throw new RuntimeException('Invalid credentials.');
        }

        return $this->tokens->issue($this->credentials->subject());
    }
}
