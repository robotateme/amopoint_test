<?php

namespace Infrastructure\Cache;

use Closure;
use Domain\Visit\VisitStatisticsCache;
use Illuminate\Contracts\Cache\Repository;

final readonly class LaravelVisitStatisticsCache implements VisitStatisticsCache
{
    private const VERSION_KEY = 'visit-statistics:version';

    public function __construct(private Repository $cache)
    {
    }

    public function remember(int $hours, Closure $callback): array
    {
        $version = (int) $this->cache->get(self::VERSION_KEY, 1);
        $key = "visit-statistics:v{$version}:hours:{$hours}";

        return $this->cache->remember($key, now()->addMinute(), $callback);
    }

    public function flush(): void
    {
        if (! $this->cache->has(self::VERSION_KEY)) {
            $this->cache->forever(self::VERSION_KEY, 1);
        }

        $this->cache->increment(self::VERSION_KEY);
    }
}
