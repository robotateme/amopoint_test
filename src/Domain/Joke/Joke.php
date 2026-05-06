<?php

namespace Domain\Joke;

final readonly class Joke
{
    public function __construct(
        public int $externalId,
        public string $type,
        public string $setup,
        public string $punchline,
    ) {
    }
}
