<?php

namespace Infrastructure\Joke;

use App\Models\JokeRecord;
use Application\Persistence\SearchCriteria\Units\Order;
use Application\Persistence\Units\Criteria;
use Application\Persistence\Units\OrderType;
use Domain\Joke\Joke;
use Domain\Joke\JokeRepository;
use Illuminate\Database\Eloquent\Builder;
use Infrastructure\Persistence\Mapping\JokeMapper;
use Infrastructure\Persistence\SQL\EloquentCriteriaContext;

final class EloquentJokeRepository implements JokeRepository
{
    public function save(Joke $joke): void
    {
        JokeRecord::query()->create(JokeMapper::attributesFromDomain($joke));
    }

    public function latest(int $limit = 50): array
    {
        $criteria = new Criteria(
            orders: [
                new Order('id', OrderType::DESC),
            ],
            limit: $limit,
        );

        $context = new EloquentCriteriaContext($this->query());

        /** @psalm-suppress InvalidTemplateParam Eloquent keeps model instances while get() accepts selected columns. */
        return $context
            ->query($criteria)
            ->get(['id', 'external_id', 'type', 'setup', 'punchline', 'created_at'])
            ->map(fn ($record): array => JokeMapper::responseFromModel($record))
            ->all();
    }

    /**
     * @return Builder<JokeRecord>
     */
    private function query(): Builder
    {
        return JokeRecord::query();
    }
}
