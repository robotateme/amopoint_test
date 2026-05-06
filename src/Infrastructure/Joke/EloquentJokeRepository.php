<?php

namespace Infrastructure\Joke;

use Application\Persistence\SearchCriteria\Units\Order;
use Application\Persistence\Units\Criteria;
use Application\Persistence\Units\OrderType;
use Domain\Joke\Joke;
use Domain\Joke\JokeRepository;
use Infrastructure\Persistence\Mapping\JokeMapper;
use Infrastructure\Persistence\ModelResolver;
use Infrastructure\Persistence\SQL\EloquentCriteriaContext;

final class EloquentJokeRepository implements JokeRepository
{
    public function __construct(
        private readonly ModelResolver $models,
    ) {}

    public function save(Joke $joke): void
    {
        $modelClass = $this->models->classFor('joke');
        $modelClass::query()->create(JokeMapper::attributesFromDomain($joke));
    }

    public function latest(int $limit = 50): array
    {
        $criteria = new Criteria(
            orders: [
                new Order('id', OrderType::DESC),
            ],
            limit: $limit,
        );

        /** @psalm-suppress InvalidTemplateParam Eloquent keeps model instances while get() accepts selected columns. */
        return (new EloquentCriteriaContext($this->models->query('joke')))
            ->query($criteria)
            ->get(['id', 'external_id', 'type', 'setup', 'punchline', 'created_at'])
            ->map(fn ($record): array => JokeMapper::responseFromModel($record))
            ->all();
    }
}
