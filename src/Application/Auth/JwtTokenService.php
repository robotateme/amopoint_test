<?php

namespace Application\Auth;

interface JwtTokenService
{
    public function issue(string $subject): string;

    public function validate(string $token): ?string;
}
