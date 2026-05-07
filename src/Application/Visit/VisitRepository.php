<?php

declare(strict_types=1);

namespace Application\Visit;

use Application\Persistence\SearchCriteria\Contracts\Criteria;
use Domain\Visit\Visit;

interface VisitRepository
{
    public function save(Visit $visit): void;

    public function uniqueTotal(Criteria $criteria): int;

    /**
     * @return array<int, array{hour: string, visits: int}>
     */
    public function uniqueByHour(Criteria $criteria): array;

    /**
     * @return array<int, array{city: string, visits: int}>
     */
    public function uniqueByCity(Criteria $criteria): array;
}
