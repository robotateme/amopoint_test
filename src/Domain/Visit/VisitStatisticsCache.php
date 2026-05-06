<?php

namespace Domain\Visit;

use Closure;

interface VisitStatisticsCache
{
    /**
     * @param  Closure(): array{hours: array<int, array{hour: string, visits: int}>, cities: array<int, array{city: string, visits: int}>}  $callback
     * @return array{hours: array<int, array{hour: string, visits: int}>, cities: array<int, array{city: string, visits: int}>}
     */
    public function remember(int $hours, Closure $callback): array;

    public function flush(): void;
}
