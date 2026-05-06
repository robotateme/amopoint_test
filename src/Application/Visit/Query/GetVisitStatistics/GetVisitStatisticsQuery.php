<?php

namespace Application\Visit\Query\GetVisitStatistics;

final readonly class GetVisitStatisticsQuery
{
    public function __construct(public int $hours = 24) {}
}
