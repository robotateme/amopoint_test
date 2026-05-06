<?php

namespace Infrastructure\Redis;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Redis;
use RuntimeException;

final class PhpRedisConnection implements RedisConnectionPort
{
    private Redis $redis;

    public function __construct(
        ConfigRepository $config,
        string $connectionName = 'cache',
        float $timeout = 2.5,
    ) {
        if (! class_exists(Redis::class)) {
            throw new RuntimeException('phpredis extension is required for Redis rate limiting.');
        }

        /** @var array<string, mixed> $connection */
        $connection = (array) $config->get("database.redis.{$connectionName}", []);
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (int) ($connection['port'] ?? 6379);
        /** @var mixed $password */
        $password = $connection['password'] ?? null;
        $database = (int) ($connection['database'] ?? 0);

        $this->redis = new Redis;
        $this->redis->connect($host, $port, $timeout);

        if (is_string($password) && $password !== '') {
            $this->redis->auth($password);
        }

        if ($database > 0) {
            $this->redis->select($database);
        }
    }

    public function command(string $command, string|int ...$arguments): mixed
    {
        /** @var mixed $result */
        $result = $this->redis->rawCommand($command, ...$arguments);

        if ($result === false) {
            /** @var mixed $lastError */
            $lastError = $this->redis->getLastError();

            if (is_string($lastError) && $lastError !== '') {
                throw new RuntimeException($lastError);
            }
        }

        return $result;
    }
}
