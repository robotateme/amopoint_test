<?php

namespace Infrastructure\Visit;

use Application\Persistence\SearchCriteria\Units\FilterType;
use Application\Persistence\SearchCriteria\Units\Order;
use Application\Persistence\Units\Criteria;
use Application\Persistence\Units\Filter;
use Application\Persistence\Units\OrderType;
use Domain\Visit\Visit;
use Domain\Visit\VisitRepository;
use Illuminate\Support\Facades\DB;
use Infrastructure\Persistence\Mapping\VisitMapper;
use Infrastructure\Persistence\ModelResolver;
use Infrastructure\Persistence\SQL\QueryCriteriaContext;

final class EloquentVisitRepository implements VisitRepository
{
    public function __construct(
        private readonly ModelResolver $models,
    ) {}

    public function save(Visit $visit): void
    {
        $modelClass = $this->models->classFor('visit');
        $modelClass::query()->create(VisitMapper::attributesFromDomain($visit));
    }

    public function uniqueByHour(int $hours = 24): array
    {
        $driver = DB::connection()->getDriverName();
        $hourExpression = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d %H:00', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD HH24:00')",
            default => "date_format(created_at, '%Y-%m-%d %H:00')",
        };

        $criteria = new Criteria(
            filters: [
                new Filter('created_at', FilterType::GREATER_OR_EQUAL, now()->subHours($hours)),
            ],
            orders: [
                new Order('hour', OrderType::ASC),
            ],
        );

        $query = (new QueryCriteriaContext(DB::table($this->models->tableFor('visit'))))
            ->query($criteria);

        return $query
            ->selectRaw("{$hourExpression} as hour, count(distinct fingerprint) as visits")
            ->groupBy('hour')
            ->get()
            ->map(fn ($row): array => [
                'hour' => (string) $row->hour,
                'visits' => (int) $row->visits,
            ])
            ->all();
    }

    public function uniqueByCity(): array
    {
        $criteria = new Criteria(
            orders: [
                new Order('visits', OrderType::DESC),
            ],
        );

        $query = (new QueryCriteriaContext(DB::table($this->models->tableFor('visit'))))
            ->query($criteria);

        return $query
            ->selectRaw('city, count(distinct fingerprint) as visits')
            ->groupBy('city')
            ->get()
            ->map(fn ($row): array => [
                'city' => (string) $row->city,
                'visits' => (int) $row->visits,
            ])
            ->all();
    }
}
