<?php

namespace Domain\Joke;

interface JokeRepository
{
    public function save(Joke $joke): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function latest(int $limit = 50): array;
}
