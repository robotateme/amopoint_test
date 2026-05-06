<?php

namespace Infrastructure\Auth;

use Application\Auth\StatsCredentials;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

final readonly class LaravelStatsCredentials implements StatsCredentials
{
    public function __construct(
        private ConfigRepository $config,
    ) {}

    public function check(string $login, string $password): bool
    {
        return hash_equals($this->subject(), $login)
            && hash_equals((string) $this->config->get('services.stats.password', 'secret'), $password);
    }

    public function subject(): string
    {
        return (string) $this->config->get('services.stats.login', 'admin');
    }
}
