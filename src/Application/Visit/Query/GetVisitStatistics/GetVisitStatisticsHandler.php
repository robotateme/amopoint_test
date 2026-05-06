<?php

namespace Application\Visit\Query\GetVisitStatistics;

use Domain\Visit\VisitRepository;
use Domain\Visit\VisitStatisticsCache;

final readonly class GetVisitStatisticsHandler
{
    public function __construct(
        private VisitRepository $visits,
        private VisitStatisticsCache $cache,
    ) {}

    /**
     * @return array{hours: array<int, array{hour: string, visits: int}>, cities: array<int, array{city: string, visits: int}>}
     */
    public function handle(GetVisitStatisticsQuery $query): array
    {
        return $this->cache->remember($query->hours, fn (): array => [
            'hours' => $this->visits->uniqueByHour($query->hours),
            'cities' => $this->visits->uniqueByCity(),
        ]);
    }
}
