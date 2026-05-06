<?php

declare(strict_types=1);

namespace Application\Persistence\SearchCriteria\Units;

use Application\Persistence\Units\OrderType;

final readonly class Order
{
    public function __construct(
        public string $field,
        public OrderType $direction,
    ) {}
}
