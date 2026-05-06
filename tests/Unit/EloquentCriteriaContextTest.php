<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\JokeRecord;
use Application\Persistence\SearchCriteria\Units\FilterType;
use Application\Persistence\SearchCriteria\Units\Order;
use Application\Persistence\Units\Criteria;
use Application\Persistence\Units\Filter;
use Application\Persistence\Units\OrderType;
use Infrastructure\Persistence\SQL\EloquentCriteriaContext;
use InvalidArgumentException;
use Tests\TestCase;

final class EloquentCriteriaContextTest extends TestCase
{
    public function test_applies_filters_ordering_and_limit(): void
    {
        $criteria = new Criteria(
            filters: [
                new Filter('type', FilterType::EQUAL, 'general'),
                new Filter('setup', FilterType::LIKE, '%test%'),
            ],
            orders: [
                new Order('id', OrderType::DESC),
            ],
            limit: 10,
        );

        $query = (new EloquentCriteriaContext(JokeRecord::query()))->query($criteria);

        self::assertSame(
            'select * from "jokes" where "type" = ? and "setup" like ? order by "id" desc limit 10',
            $query->toSql(),
        );
        self::assertSame(['general', '%test%'], $query->getBindings());
    }

    public function test_applies_in_filters(): void
    {
        $criteria = new Criteria(filters: [
            new Filter('id', FilterType::IN, [1, 2, 3]),
            new Filter('type', FilterType::NOT_IN, ['programming']),
        ]);

        $query = (new EloquentCriteriaContext(JokeRecord::query()))->query($criteria);

        self::assertSame(
            'select * from "jokes" where "id" in (?, ?, ?) and "type" not in (?)',
            $query->toSql(),
        );
        self::assertSame([1, 2, 3, 'programming'], $query->getBindings());
    }

    public function test_rejects_non_iterable_value_for_in_filter(): void
    {
        $criteria = new Criteria(filters: [
            new Filter('id', FilterType::IN, 7),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value must be iterable for IN operators.');

        (new EloquentCriteriaContext(JokeRecord::query()))->query($criteria);
    }
}
