<?php

namespace Application\Auth;

interface StatsCredentials
{
    public function check(string $login, string $password): bool;

    public function subject(): string;
}
