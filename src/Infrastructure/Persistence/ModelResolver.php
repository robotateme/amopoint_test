<?php

declare(strict_types=1);

namespace Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface ModelResolver
{
    /**
     * @return class-string<Model>
     */
    public function classFor(string $alias): string;

    /**
     * @return Builder<Model>
     */
    public function query(string $alias): Builder;

    public function tableFor(string $alias): string;
}
