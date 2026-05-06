<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

use InvalidArgumentException;

final readonly class JokeExternalId
{
    public function __construct(
        private int $value,
    ) {
        if ($value < 1) {
            throw new InvalidArgumentException('Joke external id must be positive.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}
