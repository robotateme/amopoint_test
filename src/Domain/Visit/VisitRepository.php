<?php

namespace Domain\Visit;

interface VisitRepository
{
    public function save(Visit $visit): void;

    /**
     * @return array<int, array{hour: string, visits: int}>
     */
    public function uniqueByHour(int $hours = 24): array;

    /**
     * @return array<int, array{city: string, visits: int}>
     */
    public function uniqueByCity(): array;
}
