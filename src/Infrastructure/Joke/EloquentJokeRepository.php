<?php

namespace Infrastructure\Joke;

use Application\Persistence\SearchCriteria\Units\Order;
use Application\Persistence\Units\Criteria;
use Application\Persistence\Units\OrderType;
use Domain\Joke\Joke;
use Domain\Joke\JokeRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Infrastructure\Persistence\Mapping\JokeMapper;
use Infrastructure\Persistence\SQL\EloquentCriteriaContext;
use InvalidArgumentException;

final class EloquentJokeRepository implements JokeRepository
{
    private const MODEL_ALIAS = 'joke';

    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function save(Joke $joke): void
    {
        $modelClass = $this->modelClass();
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

        $context = new EloquentCriteriaContext($this->query());

        /** @psalm-suppress InvalidTemplateParam Eloquent keeps model instances while get() accepts selected columns. */
        return $context
            ->query($criteria)
            ->get(['id', 'external_id', 'type', 'setup', 'punchline', 'created_at'])
            ->map(fn ($record): array => JokeMapper::responseFromModel($record))
            ->all();
    }

    /**
     * @return class-string<Model>
     */
    private function modelClass(): string
    {
        $modelClass = $this->config->get('persistence.models.'.self::MODEL_ALIAS);

        if (! is_string($modelClass) || ! is_a($modelClass, Model::class, true)) {
            throw new InvalidArgumentException('Joke persistence model is not configured.');
        }

        return $modelClass;
    }

    /**
     * @return Builder<Model>
     */
    private function query(): Builder
    {
        $modelClass = $this->modelClass();

        return $modelClass::query();
    }
}
