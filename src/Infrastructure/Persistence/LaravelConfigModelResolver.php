<?php

declare(strict_types=1);

namespace Infrastructure\Persistence;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final readonly class LaravelConfigModelResolver implements ModelResolver
{
    public function __construct(
        private ConfigRepository $config,
    ) {}

    /**
     * @return class-string<Model>
     */
    public function classFor(string $alias): string
    {
        $modelClass = $this->config->get("persistence.models.{$alias}");

        if (! is_string($modelClass) || ! is_a($modelClass, Model::class, true)) {
            throw new InvalidArgumentException("Persistence model [{$alias}] is not configured.");
        }

        return $modelClass;
    }

    /**
     * @return Builder<Model>
     */
    public function query(string $alias): Builder
    {
        $modelClass = $this->classFor($alias);

        return $modelClass::query();
    }

    public function tableFor(string $alias): string
    {
        $modelClass = $this->classFor($alias);
        /** @psalm-suppress UnsafeInstantiation Laravel model constructors are parameterless here. */
        $model = new $modelClass;

        return $model->getTable();
    }
}
