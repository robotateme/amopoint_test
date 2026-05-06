<?php

namespace Application\Visit\Query\GetVisitStatistics;

use Application\Persistence\SearchCriteria\Units\FilterType;
use Application\Persistence\Units\Criteria;
use Application\Persistence\Units\Filter;
use Application\Visit\VisitRepository;
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
        return $this->cache->remember($query->hours, function () use ($query): array {
            $hourCriteria = new Criteria(
                filters: [
                    new Filter('created_at', FilterType::GREATER_OR_EQUAL, date('Y-m-d H:i:s', time() - ($query->hours * 3600))),
                ],
            );

            return [
                'hours' => $this->visits->uniqueByHour($hourCriteria),
                'cities' => $this->visits->uniqueByCity($hourCriteria),
            ];
        });
    }
}
