<?php

namespace Application\Visit\GetVisitStatistics;

final readonly class GetVisitStatisticsQuery
{
    public function __construct(public int $hours = 24)
    {
    }
}
