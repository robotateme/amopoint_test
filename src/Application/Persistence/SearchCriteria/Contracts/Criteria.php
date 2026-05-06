<?php

declare(strict_types=1);

namespace Application\Persistence\SearchCriteria\Contracts;

use Application\Persistence\SearchCriteria\Units\Order;
use Application\Persistence\Units\Filter;

interface Criteria
{
    /**
     * @return list<Filter>
     */
    public function getFilters(): array;

    /**
     * @return list<Order>
     */
    public function getOrders(): array;

    public function getLimit(): ?int;
}
