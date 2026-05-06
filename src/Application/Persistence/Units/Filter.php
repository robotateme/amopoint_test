<?php

declare(strict_types=1);

namespace Application\Persistence\Units;

use Application\Persistence\SearchCriteria\Units\FilterType;

final readonly class Filter
{
    public function __construct(
        public string $column,
        public FilterType $operator,
        public mixed $value,
    ) {}
}
