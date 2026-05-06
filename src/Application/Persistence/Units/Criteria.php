<?php

declare(strict_types=1);

namespace Application\Persistence\Units;

use Application\Persistence\SearchCriteria\Contracts\Criteria as CriteriaContract;
use Application\Persistence\SearchCriteria\Units\Order;
use Override;

final readonly class Criteria implements CriteriaContract
{
    /**
     * @param  list<Filter>  $filters
     * @param  list<Order>  $orders
     */
    public function __construct(
        private array $filters = [],
        private array $orders = [],
        private ?int $limit = null,
    ) {}

    /**
     * @return list<Filter>
     */
    #[Override]
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return list<Order>
     */
    #[Override]
    public function getOrders(): array
    {
        return $this->orders;
    }

    #[Override]
    public function getLimit(): ?int
    {
        return $this->limit;
    }
}
