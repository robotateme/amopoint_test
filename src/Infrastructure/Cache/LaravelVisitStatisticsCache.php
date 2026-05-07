<?php

namespace Infrastructure\Cache;

use Closure;
use Domain\Visit\VisitStatisticsCache;
use Illuminate\Contracts\Cache\Repository;

final readonly class LaravelVisitStatisticsCache implements VisitStatisticsCache
{
    private const VERSION_KEY = 'visit-statistics:version';

    public function __construct(private Repository $cache) {}

    /**
     * @param  Closure(): array{total: int, hours: array<int, array{hour: string, visits: int}>, cities: array<int, array{city: string, visits: int}>}  $callback
     * @return array{total: int, hours: array<int, array{hour: string, visits: int}>, cities: array<int, array{city: string, visits: int}>}
     */
    public function remember(int $hours, Closure $callback): array
    {
        $version = (int) $this->cache->get(self::VERSION_KEY, 1);
        $key = "visit-statistics:v{$version}:hours:{$hours}";
        /** @var array{total: int, hours: array<int, array{hour: string, visits: int}>, cities: array<int, array{city: string, visits: int}>} $value */
        $value = $this->cache->remember(
            $key,
            now()->addMinute(),
            /**
             * @return array{total: int, hours: array<int, array{hour: string, visits: int}>, cities: array<int, array{city: string, visits: int}>}
             */
            static fn (): array => $callback(),
        );

        return $value;
    }

    public function flush(): void
    {
        if (! $this->cache->has(self::VERSION_KEY)) {
            $this->cache->forever(self::VERSION_KEY, 1);
        }

        $this->cache->increment(self::VERSION_KEY);
    }
}
