<?php

namespace Infrastructure\Auth;

use Application\Auth\LoginRateLimit;
use Application\Auth\LoginRateLimiter;
use Illuminate\Support\Carbon;
use Infrastructure\Redis\LuaScriptResolver;
use Infrastructure\Redis\RedisConnectionPort;
use RuntimeException;

final readonly class RedisSlidingWindowLoginRateLimiter implements LoginRateLimiter
{
    public function __construct(
        private LuaScriptResolver $scripts,
        private RedisConnectionPort $redis,
        private int $maxAttempts,
        private int $windowSeconds,
    ) {}

    public function attempt(string $key): LoginRateLimit
    {
        $nowMs = Carbon::now()->getTimestampMs();
        $windowMs = $this->windowSeconds * 1000;
        $member = sprintf('%d-%s', $nowMs, bin2hex(random_bytes(8)));
        $result = $this->scripts->eval(
            'sliding_window_rate_limiter',
            1,
            $key,
            $nowMs,
            $windowMs,
            $this->maxAttempts,
            $member,
        );

        if (! is_array($result) || count($result) < 3) {
            throw new RuntimeException('Invalid response from Redis sliding window rate limiter.');
        }

        $allowed = (int) $result[0] === 1;
        $remainingAttempts = max(0, (int) $result[1]);
        $retryAfterSeconds = max(0, (int) ceil(((int) $result[2]) / 1000));

        return new LoginRateLimit($allowed, $remainingAttempts, $retryAfterSeconds);
    }

    public function clear(string $key): void
    {
        $this->redis->command('DEL', $key);
    }
}
