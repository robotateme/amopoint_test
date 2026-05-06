<?php

namespace Application\Auth\Command\Login;

final readonly class LoginCommand
{
    public function __construct(
        public string $login,
        public string $password,
    ) {}
}
